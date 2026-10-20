<?php

namespace App\Domain\Warehouse;

use App\Domain\Warehouse\WarehouseLookups as W;
use App\Support\Legacy\Format;
use App\Support\Legacy\Legacy;
use Illuminate\Support\Facades\DB;

/**
 * _admin/_warehouse/save_lot_detail.php เดิม: เลือกล็อตเข้าใบรับเข้า แล้วจัดชั้นวางให้ tracking อัตโนมัติ
 *
 * ลำดับและเงื่อนไขเหมือนเดิมทุกขั้น (รวม bug ที่มีผลต่อข้อมูล) ยกเว้นที่ระบุว่า "แก้":
 *  1. ชั้นวางทั้งหมด (is_delete = 0, name != 'zzz') เรียงตาม floors
 *  2. tracking ของล็อตที่เลือก ("a.ct IS NULL" เดิมเทียบ id ของ warehouse_section_detail แถวแรกกับ tracking.id
 *     = ตัดออกได้อย่างมาก 1 แถว — แก้แล้ว: ข้าม tracking ที่มีแถวชั้นวางอยู่แล้ว) รวม cubic/น้ำหนัก ต่อ "user-user_agent" (ไม่มีคอลัมน์ user_agent → "user-")
 *  3. tracking ของผู้ใช้ code 'nobody' (SR define('nobody', 'nobody'); ไม่มีผู้ใช้นี้ → ตรงกับ user NULL) → ชั้น 2
 *  3b. SR: ลูกค้าที่กำหนดชั้นตายตัวในโค้ด (FIXED_SECTIONS: user id → section id) → ชั้นนั้นเสมอ ไม่เข้าการจัดอัตโนมัติ
 *  4. ต่อลูกค้า: cubic >= 0.7 หรือ น้ำหนัก >= 50 → ชั้น 1, ไม่งั้นหาชั้นแรกที่ที่ว่างพอ (กติกา 10/17 รหัสต่อชั้น
 *     — SR นับ "รหัส" เป็นคู่ user + user_agent, ชั้นที่ชื่อลงท้าย 3 ไม่รับของ >= 15 kg — SR ไม่มีกติกาชั้นลงท้าย 4 ของ jl)
 *     แถวที่จัดในขั้นนี้ SR ใส่ user_agent = tracking.user_agent (ไม่มีคอลัมน์นี้ใน tracking → '' → 0)
 *  5. แสดงรายการของล็อตที่ยังไม่ถึงไทย, บันทึก warehouse_detail ของล็อตที่เลือก
 *
 * ที่เปลี่ยน:
 *  - เดิม getSpace()/getCountCode() = 2 query ต่อ (ลูกค้า × ชั้น) → โหลดครั้งเดียว แล้วอัปเดตในหน่วยความจำทุกครั้งที่ INSERT (ผลเท่ากัน)
 *  - prints (GET) เดิมต่อลง IN (...) ตรงๆ (SQL injection) → รับเฉพาะจำนวนเต็ม/null; ค่าอื่น = เดิม SQL error ทุก query ที่มี IN
 *  - แก้: "UPDATE warehouse_detail SET is_delete = 1 WHERE id = <warehouse id>" เดิมลบแถวของใบรับเข้าอื่นที่ id บังเอิญตรงกัน
 *    → ล้างแถวของใบรับเข้านี้ (warehouse_id = id) ก่อนบันทึกชุดล็อตที่เลือก
 *  - แก้ (E2E 2026-10-18): กดคำนวณซ้ำ = แถวชั้นวาง/ล็อตซ้ำ → ข้าม tracking ที่จัดชั้นแล้ว + route ใช้ legacy.once:lock
 *  - แก้ (SR): เดิม SR ต่อผล query "tracking ของลูกค้าใน REPEAT_USERS" ท้ายรายการ tracking ทั้งหมด (ไม่ล้างก่อน) แล้ววนจัดชั้น
 *    → tracking ของลูกค้าที่อยู่ทั้งใน REPEAT_USERS และ FIXED_SECTIONS ถูก INSERT ชั้นวาง 2 แถว (ซ้ำ) → ใหม่ INSERT แถวเดียวต่อ tracking
 *  - ทั้งหมดอยู่ใน transaction เดียว
 */
