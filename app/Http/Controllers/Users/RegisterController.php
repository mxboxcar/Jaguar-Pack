<?php

namespace App\Http\Controllers\Users;

use App\Domain\Users\Php74;
use App\Domain\Users\SignupGuard;
use App\Domain\Users\UsersMailer;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Users\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Blocklist;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyCrypt;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * หน้าสาธารณะของกลุ่ม users (ยังไม่ล็อกอิน):
 *   GET  /register                = register.php
 *   GET  /lostpassword            = lost_password.php (RewriteRule) — /lost_password ด้วย
 *   GET  /password                = password.php (สคริปต์ย้ายรหัสครั้งเดียว: ตาราง user_copy ไม่มี → ว่าง)
 *   POST /_register/save          = _register/save.php (+ _register/email.php)
 *   POST /_register/lost_password = _register/lost_password.php (+ _register/email_lost_password.php)
 *   GET  /system_check_username   = system_check_username.php (validationEngine ajax[checkusername])
 */
class RegisterController extends Controller
{
    use ReadsLegacyInput;

    public function register()
    {
        if (Legacy::userId() !== null) {
            return redirect()->to(Legacy::href('dashboard'));
        }
        $html = '';
        $rows = DB::table('user_type')->where('is_delete', '0')->where('child', '0')->where('is_show', '1')
            ->orderBy('id')->get(['id', 'name', 'is_default']);
        foreach ($rows as $row) {
            $id = Php74::h($row->id);
            $checked = (string) $row->is_default === '1' ? ' checked="checked"' : '';
            $html .= '<div class="radio"><input type="radio" id="radio'.$id.'"'.$checked.' name="user_type" value="'.$id.'">
                                                                    <label for="radio'.$id.'">
                                                                        '.Php74::h($row->name).'
                                                                    </label></div>';
        }

        return response()->view('users.register', ['userTypesHtml' => $html])->header('Cache-control', 'private');
    }

    public function lostPassword()
    {
        // lost_password.php เดิมไม่ redirect เมื่อล็อกอินอยู่
        return response()->view('users.lost_password')->header('Cache-control', 'private');
    }

    public function password(): Response
    {
        return response('');
    }

    /** _register/save.php ของ SR */
    public function save(Request $request): Response
    {
        $username = $this->in($request, 'username');
        $email = $this->in($request, 'email');
        $name = $this->in($request, 'name');
        $address = $this->in($request, 'address');
        $phone = $this->in($request, 'phone');
        $upload = $this->in($request, 'upload_file');
        $userType = $this->in($request, 'user_type');
        $phone2 = $this->in($request, 'phone2');
        $lineid = $this->in($request, 'lineid');
        $wechatid = $this->in($request, 'wechatid');
        $password = Format::randd(6);

        // SR: รหัสผ่านสุ่ม 6 ตัว — เทียบกับ username แบบเดิม (แทบไม่มีทางตรง)
        if (Php74::eq($password, $this->php($request, 'username'))) {
            return $this->js(Alerts::error('CloseLoading()', 'ชื่อผู้ใข้และรหัสผ่านต้องไม่ตรงกัน'));
        }
        if (Blocklist::isBlockedName($this->php($request, 'username'))) {
            // เดิมข้อความภาษาไทยถูกบันทึกเป็น UTF-8 ซ้อน 2 ชั้น (แสดงเป็นตัวอักษรเพี้ยน) → แก้เป็นข้อความที่อ่านได้
            return $this->js("ErrorBox('ไม่อนุญาตให้ใช้คำ ".htmlspecialchars($this->php($request, 'username'), ENT_QUOTES)."', 'CloseLoading()'); ");
        }
        if (DB::table('user')->where('username', trim($username))->exists()) {
            return $this->js("ErrorBox('ผู้ใช้งานนี้มีในระบบแล้ว', 'CloseLoading()'); ");
        }
        // SR: อีเมลซ้ำไม่ได้ (ทุกแถว รวมที่ลบแล้ว แบบเดิม)
        if (DB::table('user')->where('email', trim($email))->exists()) {
            return $this->js("ErrorBox('อีเมล์นี้มีในระบบแล้ว', 'CloseLoading()'); ");
        }
        $type = DB::table('user_type')->where('id', $userType)->orderBy('id')->first(['is_show', 'child', 'is_delete']);
        // เดิมตรวจแค่ is_show → ส่ง user_type เป็นกลุ่มราคาตั้งเองของลูกค้าคนอื่น (child <> 0, is_show = 1) หรือกลุ่มที่ลบแล้วได้
        //   → รับเฉพาะกลุ่มที่หน้าสมัครแสดงให้เลือก (child = 0, is_delete = 0, is_show <> 0)
        if (! $type || Php74::eq($type->is_show, 0) || Php74::neq($type->child, 0) || Php74::neq($type->is_delete, 0)) {
            return $this->js("ErrorBox('".Lang::get('invalid_user_type')."', 'CloseLoading()'); ");
        }
        $err = SignupGuard::check($request, $email, $phone);
        if ($err !== '') {
            return $this->js("ErrorBox('".$err."', 'CloseLoading()'); ");
        }

        // เดิมอ่าน user_count แล้วค่อย +1 แยกกัน (สมัครพร้อมกัน 2 คนได้รหัสลูกค้าซ้ำ) → ล็อกแถว user_count ใน transaction
        $id = DB::transaction(function () use ($request, $username, $password, $name, $email, $phone, $userType, $upload, $address, $phone2, $lineid, $wechatid) {
            $count = DB::table('user_count')->where('id', '1')->lockForUpdate()->value('count');
            $next = Php74::num($count) + 1;
            $id = (int) DB::table('user')->insertGetId([
                'create_date' => DB::raw('NOW()'), 'create_by' => '', 'mod_date' => DB::raw('NOW()'), 'mod_by' => '',
                'is_delete' => 0, 'id' => null, 'username' => $username, 'password' => LegacyCrypt::encrypt($password),
                'code' => 'SU'.str_pad((string) $next, 4, '0', STR_PAD_LEFT), 'role' => '0', 'name' => $name, 'email' => $email,
                'phone' => $phone, 'is_enable' => '1', 'type' => $userType, 'identity' => $upload, 'address' => $address,
                'is_approved' => '0', 'approved_date' => null, 'phone2' => $phone2, 'lineid' => $lineid, 'wechatid' => $wechatid,
            ]);
            SignupGuard::log($request, $id);
            DB::table('user_count')->where('id', '1')->update(['count' => (string) $next]);

            return $id;
        });

        $this->storeIdentity($upload);
        if (! config('legacy.debug_mail')) {
            UsersMailer::registered($id);
        }

        return $this->js(Alerts::success('window.location.href = "'.Legacy::href('login').'";', Lang::get('register_complete').Alerts::js($email)));
    }

