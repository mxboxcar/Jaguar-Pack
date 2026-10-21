<?php

namespace App\Domain\Reports;

use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;

/**
 * getupload() ของ system_config.php เดิมของ PACK (HTML + JS ของกล่องแนบไฟล์ dropper → system_upload)
 *
 * PACK ใช้ getupload() รุ่นเก่าแบบ jl (ไม่มี hotfix ของ SR เรื่องเลือกไฟล์เดิมซ้ำ/แสดงไฟล์ที่ไม่ใช่รูปเป็นลิงก์):
 * - ไฟล์เดิม: นามสกุล jpg/png/jpeg (ตัวเล็กหรือตัวใหญ่ทั้งคำ) = รูป fancybox, อื่นๆ = ลิงก์ชื่อไฟล์ class fancybox
 * - อัปโหลดเสร็จ: zip/rar/7z = ใส่ข้อความที่ system_upload ตอบลงกล่อง, อื่นๆ = รูปจาก upload/temp
 * ใช้ในกลุ่มเดียว (_admin/_export/form.php: getupload("file", export_attachment, true)) จึงอยู่ในกลุ่ม
 * ต่างจากเดิม: ไม่มี target="_blank" (นโยบายเจ้าของ), $filename มาจาก DB → escape; $field ถูกฝังเป็นชื่อฟังก์ชัน JS → ค่าคงที่ในโค้ดเท่านั้น
 */
final class UploadBox
{
    /** $cool_extensions เดิม (in_array แบบ PHP 7.4 กับ pathinfo EXTENSION) */
    private const IMAGE_EXT = ['jpg', 'png', 'jpeg', 'JPG', 'JPEG', 'PNG'];

    public static function single(string $field = 'path', ?string $filename = '', bool $isDelete = false, bool $isValidate = true, string $class = 'col-xs-12'): string
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
            if (in_array(pathinfo($filename, PATHINFO_EXTENSION), self::IMAGE_EXT, true)) {
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
        $html .= 'maxQueue : 1,';
        $html .= '}).on("start.dropper", '.$rand.'_start).on("fileComplete.dropper", '.$rand.');';
        $html .= 'function '.$rand.'(e, file, response) {';
        $html .= ' if (response.trim() === "" || response.toLowerCase().indexOf("error") > -1) {';
        $html .= ' ErrorBox(response.toLowerCase(), "CloseLoading()"); $(".upload_'.$rand.'_preview").html("");$("input[name=\'upload_'.$rand.'\']").val("") ';
        $html .= ' } else {';
        $html .= ' if(response.toLowerCase().indexOf(".zip") > -1 || response.toLowerCase().indexOf(".rar") > -1 || response.toLowerCase().indexOf(".7z") > -1) { ';
        $html .= ' $(".upload_'.$rand.'_preview").html(response); ';
        $html .= ' } else {';
        $html .= ' $(".upload_'.$rand.'_preview").html(\'<a class="fancybox" style="float:left" href="'.$href.'upload/temp/\' + response + \'"><img src="'.$href.'upload/temp/\' + response + \'" style="max-width: 120px;max-height: 120px;" /></a>\'); ';
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
