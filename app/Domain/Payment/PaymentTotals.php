<?php

namespace App\Domain\Payment;

use App\Support\Legacy\Format;
use App\Support\Legacy\Lookup;
use Illuminate\Support\Collection;

/**
 * ยอดรวมของหน้า/ไฟล์ payment — เลียนแบบลำดับการบวกและค่าเริ่มต้น (undefined = null) ของไฟล์เดิม
 * (ผลของ numberformat*() กับ null ต่างจาก 0 จึงต้องคง null ไว้เมื่อไฟล์เดิมไม่ได้กำหนดค่าเริ่มต้น)
 */
final class PaymentTotals
{
    private static function n(mixed $v): int|float
    {
        return Php74::num($v);
    }

    private static function plus(mixed $a, mixed $b): int|float
    {
        return self::n($a) + self::n($b);
    }

    /** ผลรวมความกว้าง/สูง/ยาว ของทุกแถว (ลูปแรกของ create_form/form เดิม — เริ่มจาก undefined) */
    private static function dims(Collection $items, array &$out): void
    {
        $out['width'] = $out['height'] = $out['long'] = null;
        foreach ($items as $i) {
            $out['width'] = self::plus($out['width'], $i->width ?? null);
            $out['height'] = self::plus($out['height'], $i->height ?? null);
            $out['long'] = self::plus($out['long'], $i->long ?? null);
        }
    }

    /**
     * ลูปรวมยอดแบบ form.php / create_form.php เดิม (ค่าเริ่มต้น 0)
     * service = service + qc + express + repack + else + craft
     *
     * @return array<string, int|float|null>
     */
    private static function loop(Collection $items, bool $headerOnly, bool $withPriceAmount): array
    {
        $o = ['amount' => 0, 'weight' => 0, 'cubic' => 0, 'service' => 0, 'kg' => 0, 'pcubic' => 0, 'pamount' => 0];
        foreach ($items as $i) {
            if ($headerOnly && Php74::neq($i->header ?? null, 0)) {
                continue;
            }
            $o['amount'] = self::plus($o['amount'], $i->amount ?? null);
            $o['weight'] = $o['weight'] + (self::n($i->weight ?? null) * self::n($i->amount ?? null));
            $o['cubic'] = $o['cubic'] + PaymentLookups::cubic($i);
            $o['service'] = PaymentLookups::add($i, ['price_qc', 'price_express', 'price_repack', 'price_else', 'price_craft'], $o['service']);
            $o['kg'] = self::plus($o['kg'], $i->price_kg ?? null);
            $o['pcubic'] = self::plus($o['pcubic'], $i->price_cubic ?? null);
            if ($withPriceAmount) {
                $o['pamount'] = self::plus($o['pamount'], $i->price_amount ?? null);
            }
        }
        self::dims($items, $o);

        return $o;
    }

    /** _payment/form.php, _admin/_payment/form.php: ทุกแถว รวม price_amount */
    public static function all(Collection $items): array
    {
        return self::loop($items, false, true);
    }

    /** _payment/create_form.php: ลูปที่สองรวมเฉพาะ header = 0, price_amount ถูก comment (= 0) */
    public static function headerOnly(Collection $items): array
    {
        return self::loop($items, true, false);
    }

    /** _admin/_payment/create_form.php: ลูปที่สองรวมทุกแถว (ไม่ได้ select header), price_amount ถูก comment */
    public static function adminCreate(Collection $items): array
    {
        return self::loop($items, false, false);
    }

    /**
     * load_create.php (ลูกค้า/แอดมิน): ตัวแปร $sum_* ไม่ได้กำหนดค่าเริ่มต้น (null) และรวมเฉพาะ header = 0
     *
     * @return array<string, int|float|null>
     */
    public static function selectable(Collection $items): array
    {
        $o = array_fill_keys(['amount', 'weight', 'cubic', 'kg', 'pcubic', 'width', 'height', 'long', 'service'], null);
        foreach ($items as $i) {
            if (Php74::neq($i->header ?? null, 0)) {
                continue;
            }
            $o['amount'] = self::plus($o['amount'], $i->amount);
            $o['weight'] = self::n($o['weight']) + (self::n($i->weight) * self::n($i->amount));
            $o['cubic'] = self::n($o['cubic']) + PaymentLookups::cubic($i);
            $o['kg'] = self::plus($o['kg'], $i->price_kg);
            $o['pcubic'] = self::plus($o['pcubic'], $i->price_cubic);
            $o['width'] = self::plus($o['width'], $i->width);
            $o['height'] = self::plus($o['height'], $i->height);
            $o['long'] = self::plus($o['long'], $i->long);
            $o['service'] = PaymentLookups::add($i, ['price_qc', 'price_express', 'price_repack', 'price_else', 'price_craft'], self::n($o['service']));
        }

        return $o;
    }

