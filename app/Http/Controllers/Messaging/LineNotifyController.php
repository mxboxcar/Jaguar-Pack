<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Messaging\Concerns\RejectsCrossSiteGet;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyCrypt;
use App\Support\Legacy\LegacyLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

/**
 * เชื่อมต่อ LINE Notify: _linenotify/*.php เดิม — สิทธิ์ profile
 *
 * - client_id / client_secret / callback อยู่ใน .env (config legacy.external.line_notify.*) — เดิมฝังในโค้ด (ห้ามคัดลอก)
 * - legacy.external.enabled = false (dev): ไม่เรียก notify-bot.line.me / notify-api.line.me จริง
 *   callback ใช้ token ปลอม "zz_fake_line_token" และ revoke ไม่ยิง API (บันทึก log แทน)
 * - แก้จากเดิม: callback เขียน line_uid ให้ user ที่ถอดจาก state ได้ทุกคน (state = encrypt(user id) แบบ ECB ตายตัว
 *   → ผูก LINE ของคนอื่นเข้าบัญชีเหยื่อได้) — ที่นี่ต้องเป็นผู้ที่ล็อกอินอยู่เท่านั้น
 * หมายเหตุ: บริการ LINE Notify ปิดไปแล้ว (31 มี.ค. 2025) — endpoint เหล่านี้ในเว็บจริงใช้งานไม่ได้อยู่แล้ว
 */
class LineNotifyController extends Controller
{
    use RejectsCrossSiteGet;

    /** GET /linenotify — _linenotify/index.php */
    public function index(): View
    {
        $user = Legacy::user();
        $query = [
            'response_type' => 'code',
            'client_id' => (string) config('legacy.external.line_notify.client_id'),
            'redirect_uri' => (string) config('legacy.external.line_notify.callback_url'),
            'scope' => 'notify',
            'state' => LegacyCrypt::encrypt((string) $user->id),
        ];

        return view('messaging.linenotify.index', [
            'connected' => (string) ($user->line_uid ?? '') !== '',
            'url' => 'https://notify-bot.line.me/oauth/authorize?'.http_build_query($query),
        ]);
    }

    /** GET /_linenotify/callback?code=&state= */
    public function callback(Request $request): Response
    {
        $code = (string) (is_scalar($c = $request->query('code')) ? $c : '');
        $state = (string) (is_scalar($s = $request->query('state')) ? $s : '');
        $uid = Legacy::userId();

        // ตรวจ state ก่อนแลก token (ไม่ยิง LINE ด้วย code ของคนอื่นเมื่อ state ไม่ใช่ของผู้ที่ล็อกอิน)
        $stateUser = $state === '' ? '' : (string) LegacyCrypt::decrypt($state);
        if ($stateUser !== '' && $stateUser === (string) $uid) {
            $token = $this->exchange($code);
            DB::table('user')->where('id', $uid)->update(['line_uid' => (string) $token]);
        }

        return $this->back();
    }

    /** GET /_linenotify/revoke */
    public function revoke(Request $request): Response
    {
        if ($this->isCrossSite($request)) {
            return $this->crossSiteResponse();
        }
        $uid = Legacy::userId();
        $row = DB::table('user')->where('id', $uid)->first(['id', 'line_uid']);
        if ($row !== null) {
            if (config('legacy.external.enabled')) {
                try {
                    Http::asForm()->withToken((string) $row->line_uid)->timeout(10)
                        ->post('https://notify-api.line.me/api/revoke');
                } catch (\Throwable) {
                    // เดิมไม่สนผลลัพธ์
                }
            } else {
                LegacyLog::goLogMessage('linenotify', 'revoke skipped (external disabled) user '.$row->id);
            }
            DB::table('user')->where('id', $row->id)->update(['line_uid' => '']);
        }

        return $this->back();
    }

    /** แลก code เป็น access token (เดิม curl → notify-bot.line.me/oauth/token; ไม่มี token = เขียน '' ลง line_uid) */
    private function exchange(string $code): string
    {
        if (! config('legacy.external.enabled')) {
            LegacyLog::goLogMessage('linenotify', 'token exchange faked (external disabled)');

            return 'zz_fake_line_token';
        }
        $res = Http::asForm()->timeout(10)->post('https://notify-bot.line.me/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => (string) config('legacy.external.line_notify.callback_url'),
            'client_id' => (string) config('legacy.external.line_notify.client_id'),
            'client_secret' => (string) config('legacy.external.line_notify.client_secret'),
        ]);

        return (string) ($res->json('access_token') ?? '');
    }

    /** เดิม echo "<script>window.location.href='".href."/linenotify'</script>" (มี // ซ้อน) */
    private function back(): Response
    {
        return response("<script>window.location.href='".Legacy::href()."/linenotify'</script>");
    }
}