final class ShelfAllocator
{
    private const NOBODY_SECTION = 2;

    private const DEFAULT_SECTION = 1;

    private const MAX_CODE = 17;

    /**
     * SR: ลูกค้าที่จัดชั้นวางตายตัว (user id → section id) ตามลำดับ if ในไฟล์เดิม (บรรทัด 110–432)
     * ที่ comment ไว้ในไฟล์เดิม (840→44, 751→53, 1824→47, 1827→52) ไม่ใส่
     */
    public const FIXED_SECTIONS = [
        3621 => 59, 619 => 31, 30 => 32, 4 => 33, 891 => 34, 2802 => 30, 960 => 30, 784 => 30, 903 => 38,
        2596 => 39, 2033 => 40, 1473 => 41, 1362 => 41, 648 => 42, 792 => 42, 745 => 43, 731 => 45, 2005 => 46,
        1331 => 48, 747 => 49, 721 => 50, 75 => 51, 2120 => 54, 692 => 55, 1 => 56, 2 => 56, 2110 => 56,
        682 => 57, 665 => 58, 2392 => 56, 2371 => 56, 749 => 61, 3893 => 60,
    ];

    /** SR: user ใน "AND `user` IN (...)" ของ query tracking ที่ 2 (ไฟล์เดิมบรรทัด 90–92) */
    private const REPEAT_USERS = [
        753, 619, 30, 4, 891, 2802, 960, 784, 903, 2596, 2033, 1473, 648, 745, 840, 751, 731, 2005, 1824, 1331,
        747, 721, 75, 827, 1827, 2120, 692, 1, 2, 682, 665, 2392, 3621,
    ];

    /** @var array<int, float|int> พื้นที่ใช้แล้วต่อชั้น (getSpace) */
    private array $space = [];

    /** @var array<int, array<string, true>> คู่ user|user_agent ที่อยู่ในชั้น (getCountCode ของ SR: GROUP BY user, user_agent) */
    private array $users = [];

    private string $out = '';

    public function __construct(private int $warehouseId, private string $prints) {}

    /** @return list<int|null>|null รายการใน IN (...) — null = เดิม SQL error */
    public static function parsePrints(string $prints): ?array
    {
        $out = [];
        foreach (explode(',', $prints) as $p) {
            $p = trim($p);
            if (preg_match('/^-?\d+$/', $p)) {
                $out[] = (int) $p;
            } elseif (strcasecmp($p, 'null') === 0) {
                $out[] = null; // jQuery .val() ของ select ว่าง = "null" → IN (null) ไม่ตรงอะไร (ไม่ error)
            } else {
                return null;
            }
        }

        return $out;
    }

    public function run(): string
    {
        $ids = self::parsePrints($this->prints);

        return DB::transaction(fn () => $this->allocate($ids));
    }

    /** @param list<int|null>|null $ids */
    private function allocate(?array $ids): string
    {
        $uid = (int) Legacy::userId();
        $nobody = DB::table('user')->where('code', 'nobody')->value('id');
        $nobody = $nobody === null ? null : (string) $nobody;

        $sections = array_map(fn ($r) => W::strings($r), DB::table('warehouse_section')
            ->where('is_delete', 0)->where('name', '!=', 'zzz')->orderBy('floors')->get()->all());

        $arrCubic = [];
        $arrWeight = [];
        $usersAdd = [];
        $dataA = [];
        $dataB = [];
        if ($ids !== null) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            // แก้: เดิม "LEFT JOIN (SELECT id AS ct FROM warehouse_section_detail ... LIMIT 1) ON a.ct = tracking.id WHERE a.ct IS NULL"
            //      ตั้งใจข้าม tracking ที่จัดชั้นแล้ว แต่เทียบ id ของแถวแรกกับ tracking.id → ไม่ข้ามอะไรเลย
            //      = กดคำนวณซ้ำ/ดับเบิลคลิก INSERT warehouse_section_detail ซ้ำทุก tracking (E2E: 3 tracking → 9 แถว)
            //      → ข้าม tracking ที่มีแถวชั้นวาง (is_delete = 0) อยู่แล้ว (ยกเลิกการคำนวณ = is_delete 1 → คำนวณใหม่ได้)
            $base = "SELECT tracking.*, NULL AS ct FROM tracking
                WHERE lot_id IN ($in)
                AND NOT EXISTS (SELECT 1 FROM warehouse_section_detail AS t2 WHERE t2.tracking_id = tracking.id AND t2.is_delete = 0)";
            $dataA = array_map(fn ($r) => W::strings($r), DB::select($base, $ids));
            // เดิม: `user` != '<id ของ nobody>' — ไม่มี nobody → != '' (= != 0)
            $dataB = array_map(fn ($r) => W::strings($r), DB::select($base.' AND `user` != ?', [...$ids, $nobody ?? '']));
        }

