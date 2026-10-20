<?php

namespace App\Domain\Points;

/**
 * เงื่อนไขของไฟล์เดิมที่พึ่งการเทียบแบบ PHP 7.4 (ทดสอบกับ fixture ที่สร้างด้วย php7.4: tests/fixtures/points-php74-cases.json)
 */
final class Rules
{
    /** _admin/_shop_cashback/save_approved.php: if($data['price'] != "" && != "0.00" && != "0") → เติม balance_cn */
    public static function cashbackCreditable(mixed $price): bool
    {
        return ! Php74::eq($price, '') && ! Php74::eq($price, '0.00') && ! Php74::eq($price, '0');
    }
}
