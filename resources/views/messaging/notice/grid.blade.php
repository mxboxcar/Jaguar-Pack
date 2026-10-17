@use('App\Domain\Messaging\NoticeText')
@use('App\Support\Legacy\Format')
@use('App\Support\Legacy\Paging')
{{-- _notice/grid.php เดิม — ข้อความย่อเป็น HTML ที่แอดมินเขียน (strip_tags แล้ว) แสดงดิบแบบเดิม --}}

<div class="row">
	<div class="col-lg-12">
		<div class="card-box">
			<div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 150px;">{!! res('create_date') !!}</th>
                            <th class="text-center">{!! res('text') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
@if(count($rows) != 0)
@foreach($rows as $item)
                        <tr>
                            <td class="text-center">
@php $exp = explode(' ', (string) $item->create_date); @endphp
@if($item->create_date === '0000-00-00 00:00:00' || (string) $item->create_date === '')
 
@else
{!! Format::getthaidate($exp[0], false, true).' '.Format::gettime($exp[1] ?? '') !!}
@endif
                            </td>
                            <td>{!! NoticeText::excerpt($item->text)."...&nbsp;&nbsp;&nbsp;&nbsp;<a href='".legacy_href('notice/'.(int) $item->id)."'>อ่านต่อ</a>" !!}</td>
                            
                            
                        </tr>
@endforeach
@else
<td class="text-center" colspan="20">{!! res('no_data') !!}</td>
@endif
                    </tbody>
                </table>
                <div class="pull-right">
                    {!! Paging::getpaging($page, $total, 10, $ajaxpage) !!}
                </div>
            </div>
		</div>
        
    </div> <!-- end col -->
</div>

<input type="hidden" id="Checking" />
<input type="hidden" id="txtSearch" value="{{ $txtsearch }}">
