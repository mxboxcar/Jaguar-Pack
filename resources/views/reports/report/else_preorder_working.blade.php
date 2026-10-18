{{-- _admin/_report/else_preorder_working.php เดิม — วันที่ใช้ who_did_this_3_date เสมอ (เดิมเทียบ $txtSearch[4] ที่ไม่มีอยู่) --}}
@php
    use App\Support\Legacy\Format;
@endphp


<table >
    <thead>
        <tr>
            <th  class="text-center" style="width: 120px;">{!! res('no_for_grid') !!}</th>
            <th class="text-center" style="width: 105px;">{!! res('working_date') !!}</th>
            <th class="text-center" style="width: 105px;">จำนวนร้าน</th>
            <th class="text-center" style="width: 105px;">จำนวนลิ้งค์</th>
        </tr>
    </thead>

    <tbody>

@foreach ($rows as $item)
@php $exp = explode(' ', (string) $item->who_did_this_3_date); @endphp
<tr>
    <td>{{ $prefix.$item->id }}</td>
    <td>{!! Format::getthaidate($exp[0]) !!} {{ $exp[1] ?? '' }}</td>
    <td class="text-center">{{ $shops[$item->id] ?? 0 }}</td>
    <td class="text-center">{{ $links[$item->id] ?? 0 }}</td>
    
</tr>
@endforeach

</tbody>
</table>
