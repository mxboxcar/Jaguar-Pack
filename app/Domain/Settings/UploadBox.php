<?php

namespace App\Domain\Settings;

use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;

/**
 * getupload() / getupload_inline() / getuploadmultiple() ของ system_config.php เดิม — กล่องแนบไฟล์ (jQuery dropper → POST /system_upload)
 * HTML/JS ตรงกับเดิมทุกตัวอักษร ยกเว้นชื่อไฟล์จาก DB ถูก escape (เดิมต่อดิบ = stored XSS ผ่านชื่อไฟล์)
 * $field / $class มาจากโค้ดเท่านั้น (ถูกฝังเป็นชื่อฟังก์ชัน JS / class) ห้ามส่งค่าจากผู้ใช้
 *
 * ใช้ร่วมได้ทุกกลุ่ม: {!! \App\Domain\Settings\UploadBox::getupload('path', $row->path, true) !!}
 */
final class UploadBox
{
    public static function getupload(string $field = 'path', ?string $filename = '', bool $isDelete = false, bool $isValidate = true, string $class = 'col-xs-12'): string
    {
        return self::render('single', $field, (string) $filename, $isDelete, $isValidate, $class);
    }

    public static function getuploadInline(string $field = 'path', ?string $filename = '', bool $isDelete = false, bool $isValidate = true, string $class = 'col-xs-12'): string
    {
        return self::render('inline', $field, (string) $filename, $isDelete, $isValidate, $class);
    }

    public static function getuploadmultiple(string $field = 'path', ?string $filename = '', bool $isDelete = false, bool $isValidate = true, string $class = 'col-xs-12'): string
    {
        return self::render('multiple', $field, (string) $filename, $isDelete, $isValidate, $class);
    }

