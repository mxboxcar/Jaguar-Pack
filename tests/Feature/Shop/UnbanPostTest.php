<?php

namespace Tests\Feature\Shop;

use Tests\Feature\Money\BenchDbTestCase;

/** (bench DB, opt-in JL_BENCH_DB_TESTS=1) _api/unban: cron ส่ง token ใน POST body โดยไม่มี Origin/Referer — ต้องไม่โดน LegacyCsrf (403) และไม่ปลดแบนเมื่อ token ผิด */
class UnbanPostTest extends BenchDbTestCase
{
    public function test_post_without_origin_is_accepted_and_wrong_token_does_nothing(): void
    {
        config(['legacy.shop.unban_token' => '']);
        $this->post('/_api/unban', ['token' => 'wrong'])->assertOk()->assertContent('');
    }
}
