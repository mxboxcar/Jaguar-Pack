@foreach ($images as $img)<div class="img_list_detail" style="float:left;margin-left: 10px;width:85px"><a style="float:left" class="fancybox" target="_blank" href="{{ legacy_blob((string) $img) }}">
				                <img src="{{ legacy_blob((string) $img) }}" style="max-width: 50px;max-height: 50px;">
				                <input type="hidden" name="img[]" value="{{ $img }}" />
				                </a>
				                
				                </div>@endforeach