    private static function render(string $variant, string $rand, string $filename, bool $isDelete, bool $isValidate, string $class): string
    {
        $href = Legacy::href();
        $del = Lang::get('delete');
        $f = htmlspecialchars($filename, ENT_QUOTES | ENT_HTML401, 'UTF-8');
        $blob = htmlspecialchars(Legacy::hrefBlob($filename), ENT_QUOTES | ENT_HTML401, 'UTF-8');

        $html = '<div class="'.$class.'"> ';
        $html .= '<div style="float:left" class="text-left upload_'.$rand.'"></div>';
        $html .= $variant === 'inline'
            ? '<div style="float:left;clear: none;margin-left: 10px;" class=" upload_'.$rand.'_preview">'
            : '<div style="float:left;clear: both;margin-top: 10px;" class=" upload_'.$rand.'_preview">';
        if ($filename !== '') {
            if (Format::uploadIsImage($filename)) {
                $html .= "<a class='fancybox' style='float:left;' target='_blank' href='".$blob."'><img src='".$blob."' style='max-width: 120px;max-height: 120px;' /></a>";
            } else {
                $html .= "<a style='float:left;' target='_blank' download='".$f."' href='".$blob."'>".$f.'</a>';
            }
        }
        $html .= '</div>';
        $onclick = 'onclick="$(this).parent().css(\'display\', \'none\');$(\'#upload_'.$rand.'\').val(\'\');$(\'.upload_'.$rand.'_preview\').html(\'\')"';
        if ($filename !== '' && $isDelete) {
            $html .= '<div class="upload_'.$rand.'_delete" style="display:block;float:left;margin-left:10px;margin-top: 10px;"><div '.$onclick.' class="btn btn-danger btn-xs">'.$del.'</div></div>';
        } else {
            $top = $variant === 'inline' ? '0px' : '10px';
            $html .= '<div class="upload_'.$rand.'_delete" style="display:none;float:left;margin-left:10px;margin-top: '.$top.';"><div '.$onclick.' class="btn btn-danger btn-xs">'.$del.'</div></div>';
        }
        if ($isValidate) {
            $html .= '<input type="hidden" class="validate[requiredattachment]" id="upload_'.$rand.'" name="upload_'.$rand.'" value="'.$f.'" />';
        } else {
            $html .= '<input type="hidden" id="upload_'.$rand.'" name="upload_'.$rand.'" value="'.$f.'" />';
        }
        $html .= '</div>';
        if (! $isDelete) {
            return $html;
        }

        $temp = $href.'upload/temp/';
        $html .= '<script>';
        $html .= '$(".upload_'.$rand.'").dropper({';
        $html .= 'action: rooturl + \'system_upload\',';
        $html .= 'label: "'.Lang::get('click_to_add_attachment').'",';
        $html .= 'maxQueue : '.($variant === 'multiple' ? '10' : '1').',';
        $html .= '}).on("start.dropper", '.$rand.'_start).on("fileComplete.dropper", '.$rand.');';
        $html .= '$(".upload_'.$rand.'").find(".dropper-input").on("change.reselect", function() { var el = this; setTimeout(function() { el.value = ""; }, 0); });';
        $html .= 'function '.$rand.'(e, file, response) {';
        $html .= ' if (response.trim() === "" || response.toLowerCase().indexOf("error") > -1) {';
        $html .= ' ErrorBox(response.toLowerCase(), "CloseLoading()"); $(".upload_'.$rand.'_preview").html("");$("input[name=\'upload_'.$rand.'\']").val("") ';
        $html .= ' } else {';
        $html .= ' if(/\.(jpg|jpeg|png)$/i.test(response)) { ';
        if ($variant === 'multiple') {
            $html .= ' $(".upload_'.$rand.'_preview").append(\'<div class="image_list_items"><a class="fancybox" style="float:left" target="_blank" href="'.$temp.'\' + response + \'"><input type="hidden" class="" id="upload_'.$rand.'" name="upload_'.$rand.'_mul[]" value="\' + response + \'"><img src="'.$temp.'\' + response + \'" style="max-width: 120px;max-height: 120px;" /></a><div onclick="$(this).parent().remove()" style="display:block;float:left;margin-left:10px;margin-top: 10px;"><div onclick="" class="btn btn-danger btn-xs">'.$del.'</div></div></div>\'); ';
            $html .= ' } else {';
            $html .= ' $(".upload_'.$rand.'_preview").append(\'<div class="image_list_items"><a style="float:left" download target="_blank" href="'.$temp.'\' + response + \'"><input type="hidden" class="" id="upload_'.$rand.'" name="upload_'.$rand.'_mul[]" value="\' + response + \'">\' + response + \'</a><div onclick="$(this).parent().remove()" style="display:block;float:left;margin-left:10px;margin-top: 10px;"><div onclick="" class="btn btn-danger btn-xs">'.$del.'</div></div></div>\'); ';
        } else {
            $size = $variant === 'inline' ? '21px' : '120px';
            $html .= ' $(".upload_'.$rand.'_preview").html(\'<a class="fancybox" style="float:left" target="_blank" href="'.$temp.'\' + response + \'"><img src="'.$temp.'\' + response + \'" style="max-width: '.$size.';max-height: '.$size.';" /></a>\'); ';
            $html .= ' } else {';
            $html .= ' $(".upload_'.$rand.'_preview").html(\'<a style="float:left" download target="_blank" href="'.$temp.'\' + response + \'">\' + response + \'</a>\'); ';
        }
        $html .= ' } ';
        $html .= ' $("input[name=\'upload_'.$rand.'\']").val(response);';
        if ($variant !== 'multiple') {
            $html .= ' $(".upload_'.$rand.'_delete").css("display", "block");';
        }
        $html .= ' }';
        $html .= '}';
        $html .= 'function '.$rand.'_start(e, file, response) {';
        if ($variant !== 'multiple') {
            $html .= ' $(".upload_'.$rand.'_preview").html("<img src=\''.$href.'assets/images/loading.gif\' />"); ';
            $html .= ' $(".upload_'.$rand.'_delete").css("display", "none");';
        }
        $html .= '}';
        $html .= '</script>';

        return $html;
    }
}
