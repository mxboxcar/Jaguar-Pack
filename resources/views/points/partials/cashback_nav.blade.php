{{-- ปุ่ม taobao / shop_cashback / balance_cn ด้านบนของหน้า shop_cashback เดิม ($base = '' ลูกค้า, 'admin/' แอดมิน) --}}
<div class="col-sm-12">
	<div class="button-list text-center">
		<a type="button" href="{{ legacy_href() }}{{ $base }}taobao" class=" btn btn-inverse  waves-effect waves-light">
		    <span class="btn-label"><i class="fa  fa-list-ol"></i></span>
		    {!! res('taobao') !!}
	    </a>
	    <a type="button" href="{{ legacy_href() }}{{ $base }}shop_cashback" class=" btn btn-primary  waves-effect waves-light">
		    <span class="btn-label"><i class="fa  fa-undo"></i></span>
		    {!! res('shop_cashback') !!}
	    </a>
	    <a type="button" href="{{ legacy_href() }}{{ $base }}balance_cn" class=" btn btn-inverse  waves-effect waves-light">
		    <span class="btn-label"><i class="fa fa-cny"></i></span>
		    {!! res('balance_cn') !!}
	    </a>
	</div>
</div>
