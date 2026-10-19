<?php

namespace App\Http\Controllers\Tracking\Admin;

use App\Domain\Tracking\Php74;
use App\Domain\Tracking\TrackingChecks;
use App\Domain\Tracking\TrackingLookups;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Tracking\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * SR-only: _admin/_check/*.php — รายการรอเช็ค (tracking ที่ลูกค้าขอ QC / ไม้กันกระแทก / ถ่ายรูป, ตาราง tracking_check)
 * สิทธิ์ admin_tracking ทั้ง 3 ไฟล์ (เหมือนเดิม)
 */
class CheckController extends Controller
{
    use ReadsLegacyInput;

    /** GET /admin/check — _admin/_check/index.php */
    public function index(): View
    {
        return view('tracking.check.index', [
            'items' => TrackingChecks::pending(),
            'prefix' => TrackingLookups::prefix('tracking'),
        ]);
    }

    /** POST /_admin/_check/save_clear — id เดียว */
    public function saveClear(Request $request): Response
    {
        TrackingChecks::clear($this->in($request, 'id'));

        return response(Alerts::success('window.location.reload();'));
    }

    /** POST /_admin/_check/save_clearall — id = ", 1 , 2 " จาก checkbox ที่เลือก */
    public function saveClearAll(Request $request): Response
    {
        $id = $this->in($request, 'id');
        if ($id !== '') {
            TrackingChecks::clearAll(Php74::idList(substr($id, 1)) ?? []);
        }

        return response(Alerts::success('window.location.reload();'));
    }
}
