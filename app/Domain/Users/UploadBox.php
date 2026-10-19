<?php

namespace App\Domain\Users;

use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;

/**
 * getupload() ของ system_config.php เดิม (กล่องแนบไฟล์ dropper → system_upload)
 *
 * เจ้าของตาม CONVENTIONS ข้อ 8 คือกลุ่ม settings (ใช้ร่วม) — ยังไม่มีในฐานร่วม จึงพอร์ตไว้ที่นี่
 * (สำเนาเดียวกับ App\Domain\Tracking\UploadBox::single) ตอน merge ให้เปลี่ยนไปเรียกของกลาง (HTML ต้องเท่ากัน)
 * กลุ่ม users ใช้แค่แบบแสดงผล ($isDelete = false) ในหน้าโปรไฟล์/ฟอร์มผู้ใช้ของแอดมิน
 * $field ถูกฝังเป็นชื่อฟังก์ชัน JS → ต้องเป็นค่าคงที่ในโค้ดเท่านั้น; $filename มาจาก DB → escape (เดิมต่อดิบ)
 */
final class UploadBox
{
    public static function single(string $rand = 'path', ?string $filename = '', bool $isDelete = false, bool $isValidate = true, string $class = 'col-xs-12'): string
    {
        $filename = (string) $filename;
        $href = Legacy::href();
        $blob = Legacy::hrefBlob();
        $del = Lang::get('delete');
        $f = e($filename);
        $html = '<div class="'.$class.'"> ';
        $html .= '<div style="float:left" class="text-left upload_'.$rand.'"></div>';
        $html .= '<div style="float:left;clear: both;margin-top: 10px;" class=" upload_'.$rand.'_preview">';
        if ($filename !== '') {
            if (Format::uploadIsImage($filename)) {
                $html .= "<a class='fancybox' style='float:left;' href='".$blob.$f."'><img src='".$blob.$f."' style='max-width: 120px;max-height: 120px;' /></a>";
            } else {
                $html .= "<a style='float:left;' download='".$f."' href='".$blob.$f."'>".$f.'</a>';
            }
        }
        $html .= '</div>';
        $display = ($filename !== '' && $isDelete) ? 'block' : 'none';
        $html .= '<div class="upload_'.$rand.'_delete" style="display:'.$display.';float:left;margin-left:10px;margin-top: 10px;"><div onclick="$(this).parent().css(\'display\', \'none\');$(\'#upload_'.$rand.'\').val(\'\');$(\'.upload_'.$rand.'_preview\').html(\'\')" class="btn btn-danger btn-xs">'.$del.'</div></div>';
        if ($isValidate) {
            $html .= '<input type="hidden" class="validate[requiredattachment]" id="upload_'.$rand.'" name="upload_'.$rand.'" value="'.$f.'" />';
        } else {
            $html .= '<input type="hidden" id="upload_'.$rand.'" name="upload_'.$rand.'" value="'.$f.'" />';
        }
        $html .= '</div>';
        if (! $isDelete) {
            return $html;
        }

        $html .= '<script>';
        $html .= '$(".upload_'.$rand.'").dropper({';
        $html .= 'action: rooturl + \'system_upload\',';
        $html .= 'label: "'.Lang::get('click_to_add_attachment').'",';
        $html .= 'maxQueue : 1,';
        $html .= '}).on("start.dropper", '.$rand.'_start).on("fileComplete.dropper", '.$rand.');';
        $html .= '$(".upload_'.$rand.'").find(".dropper-input").on("change.reselect", function() { var el = this; setTimeout(function() { el.value = ""; }, 0); });';
        $html .= 'function '.$rand.'(e, file, response) {';
        $html .= ' if (response.trim() === "" || response.toLowerCase().indexOf("error") > -1) {';
        $html .= ' ErrorBox(response.toLowerCase(), "CloseLoading()"); $(".upload_'.$rand.'_preview").html("");$("input[name=\'upload_'.$rand.'\']").val("") ';
        $html .= ' } else {';
        $html .= ' if(/\.(jpg|jpeg|png)$/i.test(response)) { ';
        $html .= ' $(".upload_'.$rand.'_preview").html(\'<a class="fancybox" style="float:left" href="'.$href.'upload/temp/\' + response + \'"><img src="'.$href.'upload/temp/\' + response + \'" style="max-width: 120px;max-height: 120px;" /></a>\'); ';
        $html .= ' } else {';
        $html .= ' $(".upload_'.$rand.'_preview").html(\'<a style="float:left" download href="'.$href.'upload/temp/\' + response + \'">\' + response + \'</a>\'); ';
        $html .= ' } ';
        $html .= ' $("input[name=\'upload_'.$rand.'\']").val(response);';
        $html .= ' $(".upload_'.$rand.'_delete").css("display", "block");';
        $html .= ' }';
        $html .= '}';
        $html .= 'function '.$rand.'_start(e, file, response) {';
        $html .= ' $(".upload_'.$rand.'_preview").html("<img src=\''.$href.'assets/images/loading.gif\' />"); ';
        $html .= ' $(".upload_'.$rand.'_delete").css("display", "none");';
        $html .= '}';
        $html .= '</script>';

        return $html;
    }
}
