{{-- SR: ช่องแนบรูปพัสดุในฟอร์มล็อต (form.php = $wrap true) / data[7] ของ load_tracking.php ($wrap false)
     $field = upload_track (ฟอร์ม) หรือ upload_trackquick (load_quickfile) — รูปเดิมของ tracking เป็น hidden {field}_mul[] (ลบออกจากรายการได้) --}}
@php($field = $field ?? 'upload_track')
@if ($wrap)
<div class="form-group">
						<label class="col-xs-3 control-label">{!! res('attachment') !!}</label>
						<div class="col-xs-7 ">
							{!! \App\Domain\Tracking\UploadBox::multiple($field, '', true) !!}
						</div>
					</div>

					<div class="form-group">
						<div class="col-xs-9 col-xs-offset-3" id="image_list">
						@include('lot.admin.image_list', ['list' => $list, 'field' => $field])
						</div>
					</div>
@else
<label class="col-xs-3 control-label">{!! res('attachment') !!}</label>
<div class="col-xs-7 ">
	{!! \App\Domain\Tracking\UploadBox::multiple($field, '', true) !!}</div>
		<div class="form-group">
		<div class="col-xs-9 col-xs-offset-3" id="image_list">
		@include('lot.admin.image_list', ['list' => $list, 'field' => $field])		</div>
	</div>
@endif
