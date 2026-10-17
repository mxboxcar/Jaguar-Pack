<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>รายการ Tracking</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 16px; color: #222; }
        table { border-collapse: collapse; width: 100%; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; vertical-align: top; }
        th { background: #f4f4f4; }
        .muted { color: #888; }
        form { margin-bottom: 12px; display: flex; gap: 8px; flex-wrap: wrap; }
        nav svg { width: 16px; }
    </style>
</head>
<body>
<h2>รายการ Tracking</h2>

<form method="get">
    <input type="text" name="q" value="{{ $q }}" placeholder="ค้นหา tracking / order / quote" maxlength="100">
    <select name="status">
        <option value="-1" @selected($status === -1)>- ทั้งหมด -</option>
        <option value="0" @selected($status === 0)>เข้าโกดังจีน</option>
        <option value="1" @selected($status === 1)>ออกจากจีน</option>
        <option value="2" @selected($status === 2)>ถึงไทย</option>
        <option value="3" @selected($status === 3)>ยังไม่ชำระ</option>
    </select>
    <button type="submit">ค้นหา</button>
</form>

<p class="muted">ทั้งหมด {{ number_format($trackings->total()) }} รายการ</p>

<table>
    <thead>
    <tr>
        <th>เลข Tracking</th>
        <th>เลขออเดอร์</th>
        <th>ออเดอร์ในระบบ</th>
        <th>ประเภทสินค้า</th>
        <th>Lot</th>
        <th>น้ำหนัก / ขนาด</th>
        <th>ค่าขนส่ง</th>
        <th>สถานะ</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($trackings as $t)
        <tr>
            <td>{{ $t->tracking ?: '-' }}</td>
            <td>{{ $t->order ?: '-' }}</td>
            <td>{{ implode(' / ', $orderIds[$t->id] ?? []) ?: '-' }}</td>
            <td>{{ $t->productType?->name ?? '-' }}</td>
            <td>{{ $t->lot?->name ?? '-' }}</td>
            <td>{{ $t->weight }} kg · {{ $t->width }}×{{ $t->long }}×{{ $t->height }}</td>
            <td>{{ number_format((float) $t->price_amount, 2) }}</td>
            <td>
                @if (\App\Models\Tracking::hasDate($t->date_thai)) ถึงไทย {{ $t->date_thai }}
                @elseif (\App\Models\Tracking::hasDate($t->date_outofchina)) ออกจากจีน {{ $t->date_outofchina }}
                @elseif (\App\Models\Tracking::hasDate($t->date_warehouse)) เข้าโกดัง {{ $t->date_warehouse }}
                @else <span class="muted">รอเข้าโกดัง</span>
                @endif
                @if ((int) $t->payment_id === 0) <br><span class="muted">ยังไม่ชำระ</span> @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="8" class="muted">ไม่พบข้อมูล</td></tr>
    @endforelse
    </tbody>
</table>

{{ $trackings->links() }}
</body>
</html>