        $key = fn (array $r) => ($r['user'] ?? '').'-'.($r['user_agent'] ?? '');
        foreach ($dataA as $r) {
            $k = $key($r);
            $cb = (Php74::num($r['width']) * Php74::num($r['height']) * Php74::num($r['long'])) / 1000000;
            $arrCubic[$k] = Php74::num($arrCubic[$k] ?? null) + ($cb * Php74::num($r['amount']));
            $arrWeight[$k] = Php74::num($arrWeight[$k] ?? null) + (Php74::num($r['weight']) * Php74::num($r['amount']));
        }

        $this->loadSections($sections);

        // nobody → ชั้น 2, ลูกค้าชั้นตายตัว → ชั้นของตัวเอง
        // (เดิม push "user-".$row['user_agent'] โดย $row = null หลัง while ของ query ก่อนหน้า = "user-" เสมอ)
        // SR วนรายการ = tracking ทั้งหมด ต่อด้วย tracking ของลูกค้าใน REPEAT_USERS ซ้ำอีกรอบ (query ที่ 2 ไม่ได้ล้างรายการก่อน)
        // แก้: รอบซ้ำไม่ INSERT อีก (เดิมได้แถวชั้นวางซ้ำ) แต่ยัง push รายการ "จัดแล้ว" ซ้ำเหมือนเดิม (print_r ที่แสดงตรงกับเดิม)
        $repeat = array_values(array_filter($dataA, fn ($r) => $r['user'] !== null && in_array((int) Php74::num($r['user']), self::REPEAT_USERS, true)));
        foreach ([[$dataA, true], [$repeat, false]] as [$list, $write]) {
            foreach ($list as $item) {
                if (Php74::eq($item['user'], $nobody)) {
                    $write && $this->insert([$item], self::NOBODY_SECTION, $uid);
                    $usersAdd[] = ($item['user'] ?? '').'-';
                }
                $fixed = $item['user'] === null ? null : (self::FIXED_SECTIONS[(int) Php74::num($item['user'])] ?? null);
                if ($fixed !== null) {
                    $write && $this->insert([$item], $fixed, $uid);
                    $usersAdd[] = $item['user'].'-';
                }
            }
        }

        $this->out .= "<pre>".print_r($arrCubic, true).print_r($arrWeight, true).print_r($usersAdd, true).'</pre>';

