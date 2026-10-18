{{-- _admin/_lot/load_lot_memo.php เดิม --}}
@php($eq = fn ($a, $b) => \App\Domain\Lot\Php74::eq($a, $b))

<div class="col-xs-12">
	<div class="card-box col-xs-12">
		<h4 class="m-t-0 header-title"><b>{!! res('memo') !!}</b> </h4>
		<p class="text-muted font-13 m-b-30">
        </p>
        <div class="form-horizontal" role="form"> 
	        <div class="col-xs-6">
	        	<div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('no') !!}</label>
		            <div class="col-xs-4">
		            	<div class="input-group">
                            <input type="text" class="form-control" id="no" value="{{ \App\Domain\Lot\Php74::str($nextNo) }}"/>
                            <span class="input-group-addon">-</span>
                            <input type="text" class="form-control" id="no_max" />
                        </div>
		            </div>
		        </div>
                <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('weight') !!}</label>
		            <div class="col-xs-6">
                        <div class="input-group">
                            <input type="text" class="form-control" id="memo_weight" />
                            <span class="input-group-addon">{!! res('kg') !!}</span>
                        </div>
                    </div>
		        </div>
                <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('cubic') !!}</label>
		            <div class="col-xs-6">
                        <div class="input-group">
                            <input type="text" class="form-control" id="memo_cubic" />
                            <span class="input-group-addon">{!! res('cubic') !!}</span>
                        </div>
                    </div>
		        </div>
            </div>

            <div class="col-xs-6">
	        	<div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('detail') !!}</label>
		            <div class="col-xs-6">
                        <input type="text" class="form-control" id="memo_detail" />
		            </div>
		        </div>
                <div class="form-group">
		            <label class="col-xs-4 control-label"> {!! res('customer_code') !!}</label>
		            <div class="col-xs-6">
                        <input type="text" class="form-control autocomplete2" id="memo_code" />
		            </div>
		        </div>
            </div>


            <div class="col-xs-6">
                <label class="col-xs-4 control-label"></label>
                <div class="col-xs-6">
                    <button class="btn btn-primary" type="button" onclick="on_addmemo()">{!! res('add_new_list') !!}</button>
                </div>
	        	
            </div>
        </div>


        <table class="lot_table" style="margin-top:20px;">
            <thead>
                <tr>
                    <th style="width:50px;"  class="text-center"></th>
                    <th style="width:100px;"  class="text-center">{!! res('no') !!}</th>
                    <th style="width:100px;"  class="text-center">{!! res('to') !!}</th>
                    <th class="text-center">{!! res('detail') !!}</th>
                    <th class="text-center">{!! res('weight') !!}</th>
                    <th class="text-center">{!! res('cubic') !!}</th>
                    <th class="text-center">{!! res('customer_code') !!}</th>
                </tr>
            </thead>
            <tbody style="width:100%">
                @foreach ($memos as $item)<tr style="width:100%"><td style="width:50px;"  class="text-center"><span class="fa fa-remove text-danger cursor" onclick="on_deletememo({{ $item->id }})" ></span><input type="hidden" name="memo_id[]" value="{{ $item->id }}" /></td><td style="width:100px;"  class="sorting_tracking text-center">{{ $item->no }}<input type="hidden" name="memo_no[]" value="{{ $item->no }}"/></td>@if ($eq($item->no, $item->no_max))<td style="width:100px;"  class="sorting_tracking text-center">-<input type="hidden" name="memo_nomax[]" value="{{ $item->no }}"/></td>@else<td style="width:100px;"  class="sorting_tracking text-center">{{ $item->no_max }}<input type="hidden" name="memo_nomax[]" value="{{ $item->no_max }}"/></td>@endif<td class="text-left"><input type="hidden" name="memo_detail[]" value="{{ $item->detail }}" />{{ $item->detail }}</td><td class="text-right"><input type="hidden" name="memo_weight[]" value="{{ $item->weight }}" />{{ $item->weight }}</td><td class="text-right"><input type="hidden" name="memo_cubic[]" value="{{ $item->cubic }}" />{{ $item->cubic }}</td><td class="text-center"><input type="hidden" name="memo_code[]" value="{{ $item->user_id }}" />{{ $item->code }}</td></tr>@endforeach
            </tbody>
        </table>
    </div>
</div>



<script>
$('.autocomplete2').typeahead({
  source: function (query, process) {
    $.ajax({
      url: rooturl + '_admin/_lot/load_autocomplete_user',
      type: 'POST',
      dataType: 'JSON',
      data: 'query=' + query,
      success: function(data) {
        process(data.code);
      }
    })
  }
});
</script>
