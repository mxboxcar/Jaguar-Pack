@use('App\Support\Legacy\Format')
@use('App\Domain\Messaging\NoticeText')
{{-- notification.php เดิม: แถบแจ้งเตือนด้านบน ($html สร้างใน NotificationController — ค่าทั้งหมดเป็นตัวเลข/prefix จากระบบ) --}}
{!! $html !!}
@if($order)
<li class="">
    <a href="{{ legacy_href() }}?l=&page=checkout_product" class="waves-effect waves-light"><i class="icon-basket"></i> </a>
</li>
@endif


{{-- SR: กระดิ่งประกาศล่าสุด --}}
<li class="dropdown triggermenu">
    <a href="#" data-target="#" class="dropdown-toggle waves-effect waves-light" data-toggle="dropdown" aria-expanded="false">

        <i class="icon-bell"></i> 
        @if($notice)
        <span class="badge badge-xs badge-danger">1</span>
       	@endif
    </a>
    <ul class="dropdown-menu dropdown-menu-lg">
        <li class="notifi-title">{!! res('notice_list') !!}</li>
        <li class="list-group nicescroll notification-list" tabindex="5000" style="overflow-x:hidden;overflow-y: scroll; outline: none;">
           <!-- list item-->
           	@if(! $notice)
           		<a style="white-space:normal!important;" href="javascript:void(0);" class="list-group-item">
	                <small>{!! res('no_data') !!}</small>
	           </a>
           		
           	@else
           		<div style="white-space:normal!important;" href="javascript:void(0);" class="list-group-item">
           		<div style="font-weight: bold">{!! Format::getthaidate(explode(' ', (string) $notice->create_date)[0], false, true) !!}</div>
                <small>&nbsp;&nbsp;&nbsp;&nbsp;{!! substr(strip_tags(NoticeText::decode($notice->text)), 0, 1000) !!}...</small>
                <div class="text-center" style="font-weight: bold;margin: 10px;"><a href='{{ legacy_href() }}notice'>{!! res('view_all') !!}</a></div>

                
	           </div>
           	@endif
           
           
        </li>
        
    <div id="ascrail2000" class="nicescroll-rails" style="width: 8px; z-index: 1000; cursor: default; position: absolute; top: 52.1429px; left: 292px; height: 230px; display: block; opacity: 0;"><div style="position: relative; top: 0px; float: right; width: 6px; height: 150px; background-color: rgb(152, 166, 173); border: 1px solid rgb(255, 255, 255); background-clip: padding-box; border-radius: 5px;"></div></div><div id="ascrail2000-hr" class="nicescroll-rails" style="height: 8px; z-index: 1000; top: 274.143px; left: 0px; position: absolute; cursor: default; display: none; opacity: 0; width: 292px;"><div style="position: relative; top: 0px; height: 6px; width: 300px; background-color: rgb(152, 166, 173); border: 1px solid rgb(255, 255, 255); background-clip: padding-box; border-radius: 5px; left: 0px;"></div></div></ul>
</li>

<li class="dropdown">
    <a href="" class="dropdown-toggle profile" data-toggle="dropdown" aria-expanded="true"><i class="fa fa-language m-r-5"></i></a>
    <ul class="dropdown-menu">
        <li class="{{ $langCookie === 'th' ? 'active' : '' }}"><a href="#" onclick="Language('th');return false;">ภาษาไทย</a></li>
        <li class="{{ $langCookie === 'cn' ? 'active' : '' }}"><a href="#" onclick="Language('cn');return false;">中国</a></li>
    </ul>
</li>

<li class="dropdown">
    <a href="" class="dropdown-toggle profile" data-toggle="dropdown" aria-expanded="true">{{ $username }}</a>
    <ul class="dropdown-menu">
        <li><a href="{{ legacy_href() }}profile"><i class="fa fa-sliders m-r-5"></i> {!! res('edit_profile') !!}</a></li>
        <li><a href="#" onclick="Logout();return false;"><i class="ti-power-off m-r-5"></i> {!! res('logout') !!}</a></li>
    </ul>
</li>
<script src="{{ legacy_href() }}assets/cart/js/main.js"></script>
