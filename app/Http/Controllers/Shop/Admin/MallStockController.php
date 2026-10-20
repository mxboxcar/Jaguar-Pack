<?php

namespace App\Http\Controllers\Shop\Admin;

use App\Domain\Balance\Ledger;
use App\Http\Controllers\Balance\Admin\AdminBalanceController;
use App\Http\Controllers\Shop\ShopController;
use App\Support\GridQuery;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * _admin/_mallstock/*.php ของ SR — SR ไม่มีร้านค้า (ไม่มีตาราง mall / mall_stock) ไฟล์ทั้งโฟลเดอร์เป็น "สำเนาเก่า" ของ _admin/_balance
 *   - index.php: ไม่มี page admin_mall_stock ใน content.php → หน้าไม่มีจริง (dead, ไม่ทำ route) — เปิด URL = 404 → หน้าแรก
 *   - grid.php: รายการบัญชีเงินบาทของลูกค้าทุกคน (สำเนา _admin/_balance/grid รุ่นเก่า) — ตรวจ admin_balance ไม่มีสิทธิ์ = exit() เงียบ
 *   - load_amount.php / save_amount.php: เหมือน _admin/_balance/load_amount|save_amount ทุกตัวอักษร (สิทธิ์ admin_balance)
 * เปิดตรงด้วย POST ได้ → ใหม่: ใช้สิทธิ์ admin_balance แบบ SR เดิม และทำงานผ่าน AdminBalanceController ของกลุ่ม balance
 * (กฎ/การแก้เรื่องเงินชุดเดียวกับ _admin/_balance: ยอดต้องเป็นตัวเลข Money::adminAmount, bind ทุกค่า, กันกดซ้ำ legacy.once)
 * — ไม่ได้ให้ทำอะไรได้มากกว่าหน้า admin/balance ที่ผู้ถือ admin_balance ใช้อยู่แล้ว
 * (jl เดิมเปลี่ยนเป็นสิทธิ์ admin_mall_stock + ตาราง mall_stock — ใน SR ไม่มีตารางนั้น และ admin_mall_stock ไม่มี role ใดถือ)
 */
class MallStockController extends ShopController
{
    /** _admin/_mallstock/grid.php (SR) — $ajaxpage เดิม = "_admin/_balance/grid" (เปลี่ยนหน้า/ค้นหา = ไป grid ของ balance) */
    public function grid(Request $request): Response
    {
        if (! perm('admin_balance')) {
            return $this->out(''); // เดิม exit() เงียบๆ
        }
        $s = Ledger::search($request);

        $filter = function (Builder $q) use ($s) {
            if ($s['term'] !== '') {
                // เดิม LIKE '%...%' ต่อ string (ไม่ escape — %/_ เป็น wildcard, ' เจาะได้) → bind + escape (CONVENTIONS ข้อ 4)
                $like = Ledger::contains(Ledger::stripPrefix('balance', $s['term']));
                $q->where(fn (Builder $w) => $w->where('balance.id', 'like', $like)
                    ->orWhere('balance.detail', 'like', $like)
                    ->orWhere('user.code', 'like', $like));
            }
            if (Ledger::typeFilterApplies($s['type'])) {
                $q->where('balance.type', $s['type']);
            }
        };
        $q = DB::table('balance')
            ->leftJoin('balance_type', 'balance.type', '=', 'balance_type.id')
            ->leftJoin('prefix', 'balance.prefix', '=', 'prefix.id')
            ->leftJoin('user', 'user.id', '=', 'balance.user')
            ->where('balance.is_delete', '0');
        $filter($q);

        // นับ: ไม่ค้นหา = นับเฉพาะตาราง balance (join ทั้งหมดเป็น LEFT JOIN 1:1 จำนวนแถวเท่ากัน)
        $total = $s['term'] === '' ? GridQuery::liveCount('balance', function (Builder $c) use ($s) {
            if (Ledger::typeFilterApplies($s['type'])) {
                $c->where('balance.type', $s['type']);
            }
        }) : null;
        $page = Ledger::php($request, 'page');
        [$total, $items] = Ledger::page($q, 'balance', $page, [
            'balance.*', 'balance_type.name', 'prefix.link_admin as link', 'user.code', 'user.id as user_id',
        ], $total);
        // ยอดสะสมก่อนหน้า (เดิม SUM ต่อแถว: user = '".$item['user_id']."' — ไม่มีลูกค้า = '' = 0)
        GridQuery::attachPrevSums('balance', $items, fn ($item) => (int) ($item->user_id ?? 0));

        return $this->out(view('shop.admin.mall_stock.grid', [
            'ajaxpage' => '_admin/_balance/grid',
            'items' => $items,
            'types' => Ledger::activeTypes(),
            'allTypes' => Ledger::allTypes(),
            's' => $s,
            'page' => $page,
            'total' => $total,
            'prefix' => Ledger::prefixText('balance'),
        ])->render());
    }

    /** _admin/_mallstock/load_amount.php = _admin/_balance/load_amount.php */
    public function loadAmount(Request $request): Response
    {
        return app(AdminBalanceController::class)->loadAmount($request);
    }

    /** _admin/_mallstock/save_amount.php = _admin/_balance/save_amount.php */
    public function saveAmount(Request $request): Response
    {
        return app(AdminBalanceController::class)->saveAmount($request);
    }
}
