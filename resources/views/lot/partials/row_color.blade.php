{{-- สีแถว tracking ในล็อต (load_lot_detail.php ทั้งสองฝั่ง): craft+qc / claim / ไม่มีราคา / craft / qc
     เดิมต่อ <tr ...> ได้หลายอันในแถวเดียว (เช่น claim และไม่มีราคา) — คงไว้แบบเดิม --}}
@php($eq = fn ($a, $b) => \App\Domain\Lot\Php74::eq($a, $b))
@if ($eq($item->is_craft, 1) && $eq($item->is_qc, 1))<tr style="background-color:#f9ffa5">@else{!!
    ($eq($item->payment_id, '-3') ? '<tr style="background-color:#e789ff">' : '')
    .($eq($item->price_kg, 0) && $eq($item->price_cubic, 0) ? '<tr style="background-color:#ff0c00">' : '')
    .($eq($item->is_craft, 1) ? '<tr style="background-color:#ffdfa5">' : '')
    .($eq($item->is_qc, 1) ? '<tr style="background-color:#a5ffc5">' : '')
    .(! ($eq($item->payment_id, '-3') || ($eq($item->price_kg, 0) && $eq($item->price_cubic, 0)) || $eq($item->is_craft, 1) || $eq($item->is_qc, 1)) ? '<tr>' : '')
!!}@endif
