<?php

namespace App\Http\Controllers\Reports\Admin;

use App\Domain\Reports\BalanceReport;
use App\Domain\Reports\Php74;
use App\Domain\Reports\Rows;
use App\Domain\Reports\XlsxWriter;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Reports\Concerns\ReadsLegacyInput;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * แอดมิน: รายงาน (_admin/_report/*) — สิทธิ์ admin_report ทุกไฟล์
 * ไฟล์ที่เปิดตรง (ไม่มี layout) ตรวจสิทธิ์ใน controller เพื่อตอบแบบเดิม (alert ใน HTML เปล่า / ตอบว่าง)
 */
class AdminReportController extends Controller
{
    use ReadsLegacyInput;

    private const AJAX = '_admin/_report/grid';

    /** "<meta charset="utf-8">\n" ที่ else.php / else_preorder_working.php echo ก่อนตรวจสิทธิ์ */
    private const META = "<meta charset=\"utf-8\">\n";

    /** GET /admin/report — index.php (เดิมไม่ตรวจสิทธิ์เลย → ใส่ admin_report ที่ route) */
    public function index(): View
    {
        $types = DB::table('balance_type')->where('is_delete', '0')->orderBy('id')->get(['id', 'name']);

        return view('reports.report.index', ['types' => $types, 'year' => date('Y')]);
    }

    /** GET /admin/report/{id} — form.php */
    public function form(Request $request): View
    {
        $id = (int) $request->route('id');
        // เดิม "WHERE id = ".$id ไม่มี '' (id มาจาก RewriteRule [0-9]+ จึงเป็นตัวเลขเสมอ)
        $name = DB::table('balance_type')->where('id', $id)->where('is_delete', 0)->value('name');

        return view('reports.report.form', ['id' => $id, 'name' => $name]);
    }

    /** POST /_admin/_report/grid — เดิมไม่มีสิทธิ์ = exit() (ตอบว่าง) */
    public function grid(Request $request): Response
    {
        if (! perm('admin_report')) {
            return response('');
        }
        $ts = explode('[]', $this->in($request, 'txtsearch'));
        $tsPhp = explode('[]', $this->php($request, 'txtsearch'));
        $type = $ts[0];
        $d1 = $ts[1] ?? null;
        $d2 = $ts[2] ?? null;
        // เดิม if($txtSearch[1] && $txtSearch[2]) — ค่าว่าง/"0" = ไม่กรองวันที่
        $range = ($d1 !== null && $d1 !== '' && $d1 !== '0' && $d2 !== null && $d2 !== '' && $d2 !== '0')
            ? BalanceReport::range($d1, $d2) : null;

        $sum = BalanceReport::sum($type, $range);
        $q = BalanceReport::list($type, $range, Php74::eq($type, 5));
        $total = (clone $q)->count();
        $page = $this->php($request, 'page');
        $offset = Rows::offset($page);
        $items = $offset === null ? collect()
            : Rows::strAll($q->orderByDesc(BalanceReport::table($type).'.id')->offset($offset)->limit(10)->get());

        return response(view('reports.report.grid', [
            'ajaxpage' => self::AJAX,
            'type' => $type,
            'typePhp' => $tsPhp[0],
            'date1' => $tsPhp[1] ?? '',
            'date2' => $tsPhp[2] ?? '',
            'sum' => $sum,
            'items' => $items,
            'extras' => BalanceReport::extras($type, $items),
            'page' => $page,
            'total' => $total,
            'txtsearch' => $this->php($request, 'txtsearch'),
        ])->render());
    }

    /** GET /_admin/_report/file — file.php = require else.php */
    public function file(Request $request): Response
    {
        return $this->else($request);
    }

    /** GET /_admin/_report/else — "Excel" แบบตาราง HTML (export.xls) */
    public function else(Request $request): Response
    {
        if (! perm('admin_report')) {
            return response(self::META.Alerts::permissionPage('admin_report'));
        }
        $types = $this->get($request, 'type');
        $range = BalanceReport::range($this->get($request, 'start'), $this->get($request, 'end'));
        // เดิมไม่มี ORDER BY — ใช้ SQL รูปเดียวกัน (ไม่ใส่ ORDER BY) ให้ลำดับแถวตามแผน query เดิม
        $rows = Rows::strAll(BalanceReport::list($types, $range, false)->get());

        return $this->xls(view('reports.report.else', [
            'types' => $types,
            'rows' => $rows,
            'extras' => BalanceReport::extras($types, $rows),
        ])->render());
    }

    /** GET /_admin/_report/else_preorder_working — สรุปงานพรีออเดอร์ของพนักงาน (code) */
    public function elsePreorderWorking(Request $request): Response
    {
        if (! perm('admin_report')) {
            return response(self::META.Alerts::permissionPage('admin_report'));
        }
        $range = BalanceReport::range($this->get($request, 'start'), $this->get($request, 'end'));
        $code = trim($this->get($request, 'code'));
        $memId = DB::table('user')->where('code', $code)->orderBy('id')->value('id');
        // เดิม status_type == "0" → who_did_this_1, อื่นๆ → who_did_this_3; ไม่เจอผู้ใช้ = '' (MySQL แปลงเป็น 0)
        $n = $this->get($request, 'status_type') === '0' ? '1' : '3';
        $rows = Rows::strAll(DB::table('bill_product')
            ->where('who_did_this_'.$n, $memId === null ? '' : (string) $memId)
            ->whereBetween('who_did_this_'.$n.'_date', $range)
            ->get(['id', 'who_did_this_3_date']));
        $ids = $rows->pluck('id')->all();
        $shops = [];
        $links = [];
        if ($ids !== []) {
            // เดิมต่อแถว: SELECT id ... GROUP BY shop_name (นับแถว) และ SELECT count(id) — รวมเป็น 2 query
            foreach (DB::table('bill_product_detail')->whereIn('bill_product_id', $ids)->groupBy('bill_product_id', 'shop_name')
                ->get(['bill_product_id', DB::raw('COUNT(id) as c')]) as $r) {
                $k = (string) $r->bill_product_id;
                $shops[$k] = ($shops[$k] ?? 0) + 1;
                $links[$k] = ($links[$k] ?? 0) + (int) $r->c;
            }
        }

        return $this->xls(view('reports.report.else_preorder_working', [
            'rows' => $rows, 'shops' => $shops, 'links' => $links, 'prefix' => Rows::prefix('order'),
        ])->render());
    }

    /** GET /_admin/_report/refill_5 (bill_transfer), refill_6 (bill_alipay), refill_7 (taobao), shipping (bill_payment) — Excel2007 */
    public function refill5(Request $request): Response
    {
        return $this->excel($request, 5);
    }

    public function refill6(Request $request): Response
    {
        return $this->excel($request, 6);
    }

    public function refill7(Request $request): Response
    {
        return $this->excel($request, 7);
    }

    public function shipping(Request $request): Response
    {
        return $this->excel($request, 3);
    }

    /** GET /_admin/_report/cashmove?m=YYYY-MM — ยอดเงินคงเหลือรวมทุกวันของเดือน */
    public function cashmove(Request $request): Response
    {
        if (! perm('admin_report')) {
            return response('');
        }
        $m = $this->get($request, 'm');
        // ลำดับวันที่แบบลูปเดิม (strtotime('YYYY-02-31') = 3 มี.ค. → เดือนสั้นเกินท้ายเดือนเหมือนเดิม)
        $dates = [];
        $start = $m.'-01';
        $end = $m.'-31';
        // strtotime() ผิดรูปแบบ = false; PHP 7.4 เทียบ int กับ bool แบบ bool (false <= false = จริง → แถว m-01 แล้วหยุดที่ 1970-01-02)
        $le = fn ($a, $b) => (is_bool($a) || is_bool($b)) ? ((bool) $a <= (bool) $b) : ($a <= $b);
        $guard = 0;
        while ($le(strtotime($start), strtotime($end)) && $guard++ < 400) {
            $dates[] = $start;
            $start = date('Y-m-d', (int) strtotime('+1 day', (int) strtotime($start)));
        }
        // เดิม 1 query ต่อวัน → รวมเป็น SUM(CASE ...) ครั้งเดียว (เงื่อนไขเดียวกันทุกวัน: create_date <= 'วัน 23:59:59')
        $sums = [];
        if ($dates !== []) {
            $sel = [];
            $bind = [];
            foreach ($dates as $i => $d) {
                $sel[] = 'SUM(CASE WHEN `create_date` <= ? THEN amount END) as c'.$i;
                $bind[] = $d.' 23:59:59';
            }
            $row = (array) DB::selectOne('SELECT '.implode(', ', $sel).' FROM balance WHERE is_delete = 0', $bind);
            foreach ($dates as $i => $d) {
                $sums[$i] = Rows::s($row['c'.$i] ?? null);
            }
        }

        return response(view('reports.report.cashmove', ['dates' => $dates, 'sums' => $sums])->render());
    }

    /** GET /_admin/_report/current_cash — ยอดเงินคงเหลือลูกค้าทุกคน */
    public function currentCash(): Response
    {
        if (! perm('admin_report')) {
            return response(Alerts::permissionPage('admin_report'));
        }

        return response(view('reports.report.current_cash', ['rows' => $this->cashRows('balance')])->render());
    }

    /** GET /_admin/_report/current_cash_cn — ยอดเงินหยวนคงเหลือ */
    public function currentCashCn(): Response
    {
        if (! perm('admin_report')) {
            return response(Alerts::permissionPage('admin_report'));
        }
        $html = '<meta charset="utf-8"><table border="1">';
        foreach ($this->cashRows('balance_cn') as $r) {
            $html .= '<tr><td>'.e((string) $r->code).'</td><td>'.e(Rows::s($r->count)).'</td></tr>';
        }

        return response($html.'</table>');
    }

    /** SELECT user.code, cash.count ... where code != '' AND count != '' order by count desc */
    private function cashRows(string $table): iterable
    {
        $cash = DB::table($table)->where('is_delete', '0')->groupBy('user')->select(['user', DB::raw('SUM(amount) as count')]);

        return DB::table('user')->leftJoinSub($cash, 'cash', 'user.id', '=', 'cash.user')
            ->where('code', '!=', '')->where('count', '!=', '')
            ->orderByDesc('count')
            ->get(['user.code', 'cash.count']);
    }

    private function xls(string $html): Response
    {
        return response(self::META.$html)
            ->header('Content-type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename=export.xls');
    }

    /** refill_5/6/7.php, shipping.php (PHPExcel → XlsxWriter) */
    private function excel(Request $request, int $kind): Response
    {
        if (! perm('admin_report')) {
            return response(Alerts::permissionPage('admin_report'));
        }
        $types = $this->get($request, 'type');
        $range = BalanceReport::range($this->get($request, 'start'), $this->get($request, 'end'));
        // เดิม SELECT แถว balance ตาม type ที่ส่งมา แต่ข้อมูลประกอบตายตัวตามไฟล์ (refill_5 = bill_transfer ฯลฯ)
        $rows = Rows::strAll(DB::table('balance')
            ->leftJoin('balance_type', 'balance.type', '=', 'balance_type.id')
            ->leftJoin('prefix', 'balance.prefix', '=', 'prefix.id')
            ->leftJoin('user', 'user.id', '=', 'balance.user')
            ->where('balance.is_delete', '0')->where('balance.type', $types)
            ->whereBetween('balance.create_date', $range)
            ->get(['balance.*', 'balance_type.name', 'prefix.link_admin as link', 'user.code']));
        $extras = BalanceReport::extras($kind, $rows);
        $x = new XlsxWriter('Report');
        $heads = match ($kind) {
            5, 6 => ['หมายเลขรายการ', 'วันที่ทำรายการ', 'รหัสลูกค้า', 'รวม', 'เรท', 'รายละเอียด', 'จำนวน'],
            7 => ['หมายเลขรายการ', 'วันที่ทำรายการ', 'รหัสลูกค้า', 'ค่าบริการ', 'รวม', 'เรท', 'รายละเอียด', 'จำนวน'],
            default => ['หมายเลขรายการ', 'วันที่ทำรายการ', 'รหัสลูกค้า', 'ค่าขนส่งสินค้า', 'ค่าบริการโกดังไทย', 'ค่าจัดส่งในไทย', 'ประเภทการจัดส่ง', 'รายละเอียด', 'จำนวน'],
        };
        $widths = match ($kind) {
            5, 6 => [15, 27, 15, 15, 15, 25, 15],
            7 => [15, 27, 15, 15, 15, 15, 15, 25],
            default => [15, 27, 15, 15, 15, 15, 25, 25, 15],
        };
        foreach ($widths as $i => $w) {
            $x->width(chr(65 + $i), $w);
        }
        foreach ($heads as $i => $h) {
            $x->set(chr(65 + $i), 1, $h, XlsxWriter::HEAD);
        }
        $r = 2;
        foreach ($rows as $row) {
            $e = fn (string $k) => BalanceReport::ex($extras, $row->prefix_ref_id, $k);
            $exp = explode(' ', (string) $row->create_date);
            $ymd = explode('-', $exp[0]);
            $date = ($ymd[2] ?? '').'/'.($ymd[1] ?? '').'/'.$ymd[0].' '.($exp[1] ?? '');
            $x->set('A', $r, Rows::prefix('balance').$row->id, XlsxWriter::CENTER);
            $x->set('B', $r, $date, XlsxWriter::CENTER);
            $x->set('C', $r, $row->code, XlsxWriter::CENTER);
            if ($kind === 5 || $kind === 6) {
                $x->set('D', $r, $e('total'), XlsxWriter::RIGHT_NUM);
                $x->set('E', $r, $e('rate'), XlsxWriter::RIGHT_NUM);
                $x->set('F', $r, $row->detail);
                $x->set('G', $r, $row->amount, XlsxWriter::RIGHT_NUM);
            } elseif ($kind === 7) {
                $x->set('D', $r, $e('charge'), XlsxWriter::RIGHT_NUM);
                $x->set('E', $r, $e('sum'), XlsxWriter::RIGHT_NUM);
                $x->set('F', $r, $e('rate'), XlsxWriter::RIGHT_NUM);
                $x->set('G', $r, $row->detail);
                $x->set('H', $r, $row->amount, XlsxWriter::RIGHT_NUM);
            } else {
                $x->set('D', $r, $e('price_shipping'), XlsxWriter::RIGHT_NUM);
                $x->set('E', $r, $e('price_thai_storage'), XlsxWriter::RIGHT_NUM);
                $x->set('F', $r, $e('price_thai_shipping'), XlsxWriter::RIGHT_NUM);
                $x->set('G', $r, $e('ship_name'));
                $x->set('H', $r, $row->detail, XlsxWriter::RIGHT);
                $x->set('I', $r, $row->amount, XlsxWriter::RIGHT_NUM);
            }
            $r++;
        }

        return response($x->toString())
            ->header('Content-type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="export.xls"');
    }
}
