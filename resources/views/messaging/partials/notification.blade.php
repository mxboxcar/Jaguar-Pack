{{-- notification.php เดิม: แถบแจ้งเตือนด้านบน ($html สร้างใน NotificationController — ค่าทั้งหมดเป็นตัวเลข/prefix จากระบบ) --}}
{!! $html !!}
@if($order)
<li class="">
    <a href="{{ legacy_href() }}?l=&page=checkout_product" class="waves-effect waves-light"><i class="icon-basket"></i> </a>
</li>
@endif


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
