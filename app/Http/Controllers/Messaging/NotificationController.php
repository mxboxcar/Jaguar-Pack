<?php

namespace App\Http\Controllers\Messaging;

use App\Domain\Messaging\NotifyCounts;
use App\Http\Controllers\Controller;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * แถบแจ้งเตือนด้านบน: notifications.php (ตัวเลขรวมให้ JS eval) และ notification.php (HTML ของแถบ)
 *
 * เดิม notification.php ยิง COUNT ทีละช่อง (สูงสุด ~17 query + getPrefix อีกช่องละ 1 query)
 * ที่นี่: COUNT ทุกช่องที่มีสิทธิ์รวมเป็น 1 query, prefix ทั้งหมด 1 query, nobilling นับด้วย COUNT แทนการดึงทุกแถว
 */
class NotificationController extends Controller
{
    /** [สิทธิ์, index ใน notify_counts] ตามลำดับเดิม */
    private const SUM = [
        ['admin_user', 0], ['admin_order', 1], ['admin_order', 2], ['admin_refill', 3], ['admin_payment', 4],
        ['admin_transfer', 5], ['admin_alipay', 6], ['admin_lost_package', 7], ['admin_taobao', 8],
        ['admin_withdrawpoint', 9], ['admin_shop_cashback', 10], ['admin_export', 11],
    ];

    /** POST /notifications — ผลรวมตัวเลขที่ผู้ใช้มีสิทธิ์เห็น ('' ถ้าไม่มีสิทธิ์สักช่อง = $html ไม่ถูกกำหนด) */
    public function count(): Response
    {
        $sum = null;
        $counts = null;
        foreach (self::SUM as [$perm, $i]) {
            if (perm($perm)) {
                $counts ??= NotifyCounts::get();
                if ($counts[$i] !== 0) {
                    $sum = (int) $sum + $counts[$i];
                }
            }
        }

        return response($sum === null ? '' : (string) $sum);
    }

