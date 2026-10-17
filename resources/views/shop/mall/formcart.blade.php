{{-- _mall/formcart.php เดิม (page=mall_cart) --}}
@use('App\Support\Legacy\Format')
@extends('legacy.layout')

@section('content')



<div class="col-sm-7">
    <h4 class="page-title">{!! res('cart') !!}</h4>
    <ol class="breadcrumb">
        <li>
            <a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
        </li>
        <li class="active">
            {!! res('cart') !!}
        </li>
    </ol>
</div>

<div class="col-sm-5 pull-right">
    <div class="button-list">

        <a type="button" href="{{ legacy_href() }}mall" class="pull-right btn btn-info waves-effect waves-light">
            <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
            กลับไปเลือกสินค้า
        </a>
    </div>
</div>


<div class=" col-xs-12">
    <div class="card-box">
        <h4 class="m-t-0 header-title"><b>{!! res('cart_detail') !!}</b></h4>
        <p class="text-muted font-13 m-b-30">
        </p>
        


        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th class="text-center" > <div class="text-center"></div></th>
                    <th class="text-center" ></th>
                    <th class="text-center">รหัสสินค้า</th>
                    <th class="text-center" >{!! res('product_name') !!}</th>
                    <th class="text-center">{!! res('detail') !!}</th>
                    <th class="text-center" style="width: 80px;">{!! res('amount') !!}</th> 
                    <th class="text-center" >ราคาต่อชิ้น {!! res('thai_currency') !!}</th>
                    <th class="text-center" >{!! res('total') !!}{!! res('thai_currency') !!}</th>
                    <th class="text-center" >{!! res('manage') !!}</th>
                </tr>
            </thead>

            <tbody>
@php
    $n = fn ($v) => \App\Domain\Shop\Php74::num($v);
    $tAmount = 0; $tTotal = 0; $no = 0;
@endphp
@if (count($cart) === 0)<tr><td class="text-center" colspan="11" style="line-height: 100px;">{!! res('no_data') !!}</td></tr>@else
@foreach ($cart as $item)
@php
    $tAmount = $tAmount + $n($item->amount);
    $tTotal = $tTotal + ($n($item->price) * $n($item->amount));
    $no++;
@endphp
<tr><td>{{ $no }}.</td><td class="text-center"><a class="fancybox" target="_blank" href="{{ legacy_blob($item->main_image) }}">
                                <img src="{{ legacy_blob($item->main_image) }}" style="max-width: 46px;max-height: 46px;">
                            </a></td><td class="text-center">{{ $item->code }}</td><td >{{ $item->name }}</td><td >{{ $item->color.((string) $item->size !== '' ? ' - '.$item->size : '') }}</td><td class="text-center">{{ $item->amount }}</td><td class="text-right">{!! Format::numberformat($item->price) !!}</td><td class="text-right">{!! Format::numberformat($n($item->price) * $n($item->amount)) !!}</td><td class="text-center">
                                <a type="button" href="#" onclick="RemoveShopCart({{ $item->ids }});return false;" class="table-action-btn"><i class="md  md-cancel text-danger"></i></a>
                                </td></tr>
@endforeach
@endif
            </tbody>
            <tfoot>
                <th>
                    <td colspan="3" class="text-right">{!! res('total') !!}</td>
                    <td class="text-right">{{ \App\Domain\Shop\Php74::str($tAmount) }}</td>
                    <td class="text-right"></td>
                    <td class="text-right">{!! Format::numberformat($tTotal) !!}</td>
                    <td class="text-right"></td>
                </th>
            </tfoot>
        </table>


    </div>
</div>

<div class="col-xs-6 col-xs-offset-3 text-center">
    <button type="button" onclick="submitting()" class="btn btn-default form-control">{!! res('confirm_order_last') !!}</button>
</div>


<script>


function RemoveShopCart(index) {
    ConfirmBox(function() {
        $.ajax({
              type: 'POST',
              async: true,
              data : 'index=' + index,
              url: rooturl + '_mall/delete_cart',
              success: function(data) {
                eval(data);
              }
          });
    });
    
}


function submitting() {
    ConfirmBox(function() {
        $.ajax({
              type: 'POST',
              async: true,
              url: rooturl + '_mall/save',
              success: function(data) {
                eval(data);
              }
          });
    });
}
</script>
@endsection
