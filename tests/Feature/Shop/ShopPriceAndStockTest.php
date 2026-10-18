<?php

namespace Tests\Feature\Shop;

use App\Domain\Shop\CrawlPrices;
use App\Http\Controllers\Shop\Admin\MallStockController;
use App\Http\Controllers\Shop\CartController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\Concerns\FakeLegacyUser;
use Tests\TestCase;

/**
 * แก้จากเดิม (เจ้าของอนุมัติ 2026-10-18 "ให้แอดมินแก้ไขก็พอ"):
 *   - _cart/save_product: ลูกค้ากำหนดราคาเองไม่ได้ (ราคาจาก crawler ที่เซิร์ฟเวอร์จำไว้) — admin_mall ใส่เองได้
 *   - _admin/_mallstock/load_amount, save_amount: สิทธิ์ admin_mall_stock และแตะเฉพาะ mall_stock (ไม่ใช่ balance)
 */
class ShopPriceAndStockTest extends TestCase
{
    use FakeLegacyUser;

    private const URL = 'https://item.taobao.com/item.htm?id=1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpLegacyWorld();
    }

    private function crawl(int $uid, array $extra = []): void
    {
        CrawlPrices::remember($uid, self::URL, (string) json_encode($extra + [
            'product_name' => 'x', 'shop_name' => 's', 'price' => '10.50', 'url' => self::URL.'&x=1',
            'skus' => ['red:M' => ['price' => ['priceText' => '12.00'], 'quantity' => '5']],
            'priceRange' => [[1, '10.50'], [10, '9.80']],
        ]));
    }

    public function test_price_matching_crawler_data_is_kept(): void
    {
        $this->crawl(137);
        $this->assertSame('10.50', CrawlPrices::resolve(137, self::URL, '10.50'));
        $this->assertSame('12', CrawlPrices::resolve(137, self::URL, '12'), 'ราคา SKU (ค่าที่ส่งมาเดิมลง DB)');
        $this->assertSame('9.80', CrawlPrices::resolve(137, self::URL.'&x=1', '9.80'), 'priceRange + URL ที่ crawler ตอบ');
    }

    public function test_customer_price_is_ignored_when_not_from_crawler(): void
    {
        $this->crawl(137);
        foreach (['0.01', '0', '1', '10.5abc', '100', '-1', '10.50 OR 1', ''] as $fake) {
            $this->assertSame('10.50', CrawlPrices::resolve(137, self::URL, $fake), $fake);
        }
    }

    public function test_no_crawler_data_means_no_price(): void
    {
        $this->crawl(137);
        $this->assertNull(CrawlPrices::resolve(138, self::URL, '10.50'), 'ข้อมูลของผู้ใช้อื่นใช้ไม่ได้');
        $this->assertNull(CrawlPrices::resolve(137, 'https://other.example/', '10.50'));
        CrawlPrices::remember(139, self::URL, (string) json_encode(['product_name' => '', 'price' => null]));
        $this->assertNull(CrawlPrices::resolve(139, self::URL, '1'), 'crawler หาไม่เจอ = ไม่จำ');
    }

    public function test_save_product_refuses_customer_without_crawler_data_before_writing(): void
    {
        DB::shouldReceive('transaction')->never();
        DB::shouldReceive('table')->never();
        $req = $this->actAs(137, ['order'], [], [
            'search_url' => self::URL, 'search_price' => '0.01', 'search_amount' => '1', 'search_product_name' => 'x',
        ], '/_cart/save_product');
        $out = (string) app(CartController::class)->saveProduct($req)->getContent();
        $this->assertStringContainsString("ErrorBox('ไม่พบข้อมูลราคาสินค้า", $out);
    }

    public function test_save_product_stores_crawler_price_for_customer(): void
    {
        $this->crawl(137);
        $inserted = $this->expectCartInsert();
        $req = $this->actAs(137, ['order'], [], [
            'search_url' => self::URL, 'search_price' => '0.01', 'search_amount' => '2', 'search_product_name' => 'x',
        ], '/_cart/save_product');
        $out = (string) app(CartController::class)->saveProduct($req)->getContent();
        $this->assertStringContainsString('SuccessBox(', $out);
        $this->assertSame('10.50', $inserted->row['price']);
    }

    public function test_mall_admin_may_set_price(): void
    {
        $inserted = $this->expectCartInsert();
        $req = $this->actAs(11938, ['order', 'admin_mall'], [], [
            'search_url' => self::URL, 'search_price' => '7.25', 'search_amount' => '1', 'search_product_name' => 'x',
        ], '/_cart/save_product');
        app(CartController::class)->saveProduct($req);
        $this->assertSame('7.25', $inserted->row['price']);
    }

    /** DB ปลอม: ตะกร้ามีอยู่แล้ว (CartRepo::find) + จับแถวที่ insert ลง cart_detail */
    private function expectCartInsert(): object
    {
        $captured = (object) ['row' => null];
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());
        DB::shouldReceive('raw')->andReturnUsing(fn ($v) => new \Illuminate\Database\Query\Expression($v));
        $cart = Mockery::mock();
        $cart->shouldReceive('where', 'orderBy', 'orderByDesc', 'select')->andReturnSelf();
        $cart->shouldReceive('first')->andReturn((object) ['id' => 5, 'user' => 137, 'random' => 'zzrand']);
        DB::shouldReceive('table')->with('cart')->andReturn($cart);
        $detail = Mockery::mock();
        $detail->shouldReceive('insert')->once()->andReturnUsing(function ($row) use ($captured) {
            $captured->row = $row;

            return true;
        });
        DB::shouldReceive('table')->with('cart_detail')->andReturn($detail);

        return $captured;
    }

    public function test_mallstock_amount_routes_need_mall_stock_permission(): void
    {
        foreach (['_admin/_mallstock/load_amount', '_admin/_mallstock/save_amount'] as $uri) {
            $r = collect(Route::getRoutes()->getRoutes())->first(fn ($r) => $r->uri() === $uri);
            $this->assertNotNull($r, $uri);
            $this->assertContains('legacy.perm:admin_mall_stock', $r->gatherMiddleware(), $uri);
            $this->assertNotContains('legacy.perm:admin_balance', $r->gatherMiddleware(), $uri);
        }
    }

    public function test_mallstock_save_amount_updates_only_mall_stock(): void
    {
        DB::shouldReceive('table')->with('balance')->never();
        DB::shouldReceive('raw')->andReturnUsing(fn ($v) => new \Illuminate\Database\Query\Expression($v));
        $stock = Mockery::mock();
        $stock->shouldReceive('where')->andReturnSelf();
        $stock->shouldReceive('update')->once()->with(Mockery::on(
            fn ($v) => $v['amount'] === -3 && $v['detail'] === 'zz adjust' && ! array_key_exists('type', $v) && ! array_key_exists('user', $v)
        ))->andReturn(1);
        DB::shouldReceive('table')->with('mall_stock')->andReturn($stock);

        $req = $this->actAs(11938, ['admin_mall_stock'], [], ['id' => '49', 'amount' => '-3', 'detail' => 'zz adjust', 'type' => '2', 'code' => '']);
        $this->assertStringContainsString('SuccessBox(', (string) app(MallStockController::class)->saveAmount($req)->getContent());
    }

    public function test_mallstock_save_amount_rejects_bad_input_without_touching_db(): void
    {
        DB::shouldReceive('table')->never();
        DB::shouldReceive('raw')->andReturnUsing(fn ($v) => new \Illuminate\Database\Query\Expression($v));
        $c = app(MallStockController::class);
        foreach ([
            ['id' => '1', 'amount' => '1.5'], ['id' => '1', 'amount' => 'abc'], ['id' => '1) OR (1=1', 'amount' => '1'],
            ['id' => '', 'amount' => '1', 'mall_detail_id' => ''], ['id' => '', 'amount' => '1', 'mall_detail_id' => '4 OR 1'],
        ] as $post) {
            $req = $this->actAs(11938, ['admin_mall_stock'], [], $post + ['code' => '', 'detail' => '']);
            $this->assertStringContainsString('ErrorBox(', (string) $c->saveAmount($req)->getContent(), json_encode($post));
        }
    }
}
