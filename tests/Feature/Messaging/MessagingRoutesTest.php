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
        'POST _admin/_notice/grid' => ['legacy.perm:admin_notice', true],
        'POST _admin/_notice/save' => ['legacy.perm:admin_notice', true],
        'POST _admin/_notice/save_isshow' => ['legacy.perm:admin_notice', true],
        'POST _admin/_notice/delete' => ['legacy.perm:admin_product_type', true],
        'GET _admin/_notice/email' => ['legacy.perm:admin_notice', false],
        'GET _admin/_notice/email_notice' => ['legacy.perm:admin_notice', false],
        'GET _admin/_notice/cron_notice_mail' => ['legacy.perm:admin_notice', false],
        'GET admin/send' => ['legacy.perm:admin_export', false],
        'GET _admin/_send/index_self' => ['legacy.perm:admin_export', false],
        'GET linenotify' => ['legacy.perm:profile', false],
        'GET _linenotify/callback' => ['legacy.perm:profile', false],
        'GET _linenotify/revoke' => ['legacy.perm:profile', false],
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

    public function test_page_routes_set_legacy_page(): void
    {
        $pages = [
            'message' => 'message', 'admin/message' => 'admin_message', 'notice' => 'notice', 'notice/{id}' => 'notice_detail',
            'admin/notice' => 'admin_notice', 'admin/notice/create' => 'admin_notice_detail', 'admin/notice/{id}' => 'admin_notice_detail',
            'admin/send' => 'admin_send', 'linenotify' => 'linenotify',
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
        $img = '<a class="fancybox" target="_blank" href="https://x.blob.core.windows.net/upload/a.png"><img src="https://x.blob.core.windows.net/upload/a.png" style="max-width: 120px;max-height: 120px;"></a>';
        $this->assertSame($img, ChatText::render($img));
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

    public function test_mail_template_uses_raw_legacy_fragments(): void
    {
        $this->assertStringStartsWith('<div style="font-family: arial,sans-serif;font-size:13px;"><strong>', NoticeMail::template((object) ['create_date' => '', 'text' => 'x'])[1]);
        $this->assertStringEndsWith("JLcargo\r\n</div>", NoticeMail::FOOTER_BODY);
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
