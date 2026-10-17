{{-- _admin/_lot/load_ref_print.php เดิม --}}
@php($eq = fn ($a, $b) => \App\Domain\Lot\Php74::eq($a, $b))

<div class="row">
<input type="hidden" value="{{ $id }}" id="search_id" />
<div class="form-group col-xs-12">
<input type="text" class="form-control" value="" id="search_lot" placeholder="{!! res('search') !!}" /></div>
</div>
<select id="selected_print" class="form-control" multiple style="height:162px;">@foreach ($lots as $item)<option {!! $eq($item->id, $id) ? 'selected="selected" ' : '' !!}value="{{ $item->id }}">{!! $eq($item->type, 0) ? res('car') : res('ship') !!}/{{ $item->name }}</option>@endforeach</select>
