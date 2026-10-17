<?php

namespace App\Http\Controllers;

use App\Models\Tracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TrackingController extends Controller
{
    /** เท่ากับ _tracking/grid.php เดิม */
    public function index(Request $request, int $userId): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'integer', 'between:-1,3'],
        ]);
        $status = isset($validated['status']) && (int) $validated['status'] >= 0 ? (int) $validated['status'] : null;

        $trackings = Tracking::query()
            ->from(DB::raw('`tracking` FORCE INDEX (`idx_user_del_id`)'))
            ->select('tracking.*')
            ->with(['lot:id,name,type', 'productType:id,name'])
            ->active()
            ->ownedBy($userId)
            ->search($validated['q'] ?? null)
            ->status($status)
            ->orderByDesc('tracking.id')
            ->paginate(20)
            ->withQueryString();

        // เลขออเดอร์ในระบบ: ดึงครั้งเดียวทั้งหน้า (โค้ดเดิมยิง query ทีละแถว)
        $orderIds = DB::table('bill_product_on_tracking as t')
            ->join('bill_product_detail as d', 'd.id', '=', 't.bill_product_detail_id')
            ->whereIn('t.tracking_id', $trackings->pluck('id'))
            ->whereNotNull('d.bill_product_id')
            ->get(['t.tracking_id', 'd.bill_product_id'])
            ->groupBy('tracking_id')
            ->map(fn ($rows) => $rows->pluck('bill_product_id')->all());

        return view('tracking.index', [
            'trackings' => $trackings,
            'orderIds' => $orderIds,
            'q' => $validated['q'] ?? '',
            'status' => $status ?? -1,
        ]);
    }
}
