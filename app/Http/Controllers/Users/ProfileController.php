<?php

namespace App\Http\Controllers\Users;

use App\Domain\Users\Php74;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Users\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyCrypt;
use App\Support\Legacy\LegacyLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * โปรไฟล์ของลูกค้าเอง: _profile/*.php เดิม — ทุก query ใช้ Legacy::userId() (ไม่รับ id จาก browser)
 *   GET  /profile                       = _profile/index.php (สิทธิ์ profile)
 *   POST /_profile/load_user_type_detail = เรตราคาของกลุ่มตัวเอง (id ที่ส่งมาไม่ถูกใช้ เหมือนเดิม)
 *   POST /_profile/load_role_detail      = รายการสิทธิ์ของ role ตัวเอง
 *   POST /_profile/pin                   = ตั้ง PIN ใหม่ / ยืนยัน PIN (กล่อง getPinBox/getNewPinBox)
 *   POST /_profile/save                  = บันทึกชื่อ/LINE/ที่อยู่/รหัสผ่าน
 */
class ProfileController extends Controller
{
    use ReadsLegacyInput;

    public function index(Request $request)
    {
        if (! perm('profile')) {
            return response()->view('legacy.no-permission', ['module' => 'profile']);
        }
        $data = DB::table('user')->where('id', Legacy::userId())->first();
        if ($data && Php74::eq($data->option_price, 0)) {
            $role = DB::table('user_type')->where('id', (string) $data->type)->orderBy('id')->value('name');
        } else {
            $role = DB::table('user_type as t')->leftJoin('user_type as t2', 't2.id', '=', 't.child')
                ->where('t.id', (string) ($data->type ?? ''))->orderBy('t.id')->value('t2.name');
        }

        return view('users.profile', [
            'data' => $data,
            'prefix' => (string) \App\Support\Legacy\Lookup::getPrefix('user'),
            'userTypeName' => $role,
            // เดิม echo $id ในสคริปต์ (canceling2) = ตัวแปรจาก extract($_GET) (ไม่มีใน /profile) → ว่าง
        ]);
    }

    public function loadUserTypeDetail(): Response
    {
        if (! perm('profile')) {
            return response('');
        }
        $type = (string) (Legacy::user()?->getRawOriginal('type') ?? '');
        $rows = DB::table('user_type_detail')->leftJoin('product_type', 'user_type_detail.product_type_id', '=', 'product_type.id')
            ->whereIn('user_type_detail.type', ['0', '1'])->where('user_type_detail.user_type_id', $type)
            ->orderBy('user_type_detail.id')
            ->get(['user_type_detail.type', 'user_type_detail.kg', 'user_type_detail.cubic', 'product_type.name', 'product_type.desc']);

        $table = function (string $label, int $t) use ($rows): string {
            $h = '<table class="table table-striped table-bordered"'.($t === 1 ? ' ' : '').'>
			<thead>
				<tr>
					<th class="text-center" style="width:70px">'.Lang::get($label).'</th>
					<th class="text-center">'.Lang::get('description').'</th>
					<th class="text-center">'.Lang::get('kg').'</th>
					<th class="text-center">'.Lang::get('cubic').'</th>
				</tr>
			</thead>
			<tbody>';
            foreach ($rows as $row) {
                if ((int) $row->type !== $t) {
                    continue;
                }
                $h .= '<tr>
				<td class="text-center">'.Php74::h($row->name).'</td>
				<td class="text-left">'.Php74::h($row->desc).'</td>
				<td class="text-right">'.Format::numberformat($row->kg, true, true).'</td>
				<td class="text-right">'.Format::numberformat($row->cubic, true, false).'</td>
			</tr>';
            }

            return $h.'</tbody>
		</table>
';
        };

        $html = '
<div class="row">
<div class="col-xs-6">'.$table('car', 0).'</div>

<div class="col-xs-6">'.$table('ship', 1).'</div>
</div>
';

        return response($html);
    }