    /** POST /notification */
    public function bar(): Response
    {
        $uid = (string) Legacy::userId();
        $href = Legacy::href();
        $mobile = Legacy::isMobile();

        // [สิทธิ์, ตาราง, เงื่อนไข] ตามลำดับบล็อกเดิม
        $blocks = [
            ['admin_withdrawpoint', 'goodsconfirm', [['status', '1']]],
            ['admin_user', 'user', [['is_approved', '0']]],
            ['admin_order', 'bill_product', [['status', '0']]],
            ['admin_order', 'bill_product', [['status', '2']]],
            ['admin_refill', 'refill', [['status', '1']]],
            ['admin_withdrawpoint', 'point', [['status', '1']]],
            ['admin_withdrawcash', 'balance_withdraw', [['status', '1']]],
            ['admin_exchange', 'balance_withdraw_cn', [['status', '1']]],
            ['admin_payment', 'bill_payment', [['status', '1']]],
            ['admin_transfer', 'bill_transfer', [['status', '1']]],
            ['admin_alipay', 'bill_alipay', [['status', '1']]],
            ['admin_lost_package', 'lost_package', [['status', '1']]],
            ['admin_taobao', 'taobao_detail', [['status', '1']]],
            ['admin_shop_cashback', 'shop_cashback_detail', [['status', '1']]],
            ['admin_export', 'bill_payment', [['status', '2']]],
        ];
        $customer = [
            ['payment', 'bill_payment', [['status', '0'], ['user', $uid]]],
            ['order', 'bill_product', [['status', '1'], ['user', $uid]]],
        ];

        $allowed = [];
        foreach ($blocks as $i => $b) {
            if (perm($b[0])) {
                $allowed['a'.$i] = $b;
            }
        }
        foreach ($customer as $i => $b) {
            if (perm($b[0])) {
                $allowed['c'.$i] = $b;
            }
        }
        $payPerm = perm('payment');
        $counts = $this->counts($allowed, $payPerm ? $uid : null);
        $p = $this->prefixes();
        $pre = fn (string $m) => str_replace('-', '', (string) ($p[$m] ?? ''));
        $badge = fn ($n) => ' <span class="badge badge-xs badge-danger">'.$n.'</span>';
        $c = fn (string $k) => $counts[$k] ?? null;

        $html = '';
        $last = null; // $data ของบล็อกล่าสุดที่ทำงาน (nobilling เดิมเช็คค่านี้แทนจำนวนของตัวเอง)
        $li = function (string $key, callable $build) use (&$html, &$last, $c, $allowed) {
            if (! array_key_exists($key, $allowed)) {
                return;
            }
            $last = $c($key);
            if ((int) $last !== 0) {
                $html .= $build((int) $last);
            }
        };

        $li('a0', fn ($n) => '<li><a href="'.$href.'admin/goodsconfirm/status/1" ><i class="text-danger fa fa-close"></i>'
            .($mobile ? '<span class="text-danger triggermenu">'.$pre('goodsconfirm').'</span>' : '')
            .$badge($n).'</a></li>');
        $li('a1', fn ($n) => '<li><a href="'.$href.'admin/user/status/0" >
	            <i class="text-danger fa fa-users"></i> <span class="text-danger triggermenu">'.$pre('user').'-1</span>'.$badge($n).'
	        </a></li>');
        $li('a2', fn ($n) => '<li><a href="'.$href.'admin/order/status/0" >
	            <i class="text-danger icon-basket-loaded"></i> <span class="text-danger triggermenu">'.$pre('order').'-1</span>'.$badge($n).'
	        </a></li>');
        $li('a3', fn ($n) => '<li><a href="'.$href.'admin/order/status/2" >
	            <i class="text-danger fa fa-dollar"></i> <span class="text-danger triggermenu">'.$pre('order').'</span>-2<span class="badge badge-xs badge-danger">'.$n.'</span>
	        </a></li>');
        $simple = [
            'a4' => ['admin/refill/status/1', 'ti-money', 'refill', ' '],
            'a5' => ['admin/withdrawpoint/status/1', 'ti-money', 'withdrawpoint', ' '],
            'a6' => ['admin/withdrawcash/status/1', 'ti-money', 'withdrawcash', ' '],
            'a7' => ['admin/exchange/status/1', 'ti-money', 'exchange', ' '],
            'a8' => ['admin/payment/status/1', 'ti-money', 'payment', ''],
            'a9' => ['admin/transfer/status/1', 'fa fa-cny', 'transfer', ' '],
            'a10' => ['admin/alipay/status/1', 'fa fa-cny ', 'alipay', ' '],
            'a11' => ['admin/lost_package/status/1', 'fa  fa-cube', 'lost_package', ' '],
            'a12' => ['admin/taobao/status/1', 'fa fa-list-ol', 'taobao', ' '],
            'a13' => ['admin/shop_cashback/status/1', 'fa fa-undo', 'shop_cashback', ' '],
        ];
        foreach ($simple as $key => [$url, $icon, $mod, $gap]) {
            $iconClass = in_array($key, ['a4', 'a5', 'a6', 'a7', 'a8', 'a9'], true) ? 'text-danger  '.$icon : 'text-danger '.$icon;
            $li($key, fn ($n) => '<li><a href="'.$href.$url.'" >
	            <i class="'.$iconClass.'"></i>'.$gap.'<span class="text-danger triggermenu">'.$pre($mod).'</span>'.$badge($n).'
	        </a></li>');
        }
        $li('a14', fn ($n) => '<li><a href="'.$href.'admin/send" >
	            <i class="text-danger fa fa-send"></i><span class="text-danger triggermenu"></span>'.$badge($n).'
	        </a></li>');

        if (perm('admin_nobilling') && $last !== null && (int) $last !== 0) {
            $rows = DB::table('tracking')
                ->leftJoin('user', 'tracking.user', '=', 'user.id')
                ->leftJoin('lot', 'tracking.lot_id', '=', 'lot.id')
                ->where('tracking.date_thai', '!=', '0000-00-00 00:00:00')
                ->whereRaw('`tracking`.date_thai + INTERVAL 15 DAY <= NOW()')
                ->where('tracking.date_finish', '0000-00-00 00:00:00')
                ->where('payment_id', '0')->where('tracking.is_delete', '0')
                ->where('lot.id', '!=', '0')
                ->count();
            $html .= '<li><a href="'.$href.'admin/nobilling" >
	            <i class="text-danger fa fa-tags"></i> <span class="badge badge-xs badge-danger">'.$rows.'</span>
	        </a></li>';
        }

        $li('c0', fn ($n) => '<li><a href="'.$href.'export" >
			<i class=" ti-money"></i><span class=" triggermenu">'.$pre('payment').'</span>'.$badge($n).'
		</a></li>');
        $li('c1', fn ($n) => '<li><a href="'.$href.'order/status/1" >
	            <i class=" icon-basket-loaded"></i>'.$badge($n).'
	        </a></li>');
        if ($payPerm && (int) $counts['t'] !== 0) {
            $html .= '<li><a href="'.$href.'payment/create" >
	            <i class="  icon-layers"></i>'.$badge((int) $counts['t']).'
	        </a></li>';
        }

        return response(view('messaging.partials.notification', [
            'html' => $html,
            'order' => perm('order'),
            'langCookie' => Legacy::rawCookie('lang'),
            'username' => (string) (Legacy::user()->username ?? ''),
        ])->render());
    }

    /** COUNT ของทุกช่องที่มีสิทธิ์ใน query เดียว (+ tracking ที่รอสร้างบิลของลูกค้า ถ้า $trackingUser) */
    private function counts(array $blocks, ?string $trackingUser): array
    {
        if ($blocks === [] && $trackingUser === null) {
            return [];
        }
        $q = DB::query();
        foreach ($blocks as $key => [, $table, $where]) {
            $q->selectSub(function ($s) use ($table, $where) {
                $s->from($table)->selectRaw('COUNT(id)')->where('is_delete', '0');
                foreach ($where as [$col, $val]) {
                    $s->where($col, $val);
                }
            }, $key);
        }
        if ($trackingUser !== null) {
            $q->selectSub(fn ($s) => $s->from('tracking')->selectRaw('COUNT(id)')
                ->where('date_thai', '!=', '0000-00-00 00:00:00')->where('is_delete', '0')
                ->where('user', $trackingUser)->where('payment_id', '0'), 't');
        }

        return array_map(fn ($v) => (int) $v, (array) $q->first());
    }

    /** prefix ทั้งหมด (getPrefix เดิม = แถวแรกของชื่อนั้น) */
    private function prefixes(): array
    {
        $out = [];
        foreach (DB::table('prefix')->orderBy('id')->get(['name', 'prefix']) as $r) {
            $out[$r->name] ??= $r->prefix;
        }

        return $out;
    }
}
