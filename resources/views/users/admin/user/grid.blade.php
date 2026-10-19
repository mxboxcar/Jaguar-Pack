{{-- _admin/_user/grid.php เดิม — $s = txtsearch แยกด้วย | (ค่าในตัวแปร PHP เดิม) --}}
@php($eq = [\App\Domain\Users\Php74::class, 'eq'])

<div class="row">
    <div class="col-lg-12">
        <div class="card-box">
            <div class="row m-t-10 m-b-10">
                <div class="col-xs-10 col-sm-6 col-lg-6">
                    <div class="form-group contact-search m-b-30">
                        <input type="text" id="txtsearch" onblur="GenerateWhere();" class="form-control" placeholder="{!! res('search') !!}" value="{{ $s[0] ?? '' }}">
                        <button type="submit" class="btn btn-white" onclick="Javascript:loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());"><i class="fa fa-search"></i></button>
                    </div> <!-- form-group -->
                </div>

                <div class="col-xs-2 col-sm-2 col-lg-2">
                    <select name="role" class="form-control " id="role" onchange="GenerateWhere()">
<option value="-1">- {!! res('all') !!} -</option>@foreach ($roles as $row)@if ($eq($row->id, $s[1] ?? null))<option selected="selected" value="{{ $row->id }}">{{ $row->role_name }}</option>@else<option value="{{ $row->id }}">{{ $row->role_name }}</option>@endif @endforeach
                    </select>
                </div>

                <div class="col-xs-2 col-sm-2 col-lg-2">
                    <select name="role" class="form-control " id="user_type" onchange="GenerateWhere()">
<option value="-1">- {!! res('all') !!} -</option>@foreach ($userTypes as $row)@if ($eq($row->id, $s[2] ?? null))<option selected="selected" value="{{ $row->id }}">{{ $row->name }}</option>@else<option value="{{ $row->id }}">{{ $row->name }}</option>@endif @endforeach
                    </select>
                </div>

                <div class="col-xs-2 col-sm-2 col-lg-2">
                    <select name="approved" class="form-control " id="approved" onchange="GenerateWhere()">
<option value="-2">- {!! res('all') !!} -</option>@if ($eq($s[3] ?? null, -1))<option selected="selected" value="-1">{!! res('unapproveddd') !!}</option>@else<option value="-1">{!! res('cancel') !!}</option>@endif @if ($eq($s[3] ?? null, 0) && ($s[3] ?? '') !== '')<option selected="selected" value="0">{!! res('unapproveddd') !!}</option>@else<option value="0">{!! res('unapproveddd') !!}</option>@endif @if ($eq($s[3] ?? null, 1))<option selected="selected" value="1">{!! res('approveddd') !!}</option>@else<option value="1">{!! res('approveddd') !!}</option>@endif

                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th  class="text-center" style="width: 105px;">{!! res('no_for_grid') !!}</th>
                            <th class="text-center" style="width: 100px;">{!! res('customer_code') !!}</th>
                            <th class="text-center">{!! res('name_lastname') !!}</th>
                            <th class="text-center">{!! res('email') !!}</th>
                            <th class="text-center">{!! res('phone') !!}</th>
                            <th class="text-center">{!! res('role') !!}</th>
                            <th class="text-center">{!! res('user_type') !!}</th>
                            <th class="text-center" style="width: 60px;">{!! res('action') !!}</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td><a href="{{ legacy_href() }}admin/user/{{ $item->id }}">{{ $prefix }}{{ $item->id }}</a></td>
                            <td>{{ $item->code }}</td>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->email }}</td>
                            <td>{{ $item->phone }}</td>
                            <td class="text-center">{{ $item->role_name }}</td>
                            <td class="text-center">
                            {{ $eq($item->option_price, 1) ? $item->parent_type_name : $item->user_type_name }}

                             </td>
                            <td class="text-center">
                                <a href="{{ legacy_href() }}admin/user/{{ $item->id }}" class="table-action-btn"><i class="md  md-mode-edit text-primary "></i></a>
                                <a type="button" href="#" onclick="Delete('{{ $item->id }}');return false;" class="table-action-btn"><i class="md  md-cancel text-danger"></i></a>
                            </td>
                        </tr>
                    @empty
<td class="text-center" colspan="20">{!! res('no_data') !!}</td>
                    @endforelse
                    </tbody>
                </table>
                <div class="pull-right">
                    {!! \App\Support\Legacy\Paging::getpaging($page, $rows, 10, $ajaxpage) !!}
                </div>
            </div>
        </div>

    </div> <!-- end col -->
</div>

<input type="hidden" id="Checking" />
<input type="hidden" id="txtSearch" value="{{ $txtsearch }}">

<script>
    function keypressInBox(e) {
        GenerateWhere();
        var code = (e.keyCode ? e.keyCode : e.which);
        if (code == 13) { //Enter keycode
            e.preventDefault();
            loadAjaxGrid('{{ $ajaxpage }}', '1',  $('#txtSearch').val());
        }
    }
    $("#txtsearch").bind("keypress", {}, keypressInBox);

    function GenerateWhere() {
        $("#txtSearch").val($("#txtsearch").val() + "|" + $("#role").val() + '|' + $("#user_type").val() + '|' + $("#approved").val());
    }

    function Delete(id) {
        fn_public = "loadAjaxGrid('{{ $ajaxpage }}', '{{ $pageJs }}',  $('#txtSearch').val());";
        ConfirmBox(function() {
            $.ajax({
                type: 'POST',
                async: true,
                data : 'id=' + id,
                url: rooturl + '{{ $ajaxpage }}/../delete',
                success: function(data) {
                    eval(data);
                }
            });
        });
    }

</script>