    public function loadRoleDetail(): Response
    {
        if (! perm('profile')) {
            return response('');
        }
        $role = (string) (Legacy::user()?->getRawOriginal('role') ?? '');
        // เดิมไม่กรอง is_delete ในฝั่งโปรไฟล์
        $active = DB::table('role_on_detail')->where('role_id', $role)->pluck('role_detail_id')
            ->map(fn ($v) => (string) $v)->flip();

        return response(self::roleTable($active, false).'
');
    }

    /**
     * ตารางโมดูล/สถานะ ของ load_role_detail.php (ใช้ร่วมกับฝั่งแอดมิน)
     * เดิมวน role_on_detail ต่อแถว role_detail (O(n×m)) → map ด้วย id; role_detail ทั้งหมด query เดียว
     *
     * @param  \Illuminate\Support\Collection<string, int>  $active  role_detail_id => *
     */
    public static function roleTable($active, bool $showDisabled): string
    {
        $html = '

<table class="table table-condensed m-0">
			<thead>
				<tr>
					<th>'.Lang::get('module').'</th>
					<th>'.Lang::get('status').'</th>
				</tr>
			</thead>
			<tbody>';
        $rows = DB::table('role_detail')->orderBy('role_detail_name')->orderBy('id')->get(['id', 'role_detail_name']);
        foreach ($rows as $row) {
            $html .= '<tr>
				<td>'.Lang::get((string) $row->role_detail_name).'</td>
				<td>';
            if ($active->has((string) $row->id)) {
                $html .= '<span class="label label-table label-success">'.Lang::get('active').'</span>';
            } elseif ($showDisabled) {
                $html .= '<span class="label label-table label-danger">'.Lang::get('disable').'</span>';
            }
            $html .= '</td></tr>';
        }

        return $html.'</tbody>
		</table>';
    }

    /** _profile/pin.php */
    public function pin(Request $request): Response
    {
        if (! perm('profile')) {
            return response('');
        }
        $pin = $this->php($request, 'pin');
        $method = $this->php($request, 'method');
        $eq = $this->php($request, 'eq');
        $callback = $this->php($request, 'callback');
        $confirm = $this->php($request, 'confirmpin');
        $user = Legacy::user();
        $userPin = (string) ($user?->getRawOriginal('pin') ?? '');

        if (! preg_match('/^\d+$/', $pin)) {
            return response(Alerts::error('CloseLoading()', 'PIN ต้องประกอบด้วยตัวเลข 6 หลักเท่านั้น'));
        }

        if ($method === 'new') {
            if (Php74::neq($confirm, $pin)) {
                return response(Alerts::error('CloseLoading()', 'PIN และ ยืนยัน PIN ไม่ตรงกัน'));
            }
            if (strlen($pin) !== 6) {
                return response(Alerts::error('CloseLoading()', 'PIN ต้องประกอบด้วย 6 หลัก'));
            }
            if (Php74::eq($pin, LegacyCrypt::decrypt($user?->getRawOriginal('password')))) {
                return response(Alerts::error('CloseLoading()', 'PIN และรหัสผ่านเข้าระบบไม่สามารถเหมือนกันได้'));
            }
            if ($userPin !== '') {
                return response(Alerts::error('CloseLoading()', 'คุณได้ตั้ง PIN ไปแล้ว หากต้องการเปลี่ยน<br />กรุณาติดต่อโดยตรงที่ฝ่ายบริการเท่านั้น'));
            }
            // เดิมไม่มีเงื่อนไข pin ว่าง (อ่านแล้วค่อยเขียน) → ใส่ใน WHERE กันตั้งซ้อนพร้อมกัน
            DB::table('user')->where('id', Legacy::userId())->where(fn ($q) => $q->whereNull('pin')->orWhere('pin', ''))
                ->update(['pin' => $pin]);

            return response(Alerts::success('CloseLoading();$(".pinbox").modal("hide");setTimeout(function() { $("#divpin").remove(); }, 200);'));
        }

        if ($method === 'verified') {
            if ($eq === '') {
                $eq = '0';
            }
            // PIN ทั้งสองฝั่งเป็นตัวเลขล้วน → == ของ 7.4 เทียบแบบตัวเลข ("0123" == "123")
            if (Php74::neq($pin, $userPin)) {
                $out = Alerts::error('CloseLoading()', 'PIN จ่ายเงินไม่ถูกต้อง');
                // เดิม log PIN ที่พิมพ์ผิดเป็น plaintext → ปิดค่า
                LegacyLog::goLog($request->getRequestUri(), "paymentpin: '***', PIN จ่ายเงินไม่ถูกต้อง");

                return response($out);
            }
            $out = '$("#divpin").html("<input type=\'hidden\' name=\'paymentpin\' value=\''.$pin.'\' />"); CloseLoading();  ';
            // callback / eq มาจาก POST แล้วถูกฝังใน JS string "..." (เดิมต่อดิบ) → escape เฉพาะตัวที่ปิด string ได้
            if ($callback !== '') {
                $out .= ' setTimeout(function() {  eval("'.self::dq($callback).'");  }, 300); ';
            } else {
                $out .= ' setTimeout(function() { $("form").eq("'.self::dq($eq).'").submit(); }, 300); ';
            }

            return response($out);
        }

        return response('');
    }

    private static function dq(string $s): string
    {
        return str_replace(['\\', '"', "\r", "\n", '</'], ['\\\\', '\\"', '\\r', '\\n', '<\\/'], $s);
    }

    /** _profile/save.php — เดิมไม่บันทึก email/phone/phone2 ที่อยู่ในฟอร์ม (คงไว้) */
    public function save(Request $request): Response
    {
        $password = $this->php($request, 'password');
        if ($password !== '' && str_contains($password, '&')) {
            return response(Alerts::error('CloseLoading()', 'รหัสผ่านห้ามมีเครื่องหมาย & (ampersand)'));
        }
        $user = Legacy::user();
        if (Php74::eq($password, $user?->getRawOriginal('username'))) {
            return response(Alerts::error('CloseLoading()', 'ชื่อผู้ใข้และรหัสผ่านต้องไม่ตรงกัน'));
        }
        $set = [
            'name' => $this->in($request, 'name'),
            'lineid' => $this->in($request, 'lineid'),
            'address' => $this->in($request, 'address'),
        ];
        if ($password !== '') {
            $set['password'] = LegacyCrypt::encrypt($password);
        }
        $set['mod_date'] = DB::raw('NOW()');
        $set['mod_by'] = Legacy::userId();
        DB::table('user')->where('id', Legacy::userId())->update($set);

        return response(Alerts::success('window.location.reload()', Lang::get('success_process')));
    }
}
