@foreach ($list as $img)
<div class="img_list_detail" style="float:left;margin-left: 10px;width:120px"><a style="float:left" class="fancybox" href="{{ legacy_blob($img) }}">
					<img src="{{ legacy_blob($img) }}" style="max-width: 50px;max-height: 50px;">
					<input type="hidden" name="upload_{{ $field }}_mul[]" value="{{ $img }}" />
					</a>
					<div onclick="$(this).parent().remove()" style="display:block;float:left;margin-left:10px;margin-top: 10px;"><div onclick="" class="btn btn-danger btn-xs">ลบ</div></div>
					</div>@endforeach
