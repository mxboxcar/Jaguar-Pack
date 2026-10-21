<?php

namespace Tests\Feature\Messaging;

use App\Domain\Messaging\Chat;
use App\Domain\Messaging\ChatText;
use App\Domain\Messaging\NoticeMail;
use App\Domain\Messaging\NoticeText;
use App\Domain\Messaging\Php74;
use App\Domain\Messaging\UploadedFile;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * กลุ่ม messaging: ทุก URL เดิมมี route + สิทธิ์, และ helper ที่เลียนแบบเว็บเดิม (ไม่แตะ DB)
 */
class MessagingRoutesTest extends TestCase
{
    /** method uri => [middleware สิทธิ์ที่ต้องมี, ต้องมี legacy.ajax หรือไม่] */
    private const EXPECTED = [
        'GET message' => ['legacy.auth', false],
        'POST _message/load' => ['legacy.auth', false],
        'POST _message/load_dept' => ['legacy.auth', true],
        'POST _message/send' => ['legacy.auth', true],
        'POST _message/send_endconversation' => ['legacy.auth', true],
        'POST _message/save_file' => ['legacy.perm:message', true],
        'GET admin/message' => ['legacy.perm:admin_message', false],
        'POST _admin/_message/load' => ['legacy.perm:admin_message', false],
        'POST _admin/_message/load_dept' => ['legacy.perm:admin_message', true],
        'POST _admin/_message/load_user' => ['legacy.perm:admin_message', true],
        'POST _admin/_message/send' => ['legacy.perm:admin_message', true],
        'POST _admin/_message/send_endconversation' => ['legacy.perm:admin_message', true],
        'POST _admin/_message/deletechat' => ['legacy.perm:admin_message', true],
        'POST _admin/_message/save_file' => ['legacy.perm:admin_message', true],
        'GET notice' => ['legacy.auth', false],
        'GET notice/{id}' => ['legacy.auth', false],
        'POST _notice/grid' => ['legacy.auth', true],
        'GET admin/notice' => ['legacy.perm:admin_notice', false],
        'GET admin/notice/create' => ['legacy.perm:admin_notice', false],
        'GET admin/notice/{id}' => ['legacy.perm:admin_notice', false],
        'POST _admin/_notice/grid' => ['legacy.auth', true],
        'POST _admin/_notice/save' => ['legacy.perm:admin_notice', true],
        'POST _admin/_notice/save_isshow' => ['legacy.perm:admin_notice', true],
        'POST _admin/_notice/delete' => ['legacy.perm:admin_product_type', true],
        'GET _admin/_notice/email' => ['legacy.perm:admin_notice', false],
        'GET _admin/_notice/email_notice' => ['legacy.perm:admin_notice', false],
        'GET _admin/_notice/cron_notice_mail' => ['legacy.perm:admin_notice', false],
        'GET admin/send' => ['legacy.perm:admin_export', false],
        'GET admin/sendself' => ['legacy.perm:admin_export', false],
        'GET _admin/_send/index_self' => ['legacy.perm:admin_export', false],
        'POST notifications' => ['legacy.auth', true],
        'POST notification' => ['legacy.auth', true],
        'GET notify_counts' => ['legacy.auth', false],
        'GET notify_token' => ['legacy.auth', false],
        'GET email_header' => ['legacy.auth', false],
        'GET email_center' => ['legacy.auth', false],
        'GET email_footer' => ['legacy.auth', false],
    ];

    public function test_every_legacy_url_has_a_route_with_the_legacy_permission(): void
    {
        $found = [];
        foreach (Route::getRoutes() as $r) {
            foreach ($r->methods() as $m) {
                $found[$m.' '.$r->uri()] = $r;
            }
        }
        foreach (self::EXPECTED as $key => [$mw, $ajax]) {
            $this->assertArrayHasKey($key, $found, "ไม่มี route $key");
            $gathered = $found[$key]->gatherMiddleware();
            $this->assertContains($mw, $gathered, "$key ต้องมี $mw");
            $this->assertSame($ajax, in_array('legacy.ajax', $gathered, true), "$key legacy.ajax");
        }
    }

    public function test_notice_delete_also_requires_admin_notice(): void
    {
        $route = Route::getRoutes()->match(\Illuminate\Http\Request::create('/_admin/_notice/delete', 'POST'));
        $mw = $route->gatherMiddleware();
        $this->assertContains('legacy.perm:admin_product_type', $mw);
        $this->assertContains('legacy.perm:admin_notice', $mw);
    }

