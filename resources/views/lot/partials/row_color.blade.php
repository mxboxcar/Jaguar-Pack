{{-- สีแถว tracking ในล็อต (load_lot_detail.php ทั้งสองฝั่ง): craft+qc / claim / ไม่มีราคา / craft / qc / (SR แอดมิน: ถ่ายรูป)
     เดิมต่อ <tr ...> ได้หลายอันในแถวเดียว (เช่น claim และไม่มีราคา) — คงไว้แบบเดิม
     $takeapic = true: _admin/_lot/load_lot_detail.php ของ SR (is_takeapic = 1 → #768cf2); ฝั่งลูกค้า (_lot) ไม่มี --}}
@php
    $eq = fn ($a, $b) => \App\Domain\Lot\Php74::eq($a, $b);
    $pic = ($takeapic ?? false) && $eq($item->is_takeapic ?? null, 1);
@endphp
@if ($eq($item->is_craft, 1) && $eq($item->is_qc, 1))<tr style="background-color:#f9ffa5">@else{!!
    ($eq($item->payment_id, '-3') ? '<tr style="background-color:#e789ff">' : '')
    .($eq($item->price_kg, 0) && $eq($item->price_cubic, 0) ? '<tr style="background-color:#ff0c00">' : '')
    .($eq($item->is_craft, 1) ? '<tr style="background-color:#ffdfa5">' : '')
    .($eq($item->is_qc, 1) ? '<tr style="background-color:#a5ffc5">' : '')
    .($pic ? '<tr style="background-color:#768cf2">' : '')
    .(! ($eq($item->payment_id, '-3') || ($eq($item->price_kg, 0) && $eq($item->price_cubic, 0)) || $eq($item->is_craft, 1) || $eq($item->is_qc, 1) || $pic) ? '<tr>' : '')
!!}@endif
