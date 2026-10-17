<?php

namespace Tests\Feature\Balance;

use App\Domain\Balance\Ledger;
use App\Domain\Balance\Php74;
use App\Domain\Balance\Withdraw;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * กลุ่ม balance: เงื่อนไขที่เดิมพึ่งการเทียบแบบ PHP 7.4 (fixture สร้างด้วย php7.4 จากนิพจน์เดิมใน save_withdraw / save_amount)
 * และรูปแบบ JSON ของ load_amount (mysqli_fetch_array) — ไม่แตะ DB
 */
class BalanceLogicTest extends TestCase
{
    private function cases(): array
    {
        return json_decode(file_get_contents(__DIR__.'/../../fixtures/balance-php74-cases.json'), true);
    }

    public function test_withdraw_amount_check_matches_php74(): void
    {
        foreach ($this->cases()['withdraw'] as [$want, $current, $invalid]) {
            $this->assertSame($invalid, Withdraw::invalid($want, $current), json_encode([$want, $current]));
        }
    }

    public function test_company_amount_gt_zero_matches_php74(): void
    {
        foreach ($this->cases()['gt0'] as [$amount, $gt]) {
            $this->assertSame($gt, Php74::cmp($amount, 0) > 0, json_encode($amount));
        }
    }

    public function test_clean_amount_strips_minus_spaces_tabs(): void
    {
        $this->assertSame('1.50', Withdraw::cleanAmount("- 1 .5\t0"));
    }

    public function test_mysqli_json_interleaves_numeric_and_named_keys_as_strings(): void
    {
        $row = (object) ['id' => 5, 'amount' => '-1.50', 'detail' => 'ก/x', 'ref_id_2' => null];
        $this->assertSame(
            '{"0":"5","id":"5","1":"-1.50","amount":"-1.50","2":"\\u0e01\\/x","detail":"\\u0e01\\/x","3":null,"ref_id_2":null}',
            Ledger::mysqliJson($row)
        );
        $this->assertSame('null', Ledger::mysqliJson(null));
    }

    public function test_paging_offset_like_make_paging(): void
    {
        $this->assertSame(0, Php74::offset('1'));
        $this->assertSame(10, Php74::offset('2'));
        $this->assertSame(5, Php74::offset('1.5'));
        $this->assertNull(Php74::offset(''));
        $this->assertNull(Php74::offset('abc'));
        $this->assertNull(Php74::offset('1.55'));
    }

    public function test_type_filter_rule(): void
    {
        $this->assertFalse(Ledger::typeFilterApplies(null));
        $this->assertFalse(Ledger::typeFilterApplies(''));
        $this->assertFalse(Ledger::typeFilterApplies('-2'));
        $this->assertTrue(Ledger::typeFilterApplies('12'));
    }

    public function test_routes_have_legacy_urls_and_permissions(): void
    {
        $expect = [
            'GET balance' => 'legacy.perm:balance',
            'GET balance_cn' => 'legacy.perm:balance_cn',
            'GET admin/balance' => 'legacy.perm:admin_balance',
            'GET admin/balance_cn' => 'legacy.perm:admin_balance_cn',
            'GET admin/balance_company' => 'legacy.perm:admin_balance_company',
            'POST _admin/_balance/load_amount' => 'legacy.perm:admin_balance',
            'POST _admin/_balance/save_amount' => 'legacy.perm:admin_balance',
            'POST _admin/_balance_cn/load_amount' => 'legacy.perm:admin_balance_cn',
            'POST _admin/_balance_cn/save_amount' => 'legacy.perm:admin_balance_cn',
            'POST _admin/_balance_company/load_amount' => 'legacy.perm:admin_balance_company',
            'POST _admin/_balance_company/save_amount' => 'legacy.perm:admin_balance_company',
            'POST _balance/grid' => 'legacy.auth', 'POST _balance/load_withdraw' => 'legacy.auth', 'POST _balance/save_withdraw' => 'legacy.auth',
            'POST _balance_cn/grid' => 'legacy.auth', 'POST _balance_cn/load_withdraw' => 'legacy.auth', 'POST _balance_cn/save_withdraw' => 'legacy.auth',
            'POST _admin/_balance/grid' => 'legacy.auth', 'POST _admin/_balance_cn/grid' => 'legacy.auth', 'POST _admin/_balance_company/grid' => 'legacy.auth',
        ];
        foreach ($expect as $key => $mw) {
            [$method, $uri] = explode(' ', $key);
            $route = collect(Route::getRoutes()->getRoutes())->first(fn ($r) => $r->uri() === $uri && in_array($method, $r->methods(), true));
            $this->assertNotNull($route, $key);
            $this->assertContains($mw, $route->gatherMiddleware(), $key);
            if ($method === 'POST') {
                $this->assertContains('legacy.ajax', $route->gatherMiddleware(), $key);
            }
        }
    }
}
