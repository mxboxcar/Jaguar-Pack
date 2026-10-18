@foreach ($children[(int) $item->id] ?? [] as $child)
<tr style="background-color:#ffdfa5">
								<td style="white-space: nowrap;" class="showdetailbox">{{ $child }}</td>
							</tr>
@endforeach
