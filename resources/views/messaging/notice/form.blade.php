@use('App\Domain\Messaging\NoticeText')
{{-- _notice/form.php เดิม — เนื้อหาประกาศเป็น HTML ที่แอดมินเขียน (แสดงดิบแบบเดิม) --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('notice') !!} <small>{{ $prefix }}{{ $d['id'] ?? '' }}</small></h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}dashboard">{!! res('dashboard') !!}</a>
		</li>
		<li>
			{!! res('notice') !!}
		</li>
		<li class="active">
			{{ $prefix }}{{ $d['id'] ?? '' }}
		</li>
	</ol>
</div>


	<div class="col-sm-5 pull-right">
		<div class="button-list">
		    <a type="button" href="{{ legacy_href() }}notice" class="pull-right btn btn-warning waves-effect waves-light">
			    <span class="btn-label"><i class="fa fa-arrow-left"></i></span>
			    {!! res('back_to_list') !!}
		    </a>
		</div>
	</div>


	<div class="col-sm-12">
		<h2>{!! NoticeText::subject($d['create_date'] ?? '') !!}</h2>
		{!! NoticeText::decode($d['text'] ?? '') !!}
	</div>


<script>
	function sumiting() {
		if($("form").validationEngine('validate') == false) 
		{ 
			CloseLoading(); 
		} else {
			ConfirmBox(function () {
				$("form").eq(0).submit();
			});
		}
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

      setTimeout(function() { $("form").submit(); }, 300);      
    }

</script>
@endsection
