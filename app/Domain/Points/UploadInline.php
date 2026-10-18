<?php

namespace App\Domain\Points;

use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;

/**
 * getupload_inline() ของ system_config.php เดิม (กล่องแนบไฟล์แบบแถวเดียวในตาราง → dropper → system_upload)
 *
 * เจ้าของตาม CONVENTIONS ข้อ 8 คือกลุ่ม settings (ยังไม่มีในฐานร่วม) → สำเนาไว้ใช้ใน _shop_cashback/form + load_new_line ก่อน
 * ตอน merge ถ้ามีของกลางแล้วให้เปลี่ยนมาเรียกของกลาง (HTML ต้องเท่ากัน)
 *
 * $field ถูกฝังเป็นชื่อ class และชื่อฟังก์ชัน JS → ต้องเป็นค่าจากโค้ดเท่านั้น (ผู้เรียกส่ง 'file_'.(int) เสมอ)
 * เดิม _shop_cashback/load_new_line.php ส่ง $_POST['item'] ดิบเข้ามา = XSS
 */
final class UploadInline
{
    public static function render(string $field = 'path', ?string $filename = '', bool $isDelete = false, bool $isValidate = true, string $class = 'col-xs-12'): string
    {
        $rand = $field;
        $filename = (string) $filename;
        $href = Legacy::href();
        $blob = Legacy::hrefBlob();
        $f = e($filename);

        $html = '<div class="'.$class.'"> ';
        $html .= '<div style="float:left" class="text-left upload_'.$rand.'"></div>';
        $html .= '<div style="float:left;clear: none;margin-left: 10px;" class=" upload_'.$rand.'_preview">';
        if ($filename !== '') {
            if (Format::uploadIsImage($filename)) {
                $html .= "<a class='fancybox' style='float:left;' target='_blank' href='".$blob.$f."'><img src='".$blob.$f."' style='max-width: 120px;max-height: 120px;' /></a>";
            } else {
                $html .= "<a style='float:left;' target='_blank' download='".$f."' href='".$blob.$f."'>".$f.'</a>';
            }
        }
        $html .= '</div>';
        $del = Lang::get('delete');
        if ($filename !== '' && $isDelete) {
            $html .= '<div class="upload_'.$rand.'_delete" style="display:block;float:left;margin-left:10px;margin-top: 10px;"><div onclick="$(this).parent().css(\'display\', \'none\');$(\'#upload_'.$rand.'\').val(\'\');$(\'.upload_'.$rand.'_preview\').html(\'\')" class="btn btn-danger btn-xs">'.$del.'</div></div>';
        } else {
            $html .= '<div class="upload_'.$rand.'_delete" style="display:none;float:left;margin-left:10px;margin-top: 0px;"><div onclick="$(this).parent().css(\'display\', \'none\');$(\'#upload_'.$rand.'\').val(\'\');$(\'.upload_'.$rand.'_preview\').html(\'\')" class="btn btn-danger btn-xs">'.$del.'</div></div>';
        }
        if ($isValidate) {
            $html .= '<input type="hidden" class="validate[requiredattachment]" id="upload_'.$rand.'" name="upload_'.$rand.'" value="'.$f.'" />';
        } else {
            $html .= '<input type="hidden" id="upload_'.$rand.'" name="upload_'.$rand.'" value="'.$f.'" />';
        }
        $html .= '</div>';
        if ($isDelete) {
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
            $html .= ' $(".upload_'.$rand.'_preview").html(\'<a class="fancybox" style="float:left" target="_blank" href="'.$href.'upload/temp/\' + response + \'"><img src="'.$href.'upload/temp/\' + response + \'" style="max-width: 21px;max-height: 21px;" /></a>\'); ';
            $html .= ' } else {';
            $html .= ' $(".upload_'.$rand.'_preview").html(\'<a style="float:left" download target="_blank" href="'.$href.'upload/temp/\' + response + \'">\' + response + \'</a>\'); ';
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
        }

        return $html;
    }
}
