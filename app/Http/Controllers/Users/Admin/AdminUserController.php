<?php

namespace App\Http\Controllers\Users\Admin;

use App\Domain\Users\Php74;
use App\Domain\Payment\PinBox;
use App\Domain\Users\PricingMatrix;
use App\Domain\Users\RateHistory;
use App\Domain\Users\UsersMailer;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Users\Concerns\ReadsLegacyInput;
use App\Http\Controllers\Users\ProfileController;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyCrypt;
use App\Support\Legacy\LegacyLog;
use App\Support\Legacy\Lookup;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * แอดมิน ผู้ใช้/ลูกค้า: _admin/_user/*.php เดิม
 *   สิทธิ์: admin_user = แก้ไขได้, admin_user_view = ดูรายการ/ฟอร์มแบบอ่านอย่างเดียว (เหมือนเดิม)
 *   ไฟล์ email*.php / btn_email_lost_password.php / rate_history_lib.php เป็นไฟล์ที่ถูก require — ย้ายไป UsersMailer / RateHistory
 */
class AdminUserController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_admin/_user/grid';

    /** สำหรับ JS ที่ปิดกล่อง modal หลังกดปุ่ม view/reset/pin/unbanned */
    private const MODAL_FN = '$(".modal-backdrop").remove(); $("body").removeClass("modal-open"); $(".modal").modal("hide"); CloseLoading();';

    /** GET /admin/user, /admin/user/status/{status} — _admin/_user/index.php */
    public function index(Request $request)
    {
        // route default legacy_page ถูกส่งเป็นอาร์กิวเมนต์ตามตำแหน่งได้ → อ่านจาก route เอง
        return view('users.admin.user.index', ['status' => (string) ($request->route('status') ?? '')]);
    }

    /** GET /admin/user/create, /admin/user/{id} — _admin/_user/form.php */
    public function form(Request $request)
    {
        $id = (int) ($request->route('id') ?? 0);
        $permission = perm('admin_user');
        $data = DB::table('user')->where('id', $id)->first();

        $parentTypeId = null;
        if ($data && Php74::eq($data->option_price, 1)) {
            $parentTypeId = DB::table('user_type as t')->leftJoin('user_type as t2', 't2.id', '=', 't.child')
                ->where('t.id', (string) $data->type)->orderBy('t.id')->value('t2.id');
        }
        $memberCode = DB::table('user')->where('id', (string) ($data->commission ?? ''))->orderBy('id')->value('code');

        // ปุ่ม view pass / reset pass / pin reset / unbanned: เฉพาะรหัสลูกค้าที่อยู่ใน setting read_password_pin
        $allowpass = $permission && $this->allowPass();

        return view('users.admin.user.form', [
            'id' => $id,
            'data' => $data,
            'permission' => $permission,
            'permissionView' => true,
            'prefix' => (string) Lookup::getPrefix('user'),
            'memberCode' => $memberCode,
            'parentTypeId' => $parentTypeId,
            'userTypes' => DB::table('user_type')->where('is_delete', 0)->where('child', 0)->orderBy('id')->get(['id', 'name']),
            'roles' => $permission ? DB::table('role')->where('is_delete', 0)->orderBy('id')->get(['id', 'role_name']) : collect(),
            'm' => PricingMatrix::forUserType($data->type ?? null),
            'allowpass' => $allowpass,
        ]);
    }

    private function allowPass(): bool
    {
        $code = (string) (Legacy::user()?->getRawOriginal('code') ?? '');
        foreach (explode(',', (string) Lookup::getSetting('read_password_pin')) as $item) {
            if (Php74::eq($item, $code)) {
                return true;
            }
        }

        return false;
    }

    /** POST /_admin/_user/grid */
    public function grid(Request $request): Response
    {
        if (! perm('admin_user_view', 'admin_user')) {
            return response('');
        }
        $txt = $this->in($request, 'txtsearch');
        $s = explode('|', $txt);
        $sp = explode('|', $this->php($request, 'txtsearch'));
        $page = $this->php($request, 'page');

        $q = DB::table('user')->leftJoin('role', 'user.role', '=', 'role.id')
            ->leftJoin('user_type', 'user_type.id', '=', 'user.type')
            ->where('user.is_delete', 0);
        $term = '%'.addcslashes($s[0], '%_\\').'%';
        $q->where(function ($w) use ($term) {
            foreach (['id', 'email', 'name', 'code', 'phone', 'phone2', 'username'] as $c) {
                $w->orWhere('user.'.$c, 'like', $term);
            }
        });
        if (($s[1] ?? '') !== '-1' && ($s[1] ?? '') !== '') {
            $q->where('user.role', $s[1]);
        }
        if (($s[2] ?? '') !== '-1' && ($s[2] ?? '') !== '') {
            $q->where('user.type', $s[2]);
        }
        if (($s[3] ?? '') !== '-2' && ($s[3] ?? '') !== '') {
            $q->where('user.is_approved', $s[3]);
        }
        $rows = (clone $q)->count();
        $items = collect();
        $offset = $this->pageOffset($page);
        if ($offset !== null) {
            // กลุ่มราคาตั้งเอง (option_price = 1) แสดงชื่อกลุ่มแม่ — เดิม query ต่อแถว → join
            $items = $q->leftJoin('user_type as pt', 'pt.id', '=', 'user_type.child')
                ->orderByDesc('user.code')->offset($offset)->limit(10)
                ->get(['user.*', 'role.role_name', 'user_type.name as user_type_name', 'pt.name as parent_type_name']);
        }

        return response(view('users.admin.user.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'rows' => $rows,
            's' => $sp,
            'page' => $page,
            'pageJs' => self::pageJs($page),
            'txtsearch' => $this->php($request, 'txtsearch'),
            'prefix' => (string) Lookup::getPrefix('user'),
            'roles' => DB::table('role')->where('is_delete', 0)->orderBy('id')->get(['id', 'role_name']),
            'userTypes' => DB::table('user_type')->where('is_delete', 0)->where('child', 0)->orderBy('id')->get(['id', 'name']),
        ])->render());
    }

    /** เดิม echo $Page (POST) ดิบลงใน JS → เหลือเฉพาะตัวอักษร/ตัวเลข/จุด/ขีด */
    public static function pageJs(string $page): string
    {
        // ค่าที่ปลอดภัยใน JS string ('...' ใน "...") ส่งกลับเหมือนเดิม ("1.5", "2abc") — ตัวอื่นตัดทิ้ง
        return (string) preg_replace('/[^0-9A-Za-z.\-]/', '', $page);
    }

    /** POST /_admin/_user/load_role_detail (สิทธิ์ admin_user) */
    public function loadRoleDetail(Request $request): Response
    {
        if (! perm('admin_user')) {
            return response('');
        }
        $id = $this->in($request, 'id');
        $active = DB::table('role_on_detail')->where('role_id', $id)->where('is_delete', 0)->pluck('role_detail_id')
            ->map(fn ($v) => (string) $v)->flip();

        return response(ProfileController::roleTable($active, true).'
<div class="row">
<a target="_blank" href="'.Legacy::href('admin/role/').Php74::h($id).'" style="margin:10px" class="pull-right btn btn-default waves-effect waves-light">'.Lang::get('edit_this_module').'
   <span class="btn-label btn-label-right"><i class="fa fa-arrow-right"></i>
   </span>
</a></div>');
    }

    /** POST /_admin/_user/load_user_type_detail (สิทธิ์ admin_user_view || admin_user) */
    public function loadUserTypeDetail(Request $request): Response
    {
        if (! perm('admin_user_view', 'admin_user')) {
            return response('');
        }
        $id = $this->in($request, 'id');
        $rows = DB::table('user_type_detail')->leftJoin('product_type', 'user_type_detail.product_type_id', '=', 'product_type.id')
            ->whereIn('user_type_detail.type', ['0', '1'])->where('user_type_detail.user_type_id', $id)
            ->orderBy('user_type_detail.id')
            ->get(['user_type_detail.type', 'user_type_detail.kg', 'user_type_detail.cubic', 'product_type.name']);
        $body = function (int $t) use ($rows): string {
            $h = '';
            foreach ($rows as $row) {
                if ((int) $row->type === $t) {
                    $h .= '<tr>
				<td>'.Php74::h($row->name).'</td>
				<td>'.$row->kg.'</td>
				<td>'.$row->cubic.'</td>
			</tr>';
                }
            }

            return $h;
        };
        $head = fn (string $label, string $style) => '<table class="table  table-bordered"'.$style.'>
			<thead>
				<tr>
					<th style="width:120px">'.Lang::get($label).'</th>
					<th>'.Lang::get('kg').'</th>
					<th>'.Lang::get('cubic').'</th>
				</tr>
			</thead>
			<tbody>';

        $html = '

'.$head('car', '').$body(0).'</tbody>
		</table>


'.$head('ship', ' style="margin-top:10px"').$body(1).'</tbody>
		</table>

<div class="row">
<a target="_blank" href="'.Legacy::href('admin/user_type/').Php74::h($id).'" style="margin:10px" class="pull-right btn btn-default waves-effect waves-light">'.Lang::get('edit_this_module').'
   <span class="btn-label btn-label-right"><i class="fa fa-arrow-right"></i>
   </span>
</a></div>';

        return response($html);
    }

    /** POST /_admin/_user/rate_history (สิทธิ์ admin_user — เดิมตอบ alert ของตัวเอง) */
    public function rateHistory(Request $request): Response
    {
        if (! perm('admin_user')) {
            return response('<div class="alert alert-danger">ไม่มีสิทธิ์ดูประวัติ</div>');
        }
        $uid = (int) $this->in($request, 'id');
        if ($uid <= 0) {
            return response('<div class="alert alert-warning">ไม่พบลูกค้า</div>');
        }
        if (! Schema::hasTable('user_rate_history')) {
            return response('<div class="alert alert-info">ยังไม่มีประวัติ (เริ่มบันทึกตั้งแต่ 17 ต.ค. 2026)</div>');
        }
        $rows = DB::table('user_rate_history as h')->leftJoin('user as u', 'u.id', '=', 'h.actor_id')
            ->where('h.user_id', $uid)->orderByDesc('h.id')->limit(50)
            ->get(['h.create_date', 'h.summary', 'u.code', 'u.name']);
        if ($rows->isEmpty()) {
            return response('<div class="alert alert-info">ยังไม่มีประวัติการแก้ไขราคาของลูกค้ารายนี้ (เริ่มบันทึกตั้งแต่ 17 ต.ค. 2026)</div>');
        }
        $h = '<table class="table table-bordered table-condensed" style="font-size:12px"><thead><tr><th style="width:130px">วันเวลา</th><th style="width:170px">โดย</th><th>รายการที่เปลี่ยน</th></tr></thead><tbody>';
        foreach ($rows as $x) {
            $h .= '<tr><td>'.Php74::h(date('d/m/Y H:i', (int) strtotime((string) $x->create_date))).'</td><td>'.Php74::h($x->code).'<br><small>'.Php74::h($x->name).'</small></td><td>'.nl2br(Php74::h($x->summary)).'</td></tr>';
        }

        return response($h.'</tbody></table><small class="text-muted">แสดงล่าสุด 50 รายการ</small>');
    }

    /** POST /_admin/_user/save */
    public function save(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $password = $this->php($request, 'password');
        if ($password !== '' && str_contains($password, '&')) {
            return response(Alerts::error('CloseLoading()', 'รหัสผ่านห้ามมีเครื่องหมาย & (ampersand)'));
        }
        if (! perm('admin_user')) {
            return response(Alerts::permission('admin_user'));
        }
        $before = RateHistory::snapshot((int) $id);
        $actor = (int) Legacy::userId();

        $commission = $this->in($request, 'commission');
        $member = DB::table('user')->where('code', $commission)->orderBy('id')->first(['id']);
        if ($commission === '') {
            $memberId = '0';
        } elseif (! $member) {
            return response("ErrorBox('ไม่มีผู้แนะนำ code นี้', 'CloseLoading()'); ");
        } else {
            $memberId = (string) $member->id;
        }

        $f = fn (string $k) => $this->in($request, $k);
        $optionPrice = $f('option_price');
        $userType = $f('user_type');
        $uid = Legacy::userId();

        if ($id === '') {
            $newId = DB::transaction(function () use ($f, $password, $uid, $optionPrice, $userType, $memberId, $request) {
                $newId = (int) DB::table('user')->insertGetId([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                    'is_delete' => 0, 'id' => null, 'username' => $f('username'), 'password' => LegacyCrypt::encrypt($password),
                    'role' => $f('role'), 'name' => $f('name'), 'email' => $f('email'), 'phone' => $f('phone'), 'is_enable' => '1',
                    'type' => $userType, 'phone2' => $f('phone2'), 'option_price' => $optionPrice, 'address' => $f('address'),
                    // เดิม code = username
                    'code' => $f('username'), 'diff' => $f('diff'), 'commission' => $memberId,
                ]);
                $this->ownPriceGroup($request, $newId, $optionPrice, $userType);

                return $newId;
            });
            RateHistory::record($newId, $actor, null);
            if (! config('legacy.debug_mail')) {
                UsersMailer::approved($newId);
            }

            return response(Alerts::success('window.location.href = "'.Legacy::href('admin/user').'";', Lang::get('success_process_no').Lookup::getPrefix('user').$newId));
        }

        DB::transaction(function () use ($f, $password, $uid, $optionPrice, $userType, $memberId, $id, $request) {
            // เดิมไม่บันทึก email / phone / phone2 ตอนแก้ไข (คงไว้)
            $set = [
                'name' => $f('name'), 'role' => $f('role'), 'type' => $userType, 'code' => $f('code'), 'username' => $f('username'),
                'option_price' => $optionPrice, 'address' => $f('address'), 'diff' => $f('diff'),
                'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            ];
            if ($password !== '') {
                $set['password'] = LegacyCrypt::encrypt($password);
            }
            $set['commission'] = $memberId;
            DB::table('user')->where('id', $id)->update($set);
            $this->ownPriceGroup($request, $id, $optionPrice, $userType);
        });
        RateHistory::record((int) $id, $actor, $before);
        if (! config('legacy.debug_mail')) {
            UsersMailer::updated((int) $id);
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/user').'";', Lang::get('success_process_no').Lookup::getPrefix('user').Alerts::js($id)));
    }

    /** option_price = 1: สร้างกลุ่มราคาเฉพาะลูกค้า (user_type.child = กลุ่มแม่) + เรตทุกประเภทสินค้า แล้วชี้ user.type ไปกลุ่มใหม่ (เดิมทำทุกครั้งที่บันทึก) */
    private function ownPriceGroup(Request $request, int|string $userId, string $optionPrice, string $userType): void
    {
        if (! Php74::eq($optionPrice, 1)) {
            return;
        }
        $uid = Legacy::userId();
        $typeId = (int) DB::table('user_type')->insertGetId([
            'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
            'is_delete' => 0, 'id' => null, 'name' => '', 'is_default' => '0', 'is_show' => '1', 'child' => $userType,
        ]);
        self::insertRates($request, $typeId);
        DB::table('user')->where('id', (string) $userId)->update(['type' => (string) $typeId]);
    }

    /** INSERT user_type_detail ทุก product_type (is_delete = 0) จากช่อง product_type_{kg,cubic,amount}_id_<id> — ใช้ร่วมกับ _admin/_user_type/save */
    public static function insertRates(Request $request, int|string $typeId): void
    {
        $uid = Legacy::userId();
        $post = $request->request->all();
        $val = function (string $k) use ($post): ?string {
            if (! array_key_exists($k, $post)) {
                return '';
            }
            $v = $post[$k];

            return is_scalar($v) ? str_replace('\\', '', (string) $v) : 'Array';
        };
        $rows = [];
        foreach (DB::table('product_type')->where('is_delete', '0')->orderBy('id')->get(['id', 'type']) as $row) {
            $rows[] = [
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'id' => null, 'user_type_id' => (string) $typeId,
                'kg' => $val('product_type_kg_id_'.$row->id), 'cubic' => $val('product_type_cubic_id_'.$row->id),
                'amount' => $val('product_type_amount_id_'.$row->id), 'type' => (string) $row->type, 'product_type_id' => (string) $row->id,
            ];
        }
        // เดิม INSERT ทีละแถว → insert ครั้งเดียว (ค่าเหมือนเดิม)
        if ($rows !== []) {
            DB::table('user_type_detail')->insert($rows);
        }
    }

    /** POST /_admin/_user/save_approved */
    public function saveApproved(Request $request): Response
    {
        $id = $this->in($request, 'id');
        DB::table('user')->where('id', $id)->update(['is_approved' => '1', 'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId()]);
        if (! config('legacy.debug_mail')) {
            UsersMailer::approved((int) Php74::num($id));
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/user').'";', Lang::get('success_process_no').Lookup::getPrefix('user').Alerts::js($id)));
    }

    /** POST /_admin/_user/cancel */
    public function cancel(Request $request): Response
    {
        $id = $this->in($request, 'id');
        $remark = $this->in($request, 'cancel_remark').' ,ยกเลิกโดย '.(string) (Legacy::user()?->getRawOriginal('code') ?? '');
        DB::table('user')->where('id', $id)->update([
            'is_approved' => '-1', 'cancel_remark' => $remark, 'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId(),
        ]);
        if (! config('legacy.debug_mail')) {
            UsersMailer::cancelled((int) Php74::num($id));
        }

        return response(Alerts::success('window.location.href = "'.Legacy::href('admin/user').'";', Lang::get('success_process_no').Lookup::getPrefix('user').Alerts::js($id)));
    }

    /** POST /_admin/_user/delete — เดิมต่อ id / arr_id เข้า IN (...) ตรงๆ (SQL injection) → รับเฉพาะรายการตัวเลข */
    public function delete(Request $request): Response
    {
        $ids = self::deleteIds($this->in($request, 'id'), $this->in($request, 'arr_id'));
        if ($ids !== null && $ids !== []) {
            DB::table('user')->whereIn('id', $ids)->update(['is_delete' => '1', 'mod_date' => DB::raw('NOW()'), 'mod_by' => Legacy::userId()]);
        }

        return response(Alerts::success());
    }

    /** id ของ delete.php เดิม: arr_id = ",1,2,3" (ตัดตัวแรก) ไม่งั้น id — ค่าที่ไม่ใช่รายการตัวเลข = เดิม SQL error (ไม่ลบอะไร) → null */
    public static function deleteIds(string $id, string $arrId): ?array
    {
        $list = $arrId !== '' ? mb_substr($arrId, 1) : $id;

        return Php74::idList($list);
    }

    // ---------- ปุ่มในฟอร์ม (ต้องมี PIN ของแอดมิน) ----------

    /**
     * ด่านร่วมของ btn_*.php: สิทธิ์ admin_user (เดิม exit เงียบ) + PIN ของแอดมิน (getNewPinBox/getPinBox/ผิด)
     * เพิ่ม: ต้องอยู่ใน setting read_password_pin เหมือนเงื่อนไขที่หน้าเว็บใช้ซ่อนปุ่ม (เดิมเรียกตรงได้ทุกคนที่มี admin_user)
     */
    private function guard(Request $request, string $callback): ?Response
    {
        if (! perm('admin_user') || ! $this->allowPass()) {
            return response('');
        }
        $js = PinBox::guard($request, Legacy::user(), '', 0, $callback);
        if ($js !== null) {
            if (str_starts_with($js, 'ErrorBox(') && str_contains($js, 'PIN จ่ายเงินไม่ถูกต้อง')) {
                LegacyLog::goLog($request->getRequestUri(), "paymentpin: '***', PIN จ่ายเงินไม่ถูกต้อง");
            }

            return response($js);
        }

        return null;
    }

    /** POST /_admin/_user/btn_viewpassword — ตอบ "pass=<รหัสผ่าน>" (เดิมเก็บรหัสแบบถอดกลับได้) */
    public function btnViewPassword(Request $request): Response
    {
        if ($r = $this->guard($request, 'viewpassword()')) {
            return $r;
        }
        $id = $this->in($request, 'id');
        LegacyLog::goLogMessage($request->getRequestUri(), 'BY-'.Legacy::userId().' viewpassword of '.$this->php($request, 'id'));
        $data = DB::table('user')->where('id', $id)->orderBy('id')->first(['role', 'password']);
        if ($data && Php74::neq($data->role, '1')) {
            return response('pass='.Php74::h(LegacyCrypt::decrypt($data->password)));
        }

        return response('pass=');
    }

    /** POST /_admin/_user/btn_resetpassword */
    public function btnResetPassword(Request $request): Response
    {
        if ($r = $this->guard($request, 'resetpass()')) {
            return $r;
        }
        $data = DB::table('user')->where('id', $this->in($request, 'id'))->orderBy('id')->first(['id', 'role', 'code']);
        if (! $data || Php74::eq($data->role, '1')) {
            return response(Alerts::error(self::MODAL_FN, 'Reset ไม่สำเร็จ'));
        }
        DB::table('user')->where('id', $data->id)->update(['password' => LegacyCrypt::encrypt(Format::randd(10))]);
        $this->clearIps((string) $data->code, false, null);
        UsersMailer::adminResetPassword((int) $data->id);

        return response(Alerts::success(self::MODAL_FN, 'Reset สำเร็จ'));
    }

    /** POST /_admin/_user/btn_pinreset */
    public function btnPinReset(Request $request): Response
    {
        if ($r = $this->guard($request, 'pinreset()')) {
            return $r;
        }
        $data = DB::table('user')->where('id', $this->in($request, 'id'))->orderBy('id')->first(['id', 'role']);
        if (! $data || Php74::eq($data->role, '1')) {
            return response(Alerts::error(self::MODAL_FN, 'Reset ไม่สำเร็จ'));
        }
        DB::table('user')->where('id', $data->id)->update(['pin' => '']);

        return response(Alerts::success(self::MODAL_FN, 'Reset สำเร็จ'));
    }

    /** POST /_admin/_user/btn_unbanned */
    public function btnUnbanned(Request $request): Response
    {
        if ($r = $this->guard($request, 'unbanned()')) {
            return $r;
        }
        $data = DB::table('user')->where('id', $this->in($request, 'id'))->orderBy('id')->first(['id', 'code']);
        if (! $data) {
            return response(Alerts::error(self::MODAL_FN, 'unbanned ไม่สำเร็จ'));
        }
        if (! $this->clearIps((string) $data->code, true, (int) $data->id)) {
            return response(Alerts::error(self::MODAL_FN, 'ไม่มีรายการให้ unbanned'));
        }

        return response(Alerts::success(self::MODAL_FN, 'unbanned สำเร็จ'));
    }

    /**
     * ลบประวัติล็อกอินผิด (ips) ของ IP ที่เคยใช้รหัสลูกค้านี้ — เดิม:
     *   SELECT ips WHERE username LIKE '%code%' → IP = ส่วนหน้า [----] ของ text → DELETE ips WHERE text LIKE '%ip%' OR ...
     *   unbanned: + DELETE ips_ban WHERE ip LIKE '%ip%' OR ..., DELETE ips_ban_user WHERE user = id, UPDATE user is_enable = 1
     * ค่าใน LIKE bind + escape (เดิมต่อ string) — คืน false ถ้าไม่มีรายการ ips เลย
     */
    private function clearIps(string $code, bool $unban, ?int $userId): bool
    {
        // รหัสลูกค้าว่าง: เดิม username LIKE '%%' = ทุกแถวของ ips → ลบ ips/ips_ban ของทุก IP → ข้ามการลบ (ยังปลดผู้ใช้แบบกรณี IP ว่าง)
        if (trim($code) === '') {
            if ($unban) {
                DB::table('ips_ban_user')->where('user', $userId)->delete();
                DB::table('user')->where('id', $userId)->update(['is_enable' => '1']);
            }

            return $unban;
        }
        $texts = DB::table('ips')->where('username', 'like', '%'.addcslashes($code, '%_\\').'%')->pluck('text');
        if ($texts->isEmpty()) {
            return false;
        }
        // IP ว่าง (text ขึ้นต้นด้วย [----]) เดิมได้ LIKE '%%' = ลบ ips/ips_ban ทั้งตาราง → ข้าม
        $ips = $texts->map(fn ($t) => explode('[----]', (string) $t)[0])->filter(fn ($ip) => $ip !== '')->unique()->values();
        if ($ips->isEmpty()) {
            // เดิม: มีแถว ips แต่ทุก IP ว่าง → ลบทั้งตาราง แล้วตอบสำเร็จ; ที่นี่ไม่ลบอะไร แต่ยังปลดผู้ใช้แบบเดิม
            if ($unban) {
                DB::table('ips_ban_user')->where('user', $userId)->delete();
                DB::table('user')->where('id', $userId)->update(['is_enable' => '1']);
            }

            return true;
        }
        $like = function ($q, string $col) use ($ips) {
            $q->where(function ($w) use ($ips, $col) {
                foreach ($ips as $ip) {
                    $w->orWhere($col, 'like', '%'.addcslashes($ip, '%_\\').'%');
                }
            });
        };
        DB::transaction(function () use ($like, $unban, $userId) {
            $q = DB::table('ips');
            $like($q, 'text');
            $q->delete();
            if ($unban) {
                $q = DB::table('ips_ban');
                $like($q, 'ip');
                $q->delete();
                DB::table('ips_ban_user')->where('user', $userId)->delete();
                DB::table('user')->where('id', $userId)->update(['is_enable' => '1']);
            }
        });

        return true;
    }
}
