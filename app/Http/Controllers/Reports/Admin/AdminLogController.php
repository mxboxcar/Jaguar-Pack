<?php

namespace App\Http\Controllers\Reports\Admin;

use App\Domain\Reports\Rows;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Concerns\ReadsLegacyInput;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * แอดมิน: log การใช้งาน (_admin/_log/index.php สิทธิ์ admin_log, grid.php สิทธิ์ admin_balance แบบเดิม)
 */
class AdminLogController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_admin/_log/grid';

    public function index(): View
    {
        return view('reports.log.index');
    }

    /** POST /_admin/_log/grid — เดิมตรวจ getPermission('admin_balance') แล้ว exit() (ตอบว่าง) */
    public function grid(Request $request): Response
    {
        if (! perm('admin_balance')) {
            return response('');
        }
        $ts = explode('|', $this->in($request, 'txtsearch'));
        $tsPhp = explode('|', $this->php($request, 'txtsearch'));
        $q = DB::table('log')->leftJoin('user', 'user.id', '=', 'log.user')->where('log.is_delete', '0');
        if ($ts[0] !== '') {
            $like = '%'.addcslashes(str_replace(Rows::prefix('balance'), '', $ts[0]), '%_\\').'%';
            $q->where(fn ($w) => $w->where('log.cmd', 'like', $like)->orWhere('log.detail', 'like', $like)->orWhere('user.code', 'like', $like));
        }
        $total = (clone $q)->count();
        $page = $this->php($request, 'page');
        $offset = Rows::offset($page);
        $items = $offset === null ? collect() : Rows::strAll($q->orderByDesc('log.id')->offset($offset)->limit(10)
            ->get(['log.*', 'user.code', 'user.id as user_id']));
        $types = Rows::strAll(DB::table('balance_type')->orderBy('id')->get(['id', 'name', 'is_delete']));

        return response(view('reports.log.grid', [
            'ajaxpage' => self::AJAX,
            'items' => $items,
            'geo' => $this->geo($items->pluck('ip')->all()),
            // เดิม value = str_replace("''", "'", ค่าที่ ' ถูกแปลงเป็น '') = ค่าดิบที่ตัด \ ออก
            'search' => str_replace("''", "'", $tsPhp[0]),
            'status' => $tsPhp[1] ?? null,
            'types' => $types,
            'page' => $page,
            'total' => $total,
            'txtsearch' => str_replace("''", "'", $this->php($request, 'txtsearch')),
        ])->render());
    }

    /**
     * เดิมเรียก http://ip-api.com/json/<ip> ทุกแถว (บริการภายนอก, ไม่มี timeout) — ห้ามเรียกจริงใน dev
     * ปิด (LEGACY_EXTERNAL_ENABLED=false) = ไม่เรียก ได้ค่าว่างเหมือนตอนเรียกไม่สำเร็จ; เปิด = timeout 2 วินาที + cache 1 วัน
     *
     * @return array<string, array{country: ?string, city: ?string, isp: ?string}>
     */
    private function geo(array $ips): array
    {
        $out = [];
        foreach (array_unique(array_map(fn ($ip) => explode(',', (string) $ip)[0], $ips)) as $ip) {
            $out[$ip] = ['country' => null, 'city' => null, 'isp' => null];
            if (! config('legacy.external.enabled') || filter_var($ip, FILTER_VALIDATE_IP) === false) {
                continue;
            }
            $out[$ip] = Cache::remember('reports.ipapi.'.$ip, 86400, function () use ($ip) {
                try {
                    $s = Http::timeout(2)->get('http://ip-api.com/json/'.$ip)->json();
                } catch (\Throwable) {
                    $s = null;
                }

                return ['country' => $s['country'] ?? null, 'city' => $s['city'] ?? null, 'isp' => $s['isp'] ?? null];
            });
        }

        return $out;
    }
}
