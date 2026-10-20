{{-- _admin/_printorder/grid.php (SR) — ใบสั่งซื้อสถานะ 3 เรียงตามวันที่สั่งซื้อสำเร็จ --}}
@php
    use App\Domain\Order\Html;
    use App\Domain\Order\Php74;
    use App\Domain\Order\PrintOrder;
    use App\Support\Legacy\Format;
    use App\Support\Legacy\Paging;
    use App\Support\Legacy\Status;
    // เดิม selected เทียบ $txtSearch[2] แต่ GenerateWhere() ส่งแค่ "คำค้น|สถานะ" → เลือกค้างเฉพาะตอนโหลดจากหน้า index (|-2|status)
    $sel = fn ($x) => Php74::eq($status, $x) ? "selected='selected'" : '';
@endphp

<div class="row">
    <div class="col-lg-12">
        <div class="card-box">
            <div class="row m-t-10 m-b-10">
                <div class="col-xs-10 col-sm-8 col-lg-8">
                    <div class="form-group contact-search m-b-30">
                        <input type="text" id="txtsearch" onblur="GenerateWhere();" class="form-control" placeholder="{!! res('search') !!}" value="{!! Html::h($search) !!}">
                        <button type="submit" class="btn btn-white" onclick="Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());"><i class="fa fa-search"></i></button>
                    </div> <!-- form-group -->
                </div>

                <div class="col-xs-2 col-sm-2 col-lg-2">
                    <select name="status" class="form-control " id="status" onchange="GenerateWhere();loadAjaxGrid('{{ $ajaxpage }}', '{!! Html::jsAttr($page) !!}',  $('#txtSearch').val());">
                        <option {!! $sel('-2') !!} value="-2">- {!! res('all') !!} -</option>  
                        <option {!! $sel('0') !!} value="0">{!! res('unalready_print') !!}</option>    
                        <option {!! $sel('1') !!} value="1">{!! res('already_print') !!}</option>   
                    </select>
                </div>
                
            </div>


            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th  class="text-center" style="width: 111px;">{!! res('no_for_grid') !!}</th>
                            <th  class="text-center" style="width: 111px;">{!! res('order') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('customer_code') !!}</th>
                            <th class="text-center" >{!! res('admin_remark') !!}</th>
                            <th class="text-center" style="width: 70px;">{!! res('amount_shop') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('total') !!}</th>
                            <th class="text-center" style="width: 70px;">{!! res('shipping_type') !!}</th>
                            <th class="text-center" style="width: 92px;">{!! res('status') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
                    @if (count($items) !== 0)
                        @foreach ($items as $item)
                        @php
                            // เดิม: status 0 + is_lock 1 = แถวสีแดง + รหัสผู้ล็อก — grid นี้กรอง status = 3 เสมอ จึงไม่เกิด
                            $color = (Php74::eq($item->is_lock, 1) && Php74::eq($item->status, 0)) ? "class='danger'" : '';
                            $d3 = (string) ($item->who_did_this_3_date ?? '');
                            $exp = explode(' ', $d3);
                        @endphp
                        <tr {!! $color !!}>
                            
                            <td><a href="{{ legacy_href() }}admin/printorder/{{ $item->id }}">{!! Html::h(PrintOrder::runNo($item->run_id, $item->id)) !!}</a>
                            <div class="text-center">
                                {!! ($d3 === '0000-00-00 00:00:00' || $d3 === '') ? '-&nbsp;|&nbsp;-' : Format::getthaidate($exp[0], true).'&nbsp;|&nbsp;'.Format::gettime($exp[1] ?? '') !!}
                            </div>
                            </td>
                            <td class="text-center"><a href="{{ legacy_href() }}admin/order/{{ $item->id }}">{{ $prefix }}{{ $item->id }}</a></td>
                            <td class="text-center">{!! Html::h($item->code) !!}</td>
                            <td class="text-center">
                                <input onblur="on_saveremark($(this))" onkeydown="javascript: if(event.keyCode == 13) on_saveremark($(this));" class="editable" type="text" value="{!! Html::h($item->admin_quote) !!}" ref="{{ $item->id }}">
                            </td>
                            <td class="text-right">{!! Format::numberformatzero($shops[(int) $item->id] ?? 0) !!}</td>
                            
                            <td class="text-right">{!! Format::numberformat($item->total) !!}</td>
                            <td class="text-center">{!! Status::getShipType($item->ship) !!}</td>
                            <td class="text-center">{!! PrintOrder::getstatusprint($item->is_print) !!}<br /><small></small></td>
                            <td class="text-center">
                                <a href="{{ legacy_href() }}admin/printorder/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary "></i></a>
                            </td>
                        </tr>
                        @endforeach
                    @else
<td class="text-center" colspan="20">{!! res('no_data') !!}</td>
                    @endif
                    </tbody>
                </table>
                <div class="pull-right">
                    {!! Paging::getpaging($page, $rows, 10, $ajaxpage) !!}
                </div>
            </div>
        </div>
        
    </div> <!-- end col -->
</div>

<input type="hidden" id="Checking" />
<input type="hidden" id="txtSearch" value="{!! Html::h($txtsearch) !!}">

<script>

    

    function keypressInBox(e) {
        GenerateWhere();
        var code = (e.keyCode ? e.keyCode : e.which);
        if (code == 13) { //Enter keycode                        
            e.preventDefault();
            loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());
        }
    }
    $("#txtsearch").bind("keypress", {}, keypressInBox);

  

    function GenerateWhere() {
        $("#txtSearch").val($("#txtsearch").val() + "|" + $("#status").val());
    }

</script>
