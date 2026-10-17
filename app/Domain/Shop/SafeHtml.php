<?php

namespace App\Domain\Shop;

/**
 * HTML ที่แอดมินเขียนด้วย CKEditor (mall.detail) แล้วแสดงดิบให้ลูกค้า (_mall/load_detail)
 * เดิมแสดงตรงๆ = staff ที่มีสิทธิ์ admin_mall ฝัง <script>/onerror= ใส่ลูกค้าได้ (stored XSS)
 *
 * เพื่อให้ HTML ปกติออกมาเหมือนเดิมทุกตัวอักษร: ถ้าไม่มีรูปแบบอันตรายเลย คืนค่าเดิม
 * ถ้ามี → ผ่าน DOM แล้วตัดแท็กอันตราย, แอตทริบิวต์ on*, และ URL javascript:/vbscript:/data: (ยกเว้นรูป data:image)
 */
final class SafeHtml
{
    private const DANGER = '/<\s*\/?\s*(script|iframe|frame|object|embed|applet|style|link|meta|base|form|svg|math|template)\b|[\s\/"\'\x00-\x20]on[a-z]+\s*=|srcdoc\s*=|expression\s*\(/i';

    /** ตรวจหลังถอด entity และตัดช่องว่าง/ตัวควบคุม (กัน jav&#x09;ascript:) */
    private const DANGER_SCHEME = '/(java|vb)script:|data:text/i';

    private const DROP_TAGS = ['script', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet', 'style', 'link', 'meta',
        'base', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'template', 'noscript'];

    public static function clean(string $html): string
    {
        $probe = (string) preg_replace('/[\x00-\x20]+/', '', html_entity_decode($html, ENT_QUOTES | ENT_HTML5));
        if ($html === '' || (preg_match(self::DANGER, $html) !== 1 && preg_match(self::DANGER_SCHEME, $probe) !== 1)) {
            return $html;
        }

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"?><div id="__safe">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xp = new \DOMXPath($doc);
        foreach (iterator_to_array($xp->query('//*')) as $el) {
            /** @var \DOMElement $el */
            if (in_array(strtolower($el->nodeName), self::DROP_TAGS, true)) {
                $el->parentNode?->removeChild($el);

                continue;
            }
            foreach (iterator_to_array($el->attributes) as $attr) {
                $name = strtolower($attr->nodeName);
                $val = preg_replace('/[\x00-\x20]+/', '', html_entity_decode($attr->nodeValue ?? '', ENT_QUOTES | ENT_HTML5));
                $badUrl = in_array($name, ['href', 'src', 'action', 'formaction', 'xlink:href', 'background', 'poster', 'lowsrc', 'dynsrc'], true)
                    && preg_match('/^(javascript|vbscript|data):/i', (string) $val) === 1
                    && ! preg_match('/^data:image\/(png|jpe?g|gif|webp);/i', (string) $val);
                if (str_starts_with($name, 'on') || $name === 'srcdoc' || $badUrl
                    || ($name === 'style' && preg_match('/expression\s*\(|url\s*\(\s*["\']?\s*(javascript|vbscript):/i', (string) $attr->nodeValue))) {
                    $el->removeAttribute($attr->nodeName);
                }
            }
        }

        $root = $xp->query('//div[@id="__safe"]')->item(0);
        $out = '';
        foreach ($root?->childNodes ?? [] as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }
}
