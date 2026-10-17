<?php

namespace App\Http\Controllers\Auth;

use App\Auth\LegacyLogin;
use App\Http\Controllers\Controller;
use App\Support\Legacy\Format;
use App\Support\Legacy\Jail;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyInput;
use App\Support\Legacy\LegacyLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * login / logout / เปลี่ยนภาษา — ใช้ session เดียวกับเว็บเดิม (sessions_v2) ข้อความตอบกลับเหมือนเดิมทุกตัวอักษร
 *   GET  /login                  = login.php
 *   POST /system_check_login     = system_check_login.php  (JS เดิม eval() ผลลัพธ์)
 *   POST /system_checkuser       = system_checkuser.php    (action=logout / RandomStr)
 *   POST /system_change_language = system_change_language.php
 */
class LegacyLoginController extends Controller
{
    public function show()
    {
        if (Legacy::userId() !== null) {
            return redirect()->to(Legacy::href('dashboard'));
        }

        return response()->view('legacy.login')->header('Cache-control', 'private');
    }

    public function check(Request $request): Response
    {
        $username = (string) $request->request->get('username', '');
        $password = (string) $request->request->get('password', '');
        $ip = Legacy::clientIp();
        $tries = ' คุณสามารถลองได้อีก 5 ครั้ง'; // เดิม (5 - $datacount) โดย $datacount ไม่เคยถูกตั้งค่า = 5 เสมอ

        if ($username === '' || $password === '') {
            Jail::recordFailure($ip, LegacyInput::sqlValue($username));

            return $this->js("ErrorBox('".Lang::get('notfound_user')."', 'CloseLoading();')");
        }

        $user = LegacyLogin::findByCredentials($username, $password);
        $rid = Format::randd(16);
        // เดิมบันทึกรหัสผ่านที่พิมพ์ผิดเป็น plaintext ลง log — ที่นี่ปิดไว้เสมอ
        LegacyLog::goLogMessage($request->getRequestUri(), 'request_id: '.$rid.' login:: username: '.LegacyInput::phpValue($username).', password: ***');

        if (! $user) {
            LegacyLog::goLogMessage($request->getRequestUri(), 'request_id: '.$rid.' login:: notfound_user');
            Jail::recordFailure($ip, LegacyInput::sqlValue($username));

            return $this->js("ErrorBox('".Lang::get('notfound_user').$tries."', 'CloseLoading();')");
        }
        if ((int) $user->is_approved === 0) {
            LegacyLog::goLogMessage($request->getRequestUri(), 'request_id: '.$rid.' login:: unapproved_user');

            return $this->js("ErrorBox('".Lang::get('unapproved_user').$tries."', 'CloseLoading();')");
        }

        LegacyLog::goLogMessage($request->getRequestUri(), 'request_id: '.$rid.' login:: success');
        Auth::guard('legacy')->login($user);

        return $this->js("window.location.href = '".Legacy::href('dashboard')."' ");
    }

    public function checkUser(Request $request): Response
    {
        $guard = Auth::guard('legacy');
        // เดิม isset($_POST['action']) == "logout" → true เมื่อมี action ค่าใดก็ได้
        if ($request->request->has('action')) {
            $guard->logout();

            return $this->js(json_encode(['iserror' => false]));
        }
        if ((string) $request->request->get('RandomStr', '') !== '') {
            $raw = $guard instanceof \App\Auth\LegacySessionGuard ? $guard->legacyIdentity() : null;

            return $this->js(json_encode($raw ?? ['username' => null, 'id' => null]));
        }

        return $this->js('');
    }

    public function changeLanguage(Request $request): Response
    {
        $lang = (string) $request->request->get('lang', '');
        $res = $this->js('');
        // เดิมรับค่าอะไรก็ได้แล้ว include('resource/'.$lang.'.php') = LFI → รับเฉพาะ th/en/cn
        if (in_array($lang, Legacy::LANGS, true)) {
            $res->headers->setCookie(Cookie::create(Legacy::cookie('lang'), $lang, time() + 99999999999, '/', null, false, false, true, null));
        }

        return $res;
    }

    private function js(string $body): Response
    {
        return response($body, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
