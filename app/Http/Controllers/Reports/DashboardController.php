<?php

namespace App\Http\Controllers\Reports;

use App\Domain\Reports\Php74;
use App\Domain\Reports\Rows;
use App\Http\Controllers\Controller;
use App\Support\Legacy\Legacy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

/**
 * GET /dashboard (และ / ผ่าน routes/legacy.php) — _dashboard/index.php เดิม
 * เดิม: SELECT user / user_type (ไม่ได้ใช้) / product_type x2 / user_type_detail x2 — รวม product_type เป็น query เดียว, ตัด user_type ที่ไม่ได้ใช้
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Legacy::user();
        $type = (string) ($user->type ?? '');
        $types = Rows::strAll(DB::table('product_type')->where('is_delete', '0')->whereIn('type', [0, 1])->orderBy('id')->get());
        $details = Rows::strAll(DB::table('user_type_detail')
            ->leftJoin('product_type', 'user_type_detail.product_type_id', '=', 'product_type.id')
            ->whereIn('user_type_detail.type', ['0', '1'])->where('user_type_detail.user_type_id', $type)
            ->orderBy('user_type_detail.id')
            ->get(['user_type_detail.*', 'product_type.name', 'product_type.desc']));

        return view('reports.dashboard.index', [
            'user' => $user,
            'car' => self::rates($types->filter(fn ($r) => Php74::eq($r->type, 0)), $details->filter(fn ($r) => Php74::eq($r->type, 0))),
            'ship' => self::rates($types->filter(fn ($r) => Php74::eq($r->type, 1)), $details->filter(fn ($r) => Php74::eq($r->type, 1))),
        ]);
    }

    /** ราคาต่อ kg/คิว: ค่าของ product_type แทนด้วยแถวแรกของ user_type_detail ที่ product_type_id ตรงกัน (เหมือนลูปเดิม) */
    private static function rates(iterable $types, iterable $details): array
    {
        $out = [];
        foreach ($types as $t) {
            $m = null;
            // เดิม $it['product_type_id'] == $item['id'] (เทียบแบบ PHP 7.4)
            foreach ($details as $d) {
                if (Php74::eq($d->product_type_id, $t->id)) {
                    $m = $d;
                    break;
                }
            }
            $out[] = (object) ['name' => $t->name, 'desc' => $t->desc, 'kg' => $m ? $m->kg : $t->kg, 'cubic' => $m ? $m->cubic : $t->cubic];
        }

        return $out;
    }
}