    public function test_state_changing_get_routes_reject_cross_site_requests(): void
    {
        $guard = new class
        {
            use \App\Http\Controllers\Messaging\Concerns\RejectsCrossSiteGet;

            public function check(\Illuminate\Http\Request $r): bool
            {
                return $this->isCrossSite($r);
            }
        };
        $make = function (array $headers) {
            $r = \Illuminate\Http\Request::create('http://portal.example/_admin/_notice/email_notice?id=1', 'GET');
            foreach ($headers as $k => $v) {
                $r->headers->set($k, $v);
            }

            return $r;
        };
        $this->assertTrue($guard->check($make(['Sec-Fetch-Site' => 'cross-site'])));
        $this->assertTrue($guard->check($make(['Sec-Fetch-Site' => 'same-site'])));
        $this->assertTrue($guard->check($make(['Referer' => 'https://evil.example/x'])));
        $this->assertFalse($guard->check($make(['Sec-Fetch-Site' => 'same-origin'])));
        $this->assertFalse($guard->check($make(['Sec-Fetch-Site' => 'none'])));
        $this->assertFalse($guard->check($make(['Referer' => 'https://portal.example/admin/notice'])));
        $this->assertFalse($guard->check($make([])));
    }

    public function test_page_routes_set_legacy_page(): void
    {
        $pages = [
            'message' => 'message', 'admin/message' => 'admin_message', 'notice' => 'notice', 'notice/{id}' => 'notice_detail',
            'admin/notice' => 'admin_notice', 'admin/notice/create' => 'admin_notice_detail', 'admin/notice/{id}' => 'admin_notice_detail',
            'admin/send' => 'admin_send', 'admin/sendself' => 'admin_sendself',
        ];
        $seen = [];
        foreach (Route::getRoutes() as $r) {
            if (isset($pages[$r->uri()]) && in_array('GET', $r->methods(), true)) {
                $this->assertSame($pages[$r->uri()], $r->defaults['legacy_page'] ?? null, $r->uri());
                $seen[] = $r->uri();
            }
        }
        $this->assertEqualsCanonicalizing(array_keys($pages), $seen);
    }

    public function test_make_paging_semantics(): void
    {
        $this->assertSame(['0 , 10', 0], Chat::paging('1'));
        $this->assertSame(['20 , 10', 20], Chat::paging('3'));
        $this->assertSame(['5 , 10', 5], Chat::paging('1.5'));
        $this->assertSame(['-10 , 10', null], Chat::paging(''));      // LIMIT -10 = SQL error เดิม → ไม่มีแถว
        $this->assertSame(['-10 , 10', null], Chat::paging('abc'));
        $this->assertSame(['2.5 , 10', null], Chat::paging('1.25'));
        // admin load.php: str_replace("-10", "0", ...) ทั้งคำสั่ง
        $this->assertSame(['0 , 10', 0], Chat::paging('0', true));
        $this->assertSame(['00 , 10', 0], Chat::paging('-9', true));
        $this->assertSame(['-20 , 10', null], Chat::paging('-1', true));
    }

    public function test_chat_text_allows_only_the_image_attachment_markup(): void
    {
        $img = '<a class="fancybox" href="https://x.blob.core.windows.net/upload/a.png"><img src="https://x.blob.core.windows.net/upload/a.png" style="max-width: 120px;max-height: 120px;"></a>';
        $this->assertSame($img, ChatText::render($img));
        // ข้อความเก่าใน DB ที่ยังมี target="_blank" → แสดงได้ แต่ตัด target ออก
        $old = str_replace('class="fancybox" href', 'class="fancybox" target="_blank" href', $img);
        $this->assertSame($img, ChatText::render($old));
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', ChatText::render('<script>alert(1)</script>'));
        $bad = str_replace('a.png"><img', 'a.png" onmouseover="x"><img', $img);
        $this->assertStringNotContainsString('<a', ChatText::render($bad));
        $js = str_replace('https://x.blob.core.windows.net/upload/a.png', 'javascript:alert(1)', $img);
        $this->assertStringNotContainsString('<a', ChatText::render($js));
        $this->assertSame('ข้อความ it&#039;s', ChatText::render("ข้อความ it's"));
    }

    public function test_upload_name_cannot_escape_temp_dir(): void
    {
        $this->assertSame('', UploadedFile::moveScript('../../.env'));
        $this->assertSame('', UploadedFile::moveScript('a/b.png'));
        $this->assertSame('', UploadedFile::moveScript(''));
        $this->assertSame('', UploadedFile::moveScript(null));
    }

