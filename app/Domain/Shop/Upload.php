<?php

namespace App\Domain\Shop;

use App\Support\Legacy\Alerts;
use App\Support\Legacy\Format;
use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;

/**
 * ส่วนอัปโหลดไฟล์ที่กลุ่ม shop ใช้
 *   getupload()  = getupload() ของ system_config.php เดิม (HTML + JS dropper) — ผล HTML เหมือนเดิมทุกตัวอักษร
 *                  (CONVENTIONS ข้อ 8: เจ้าของจริงคือกลุ่ม settings — ถ้า settings พอร์ตแล้วให้เปลี่ยนมาใช้ของ settings)
 *   blobJs()     = JS ที่ save*.php เดิม echo ออกไปให้หน้าเว็บยิง _api/blockblob_ajax ต่อ (เมื่อมีไฟล์ใน upload/temp)
 */
final class Upload
{
    /** ชื่อไฟล์ใน upload/temp เท่านั้น (เดิมต่อ path จาก POST ตรงๆ → อ่าน/เช็คไฟล์นอกโฟลเดอร์ได้) */
    public static function tempPath(string $file): ?string
    {
        $name = basename($file);
        if ($name === '' || $name === '.' || $name === '..') {
            return null;
        }

        return rtrim((string) config('legacy.upload.temp_dir'), '/').'/'.$name;
    }

    public static function tempExists(string $file): bool
    {
        $p = self::tempPath($file);

        return $p !== null && file_exists($p);
    }

    /**
     * if (file_exists('../upload/temp/'.$file)) { echo "$.ajax({ ... _api/blockblob_ajax ... })"; }
     * $prefix = path ที่ไฟล์เดิมใส่ใน data (เดิม '../upload/temp/' — blockblob_ajax ใช้ basename อยู่แล้ว)
     */
    public static function blobJs(string $file, string $prefix = '../upload/temp/'): string
    {
        if ($file === '' || ! self::tempExists($file)) {
            return '';
        }

        return "$.ajax({ \n                type: 'POST', \n                async: true, \n                data : 'path=".Alerts::js($prefix.$file)."', \n                url: rooturl + '_api/blockblob_ajax', \n                success: function(data) { \n                  \n                } });";
    }

    /** getupload($field, $filename, $is_delete, $is_validate, $class) เดิม */
    public static function getupload(string $field = 'path', string $filename = '', bool $isDelete = false, bool $isValidate = true, string $class = 'col-xs-12'): string
    {
        $e = fn (string $s) => htmlspecialchars($s, ENT_QUOTES | ENT_HTML401);
        $rand = $field; // ค่าจากโค้ดเท่านั้น (ชื่อ field)
        $href = Legacy::href();
        $html = '<div class="'.$class.'"> ';
        $html .= '<div style="float:left" class="text-left upload_'.$rand.'"></div>';
        $html .= '<div style="float:left;clear: both;margin-top: 10px;" class=" upload_'.$rand.'_preview">';
        if ($filename !== '') {
            $blob = $e(Legacy::hrefBlob($filename));
            if (Format::uploadIsImage($filename)) {
                $html .= "<a class='fancybox' style='float:left;' target='_blank' href='".$blob."'><img src='".$blob."' style='max-width: 120px;max-height: 120px;' /></a>";
            } else {
                $html .= "<a style='float:left;' target='_blank' download='".$e($filename)."' href='".$blob."'>".$e($filename).'</a>';
            }
        }
        $html .= '</div>';
        $display = ($filename !== '' && $isDelete) ? 'block' : 'none';
        $html .= '<div class="upload_'.$rand.'_delete" style="display:'.$display.';float:left;margin-left:10px;margin-top: 10px;"><div onclick="$(this).parent().css(\'display\', \'none\');$(\'#upload_'.$rand.'\').val(\'\');$(\'.upload_'.$rand.'_preview\').html(\'\')" class="btn btn-danger btn-xs">'.Lang::get('delete').'</div></div>';
        if ($isValidate) {
            $html .= '<input type="hidden" class="validate[requiredattachment]" id="upload_'.$rand.'" name="upload_'.$rand.'" value="'.$e($filename).'" />';
        } else {
            $html .= '<input type="hidden" id="upload_'.$rand.'" name="upload_'.$rand.'" value="'.$e($filename).'" />';
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
            $html .= ' $(".upload_'.$rand.'_preview").html(\'<a class="fancybox" style="float:left" target="_blank" href="'.$href.'upload/temp/\' + response + \'"><img src="'.$href.'upload/temp/\' + response + \'" style="max-width: 120px;max-height: 120px;" /></a>\'); ';
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
