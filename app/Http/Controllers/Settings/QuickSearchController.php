<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Settings\Input;
use App\Http\Controllers\Controller;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * POST /load_quick_search — load_quick_search.php เดิม (ช่องค้นหาด่วนบน layout: "TRK-123" → ลิงก์ไปหน้ารายการ)
 *
 * เดิม: ชื่อตารางจาก prefix.table_name + ค่า code ต่อเข้า SQL ตรง (SQL injection),
 *       "โหมดแอดมิน" (ไม่กรองเจ้าของ) เปิดได้แค่ส่ง current_href ที่มีคำว่า admin หรือ code ขึ้นต้น sa- → ลูกค้าดูได้ว่า
 *       รายการของคนอื่นมีอยู่จริงและเป็นของรหัสลูกค้าใด (IDOR)
 * ใหม่: ค่า bind ทั้งหมด, ตารางต้องมีอยู่จริงและมีคอลัมน์ id/user/is_delete (ไม่งั้น SQL เดิม error = ไม่พบ),
 *       โหมดแอดมินต้องมีสิทธิ์ admin_<prefix.name> ด้วย (ไม่มี → ค้นแบบลูกค้า: เฉพาะรายการของตัวเอง)
 */
class QuickSearchController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $code = str_replace(' ', '', Input::in($request, 'code'));
        $currentHref = Input::in($request, 'current_href');
        $isAdmin = str_contains($currentHref, 'admin');
        if (str_contains($code, 'sa-')) {
            $isAdmin = true;
            $code = str_replace('sa-', '', $code);
        }
        if (str_contains($code, 'us-')) {
            $isAdmin = false;
            $code = str_replace('us-', '', $code);
        }
        $exp = explode('-', $code);
        $ref = $exp[1] ?? '';

        $prefix = DB::table('prefix')->where('prefix', $exp[0].'-')->first();
        $found = null;
        if ($prefix !== null && $ref !== '' && $this->searchable((string) $prefix->table_name)) {
            $isAdmin = $isAdmin && perm('admin_'.$prefix->name);
            $q = DB::table($prefix->table_name.' as t1')
                ->leftJoin('user as t2', 't1.user', '=', 't2.id')
                ->where('t1.id', $ref)->where('t1.is_delete', 0);
            if (! $isAdmin) {
                $q->where('t1.user', Legacy::userId());
            }
            $found = $q->first(['t2.code']);
        }
        if ($found === null) {
            return response((string) json_encode(['href' => '']));
        }

        $x = [
            'href' => Legacy::href(($isAdmin ? $prefix->link_admin : $prefix->link).$ref),
            // ข้อความเดิมมี CRLF + tab (ไฟล์เดิมเป็น CRLF) — ค่าจากผู้ใช้/DB escape เพราะ ConfirmBox แสดงเป็น HTML
            'prefix' => Lang::get('found_row')."\r\n\t\t\t<br /><br />".Lang::get('no_for_grid').' : '.e($prefix->prefix.$ref)
                ."\r\n\t\t\t<br />".Lang::get('customer_code').' : '.e((string) $found->code)
                ."\r\n\t\t\t<br />".Lang::get('type').' : '.Lang::get($isAdmin ? 'administrator' : 'member')
                .'<br /><br />'.Lang::get('question_loadquicksearch'),
        ];

        return response((string) json_encode($x));
    }

    /** ตารางจาก prefix.table_name ต้องมีจริงและมี id, user, is_delete (SQL เดิมใช้ทั้งสามคอลัมน์ — ขาด = error = ไม่พบ) */
    private function searchable(string $table): bool
    {
        if ($table === '' || preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
            return false;
        }
        $cols = DB::table('information_schema.columns')
            ->where('table_schema', DB::raw('DATABASE()'))
            ->where('table_name', $table)
            ->whereIn('column_name', ['id', 'user', 'is_delete'])
            ->count();

        return $cols === 3;
    }
}
