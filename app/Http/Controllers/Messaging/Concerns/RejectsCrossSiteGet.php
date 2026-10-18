<?php

namespace App\Http\Controllers\Messaging\Concerns;

use Illuminate\Http\Request;

/**
 * GET ที่เปลี่ยนข้อมูล/ส่งอีเมล (เดิมเป็น GET จึงคงไว้) — LegacyCsrf ปล่อย GET ทุกตัว
 * → เว็บอื่นฝัง <img src=".../_admin/_notice/email_notice?id=1"> ให้แอดมินที่ล็อกอินอยู่เปิด = เข้าคิวส่งอีเมลถึงลูกค้าทุกคนได้
 *
 * ยอมเมื่อ: Sec-Fetch-Site = same-origin / none (พิมพ์ URL เอง, bookmark)
 *           ไม่มี Sec-Fetch-Site (browser เก่า/ไม่ใช่ browser) → Referer ต้องเป็น host เดียวกัน หรือไม่มี Referer
 */
trait RejectsCrossSiteGet
{
    protected function isCrossSite(Request $request): bool
    {
        $site = strtolower((string) $request->headers->get('Sec-Fetch-Site', ''));
        if ($site !== '') {
            return ! in_array($site, ['same-origin', 'none'], true);
        }
        $referer = (string) $request->headers->get('Referer', '');
        if ($referer === '') {
            return false;
        }
        $host = parse_url($referer, PHP_URL_HOST);

        return ! is_string($host) || strtolower($host) !== strtolower($request->getHost());
    }

    protected function crossSiteResponse(): \Illuminate\Http\Response
    {
        return response('Forbidden (cross-site request)', 403);
    }
}
