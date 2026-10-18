<?php

namespace App\Domain\Points;

use App\Models\User;
use App\Support\Legacy\Alerts;
use App\Support\Legacy\Lang;
use Illuminate\Http\Request;

/**
 * สำเนาจาก App\Domain\Payment\PinBox (branch mod/payment) ให้ _point/save_withdraw ใช้ก่อน merge — ตอน merge ให้เปลี่ยนมาเรียกของ payment (JS ต้องเท่ากัน)
 *
 * กล่อง PIN จ่ายเงินของเว็บเดิม: getPinBox() / getNewPinBox() ใน system_status_function.php (เจ้าของ = กลุ่ม payment, CONVENTIONS ข้อ 8)
 * คืน JS ที่หน้าเว็บ eval() (สร้าง modal แล้วส่ง PIN ไป _profile/pin) — ข้อความ/JS เหมือนเดิมทุกตัวอักษร
 *
 * ต่างจากเดิม: $div / $callback ถูกฝังใน string JS แบบ "..." → escape (เดิมต่อดิบ) — ค่าคงที่จากโค้ดได้ผลเท่าเดิม
 * กลุ่มอื่นที่ต้องถาม PIN (balance/point/taobao/order/transfer/users) ใช้ PinBox::guard() แทนบล็อก
 *   if($u['pin']=="") getNewPinBox(); elseif(paymentpin=="") getPinBox(); elseif(paymentpin!=pin) getError(...)
 * ในกลุ่ม payment เอง ไฟล์เดิม (_payment/save_submit_payment.php, _alipay/save.php) comment การถาม PIN ไว้ → ไม่ถาม PIN เหมือนเดิม
 */
final class PinBox
{
    /** escape สำหรับค่าใน JS string แบบ "..." */
    private static function dq(string $s): string
    {
        return str_replace(['\\', '"', "\r", "\n", '</'], ['\\\\', '\\"', '\\r', '\\n', '<\\/'], $s);
    }

    public static function getPinBox(int|string $eq = 0, string $div = 'form', string $callback = ''): string
    {
        if ($div === '') {
            $div = 'form';
        }

        $pinmodal = '<div id="divpin"><div id="pinbox" class="pinbox modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;"><div class="modal-dialog modal-xs"><div class="modal-content p-0 b-0"><div class="panel panel-color panel-primary"><div class="panel-heading"> <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button> <h3 class="panel-title">'.Lang::get('pinbox').'</h3> </div><div class="panel-body"><div class="form-horizontal" role="form"><div class="form-group"><label class="col-xs-4 control-label">PIN : </label><div class="col-xs-4"><input type="password" id="textpaymentpin" class="form-control" /></div></div> <div class="form-group"> <div class="col-xs-12 text-danger text-center">* โปรดเข้าใจนี่คือขั้นตอนความปลอดภัยของการใช้เงินในระบบ *</div> </div><div class="form-group"> <div class="col-xs-12  text-center">หากท่านลืม PIN ของท่านกรุณาติดต่อโดยตรงที่ฝ่ายบริการเท่านั้น</div> </div></div></div> <div class="panel-footer text-right"><button type="button" class="btn btn-primary waves-effect waves-light" style="margin-right: 10px;" type="button" onclick="do_closepin()">'.Lang::get('close').'</button><button type="button" class="btn btn-default waves-effect" onclick="do_newpin()" type="button">'.Lang::get('ok').'</button></div></div></div></div></div></div>';

        $html = '';
        $html .= 'var htmlpin = \''.($pinmodal).'\'; ';
        $html .= '$("#divpin").remove();
			CloseLoading(); 
			setTimeout(function() { 
				do_closepin = function() { $(".pinbox").modal("hide"); setTimeout(function() { $("#divpin").remove(); }, 200); };
				do_newpin = function() { 
					$.ajax({
			            type: "POST",
			            async: true,
			            data : {
			            	pin : $("#textpaymentpin").val(),
			            	method : "verified",
			            	eq : '.(int) $eq.',
			            	div : "'.self::dq($div).'",
			            	callback : "'.self::dq($callback).'"
			            	},
			            url: rooturl + "_profile/pin",
			            success: function(s) {
			            	eval(s);
			            }
			        });
				 };
				$("#divpin").remove();
				$("'.self::dq($div).'").eq(0).append(htmlpin); 
				$(".pinbox").modal("show"); 
				$("#textpaymentpin").bind("keypress", {}, function (e) { 
					var code = (e.keyCode ? e.keyCode : e.which);
        			if (code == 13) { e.preventDefault(); }
    			});

			}, 200); ';

        return $html;
    }

