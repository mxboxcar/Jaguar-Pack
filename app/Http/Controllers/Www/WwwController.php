<?php

namespace App\Http\Controllers\Www;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * เว็บหน้าแรก pack-cargo.net (เดิม WordPress 5.2 + ธีม Divi บน PHP 7.4 ที่ /home/admin/web/pack-cargo.net/public_html
 * — เคยโดนฝัง webshell 14 ต.ค. 2026) ย้ายมาให้ Laravel เสิร์ฟ 2026-10-22
 *
 * เนื้อหาเป็น snapshot จาก WordPress ตรงตัว (resources/www/ สร้างด้วย tools/www-snapshot.py) — ไม่รัน PHP ของ WordPress อีก
 *  - URL เดิมทุกหน้า (/, /our-services/, /2020/05/27/<slug>/ ...) ไม่สนตัวพิมพ์ + redirect แบบ WordPress
 *    (ไม่มี / ท้าย, /index.php, /home/, ?p=, ?page_id=)
 *  - ค้นหา ?s= แบบ WP_Query::parse_search / parse_search_order ของ WP 5.2 — LIKE ทำใน MySQL ด้วย collation เดิมของ wp_posts
 *    (utf8mb4_unicode_520_ci: ไม่สนวรรณยุกต์ไทย/เครื่องหมายบนอักษรละติน) บนข้อมูลใน site.json (JSON_TABLE ไม่อ่านตารางใด),
 *    หน้าละ 5 (Divi) — HTML ผลค้นหาประกอบจากบล็อกของ WordPress เอง (resources/www/articles/<id>.html)
 *  - /feed/, /comments/feed/, /robots.txt, หน้าหมวดหมู่/ผู้เขียน และฟีดของแต่ละโพสต์ (site.json "extra") คงที่
 *  - URL อื่น = หน้า 404 ของธีมเดิม (status 404) — ไม่เดา redirect แบบ WordPress (เช่น /new/ → /news/), ไม่มีหน้า archive ตามวันที่
 * รูป/CSS/JS (wp-content, wp-includes) nginx เสิร์ฟจากโฟลเดอร์เดิมตรงๆ — ไฟล์ .php อื่นทุกไฟล์ nginx ตอบ 404
 */
class WwwController extends Controller
{
    private const BASE = 'https://pack-cargo.net';

    private const PER_PAGE = 5;

    // get_search_stopwords() ของภาษาไทย (wp-content/languages/th.po)
    private const STOPWORDS = ['เกี่ยว', 'หนึ่ง', 'เป็น', 'ที่', 'โดย', 'com', 'สำหรับ', 'จาก', 'อย่างไร', 'ใน', 'คือ', 'มัน', 'ของ',
        'บน', 'หรือ', 'นั้น', 'นั่น', 'นี่', 'อยู่', 'อะไร', 'เมื่อไหร่', 'ที่ไหน', 'ใคร', 'จะ', 'ด้วย', 'www'];

    private const STATIC = [
        '/feed/' => ['feed.xml', 'application/rss+xml; charset=UTF-8'],
        '/comments/feed/' => ['comments-feed.xml', 'application/rss+xml; charset=UTF-8'],
        '/robots.txt' => ['robots.txt', 'text/plain; charset=utf-8'],
    ];

    private static ?array $site = null;

