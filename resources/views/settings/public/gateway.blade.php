{{-- gateway.php เดิม (ประกาศย้ายระบบปี 2017 — สาธารณะ ไม่มีข้อมูล, เดิมไม่มีใครลิงก์มาแล้ว) --}}
<meta charset="utf-8">
<script src="{{ legacy_href() }}assets/js/jquery.min.js"></script>
<script src="{{ legacy_href() }}assets/cookie/jquery.cookie.js?version="></script>

<div style="text-align: center;font-family: arial;font-size: 13px;">
<img style="width: 270px;" src="{{ legacy_href() }}assets/images/logo.png" />
<br />
<p><strong>ประกาศจาก GoGo-Cargo</strong></p>

<p>หลังจากวันที่ 12 กุมภาพันธ์ 2017 บริษัทจะเปลี่ยนแปลงการให้บริการ เริ่มใช้ระบบใหม่ </p>
<p>ซึ่งลูกค้ายังสามารถสร้างรายการชำระเงินของระบบเก่าเพื่อเคลียร์รายการที่ค้างอยู่ภายในระบบเก่า</p>
<p>หลังจากเคลียร์รายการที่ค้างแล้ว รบกวนลูกค้าทุกท่านเข้าไปสมัครสมาชิกที่ <a href="#" target="_blank" onclick="setcookie(0);return false;">http://portal.gogo-cargo.com</a></p>
<br />
<p> หากลูกค้าต้องการแสดงความจำนงโอนย้าย<u>ยอดเงิน</u>ที่คงค้างอยู่ในระบบเก่าไประบบใหม่ </p>
<p>สามารถยื่นหลักฐานต่างๆได้ที่ gogo-cargo@hotmail.com</p>
<br />
<p>การเข้าถึงระบบเก่าจะเปลี่ยนจาก 
<a href="#" target="_blank" onclick="setcookie(0);return false;">http://portal.gogo-cargo.com</a> เป็น 
<a href="#" target="_blank" onclick="setcookie(1);return false;">http://old.gogo-cargo.com</a></p>
<p>การเข้าถึงระบบใหม่ 
<a href="#" onclick="setcookie(0);return false;">http://portal.gogo-cargo.com</a></p>

<br />
<br />
<p>ทีมงานหวังว่าจะได้รับการตอบรับจากลูกค้าที่ดีขึ้น ทางทีมงานขอให้คำมั่นสัญญาว่าจะดูแลลูกค้าให้ดีที่สุด</p>
</div>

<script>
function setcookie(value) {
	$.cookie("{{ config('legacy.cookies.portal') }}", 1);
	if(value == 0) {
		window.location.href = 'http://portal.gogo-cargo.com';
	} else {
		window.location.href = 'http://old.gogo-cargo.com';
	}
}
</script>