        foreach ($arrCubic as $k => $value) {
            $k = (string) $k;
            $isDefault = $value >= 0.7 || ($arrWeight[$k] ?? 0) >= 50;

            $batch = [];
            if (! in_array($k, $usersAdd, true)) {
                foreach ($dataB as $item) {
                    if ($isDefault && $key($item) === $k) {
                        $batch[] = $item;
                        $usersAdd[] = $k;
                    }
                }
            }
            // เดิม INSERT ... VALUES (ว่าง) = SQL error เมื่อไม่มีแถว
            $this->insert($batch, self::DEFAULT_SECTION, $uid, true);

            if ($isDefault) {
                continue;
            }
            $found = false;
            $cacheUser = 0;
            foreach ($sections as $w) {
                $sid = (int) $w['id'];
                $space = $this->space[$sid] ?? 0;
                $countCode = count($this->users[$sid] ?? []);
                $cbMain = (Php74::num($w['width']) * Php74::num($w['height']) * Php74::num($w['long'])) / 1000000;
                $sumCb = (float) Format::cubicformat($cbMain, false) - (float) Format::cubicformat($space, false);
                $skip = false;
                $last = substr((string) $w['name'], -1);

                if ($countCode >= 10) {
                    $skip = $sid !== self::DEFAULT_SECTION;
                    if (((float) Format::cubicformat($cbMain, false) / 2) > (float) Format::cubicformat($space, false)) {
                        $skip = false;
                        if ($countCode >= self::MAX_CODE) {
                            $skip = $sid !== self::DEFAULT_SECTION;
                        }
                    }
                }
                if (($arrWeight[$k] ?? 0) >= 15 && $last === '3') {
                    $skip = true;
                }

                if (! $skip && $sumCb > $value && ! in_array($k, $usersAdd, true)) {
                    $batch = [];
                    foreach ($dataB as $item) {
                        if ($key($item) === $k) {
                            $batch[] = $item;
                            $found = true;
                            $cacheUser = $k;
                        }
                    }
                    $this->insert($batch, $sid, $uid, true);
                }
                if ($found) {
                    $usersAdd[] = $cacheUser;
                    break;
                }
            }
        }

