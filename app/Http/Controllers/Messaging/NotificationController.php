<?php

namespace App\Http\Controllers\Messaging;

use App\Domain\Messaging\NotifyCounts;
use App\Http\Controllers\Controller;
use App\Support\Legacy\Legacy;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * แถบแจ้งเตือนด้านบน: notifications.php (ตัวเลขรวมให้ JS eval) และ notification.php (HTML ของแถบ)
 *
 * เดิม notification.php ยิง COUNT ทีละช่อง (PACK สูงสุด ~18 query + getPrefix อีกช่องละ 1 query)
 * ที่นี่: COUNT ทุกช่องที่มีสิทธิ์รวมเป็น 1 query, prefix ทั้งหมด 1 query
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

    /** POST /notification — notification.php ของ PACK (= SR ยกเว้น: ช่องแรกใช้สิทธิ์ admin_withdrawpoint แบบ jl, ไม่มีช่อง admin/check และ nobilling) */
    public function bar(): Response
    {
        $uid = (string) Legacy::userId();
        $href = Legacy::href();

        $status = fn (string $table, string $st, string $col = 'status') => fn ($s) => $s->from($table)
            ->selectRaw('COUNT(id)')->where('is_delete', '0')->where($col, $st);
        $send = fn (bool $self) => function ($s) use ($self) {
            $s->from('bill_payment')->selectRaw('COUNT(id)')->where('is_delete', '0')->where('status', '2');
            // SR: ประเภทส่ง 43/53 = รับเองที่โกดัง แยกไปช่อง admin/sendself
            $self ? $s->whereIn('shipping_type', [43, 53]) : $s->where('shipping_type', '!=', 43)->where('shipping_type', '!=', 53);
        };

        // [สิทธิ์, subquery COUNT] ตามลำดับบล็อกเดิม (PACK)
        $blocks = [
            // PACK: goodsconfirm ใช้สิทธิ์ admin_withdrawpoint (แบบ jl — SR ใช้ admin_goodsconfirm)
            'a0' => ['admin_withdrawpoint', $status('goodsconfirm', '1')],
            'a1' => ['admin_user', $status('user', '0', 'is_approved')],
            'a2' => ['admin_order', $status('bill_product', '0')],
            'a3' => ['admin_order', $status('bill_product', '2')],
            'a4' => ['admin_refill', $status('refill', '1')],
            'a5' => ['admin_withdrawpoint', $status('point', '1')],
            'a6' => ['admin_withdrawcash', $status('balance_withdraw', '1')],
            'a7' => ['admin_exchange', $status('balance_withdraw_cn', '1')],
            'a8' => ['admin_payment', $status('bill_payment', '1')],
            'a9' => ['admin_transfer', $status('bill_transfer', '1')],
            'a10' => ['admin_alipay', $status('bill_alipay', '1')],
            'a11' => ['admin_lost_package', $status('lost_package', '1')],
            'a12' => ['admin_taobao', $status('taobao_detail', '1')],
            'a13' => ['admin_shop_cashback', $status('shop_cashback_detail', '1')],
            'a14' => ['admin_export', $send(false)],
            'a15' => ['admin_export', $send(true)],
            // PACK ไม่มีช่อง admin/check (ตาราง tracking_check ไม่มีใน PACK) และไม่มีช่อง admin/nobilling ของ SR
            'c0' => ['payment', fn ($s) => $s->from('bill_payment')->selectRaw('COUNT(id)')
                ->where('is_delete', '0')->where('status', '0')->where('user', $uid)],
            'c1' => ['order', fn ($s) => $s->from('bill_product')->selectRaw('COUNT(id)')
                ->where('is_delete', '0')->where('status', '1')->where('user', $uid)],
            // SR: นับเฉพาะพัสดุที่เข้าล็อตแล้ว (lot_id != 0)
            't' => ['payment', fn ($s) => $s->from('tracking')->selectRaw('COUNT(id)')
                ->where('lot_id', '!=', 0)->where('date_thai', '!=', '0000-00-00 00:00:00')->where('is_delete', '0')
                ->where('user', $uid)->where('payment_id', '0')],
        ];
        $allowed = array_filter($blocks, fn ($b) => perm($b[0]));
        $counts = $this->counts($allowed);
        $p = $this->prefixes();
        $pre = fn (string $m) => str_replace('-', '', (string) ($p[$m] ?? ''));
        $badge = fn ($n) => ' <span class="badge badge-xs badge-danger">'.$n.'</span>';

        $html = '';
        $li = function (string $key, callable $build) use (&$html, $counts, $allowed) {
            if (! array_key_exists($key, $allowed)) {
                return;
            }
            $n = $counts[$key] ?? 0;
            if ($n !== 0) {
                $html .= $build($n);
            }
        };

        // PACK: สิทธิ์ admin_withdrawpoint (แบบ jl; SR = admin_goodsconfirm), ไม่มีเงื่อนไขมือถือ
        $li('a0', fn ($n) => '<li><a href="'.$href.'admin/goodsconfirm/status/1" >
	            <i class="text-danger fa fa-close"></i> <span class="text-danger triggermenu">'.$pre('goodsconfirm').'</span>'.$badge($n).'
	        </a></li>');
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
            'a4' => ['admin/refill/status/1', 'text-danger  ti-money', 'refill', ' '],
            'a5' => ['admin/withdrawpoint/status/1', 'text-danger  ti-money', 'withdrawpoint', ' '],
            'a6' => ['admin/withdrawcash/status/1', 'text-danger  ti-money', 'withdrawcash', ' '],
            'a7' => ['admin/exchange/status/1', 'text-danger  ti-money', 'exchange', ' '],
            'a8' => ['admin/payment/status/1', 'text-danger  ti-money', 'payment', ''],
            'a9' => ['admin/transfer/status/1', 'text-danger  fa fa-cny', 'transfer', ' '],
            'a10' => ['admin/alipay/status/1', 'text-danger fa fa-cny ', 'alipay', ' '],
            'a11' => ['admin/lost_package/status/1', 'text-danger fa  fa-cube', 'lost_package', ' '],
            'a12' => ['admin/taobao/status/1', 'text-danger fa fa-list-ol', 'taobao', ' '],
            'a13' => ['admin/shop_cashback/status/1', 'text-danger fa fa-undo', 'shop_cashback', ' '],
        ];
        foreach ($simple as $key => [$url, $iconClass, $mod, $gap]) {
            $li($key, fn ($n) => '<li><a href="'.$href.$url.'" >
	            <i class="'.$iconClass.'"></i>'.$gap.'<span class="text-danger triggermenu">'.$pre($mod).'</span>'.$badge($n).'
	        </a></li>');
        }
        $li('a14', fn ($n) => '<li><a href="'.$href.'admin/send" >
	            <i class="text-danger fa fa-send"></i><span class="text-danger triggermenu"></span>'.$badge($n).'
	        </a></li>');
        $li('a15', fn ($n) => '<li><a href="'.$href.'admin/sendself" >
	            <i class="text-danger fa fa-send"></i> '.res('pick_alone').'<span class="text-danger triggermenu"></span>'.$badge($n).'
	        </a></li>');
        $li('c0', fn ($n) => '<li><a href="'.$href.'export" >
			<i class=" ti-money"></i><span class=" triggermenu">'.$pre('payment').'</span>'.$badge($n).'
		</a></li>');
        $li('c1', fn ($n) => '<li><a href="'.$href.'order/status/1" >
	            <i class=" icon-basket-loaded"></i>'.$badge($n).'
	        </a></li>');
        $li('t', fn ($n) => '<li><a href="'.$href.'payment/create" >
	            <i class="  icon-layers"></i>'.$badge($n).'
	        </a></li>');

        // SR/PACK: กระดิ่งประกาศล่าสุด (notice.is_show = 1 แถวเดียว) — ข้อความเป็น HTML ของแอดมิน (strip_tags แล้ว แบบเดิม)
        $notice = DB::table('notice')->where('is_show', 1)->orderByDesc('create_date')->first(['create_date', 'text']);

        return response(view('messaging.partials.notification', [
            'html' => $html,
            'order' => perm('order'),
            'notice' => $notice,
            'langCookie' => Legacy::rawCookie('lang'),
            'username' => (string) (Legacy::user()->username ?? ''),
        ])->render());
    }

    /** COUNT ของทุกช่องที่มีสิทธิ์ใน query เดียว */
    private function counts(array $blocks): array
    {
        if ($blocks === []) {
            return [];
        }
        $q = DB::query();
        foreach ($blocks as $key => [, $sub]) {
            $q->selectSub($sub, $key);
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