    public static function getNewPinBox(string $div = 'form'): string
    {
        if ($div === '') {
            $div = 'form';
        }

        $pinmodal = '<div id="divpin"><div id="pinbox" class="pinbox modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;"><div class="modal-dialog modal-xs"><div class="modal-content p-0 b-0"><div class="panel panel-color panel-primary"><div class="panel-heading"> <button type="button" class="close m-t-5" data-dismiss="modal" aria-hidden="true">×</button> <h3 class="panel-title">'.Lang::get('newpinbox').'</h3> </div><div class="panel-body"><div class="form-horizontal" role="form"><div class="form-group"><label class="col-xs-4 control-label">PIN : </label><div class="col-xs-4"><input type="text" id="textpaymentpin" class="form-control" /></div></div><div class="form-group"><label class="col-xs-4 control-label">ยืนยัน PIN : </label><div class="col-xs-4"><input type="text" id="textconfirmpaymentpin" class="form-control" /></div></div> <div class="form-group"> <div class="col-xs-12 text-danger text-center">* โปรดเข้าใจนี่คือขั้นตอนความปลอดภัยของการใช้เงินในระบบ </div> </div><div class="form-group"> <div class="col-xs-12 text-danger text-center">* เพื่อความปลอดภัยกรุณาตั้ง PIN ให้ต่างกับรหัสผ่านเข้าสู่ระบบและโปรดเก็บรหัส PIN เป็นความลับ</div> </div></div></div> <div class="panel-footer text-right"><button type="button" class="btn btn-primary waves-effect waves-light" style="margin-right: 10px;" type="button" onclick="do_closepin()">'.Lang::get('close').'</button><button type="button" class="btn btn-default waves-effect" onclick="do_newpin()" type="button">'.Lang::get('ok').'</button></div></div></div></div></div></div>';

        $html = '';
        $html .= 'var htmlpin = \''.($pinmodal).'\'; ';
        $html .= '$("#divpin").remove();
			CloseLoading(); 
			setTimeout(function() { 
				do_closepin = function() { $(".pinbox").modal("hide"); setTimeout(function() { $("#divpin").remove(); }, 200); };
				do_newpin = function() { 
					$.ajax({
			            type: "POST",
			            async: true,
			            data :  {
			            	pin : $("#textpaymentpin").val(),
			            	confirmpin : $("#textconfirmpaymentpin").val(),
			            	method : "new"
			            	},
			            url: rooturl + "_profile/pin",
			            success: function(s) {
			            	eval(s);
			            }
			        });
				 };
				$("#divpin").remove();
				$("'.self::dq($div).'").eq(0).append(htmlpin); 
				$(".pinbox").modal("show"); 
				$("#textpaymentpin").bind("keypress", {}, function (e) { 
					var code = (e.keyCode ? e.keyCode : e.which);
        			if (code == 13) { e.preventDefault(); }
    			});
    			$("#textconfirmpaymentpin").bind("keypress", {}, function (e) { 
					var code = (e.keyCode ? e.keyCode : e.which);
        			if (code == 13) { e.preventDefault(); }
    			});

			}, 200); ';

        return $html;
    }

    /**
     * บล็อกตรวจ PIN แบบเดิม — คืน JS ที่ต้องตอบ (ยังไม่มี PIN / ยังไม่ได้กรอก / ผิด) หรือ null = ผ่าน
     * PIN ผิด → getError("CloseLoading()", "PIN จ่ายเงินไม่ถูกต้อง") (Alerts::error นับเป็นความพยายามผิดใน ips เหมือนเดิม)
     */
    public static function guard(Request $request, ?User $user, string $div = 'form', int $eq = 0, string $callback = ''): ?string
    {
        $pin = (string) ($user?->getRawOriginal('pin') ?? '');
        if ($pin === '') {
            return self::getNewPinBox($div);
        }
        $sent = $request->request->all()['paymentpin'] ?? '';
        $sent = is_scalar($sent) ? (string) $sent : '';
        if ($sent === '' || $sent === 'undefined') {
            return self::getPinBox($eq, $div, $callback);
        }
        if (! hash_equals($pin, $sent)) {
            return Alerts::error('CloseLoading()', 'PIN จ่ายเงินไม่ถูกต้อง');
        }

        return null;
    }
}
