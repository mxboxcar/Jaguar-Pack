<?php

namespace App\Http\Controllers\Settings\Admin;

use App\Domain\Settings\Input;
use App\Http\Controllers\Controller;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * ตั้งค่าระบบ: _admin/_setting/*.php เดิม (สิทธิ์ admin_setting)
 */
class SettingController extends Controller
{
    /** คีย์ที่ save.php เดิมเขียน (ลำดับเดิม) — คีย์อื่นในตาราง setting ไม่แตะ */
    private const KEYS = [
        'rate_order', 'rate_alipay', 'rate_transfer',
        'bank_acc', 'bank_no', 'bank_name', 'bank_branch', 'change_sender',
        'calculate_woodprotect', 'address_cn', 'phone_cn', 'phone_th', 'exchange_rate',
        'today_lot', 'today_lot_yellow', 'today_lot_green',
    ];

    /** GET /admin/setting — _admin/_setting/index.php (เดิม getKey() 1 query ต่อช่อง → ที่นี่ query เดียว) */
    public function index(): View
    {
        $s = [];
        foreach (DB::table('setting')->orderBy('id')->get(['key', 'value']) as $row) {
            $k = strtolower(rtrim((string) $row->key, ' ')); // `key` = '$key' ด้วย collation ci เดิม — แถวแรกต่อคีย์
            if (! array_key_exists($k, $s)) {
                $s[$k] = $row->value;
            }
        }

        return view('settings.admin.setting.index', ['s' => $s]);
    }

    /**
     * POST /_admin/_setting/save — _admin/_setting/save.php
     * เดิมไม่ตรวจสิทธิ์เลย (ใครก็แก้เลขบัญชีรับเงินได้) → ที่นี่ legacy.perm:admin_setting
     *
     * ตั้งใจต่างจากเดิม: เดิมช่องที่ไม่ส่งมาถูกเขียน '' ทับ (POST ว่างครั้งเดียว = ล้างเลขบัญชี/อัตราแลกเปลี่ยนทั้ง 16 ค่า)
     * ที่นี่อัปเดตเฉพาะคีย์ที่อยู่ใน POST body (ค่า string/ตัวเลข) — ไม่มีคีย์ใดเลย = ErrorBox ไม่แก้อะไร
     * (ฟอร์มจริงส่งครบทุกช่องเสมอ ผลจึงเหมือนเดิม; ช่องที่ส่งมาเป็นค่าว่างยังบันทึก '' แบบเดิม)
     */
    public function save(Request $request): Response
    {
        $keys = array_values(array_filter(self::KEYS, fn (string $k) => Input::has($request, $k)));
        if ($keys === []) {
            return response(Alerts::error('CloseLoading()', 'ไม่มีข้อมูลที่จะบันทึก'));
        }

        DB::transaction(function () use ($request, $keys) {
            foreach ($keys as $key) {
                DB::table('setting')->where('key', $key)->update(['value' => Input::in($request, $key)]);
            }
        });

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/setting').'";'));
    }
}