    public function show(Request $request): Response
    {
        $rawPath = $request->getPathInfo();   // ยังมี / ท้าย (path() ตัดทิ้ง)
        $path = mb_strtolower(rawurldecode($rawPath));
        $site = self::site();
        $pages = array_column($site['items'], 'id', 'path');
        $static = self::STATIC + $site['extra'];

        // /index.php: front controller ของ Laravel ก็ชื่อนี้ → getPathInfo() เป็น "/" ต้องดู REQUEST_URI
        if (parse_url((string) $request->server('REQUEST_URI'), PHP_URL_PATH) === '/index.php') {
            return $this->redirect('/');
        }
        // WP_Query: isset($_GET['s']) = หน้าค้นหา (s ว่าง/"0" = ทุกรายการ)
        if ($request->query->has('s') && ($path === '/' || preg_match('#^/page/(\d+)/?$#', $path, $m))) {
            $s = $request->query('s');

            return $this->search($request, is_string($s) ? $s : '', isset($m[1]) ? max((int) $m[1], 1) : 1);
        }
        if ($path === '/') {
            foreach (['p', 'page_id'] as $k) {
                $id = $request->query($k);
                if (is_string($id) && ctype_digit($id)) {
                    $to = array_search((int) $id, $pages, true);

                    return $to === false ? $this->notFound() : $this->redirect($to, false);
                }
            }
        }
        if (isset($pages[$path])) {
            return $this->file('pages/'.$pages[$path].'.html');
        }
        if (isset($static[$path])) {
            return $this->file($static[$path][0], 200, $static[$path][1], $request);
        }
        if ($path === '/home/' || $path === '/home') {
            return $this->redirect('/');
        }
        if (! str_ends_with($path, '/') && (isset($pages[$path.'/']) || isset($static[$path.'/']))) {
            return $this->redirect($rawPath.'/', true, false);   // redirect_canonical: URL เดิมตามที่ขอ + "/"
        }

        return $this->notFound();
    }