    /**
     * ไฟล์บัตรประชาชน upload/temp/<f> → upload/<f> ในเครื่อง (SR เดิม copy() ไม่ขึ้น Azure — หน้าแอดมินเปิดที่ href.'upload/<f>')
     * เดิม file_exists('../upload/temp/'.$upload_file) โดยไม่กรอง → ส่ง ../../system_config.php ได้ (คัดลอกไฟล์ใดๆ บนเครื่องไปไว้ใต้ upload/ ที่เปิดสาธารณะ)
     * ที่นี่รับเฉพาะชื่อไฟล์ล้วนใน legacy.upload.temp_dir แล้วคัดลอกไป legacy.upload.local_dir (production ต้องชี้ไป upload/ ของเว็บ)
     */
    private function storeIdentity(string $file): void
    {
        if ($file === '' || $file !== basename($file) || str_starts_with($file, '.') || str_contains($file, "\0")) {
            return;
        }
        $src = rtrim((string) config('legacy.upload.temp_dir'), '/').'/'.$file;
        if (! is_file($src)) {
            return;
        }
        $dir = rtrim((string) config('legacy.upload.local_dir'), '/');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (! @copy($src, $dir.'/'.$file)) {
            Log::warning('users: register identity copy failed', ['file' => $file]);
        }
    }

    /** _register/lost_password.php */
    public function resetPassword(Request $request): Response
    {
        $username = $this->in($request, 'username');
        $user = DB::table('user')->where('username', $username)->orderBy('id')->first(['id']);
        if (! $user) {
            return $this->js("ErrorBox('".Lang::get('no_user')."', 'CloseLoading()'); ");
        }
        DB::table('user')->where('id', $user->id)->update(['password' => LegacyCrypt::encrypt(Format::randd(6))]);
        // เดิมลบประวัติล็อกอินผิดของ IP ผู้ขอ (ปลด jail ของตัวเอง) — คงไว้ (บันทึกในรายงาน)
        $ip = Legacy::clientIp();
        DB::table('ips')->where('text', 'like', '%'.addcslashes($ip, '%_\\').'%')->delete();

        if (! config('legacy.debug_mail')) {
            UsersMailer::lostPassword((int) $user->id);
        }

        return $this->js(Alerts::success('window.location.href = "'.Legacy::href('login').'";', Lang::get('success_reset_password')));
    }

    /** system_check_username.php — {"fieldId":..., "Result": true=ใช้ได้} */
    public function checkUsername(Request $request): Response
    {
        $value = $this->get($request, 'fieldValue');
        // เดิม $_GET ผ่าน addslashes() แล้วถูก echo กลับใน JSON
        // ไม่ส่ง fieldId มา = เดิม null ใน JSON
        $fieldId = $request->query->has('fieldId') ? addslashes($this->get($request, 'fieldId')) : null;
        $exists = DB::table('user')->where('username', $value)->exists();

        return response(json_encode(['fieldId' => $fieldId, 'Result' => ! $exists]));
    }

    private function js(string $s): Response
    {
        return response($s);
    }
}
