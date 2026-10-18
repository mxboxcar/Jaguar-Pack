<?php

namespace App\Http\Controllers\Tracking\Admin;

use App\Domain\Tracking\CombineTrack;
use App\Domain\Tracking\Php74;
use App\Domain\Tracking\TrackingLookups;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Tracking\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * _admin/_combine_track/*.php เดิม (สิทธิ์ admin_combine_track — หน้า index เดิมไม่ตรวจสิทธิ์ แต่ ajax ทั้งสองตัวตรวจ)
 */
class CombineTrackController extends Controller
{
    use ReadsLegacyInput;

    public function index(): View
    {
        return view('tracking.combine.index');
    }

    /** POST /_admin/_combine_track/load_tracking — ค้นด้วย LIKE '%x%' (ช่องว่าง = ทุกแถวเหมือนเดิม) */
    public function loadTracking(Request $request): Response
    {
        $term = $this->in($request, 'tracking');
        $items = DB::table('tracking')
            ->leftJoin('user', 'tracking.user', '=', 'user.id')
            ->leftJoin('product_type', 'tracking.product_type_id', '=', 'product_type.id')
            ->leftJoin('lot', 'lot.id', '=', 'tracking.lot_id')
            ->where('tracking.is_delete', 0)
            ->where('tracking.tracking', 'like', '%'.addcslashes($term, '%_\\').'%')
            ->where('tracking.tracking', '!=', '-2')
            ->orderBy('tracking.lot_id')->orderBy('tracking.no')->orderBy('tracking.id')
            ->get(['tracking.*', 'user.code', 'product_type.name as product_type_name', 'lot.name as lot_name', 'lot.type as lot_type']);
        $ids = $items->pluck('id')->map(fn ($v) => (int) $v)->all();

        return response(view('tracking.combine.rows', [
            'items' => $items,
            'orderIds' => TrackingLookups::orderIds($ids),
            'quotes' => TrackingLookups::adminQuotes($ids),
            'orderPrefix' => TrackingLookups::prefix('order'),
            'payPrefix' => TrackingLookups::prefix('payment'),
        ])->render());
    }

    /** POST /_admin/_combine_track/save_combine — tracking=",id1,id2" */
    public function saveCombine(Request $request): Response
    {
        $ids = Php74::idList(substr($this->in($request, 'tracking'), 1));
        $result = $ids === null ? null : CombineTrack::mergeIds($ids);
        if (is_string($result)) {
            return response(Alerts::error('CloseLoading()', $result));
        }

        return response(Alerts::success());
    }
}