        $detail = [];
        $names = '';
        if ($ids !== null) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $detail = array_map(fn ($r) => W::strings($r), DB::select(
                "SELECT tracking.* , user.code ,  lot.name as lot_name, t4.name as section_name, weight_thai
                FROM tracking
                LEFT JOIN user ON tracking.user = user.id
                LEFT JOIN lot ON lot.id = tracking.lot_id
                LEFT JOIN warehouse_section_detail as t3 ON t3.tracking_id = tracking.id
                LEFT JOIN warehouse_section as t4 ON t4.id = t3.section_id
                WHERE tracking.date_thai = '0000-00-00 00:00:00'  AND tracking.lot_id IN ($in)
                AND tracking.is_delete = 0 AND (tracking.header = 0 OR tracking.header IS NULL) ORDER BY tracking.user ASC ",
                $ids,
            ));
        }

        // แก้: เดิม WHERE id = '<warehouse id>' (ผิดคอลัมน์ → ลบแถวของใบรับเข้าอื่น) — ตั้งใจล้างรายการล็อตเดิมของใบนี้
        //      แล้วบันทึกชุดที่เลือก (หน้า form โหลดล็อตที่บันทึกแล้วใส่ช่องที่เลือกไว้ให้ = ส่งมาครบทุกครั้ง)
        //      → ล้างแถวของใบรับเข้านี้ (warehouse_id = id) ไม่งั้นกดซ้ำ = warehouse_detail ซ้ำทุกล็อต; id 0 (ไม่ส่ง id) ไม่ล้าง
        if ($this->warehouseId > 0) {
            DB::table('warehouse_detail')->where('warehouse_id', $this->warehouseId)->where('is_delete', 0)
                ->update(['is_delete' => '1']);
        }

        if ($ids !== null) {
            $lots = DB::table('lot')->whereIn('id', array_values(array_filter($ids, fn ($v) => $v !== null)))->orderBy('id')->get(['id', 'name']);
            foreach ($lots as $lot) {
                $names .= ', '.$lot->name;
                DB::table('warehouse_detail')->insert([
                    'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                    'is_delete' => 0, 'warehouse_id' => $this->warehouseId, 'lot_id' => $lot->id,
                ]);
            }
        }

        return $this->out.view('warehouse.admin.warehouse.lot_saved', [
            'names' => substr($names, 1),
            'body' => $this->rows($detail),
        ])->render();
    }

    /** พื้นที่/ผู้ใช้ของทุกชั้นครั้งเดียว (เดิม getSpace/getCountCode ต่อชั้นต่อลูกค้า) */
    private function loadSections(array $sections): void
    {
        $ids = array_map(fn ($w) => (int) $w['id'], $sections);
        if ($ids === []) {
            return;
        }
        $rows = DB::table('warehouse_section_detail as t')
            ->leftJoin('tracking', 'tracking.id', '=', 't.tracking_id')
            ->whereIn('t.section_id', $ids)->where('t.is_delete', 0)->where('tracking.tracking', '!=', '')
            ->orderBy('t.section_id')->orderBy('t.id')
            ->get(['t.section_id', 't.width', 't.height', 't.long', 't.user', 't.user_agent']);
        foreach ($rows as $r) {
            $sid = (int) $r->section_id;
            $this->space[$sid] = ($this->space[$sid] ?? 0)
                + ((Php74::num($r->width) * Php74::num($r->height) * Php74::num($r->long)) / 1000000);
            $this->users[$sid][self::codeKey($r->user, $r->user_agent)] = true;
        }
    }

    /** กุญแจของ GROUP BY user, user_agent (NULL เป็นกลุ่มของตัวเอง) */
    private static function codeKey(mixed $user, mixed $agent): string
    {
        return (int) Php74::num($user).'|'.($agent === null ? 'N' : (int) Php74::num($agent));
    }

    /**
     * INSERT warehouse_section_detail หลายแถว (ไม่มีแถว = เดิม SQL error = ไม่ทำอะไร) แล้วอัปเดตพื้นที่/ผู้ใช้ของชั้น
     * $withAgent: SR ใส่คอลัมน์ user_agent (= tracking.user_agent ที่ไม่มี → '' → 0) เฉพาะการจัดชั้น 1/อัตโนมัติ;
     * nobody/ชั้นตายตัวไม่ใส่ → ค่า default ของคอลัมน์ (0) — ผลเท่ากัน
     */
    private function insert(array $items, int $sectionId, int $uid, bool $withAgent = false): void
    {
        if ($items === []) {
            return;
        }
        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                'create_date' => DB::raw('NOW()'), 'create_by' => $uid, 'mod_date' => DB::raw('NOW()'), 'mod_by' => $uid,
                'is_delete' => 0, 'section_id' => $sectionId,
                'width' => $item['width'] ?? '', 'height' => $item['height'] ?? '', 'long' => $item['long'] ?? '',
                'user' => $item['user'] ?? '', 'tracking_id' => $item['id'], 'warehouse_id' => $this->warehouseId,
            ] + ($withAgent ? ['user_agent' => (int) Php74::num($item['user_agent'] ?? null)] : []);
            // getSpace/getCountCode นับเฉพาะแถวที่ tracking.tracking != '' (MySQL ไม่สนช่องว่างท้าย)
            if ($item['tracking'] !== null && rtrim($item['tracking'], ' ') !== '') {
                $this->space[$sectionId] = ($this->space[$sectionId] ?? 0)
                    + ((Php74::num($item['width']) * Php74::num($item['height']) * Php74::num($item['long'])) / 1000000);
                $this->users[$sectionId][self::codeKey($item['user'], $item['user_agent'] ?? 0)] = true;
            }
        }
        DB::table('warehouse_section_detail')->insert($rows);
    }

    private static function h(mixed $v): string
    {
        return htmlspecialchars((string) $v, ENT_COMPAT | ENT_HTML401);
    }

    /** แถวตาราง (เหมือน load_lot_detail แต่ไม่มีช่องรับ/แก้ชั้น และน้ำหนักไม่คูณจำนวน) */
    private function rows(array $rows): string
    {
        $sum = [];
        $add = function (string $k, int|float $v) use (&$sum) {
            $sum[$k] = Php74::num($sum[$k] ?? null) + $v;
        };
        if ($rows === []) {
            $html = '<tr><td style="height: 100px;" colspan="14" class="text-center">'.res('no_data').'</td></tr>';
        } else {
            $html = '';
            foreach ($rows as $item) {
                $n = fn (string $k) => Php74::num($item[$k] ?? null);
                $rs = 'rowspan="'.Php74::str($n('header_count') + 1).'"';
                $id = ' id="'.self::h($item['tracking']).'"';
                if (Php74::eq($item['is_craft'], 1) && Php74::eq($item['is_qc'], 1)) {
                    $html .= '<tr'.$id.' style="background-color:#f9ffa5">';
                } else {
                    $found = false;
                    foreach ([
                        [Php74::eq($item['payment_id'], '-3'), '#e789ff'],
                        [Php74::eq($item['price_kg'], 0) && Php74::eq($item['price_cubic'], 0), '#ff0c00'],
                        [Php74::eq($item['is_craft'], 1), '#ffdfa5'],
                        [Php74::eq($item['is_qc'], 1), '#a5ffc5'],
                    ] as [$cond, $color]) {
                        if ($cond) {
                            $html .= '<tr'.$id.' style="background-color:'.$color.'">';
                            $found = true;
                        }
                    }
                    if (! $found) {
                        $html .= '<tr'.$id.'>';
                    }
                }
                $date = fn (string $k) => Format::getthaidate(explode(' ', (string) $item[$k])[0], true, true);
                $agent = (string) ($item['agent_code'] ?? '');
                $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.self::h($item['lot_name']).'</td>';
                $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.self::h($item['no']).'</td>';
                $html .= '<td '.$rs.'  style="vertical-align:top">'.self::h($item['tracking']).'</td>';
                $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.$date('date_warehouse').'</td>';
                $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.$date('date_outofchina').'</td>';
                $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.$date('date_thai').'</td>';
                $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.self::h($item['code']).($agent === '' ? '' : "<span class='text-danger'>[".self::h($agent).']</span>').'</td>';
                $html .= '<td '.$rs.' class="text-right" style="vertical-align:top">'.Format::getZero(Format::numberformatzero($item['amount'])).'</td>';
                $html .= '<td '.$rs.' class="text-right" style="vertical-align:top">'.Format::getZero(Format::numberformatone($item['weight'])).'</td>';
                $html .= '<td '.$rs.' class="text-center" style="vertical-align:top">'.self::h($item['section_name']).'</td>';
                if (Php74::eq($item['header'], 0)) {
                    $add('amount', $n('amount'));
                    $add('weight', $n('weight') * $n('amount'));
                    $add('cubic', (($n('width') * $n('height') * $n('long')) / 1000000) * $n('amount'));
                    $add('kg', $n('price_kg'));
                    $add('pcubic', $n('price_cubic'));
                    $add('width', $n('width'));
                    $add('height', $n('height'));
                    $add('long', $n('long'));
                    $add('qc', $n('price_qc'));
                    $add('repack', $n('price_repack'));
                    $add('express', $n('price_express'));
                    $add('else', $n('price_else'));
                    $add('craft', $n('price_craft'));
                }
                $html .= '</tr>';
                // header_count ไม่มีในตาราง tracking → เดิมไม่เคยแสดงแถวย่อย
            }
        }
        $s = fn (string $k) => $sum[$k] ?? null;
        $html .= '<input type="hidden" class="hid_sum_amount" value="'.Format::numberformatzero($s('amount'), false).'" />';
        $html .= '<input type="hidden" class="hid_sum_weight" value="'.Format::numberformatone($s('weight'), false).'" />';
        $html .= '<input type="hidden" class="hid_sum_cubic" value="'.Format::cubicformat($s('cubic'), false).'" />';
        $html .= '<input type="hidden" class="hid_sum_kg" value="'.Format::numberformat($s('kg'), false, false).'" />';
        $html .= '<input type="hidden" class="hid_sum_pcubic" value="'.Format::numberformat($s('pcubic'), false, false).'" />';
        $html .= '<input type="hidden" class="hid_sum_width" value="'.Format::numberformatone($s('width'), false).'" />';
        $html .= '<input type="hidden" class="hid_sum_height" value="'.Format::numberformatone($s('height'), false).'" />';
        $html .= '<input type="hidden" class="hid_sum_long" value="'.Format::numberformatone($s('long'), false).'" />';
        $html .= '<input type="hidden" class="hid_sum_qc" value="'.Format::numberformat($s('qc'), false, false).'" />';
        $html .= '<input type="hidden" class="hid_sum_repack" value="'.Format::numberformat($s('repack'), false, false).'" />';
        $html .= '<input type="hidden" class="hid_sum_express" value="'.Format::numberformat($s('express'), false, false).'" />';
        $html .= '<input type="hidden" class="hid_sum_else" value="'.Format::numberformat($s('else'), false, false).'" />';
        $html .= '<input type="hidden" class="hid_sum_craft" value="'.Format::numberformat($s('craft'), false, false).'" />';

        return $html;
    }
}
