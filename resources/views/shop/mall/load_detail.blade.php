{{-- _mall/load_detail.php เดิม — detail = HTML จาก CKEditor ที่แอดมินเขียน (ตั้งใจแสดงเป็น HTML แบบเดิม) --}}

<style>
	#product_details img {
		max-width: 500px!important;
    	height: auto!important;
	}

	#product_details input
</style>
<div class="form-horizontal col-xs-12" role="form">
	<div class="form-group text-left" style="text-indent: 40px">
		{!! nl2br(e($data->desc ?? null)) !!}
	</div>

	<div class="form-group" style="margin-top:10px;">
	    <label class="col-md-2 control-label"> {!! res('amount') !!}</label>
        <div class="col-md-2 " >
        	<input class="form-control" type="text" name="amount" value="1" />
        </div>
	</div>
	<div class="form-group" >
	    <label class="col-md-2 control-label"> {!! res('size') !!}</label>
        <div class="col-md-10 form-control-static" >
@foreach ($details as $it)
@php $st = $stock[$it->id] ?? 0; @endphp
            <div class="radio radio-primary" style="border-bottom: 1px dashed #ccc;">
                <input {!! \App\Domain\Shop\Php74::num($st) <= 0 ? "disabled='disabled'" : '' !!} type="radio" name="size" id="radio_size_{{ $it->id }}" value="{{ $it->id }}" ref="{{ $it->price }}">
                <label for="radio_size_{{ $it->id }}">{{ $it->color.((string) $it->size !== '' ? ' - '.$it->size : '') }}</label>

                <span style="float:right;">เหลือ {{ $st }} ชิ้น&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{!! \App\Support\Legacy\Format::numberformat($it->price) !!}</span>
            </div>
@endforeach
        </div>
	</div>

	<div class="form-group text-center" style="margin-top:10px;">
	    <button class="btn btn-primary" onclick="addtocart()">ซื้อตอนนี้!</button>
	</div>

	<div class="form-group text-center" id="product_details" style="margin-top:20px;">
	    {!! html_entity_decode((string) ($data->detail ?? ''), ENT_COMPAT | ENT_HTML401) !!}
	</div>
</div>

<script>
	$("#product_details").css('width', ($(".mall_detail_form .modal-dialog").width() - 50) + 'px').css('float', 'left');
	$("input").each(function() { 
		var t = $(this);
		if($(this).attr('src') != undefined) {
			t.css('max-width', '500px');
			t.css('height', 'auto');
			t.css('clear', 'both');
			t.css('float', 'left');
			t.parent().css('text-align', 'center');
		}
	});
	function addtocart() {
		var val = $("input[name='size']:checked").val();
		if(val == undefined) {
			ErrorBox('{!! res('add_more_then_one') !!}');
		} else {
			ConfirmBox(function() {
				$.ajax({
		            type: 'POST',
		            async: true,
		            data : { id : val, amount : $("input[name='amount']").val() },
		            url: '{{ legacy_href() }}_mall/addtocart',
		            success: function(data) {
		                eval(data);
		            }
		        });
			});
		}
	}
</script>