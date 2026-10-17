{{-- _admin/_report/form.php เดิม --}}
@extends('legacy.layout')

@section('content')
<div class="col-sm-7">
	<h4 class="page-title">{!! res('report') !!} - {{ $name }}</h4>
	<ol class="breadcrumb">
		<li>
			<a href="{{ legacy_href() }}admin/dashboard">{!! res('administrator') !!}</a>
		</li>
		<li>
			{!! res('report') !!}
		</li>
		<li class="active">
			{{ $name }}
		</li>
	</ol>
</div>

<div id="gridAjax" class="col-sm-12"></div>
<script type="text/javascript">
    loadAjaxGrid('_admin/_report/grid', 1, '{{ $id }}[]');
</script>
@endsection