    public function test_notice_text_helpers_match_php74(): void
    {
        $this->assertSame('<p>a &#039;b&#39; "</p>', NoticeText::decode('&lt;p&gt;a &#039;b&#39; &quot;&lt;/p&gt;')); // 7.4: ' ไม่ถูกถอด
        $this->assertSame('a', NoticeText::excerpt('&lt;b&gt;a&lt;/b&gt;'));
        $this->assertSame(400, strlen(NoticeText::excerpt(str_repeat('x', 500))));
    }

    /** SR: แม่แบบ/ผู้ส่ง = config('legacy.mail') — ไม่มีแบรนด์ jl และไม่มีบรรทัด PHP (require_once ของ jl / guard ของ hotfix) ในอีเมล */
    public function test_notice_mail_uses_sr_templates(): void
    {
        [$subject, $body] = NoticeMail::template((object) ['create_date' => '2026-10-20 10:00:00', 'text' => '&lt;p&gt;x&lt;/p&gt;']);
        $this->assertSame(config('legacy.mail.header').'<strong>'.$subject.'</strong>'.config('legacy.mail.center')."\n<p>x</p>".config('legacy.mail.footer'), $body);
        $this->assertStringStartsWith('<div style="font-family: arial,sans-serif;font-size:13px;max-width: 560px"><strong>แจ้งข่าวประกาศ วันที่ ', $body);
        $this->assertStringEndsWith("PACK-Cargo\r\n</div>", $body);
        $this->assertStringNotContainsString('<?php', $body);
        $this->assertStringContainsString("\r\n<p>x</p>", NoticeMail::template((object) ['text' => '&lt;p&gt;x&lt;/p&gt;'], "\r\n")[1]);
        foreach (['app/Domain/Messaging/NoticeMail.php', 'app/Console/Commands/MessagingNoticeMail.php', 'app/Http/Controllers/Messaging/MessageController.php'] as $f) {
            $src = (string) file_get_contents(base_path($f));
            $this->assertDoesNotMatchRegularExpression('/jlcargo|JL ?Cargo/i', $src, $f);
        }
        $this->assertStringContainsString("config('legacy.mail.from_address')", (string) file_get_contents(base_path('app/Domain/Messaging/NoticeMail.php')));
    }

    /** SR หลัง hotfix 2026-10-18: email_header/center/footer.php เปิดตรงจาก URL = 403 ว่าง */
    public function test_email_fragments_are_include_only(): void
    {
        $r = (new \App\Http\Controllers\Messaging\IncludeFileController)->emailPart();
        $this->assertSame(403, $r->getStatusCode());
        $this->assertSame('', $r->getContent());
    }

    /** notify_message ของ SR: dev (legacy.external.enabled = false) ไม่ยิงออก */
    public function test_notify_message_is_gated_by_external_flag(): void
    {
        \Illuminate\Support\Facades\Http::fake();
        config(['legacy.external.enabled' => false]);
        \App\Domain\Messaging\NotifyMessage::send(['imageFullsize' => 'https://example.invalid/a.jpg'], 'zz_token');
        \Illuminate\Support\Facades\Http::assertNothingSent();
        $this->assertSame(['message' => "\n\t\t", 'imageFullsize' => 'u', 'imageThumbnail' => 'u'], \App\Domain\Messaging\NotifyMessage::payload(['imageFullsize' => 'u']));
    }

    /** LINE Notify ไม่มีใน SR — ไม่มี route/controller/view เหลือ */
    public function test_no_line_notify_leftovers(): void
    {
        foreach (Route::getRoutes() as $r) {
            $this->assertStringNotContainsString('linenotify', $r->uri());
        }
        $this->assertDirectoryDoesNotExist(resource_path('views/messaging/linenotify'));
    }

    public function test_notice_save_has_double_submit_guard(): void
    {
        $route = Route::getRoutes()->match(\Illuminate\Http\Request::create('/_admin/_notice/save', 'POST'));
        $this->assertContains('legacy.once', $route->gatherMiddleware());
    }

    public function test_php74_helpers(): void
    {
        $this->assertTrue(Php74::eq(null, 0));
        $this->assertTrue(Php74::eq('abc', 0));
        $this->assertFalse(Php74::eq('1abc', 0));
        $this->assertTrue(Php74::eq(null, ''));
        $this->assertFalse(Php74::eq('0', ''));
        $this->assertNull(Php74::idList('910719) OR (1=1'));
        $this->assertSame([86], Php74::idList('86'));
    }
}