    /**
     * ค่าส่ง/ค่าฝาก/ค่าเปลี่ยนผู้ส่ง ของ save_submit_payment.php เดิม
     *
     * @param  object  $st  แถว shipping_type (ค่าแบบ string)
     * @param  array<string, mixed>|null  $stp  getShippingTypePrice(...)
     * @return array{storage: mixed, shipping: mixed, extened: mixed, sender_charge: mixed}
     */
    public static function fees(object $st, mixed $senderType, ?array $stp): array
    {
        $shipping = $st->price_thai_shipping ?? null;
        $charge = Lookup::getSetting('change_sender');
        $extened = $st->price_extened ?? null;
        $storage = Php74::eq($senderType, 1) ? self::plus($st->price_thai_storage ?? null, $charge) : ($st->price_thai_storage ?? null);
        if (Php74::eq($storage, 0)) {
            $storage = $stp['price_thai_storage'] ?? null;
        }
        if (Php74::eq($shipping, 0)) {
            $shipping = $stp['price_thai_shipping'] ?? null;
        }
        if (Php74::eq($extened, 0)) {
            $extened = $stp['price_extened'] ?? null;
        }

        return ['storage' => $storage, 'shipping' => $shipping, 'extened' => $extened, 'sender_charge' => $charge];
    }

    /**
     * VAT เดิม:
     *   total = nfs(storage) + nfs(shipping) + nfs(extened)
     *   vat_before = nfs(nfs(total * v) / 100); vat = nfs(total + vat_before)
     *   shipvat_before = nfs(nfs(<total หรือ vat> * 3) / 100)   ← ลูกค้า/สร้างบิล: total (ก่อน VAT), แอดมิน save_vat: vat
     *   shipvat = nfs(vat - shipvat_before)
     *
     * @return array{total: int|float, vat_before: string, vat: string, shipvat_before: string, shipvat: string}
     */
    public static function vat(mixed $storage, mixed $shipping, mixed $extened, mixed $v, bool $shipFromVat): array
    {
        $nfs = fn ($x) => Format::numberformatforsubmit($x);
        $total = self::n($nfs($storage)) + self::n($nfs($shipping)) + self::n($nfs($extened));
        $vatBefore = $nfs(self::n($nfs($total * self::n($v))) / 100);
        $vat = $nfs($total + self::n($vatBefore));
        $shipBefore = $nfs(self::n($nfs(($shipFromVat ? self::n($vat) : $total) * 3)) / 100);
        $shipvat = $nfs(self::n($vat) - self::n($shipBefore));

        return ['total' => $total, 'vat_before' => $vatBefore, 'vat' => $vat, 'shipvat_before' => $shipBefore, 'shipvat' => $shipvat];
    }

    /** total = nfs(storage) + nfs(price_shipping) + nfs(extened) + nfs(thai_shipping) (ไม่มี VAT) */
    public static function plain(mixed $storage, mixed $priceShipping, mixed $extened, mixed $thaiShipping): int|float
    {
        $nfs = fn ($x) => self::n(Format::numberformatforsubmit($x));

        return $nfs($storage) + $nfs($priceShipping) + $nfs($extened) + $nfs($thaiShipping);
    }

    /**
     * ยอดบิลของ _payment/save_submit_payment.php
     *
     * @param  array{storage: mixed, shipping: mixed, extened: mixed, sender_charge: mixed}  $fees
     * @return array<string, mixed>
     */
    public static function bill(array $fees, int|float $priceShipping, mixed $isVat): array
    {
        if (Php74::eq($isVat, 1)) {
            $v = Lookup::getSetting('vat');
            $t = self::vat($fees['storage'], $priceShipping, $fees['extened'], $v, false);

            return ['total' => $t['shipvat'], 'v' => $v, 'vat_before' => $t['vat_before'], 'vat' => $t['vat'],
                'shipvat_before' => $t['shipvat_before'], 'shipvat' => $t['shipvat'], 'shipv' => 3];
        }

        return ['total' => self::plain($fees['storage'], $priceShipping, $fees['extened'], $fees['shipping']),
            'v' => 0, 'vat_before' => 0, 'vat' => 0, 'shipvat_before' => 0, 'shipvat' => 0, 'shipv' => 0];
    }
}
