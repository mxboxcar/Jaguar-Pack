<?php

namespace App\Domain\MoneyMove;

use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;

/**
 * getupload() / getuploadmultiple() ของ system_config.php เดิม (HTML + JS ของกล่องแนบไฟล์ dropper → system_upload)
 *
 * ตาม CONVENTIONS ข้อ 8 เจ้าของคือกลุ่ม settings (ใช้ร่วม) — ยังไม่มีในฐานร่วม จึงพอร์ตไว้ที่นี่(สำเนาจากกลุ่ม tracking) ให้ refill / transfer / withdrawcash ใช้ก่อน
 * ตอน merge ถ้า settings มีของกลางแล้ว ให้เปลี่ยนมาเรียกของกลาง (HTML ต้องเท่ากัน)
 * $field ถูกฝังเป็นชื่อฟังก์ชัน JS → ต้องเป็นค่าคงที่ในโค้ดเท่านั้น; $filename มาจาก DB → escape
 */
final class UploadBox
{
    public static function single(string $field = 'path', ?string $filename = '', bool $isDelete = false, bool $isValidate = true, string $class = 'col-xs-12'): string
    {
        return self::build($field, (string) $filename, $isDelete, $isValidate, $class, false);
    }

    public static function multiple(string $field = 'path', ?string $filename = '', bool $isDelete = false, bool $isValidate = true, string $class = 'col-xs-12'): string
    {
        return self::build($field, (string) $filename, $isDelete, $isValidate, $class, true);
    }

    private static function build(string $rand, string $filename, bool $isDelete, bool $isValidate, string $class, bool $multi): string
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
        $html .= 'maxQueue : '.($multi ? '10' : '1').',';
        $html .= '}).on("start.dropper", '.$rand.'_start).on("fileComplete.dropper", '.$rand.');';
        $html .= '$(".upload_'.$rand.'").find(".dropper-input").on("change.reselect", function() { var el = this; setTimeout(function() { el.value = ""; }, 0); });';
        $html .= 'function '.$rand.'(e, file, response) {';
        $html .= ' if (response.trim() === "" || response.toLowerCase().indexOf("error") > -1) {';
        $html .= ' ErrorBox(response.toLowerCase(), "CloseLoading()"); $(".upload_'.$rand.'_preview").html("");$("input[name=\'upload_'.$rand.'\']").val("") ';
        $html .= ' } else {';
        $html .= ' if(/\.(jpg|jpeg|png)$/i.test(response)) { ';
        if ($multi) {
            $html .= ' $(".upload_'.$rand.'_preview").append(\'<div class="image_list_items"><a class="fancybox" style="float:left" href="'.$href.'upload/temp/\' + response + \'"><input type="hidden" class="" id="upload_'.$rand.'" name="upload_'.$rand.'_mul[]" value="\' + response + \'"><img src="'.$href.'upload/temp/\' + response + \'" style="max-width: 120px;max-height: 120px;" /></a><div onclick="$(this).parent().remove()" style="display:block;float:left;margin-left:10px;margin-top: 10px;"><div onclick="" class="btn btn-danger btn-xs">'.$del.'</div></div></div>\'); ';
            $html .= ' } else {';
            $html .= ' $(".upload_'.$rand.'_preview").append(\'<div class="image_list_items"><a style="float:left" download href="'.$href.'upload/temp/\' + response + \'"><input type="hidden" class="" id="upload_'.$rand.'" name="upload_'.$rand.'_mul[]" value="\' + response + \'">\' + response + \'</a><div onclick="$(this).parent().remove()" style="display:block;float:left;margin-left:10px;margin-top: 10px;"><div onclick="" class="btn btn-danger btn-xs">'.$del.'</div></div></div>\'); ';
            $html .= ' } ';
            $html .= ' $("input[name=\'upload_'.$rand.'\']").val(response);';
            $html .= ' }';
            $html .= '}';
            $html .= 'function '.$rand.'_start(e, file, response) {';
            $html .= '}';
        } else {
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
        }
        $html .= '</script>';

        return $html;
    }
}
