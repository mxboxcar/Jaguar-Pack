{{-- ส่วนกลางของ _admin/_order/form.php ของ SR: <div class="col-xs-6"> ครอบ หมายเหตุ (status 0) / การชำระเงิน + รายการเงินที่เกี่ยวข้อง (status >= 2 หรือ -1) / วันที่ของใบ --}}
@php
    use App\Domain\Order\Html;
    use App\Domain\Order\Php74;
    use App\Support\Legacy\Format;
@endphp
<div class="col-xs-6">
@if (Php74::eq($st, 0))
<div class="col-xs-12">
	    <div class="card-box">
	    <h4 class="m-t-0 header-title"><b><span class="text-danger">{!! res('remark') !!}</span></b></h4>
	    <p class="text-muted font-13 m-b-30">
	    </p>
	        <div class="text-left">
            1.  โปรดตรวจสอบราคาสินค้าในเว็บจีนทุกครั้งก่อนทำการสั่งซื้อ<br/><br/>
2.  ฝ่ายสั่งซื้อจะทำการตรวจสอบออเดอร์ให้ท่านภายใน 24 ชม. แต่ไม่เกิน 72 ชม.นับจากวันที่ท่านส่งใบสั่งซื้อมา<br/><br/>
3.  กรุณาชำระเงินภายใน 3 วัน นับจากวันที่เปิดใบสั่งซื้อ หากท่านยกเลิก หรือไม่ชำระเงินในเวลาที่กำหนด บริษัทขอสงวนสิทธิ์ไม่คืนค่าธรรมเนียมในการสั่งซื้อ<br/><br/>
4.  โปรดตรวจสอบความถูกต้องของใบสั่งซื้อทุกครั้งก่อนชำระเงินค่าสินค้า หากท่านชำระเงินค่าสินค้าแล้ว จะไม่สามารถแก้ไขรายการใดๆในใบสั่งซื้อได้อีก<br/><br/>
5.  เมื่อท่านชำระเงินค่าสินค้า ระบบจะคืนยอดค่าธรรมเนียมให้ท่าน
        </div>
	    </div>
	</div>
@endif
@if (Php74::num($st) >= 2 || Php74::eq($st, -1))
<div class="col-xs-12">
        <div class="card-box">
        <h4 class="m-t-0 header-title"><b>{!! res('payment_thing') !!}</b></h4>
        <p class="text-muted font-13 m-b-30">
        </p>
            <div class="form-horizontal" role="form">
                <div class="form-group">
                    <label class="col-md-4 control-label"> {!! res('total_have_to_paid') !!}</label>
                    <div class="col-md-7 form-control-static">{!! Format::numberformat($data['total'] ?? null) !!}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-4 control-label"> {!! res('remark') !!}</label>
                    <div class="col-md-7 form-control-static">
                        <textarea class="form-control" name="submit_remark" rows="5" disabled >{!! Html::h($data['submit_remark'] ?? '') !!}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div><div class="col-xs-12">
        <div class="card-box">
            <h4 class="m-t-0 header-title"><b>{!! res('ref_balance_list') !!}</b></h4>
            <p class="text-muted font-13 m-b-30">
            </p>
            <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 190px;">{!! res('working_date') !!}</th>
                            <th class="text-center">{!! res('detail') !!}</th>
                            <th class="text-center" style="width: 140px;">{!! res('amount') !!}</th>
                        </tr>
                    </thead>
                    <tbody>@if (count($balances) !== 0)@foreach ($balances as $item)@php $exp = explode(' ', (string) $item->create_date); @endphp<tr><td>{!! Format::getthaidate($exp[0]) !!}<br />{{ $exp[1] ?? '' }}</td><td class="text-left"><a href="{{ legacy_href() }}{!! Html::h($item->link) !!}{{ $item->prefix_ref_id }}">{!! Html::h($item->detail) !!}</a></td><td class="text-right">@if (Php74::num($item->amount) >= 0)<span class="text-success">+{!! Format::numberformat($item->amount) !!}</span>@else<span class="text-danger">{!! Format::numberformat($item->amount) !!}</span>@endif</td>@endforeach @else<tr><td colspan="3" class="text-center">{!! res('no_data') !!}</td></tr>@endif

                </tbody>
            </table>
        </div>
    </div>

@endif
<div class="col-xs-12">
        <div class="card-box">
            <div class="form-horizontal" role="form">
                <div class="form-group">
                    <label class="col-md-4 control-label">วันที่ลูกค้าสั่งซื้อ</label>
                    <div class="col-md-7 form-control-static">
                        {{ $data['create_date'] ?? '' }}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-4 control-label">Admin ตรวจสอบเสร็จสิ้นวันที่</label>
                    <div class="col-md-7 form-control-static">
                        {{ $data['who_did_this_1_date'] ?? '' }}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-4 control-label">วันที่ลูกค้ากดชำระเงิน</label>
                    <div class="col-md-7 form-control-static">
                        {{ $data['submit_date'] ?? '' }}
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-4 control-label">Admin สั่งซื้อวันที่</label>
                    <div class="col-md-7 form-control-static">
                        {{ $data['who_did_this_3_date'] ?? '' }}
                    </div>
                </div>
            </div>
        </div>
    </div></div>
