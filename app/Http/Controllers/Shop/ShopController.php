<?php

namespace App\Http\Controllers\Shop;

use App\Domain\Shop\Php74;
use App\Http\Controllers\Controller;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\LegacyInput;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * ฐานของ controller กลุ่ม shop
 * ajax เดิมตอบ text/html ที่หน้าเว็บ eval() หรือใส่ใน DOM — ตอบ string ตรงๆ (status 200)
 */
abstract class ShopController extends Controller
{
    protected function uid(): int
    {
        return (int) Legacy::userId();
    }

    /** ajax เดิมตอบ text/html เสมอ */
    protected function out(string $body, int $status = 200): Response
    {
        return response($body, $status)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /** getPermissionAlert($m) ของ ajax ที่ตรวจสิทธิ์เองใน controller (route ใช้ legacy.auth) */
    protected function denyAjax(string $module): Response
    {
        return $this->out(Alerts::permission($module))->header('X-Legacy-Denied', '1');
    }

    /** getPermissionAlert_Page($m) ที่ถูก echo ใน ajax (ได้ <script> กลับไปใส่ DOM) */
    protected function denyAjaxPage(string $module): Response
    {
        return $this->out(Alerts::permissionPage($module))->header('X-Legacy-Denied', '1');
    }

    /** $_POST['page'] ของ grid → [offset, page ดิบ] ; MakePaging เดิม: (10 * page) - 10 */
    protected function pageOffset(Request $request): array
    {
        $raw = $request->request->all()['page'] ?? '';
        $page = Php74::num(is_string($raw) ? $raw : '');

        return [Format::pagingOffset((int) $page), is_string($raw) ? $raw : ''];
    }

    /**
     * grid เดิม: นับแถวทั้งหมด (mysqli_num_rows) แล้ว LIMIT offset,10
     * offset ติดลบ (page ว่าง/0) เดิม = SQL error → ไม่มีแถว
     * @return array{0: list<object>, 1: int}
     */
    protected function gridRows(Builder $query, int $offset, ?\Closure $order = null): array
    {
        $total = (clone $query)->getCountForPagination();
        if ($offset < 0) {
            return [[], $total];
        }
        $q = clone $query;
        if ($order) {
            $order($q);
        }

        return [$q->offset($offset)->limit(10)->get()->all(), $total];
    }

    /** LIKE '%...%' โดย escape ตัวพิเศษ (CONVENTIONS ข้อ 4) */
    protected static function like(string $s): string
    {
        return '%'.addcslashes($s, '%_\\').'%';
    }

    /**
     * explode('|', $_POST['txtsearch'])
     * $php = true: ค่าในตัวแปร PHP เดิม (' เป็น '') ใช้แสดงผล ; false: ค่าที่ลง SQL จริง ใช้ค้นหา
     */
    protected function txtSearch(Request $request, bool $php = false): array
    {
        $v = $php ? LegacyInput::postPhp($request, 'txtsearch') : LegacyInput::post($request, 'txtsearch');

        return explode('|', is_string($v) ? $v : '');
    }

    /** $_POST[$key] ที่เป็น string (array/ไม่มี → '') ค่าที่ลง DB จริง */
    protected function post(Request $request, string $key): string
    {
        $v = LegacyInput::post($request, $key);

        return is_string($v) ? $v : '';
    }

    /** $_POST[$key] ค่าในตัวแปร PHP เดิม (' เป็น '') */
    protected function postPhp(Request $request, string $key): string
    {
        $v = LegacyInput::postPhp($request, $key);

        return is_string($v) ? $v : '';
    }
}