    /** หน้าผลค้นหา (Divi search.php): ใส่ผลลงแม่แบบ resources/www/search.html */
    private function search(Request $request, string $s, int $paged): Response
    {
        $found = $this->match($s);
        $max = (int) ceil(count($found) / self::PER_PAGE);
        $s = str_replace(["\r", "\n"], '', $s);
        $term = htmlspecialchars($s, ENT_QUOTES, 'UTF-8', false);   // esc_attr( get_search_query() )
        if ($paged > max($max, 1)) {
            return $this->notFound($term);
        }
        $rows = array_slice($found, ($paged - 1) * self::PER_PAGE, self::PER_PAGE);

        if ($rows) {
            $left = "\n\t\t";
            foreach ($rows as $r) {
                $left .= "\n\t\t\t\t\t".$this->read('articles/'.$r['id'].'.html')."\n\t\t\t";
            }
            $older = $paged < $max ? '<a href="'.$this->pageLink($request, $paged + 1).'" >&laquo; โพสต์ก่อนหน้า</a>' : '';
            $newer = $paged > 1 ? '<a href="'.$this->pageLink($request, $paged - 1).'" >โพสต์ต่อไป &raquo;</a>' : '';
            $left .= "<div class=\"pagination clearfix\">\n\t<div class=\"alignleft\">$older</div>\n\t<div class=\"alignright\">$newer</div>\n</div>\t\t\t";
        } else {
            $left = $this->read('search-empty.html');
        }

        // MonsterInsights: หมวดตามจำนวนผลทั้งหมด · gtm4wp: siteSearchFrom = Referer (ส่วน query ถูก rawurlencode)
        $n = count($found);
        $q = rawurlencode($s);
        $ga = $n === 0 ? "no-results:$q&cat=no-results" : $q.'&cat='.($n === 1 ? '1-result' : ($n < 6 ? '2-5-results' : 'plus-5-results'));
        $ref = (string) $request->headers->get('Referer', '');
        if (str_contains($ref, '?')) {
            [$a, $b] = explode('?', $ref, 2);
            $ref = $a.'?'.rawurlencode(explode('?', $b)[0]);
        }
        $body = ($n ? 'search-results' : 'search-no-results').($paged > 1 ? " paged paged-$paged search-paged-$paged" : '');

        $html = strtr($this->read('search.html'), [
            '%%LEFT%%' => $left,
            '%%TERM_HTML%%' => $term,
            '%%TERM_FEED%%' => $s === '' ? '' : urlencode($s).'/',   // get_search_feed_link()
            '%%GA%%' => $ga,
            '%%TERM_JSON%%' => json_encode($term, JSON_UNESCAPED_UNICODE),
            '%%FROM_JSON%%' => json_encode($ref, JSON_UNESCAPED_UNICODE),
            '%%COUNT%%' => (string) count($rows),
            '%%ACTION%%' => $rows ? $rows[0]['action'] : '',   // Divi ใช้ $post ตัวแรกของหน้า
            '%%PAGE_ID%%' => $rows ? (string) $rows[0]['id'] : '',
            '%%BOUNCE%%' => $rows ? '5' : '',
            '%%BUILDER%%' => $rows && $rows[0]['builder'] ? ' et_pb_pagebuilder_layout' : '',
            '%%BODY%%' => $body,
        ]);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * รายการที่ตรงคำค้น — SQL เดียวกับ WP_Query ของ WP 5.2 (parse_search + parse_search_order, ORDER BY ... post_date DESC)
     * แต่อ่านจาก site.json ผ่าน JSON_TABLE แทนตาราง wp_posts
     */
    private function match(string $s): array
    {
        $where = '1=1';
        $bind = [];
        $order = 'post_date DESC';
        $obind = [];
        if (! empty($s)) {   // WordPress: if ( ! empty( $q['s'] ) ) — '' และ '0' ไม่มีเงื่อนไขค้นหา
            $s = str_replace(["\r", "\n"], '', $s);
            $count = 0;
            $terms = [$s];
            if (preg_match_all('/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $s, $m)) {
                $count = count($m[0]);
                $terms = $this->parseTerms($m[0]);
                if (! $terms || count($terms) > 9) {
                    $terms = [$s];
                }
            }
            $titles = [];
            foreach ($terms as $t) {
                $exclude = str_starts_with($t, '-');
                [$op, $andor] = $exclude ? ['NOT LIKE', 'AND'] : ['LIKE', 'OR'];
                $like = '%'.$this->escLike($exclude ? substr($t, 1) : $t).'%';
                if (! $exclude) {
                    $titles[] = $like;
                }
                $where .= " AND ((post_title $op ?) $andor (post_excerpt $op ?) $andor (post_content $op ?))";
                array_push($bind, $like, $like, $like);
            }
            if ($titles && $count > 1) {
                $sentence = preg_match('/(?:\s|^)\-/', $s) ? null : '%'.$this->escLike($s).'%';
                $case = '';
                if ($sentence !== null) {
                    $case .= 'WHEN post_title LIKE ? THEN 1 ';
                    $obind[] = $sentence;
                }
                $num = count($titles);
                if ($num < 7) {
                    $case .= 'WHEN '.implode(' AND ', array_fill(0, $num, 'post_title LIKE ?')).' THEN 2 ';
                    array_push($obind, ...$titles);
                    if ($num > 1) {
                        $case .= 'WHEN '.implode(' OR ', array_fill(0, $num, 'post_title LIKE ?')).' THEN 3 ';
                        array_push($obind, ...$titles);
                    }
                }
                if ($sentence !== null) {
                    $case .= 'WHEN post_excerpt LIKE ? THEN 4 WHEN post_content LIKE ? THEN 5 ';
                    array_push($obind, $sentence, $sentence);
                }
                if ($case !== '') {
                    $order = "(CASE {$case}ELSE 6 END), post_date DESC";
                }
            } elseif ($titles) {
                $order = '(post_title LIKE ?) DESC, post_date DESC';
                $obind = [$titles[0]];
            }
        }

        $u = 'CHARACTER SET utf8mb4';   // ไม่ระบุ = charset ของ DB (admin_pack_bench เป็น utf8mb3)
        $c = 'COLLATE utf8mb4_unicode_520_ci';
        $sql = "SELECT id FROM (SELECT id, post_title $c AS post_title, post_excerpt $c AS post_excerpt, post_content $c AS post_content, post_date
            FROM JSON_TABLE(?, '$[*]' COLUMNS (id INT PATH '$.id', post_title LONGTEXT $u PATH '$.title', post_excerpt LONGTEXT $u PATH '$.excerpt',
                post_content LONGTEXT $u PATH '$.content', post_date DATETIME PATH '$.date')) AS j) AS wp_posts
            WHERE $where ORDER BY $order";
        $items = array_column(self::site()['items'], null, 'id');
        $json = json_encode(array_values(array_map(fn ($i) => array_intersect_key($i, array_flip(['id', 'title', 'excerpt', 'content', 'date'])), $items)));

        return array_map(fn ($r) => $items[$r->id], DB::select($sql, array_merge([$json], $bind, $obind)));
    }

    /** WP_Query::parse_search_terms */
    private function parseTerms(array $terms): array
    {
        $out = [];
        foreach ($terms as $t) {
            $t = preg_match('/^".+"$/', $t) ? trim($t, "\"'") : trim($t, "\"' ");
            if ($t === '' || $t === '0' || (strlen($t) === 1 && preg_match('/^[a-z\-]$/i', $t))) {
                continue;
            }
            if (! in_array(mb_strtolower($t), self::STOPWORDS, true)) {
                $out[] = $t;
            }
        }

        return $out;
    }

    /** $wpdb->esc_like */
    private function escLike(string $s): string
    {
        return addcslashes($s, '_%\\');
    }

    /** get_pagenum_link(): query เดิม (ยกเว้น paged) urlencode ใหม่, add_query_arg ตัด "=" ของค่าว่าง แล้ว esc_url (& → &#038;) */
    private function pageLink(Request $request, int $n): string
    {
        $qs = [];
        foreach ($request->query() as $k => $v) {
            if ($k !== 'paged' && is_string($v)) {
                $qs[] = $k.'='.urlencode($v);
            }
        }
        $qs = preg_replace('#=(&|$)#', '$1', implode('&', $qs));

        return self::BASE.'/'.($n > 1 ? "page/$n/" : '').($qs !== '' ? '?'.str_replace('&', '&#038;', $qs) : '');
    }

    /** $path = path ที่ถอดรหัสแล้ว (เข้ารหัสตัวเล็กแบบ permalink ของ WordPress) หรือ path ดิบตามที่ขอ ($encode = false) */
    private function redirect(string $path, bool $keepQuery = true, bool $encode = true): Response
    {
        if ($encode) {
            $path = implode('/', array_map(fn ($seg) => strtolower(rawurlencode($seg)), explode('/', $path)));
        }
        $qs = $keepQuery ? (string) request()->server('QUERY_STRING', '') : '';   // query ดิบ (getQueryString() เรียงใหม่)

        return new Response('', 301, ['Location' => self::BASE.$path.($qs !== '' ? '?'.$qs : '')]);
    }

    private function notFound(string $term = ''): Response
    {
        return new Response(str_replace('%%TERM_HTML%%', $term, $this->read('404.html')), 404, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function file(string $name, int $status = 200, string $type = 'text/html; charset=UTF-8', ?Request $request = null): Response
    {
        $body = $this->read($name);
        if ($request && str_ends_with($name, '.xml')) {
            // <atom:link rel="self"> = esc_url( URL ที่ขอมา ) แบบ self_link()
            $url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\[\]\x80-\xff]|i', '', self::BASE.$request->server('REQUEST_URI'));
            $body = str_replace('%%SELF%%', str_replace(['&', "'"], ['&#038;', '&#039;'], $url), $body);
        }

        return new Response($body, $status, ['Content-Type' => $type]);
    }

    private function read(string $name): string
    {
        return (string) file_get_contents(resource_path('www/'.$name));
    }

    private static function site(): array
    {
        return self::$site ??= json_decode((string) file_get_contents(resource_path('www/site.json')), true);
    }
}
