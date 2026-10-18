{{-- _admin/_notice/form.php เดิม (สิทธิ์ admin_notice ตรวจที่ middleware) --}}
@extends('legacy.layout')

@section('content')
@if($id === 0)
<div class="col-sm-7">
	<h4 class="page-title">{!! res('notice') !!}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('notice') !!}
		</li>
		<li class="active">
			{!! res('notice_new') !!}
		</li>
	</ol>
</div>
@else
<div class="col-sm-7">
	<h4 class="page-title">{!! res('notice_edit') !!} <small>{{ $prefix }}{{ $d['id'] ?? '' }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('notice') !!}
		</li>
		<li class="active">
			{{ $prefix }}{{ $d['id'] ?? '' }}
		</li>
	</ol>
</div>
@endif
<script type="text/javascript" src="{{ legacy_href() }}assets/ckeditor/ckeditor.js"></script>
<form action="{{ legacy_href() }}_admin/_notice/save" method="post">

<input type="hidden" name="id" value="{{ $d['id'] ?? '' }}">
<input type="hidden" name="tabtext" value="" class="tabtext" />

	<div class="col-sm-5 pull-right">
		<div class="button-list">
			<button onclick="saving()" type="button" class="pull-right btn btn-success waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-save"></i></span>
			    {!! res('save_button') !!}
		    </button>

		    <a type="button" href="{{ legacy_href() }}admin/notice" class="pull-right btn btn-warning waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
			    {!! res('back_to_list') !!}
		    </a>
		</div>
	</div>


	<div class="col-sm-12">
		<div class="card-box">
			<h4 class="m-t-0 header-title"><b>{!! res('detail') !!}</b></h4>
			<p class="text-muted font-13 m-b-30">
	        </p>
			<div class="form-horizontal" role="form">
				@if($id !== 0)
				<div class="form-group">
		            <label class="col-md-2 control-label"> {!! res('no_for_grid') !!}</label>
		            <div class="col-md-6  form-control-static">
		                {{ $prefix }}{{ $d['id'] ?? '' }}
		            </div>
		        </div>         
		        @endif                           
		        
		        <div class="form-group">
		            <label class="col-md-2 control-label"> </label>
		            <div class="col-md-4">
		                <div class="checkbox checkbox-primary">
                            <input name="is_show" type="checkbox" value="1" @if((int) ($d['is_show'] ?? 0) === 1)checked="checked"@endif>
                            <label for="checkbox1">
                                {!! res('active') !!}
                            </label>
                        </div>

		            </div>
		        </div>

		        <div class="form-group">
		            <label class="col-md-2 control-label"> {!! res('text') !!}</label>
		            <div class="col-md-10">
		                <textarea class="form-control" name="txtMessage" rows="10">{!! preg_replace('~</textarea~i', '&lt;/textarea', (string) ($d['text'] ?? '')) !!}</textarea>
		            </div>
		        </div>


		        
		    </div>
		</div>
	</div>

</form>

<script>
	function sumiting() {
		
	}


	$("form").validationEngine();
	$("form").ajaxForm({ 
	    success: function(data) {
	        eval(data);
	    }
	});

	CKEDITOR.replace('txtMessage');

    function htmlEscape(str) {
        return str
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/([\uE000-\uF8FF]|\uD83C[\uDF00-\uDFFF]|\uD83D[\uDC00-\uDDFF])/g, '');
    }

    function htmlUnescape(str){
      return str
        .replace(/&quot;/g, '"')
        .replace(/&#39;/g, "'")
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/&amp;/g, '&');
    }

    function saving() {
      var editor = htmlEscape(CKEDITOR.instances['txtMessage'].getData());
      $(".tabtext").val(editor);

      setTimeout(function() { 
	      	if($("form").validationEngine('validate') == false) 
			{ 
				CloseLoading(); 
			} else {
				ConfirmBox(function () {
					$("form").eq(0).submit();
				});
			}
       }, 300);      
    }

</script>
@endsection
