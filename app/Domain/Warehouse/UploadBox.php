<?php

namespace App\Domain\Warehouse;

use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;

/**
 * getupload() (system_config.php) / getuploadmultiple() (SR: system_status_function.php) เดิม (HTML + JS ของกล่องแนบไฟล์ dropper → system_upload)
 *
 * ตาม CONVENTIONS ข้อ 8 เจ้าของคือกลุ่ม settings (ใช้ร่วม) — ยังไม่มีในฐานร่วม จึงพอร์ตไว้ที่นี่ให้ warehouse_section / goodsconfirm ใช้ก่อน (สำเนาจากกลุ่ม tracking)
 * ตอน merge ถ้า settings มีของกลางแล้ว ให้เปลี่ยนมาเรียกของกลาง (HTML ต้องเท่ากัน)
 * $field ถูกฝังเป็นชื่อฟังก์ชัน JS → ต้องเป็นค่าคงที่ในโค้ดเท่านั้น; $filename มาจาก DB → escape
 */
final class UploadBox
{
    public static function single(string $field = 'path', ?string $filename = '', bool $isDelete = false, bool $isValidate = true, string $class = 'col-xs-12'): string
    {
        return self::build($field, (string) $filename, $isDelete, $isValidate, $class);
    }

    /**
     * SR: getuploadmultiple() อยู่ใน system_status_function.php และต่างจาก jl —
     * ไฟล์เดิมที่เป็นรูปดูจากนามสกุล jpg/png/jpeg (ตัวเล็ก/ใหญ่) ไม่ใช่รูป = ลิงก์ fancybox ชื่อไฟล์, หลังอัปโหลด: .zip/.rar/.7z
     * ต่อท้ายชื่อไฟล์ + ปุ่มลบ ไม่งั้นเป็นรูปตัวอย่าง (ไม่มีตัวล้างช่อง input ของ jl) — ไม่เปิดแท็บใหม่ (เจ้าของ)
     * พารามิเตอร์ $callback/$callbackremove เดิมไม่มีใครส่ง (และ eval ฝั่ง PHP) → ไม่พอร์ต
     */
    public static function multiple(string $field = 'path', ?string $filename = '', bool $isDelete = false, bool $isValidate = true, string $class = 'col-xs-12'): string
    {
        $rand = $field;
        $filename = (string) $filename;
        $href = Legacy::href();
        $blob = Legacy::hrefBlob();
        $del = Lang::get('delete');
        $f = e($filename);
        $html = '<div class="'.$class.'"> ';
        $html .= '<div style="float:left" class="text-left upload_'.$rand.'"></div>';
        $html .= '<div style="float:left;clear: both;margin-top: 10px;" class=" upload_'.$rand.'_preview">';
        if ($filename !== '') {
            if (in_array(pathinfo($filename, PATHINFO_EXTENSION), ['jpg', 'png', 'jpeg', 'JPG', 'JPEG', 'PNG'], true)) {
                $html .= "<a class='fancybox' style='float:left;' href='".$blob.$f."'><img src='".$blob.$f."' style='max-width: 120px;max-height: 120px;' /></a>";
            } else {
                $html .= "<a class='fancybox' style='float:left;' href='".$blob.$f."'>".$f.'</a>';
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
        $html .= 'maxQueue : 10,';
        $html .= '}).on("start.dropper", '.$rand.'_start).on("fileComplete.dropper", '.$rand.');';
        $html .= 'function '.$rand.'(e, file, response) {';
        $html .= ' if (response.trim() === "" || response.toLowerCase().indexOf("error") > -1) {';
        $html .= ' ErrorBox(response.toLowerCase(), "CloseLoading()"); $(".upload_'.$rand.'_preview").html("");$("input[name=\'upload_'.$rand.'\']").val("") ';
        $html .= ' } else {';
        $html .= ' if(response.toLowerCase().indexOf(".zip") > -1 || response.toLowerCase().indexOf(".rar") > -1 || response.toLowerCase().indexOf(".7z") > -1) { ';
        $html .= ' $(".upload_'.$rand.'_preview ").append(response + \'<div class="upload_'.$rand.'_delete" style="display:block;float:left;margin-left:10px;margin-top: 10px;"><div onclick="" class="btn btn-danger btn-xs">'.$del.'</div></div>\'); ';
        $html .= ' } else {';
        $html .= ' $(".upload_'.$rand.'_preview").append(\'<div class="image_list_items"><a class="fancybox" style="float:left" href="'.$href.'upload/temp/\' + response + \'"><input type="hidden" class="" id="upload_'.$rand.'" name="upload_'.$rand.'_mul[]" value="\' + response + \'"><img src="'.$href.'upload/temp/\' + response + \'" style="max-width: 120px;max-height: 120px;" /></a><div onclick="$(this).parent().remove(); " style="display:block;float:left;margin-left:10px;margin-top: 10px;"><div onclick="" class="btn btn-danger btn-xs">'.$del.'</div></div></div>\'); ';
        $html .= ' } ';
        $html .= ' $("input[name=\'upload_'.$rand.'\']").val(response);';
        $html .= ' }';
        $html .= '}';
        $html .= 'function '.$rand.'_start(e, file, response) {';
        $html .= '}';
        $html .= '</script>';

        return $html;
    }

    private static function build(string $rand, string $filename, bool $isDelete, bool $isValidate, string $class): string
    {
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
