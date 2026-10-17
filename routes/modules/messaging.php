<?php

use App\Http\Controllers\Messaging\Admin\AdminMessageController;
use App\Http\Controllers\Messaging\Admin\AdminNoticeController;
use App\Http\Controllers\Messaging\Admin\AdminSendController;
use App\Http\Controllers\Messaging\IncludeFileController;
use App\Http\Controllers\Messaging\LineNotifyController;
use App\Http\Controllers\Messaging\MessageController;
use App\Http\Controllers\Messaging\NoticeController;
use App\Http\Controllers\Messaging\NotificationController;
use Illuminate\Support\Facades\Route;

/*
| กลุ่ม: messaging — _message, _admin/_message, _notice, _admin/_notice, _linenotify, _admin/_send,
|        notification(s).php, notify_counts.php, notify_token.php, email_header/center/footer.php
| ไฟล์นี้ถูกโหลดอัตโนมัติใต้ middleware group "legacy" (session เว็บเดิม + jail + CSRF แบบ Origin)
| รายละเอียด/สิ่งที่ต่างจากเดิม: docs/modules/messaging.md
|
| สิทธิ์ = getPermission() ของไฟล์เดิม; ไฟล์เดิมที่ไม่ตรวจสิทธิ์ ใช้สิทธิ์ของหน้าแม่ (บันทึกเป็นช่องโหว่เดิมที่แก้)
*/

// ---- ลูกค้า: แชท (_message) — เดิมหน้า/ajax ไม่ตรวจสิทธิ์ (เมนูแสดงเมื่อมีสิทธิ์ message) ----
Route::middleware('legacy.auth')->group(function () {
    Route::get('/message', [MessageController::class, 'index'])->defaults('legacy_page', 'message');
    Route::post('/_message/load', [MessageController::class, 'load']);
    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_message/load_dept', [MessageController::class, 'loadDept']);
        Route::post('/_message/send', [MessageController::class, 'send']);
        Route::post('/_message/send_endconversation', [MessageController::class, 'sendEndconversation']);
    });
});
Route::post('/_message/save_file', [MessageController::class, 'saveFile'])->middleware(['legacy.perm:message', 'legacy.ajax']);

// ---- แอดมิน: แชท (_admin/_message) ----
Route::middleware('legacy.perm:admin_message')->group(function () {
    Route::get('/admin/message', [AdminMessageController::class, 'index'])->defaults('legacy_page', 'admin_message');
    Route::post('/_admin/_message/load', [AdminMessageController::class, 'load']);
    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_admin/_message/load_dept', [AdminMessageController::class, 'loadDept']);
        Route::post('/_admin/_message/load_user', [AdminMessageController::class, 'loadUser']);
        Route::post('/_admin/_message/send', [AdminMessageController::class, 'send']);
        Route::post('/_admin/_message/send_endconversation', [AdminMessageController::class, 'sendEndconversation']);
        Route::post('/_admin/_message/deletechat', [AdminMessageController::class, 'deletechat']);
        Route::post('/_admin/_message/save_file', [AdminMessageController::class, 'saveFile']);
    });
});

// ---- ลูกค้า: ประกาศ (_notice) ----
Route::middleware('legacy.auth')->group(function () {
    Route::get('/notice', [NoticeController::class, 'index'])->defaults('legacy_page', 'notice');
    Route::get('/notice/{id}', [NoticeController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'notice_detail');
    Route::post('/_notice/grid', [NoticeController::class, 'grid'])->middleware('legacy.ajax');
});

// ---- แอดมิน: ประกาศ (_admin/_notice) ----
Route::middleware('legacy.perm:admin_notice')->group(function () {
    Route::get('/admin/notice', [AdminNoticeController::class, 'index'])->defaults('legacy_page', 'admin_notice');
    Route::get('/admin/notice/create', [AdminNoticeController::class, 'form'])->defaults('legacy_page', 'admin_notice_detail');
    Route::get('/admin/notice/{id}', [AdminNoticeController::class, 'form'])->whereNumber('id')->defaults('legacy_page', 'admin_notice_detail');
    Route::get('/_admin/_notice/email', [AdminNoticeController::class, 'email']);
    Route::get('/_admin/_notice/email_notice', [AdminNoticeController::class, 'emailNotice']);
    Route::get('/_admin/_notice/cron_notice_mail', [AdminNoticeController::class, 'cron']);
    Route::middleware('legacy.ajax')->group(function () {
        Route::post('/_admin/_notice/grid', [AdminNoticeController::class, 'grid']);
        Route::post('/_admin/_notice/save', [AdminNoticeController::class, 'save']);
        Route::post('/_admin/_notice/save_isshow', [AdminNoticeController::class, 'saveIsshow']);
    });
});
// เดิม delete.php ตรวจสิทธิ์ admin_product_type (คัดลอกมาจาก _admin/_product_type) — คงสิทธิ์เดิม
Route::post('/_admin/_notice/delete', [AdminNoticeController::class, 'delete'])->middleware(['legacy.perm:admin_product_type', 'legacy.ajax']);

// ---- แอดมิน: รายการรอส่ง (_admin/_send) ----
Route::middleware('legacy.perm:admin_export')->group(function () {
    Route::get('/admin/send', [AdminSendController::class, 'index'])->defaults('legacy_page', 'admin_send');
    Route::get('/_admin/_send/index_self', [AdminSendController::class, 'indexSelf']);
});

// ---- ลูกค้า: LINE Notify (_linenotify) ----
Route::middleware('legacy.perm:profile')->group(function () {
    Route::get('/linenotify', [LineNotifyController::class, 'index'])->defaults('legacy_page', 'linenotify');
    Route::get('/_linenotify/callback', [LineNotifyController::class, 'callback']);
    Route::get('/_linenotify/revoke', [LineNotifyController::class, 'revoke']);
});

// ---- แถบแจ้งเตือน + ไฟล์ชิ้นส่วน (เดิมเปิดได้โดยไม่ล็อกอิน: ชิ้นส่วนอีเมล/notify_* — ที่นี่ต้องล็อกอิน) ----
Route::middleware('legacy.auth')->group(function () {
    Route::post('/notifications', [NotificationController::class, 'count'])->middleware('legacy.ajax');
    Route::post('/notification', [NotificationController::class, 'bar'])->middleware('legacy.ajax');
    Route::match(['GET', 'POST'], '/notify_counts', [IncludeFileController::class, 'blank']);
    Route::match(['GET', 'POST'], '/notify_token', [IncludeFileController::class, 'blank']);
    Route::get('/email_header', [IncludeFileController::class, 'emailHeader']);
    Route::get('/email_center', [IncludeFileController::class, 'emailCenter']);
    Route::get('/email_footer', [IncludeFileController::class, 'emailFooter']);
});
