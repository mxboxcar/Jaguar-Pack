{{-- _taobao/load_new_line.php เดิม --}}

<tr>
	<td class="no"></td>
	<td><input style="width: 100%;" name="order[]" type="text" class="" value=""></td>
	<td>{!! res('cn_currency') !!}<input style="width: 80%;margin-left:3px;" onkeyup="CalculatePrice();"  name="price[]" type="text" class="" value=""></td>
	<td class="text-center">
		<button type="button" class="btn-danger" onclick="on_deleteline($(this))"><i class="md md-exposure-minus-1"></i></button>
	</td>
</tr>