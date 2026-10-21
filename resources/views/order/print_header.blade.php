{{-- print_header.php (ใช้ร่วม: reports — _admin/_export/print) — ไม่มี <html>/<head> ครอบ เหมือนไฟล์เดิม --}}
<meta charset="utf-8">

<link href="{{ legacy_href() }}assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="{{ legacy_href() }}assets/css/core.css" rel="stylesheet" type="text/css" />
<link href="{{ legacy_href() }}assets/css/components.css" rel="stylesheet" type="text/css" />
<link href="{{ legacy_href() }}assets/css/icons.css" rel="stylesheet" type="text/css" />
<link href="{{ legacy_href() }}assets/css/pages.css" rel="stylesheet" type="text/css" />
<link href="{{ legacy_href() }}assets/css/responsive.css" rel="stylesheet" type="text/css" />

<style>
    .content {
        margin-top: 0px!important;
        margin-bottom:  0px!important;
    }
    .content-page {
        margin-left: 0px!important;
    }
    .footer {
        position: relative!important;
        right: auto!important;
        left: auto!important;
    }
    .lot_table_cus th , .lot_table_cus td {
        padding: 2px 2px;
        font-size: 17px!important;
    }
</style>
<script src="{{ legacy_href() }}assets/js/jquery.min.js?v={{ config('legacy.version') }}"></script>
<script>
$(function() {
    $(".currency").each(function() {
        var v = $(this).text();
        $(this).text(addCommas(v));
    });
})

function addCommas(nStr)
{
    nStr += '';
    x = nStr.split('.');
    x1 = x[0];
    x2 = x.length > 1 ? '.' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + ',' + '$2');
    }
    return x1 + x2;
}
</script>
