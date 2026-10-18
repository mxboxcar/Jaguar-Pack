<?php

/*
| ค่าคงที่ของเว็บเดิม (system_config.php / system_status_function.php) ที่ Laravel ต้องใช้ให้ตรงกัน
| ห้ามใส่ secret ตรงนี้ — secret ทุกตัวอ่านจาก .env หรือไฟล์นอก repo เท่านั้น
*/

return [

    // define('href', ...) เดิม: URL หลักของพอร์ทัล ต้องลงท้ายด้วย /
    // dev ปล่อยว่าง = ใช้ APP_URL ของ request ปัจจุบัน (จะได้ใช้ได้ทั้ง :8100 และ worktree :82xx)
    'href' => env('LEGACY_HREF'),

    // define('href_blob', ...) เดิม: ที่อยู่ไฟล์แนบ (Azure blob ผ่าน CDN)
    'href_blob' => env('LEGACY_HREF_BLOB', 'https://image.jlcargoth.com/upload/'),

    'project_name' => 'JLcargo ',
    'proj_name' => 'JLcargo',
    'admin_email' => 'jlcargoth@hotmail.com',
    'version' => '2',
    'cookie_path' => '/',

    // ชื่อ cookie เดิม (ต้องตรงเป๊ะ เว็บเดิมกับใหม่ใช้ร่วมกัน)
    'cookies' => [
        'main' => 'jlcargo',
        'role' => 'jlcargo-role',
        'lang' => 'jlcargo-lang',
        'menu' => 'jlcargo-menu',
        'admin_menu' => 'jl-cargo-admin-menu',
        'cart' => 'jlcargo-cart',
        'portal' => 'jlcargo-portal',
    ],

    // บัญชีรับโอนที่แสดงให้ลูกค้าเห็น (ไม่ใช่ความลับ — define payment_acc_* เดิม)
    'payment_account' => [
        'owner' => env('LEGACY_PAYMENT_ACC_OWNER', 'wiriyapunt wiriya'),
        'no' => env('LEGACY_PAYMENT_ACC_NO', '7582200463'),
        'bank' => env('LEGACY_PAYMENT_ACC_BANK', 'กสิกรไทย'),
        'branch' => env('LEGACY_PAYMENT_ACC_BRANCH', 'เซ็นทรัลปิ่นเกล้า'),
    ],

    // session ร่วมกับเว็บเดิม (programster/mysql-session-handler บนตาราง sessions_v2)
    'session' => [
        'cookie' => 'PHPSESSID',
        'table' => 'sessions_v2',
        'max_age' => 604800,          // SESSION_MAX_AGE เดิม (7 วัน นับจากใช้งานล่าสุด)
        'touch_every' => 300,         // เขียน modified_timestamp อย่างมากครั้งเดียวต่อ 5 นาที (เหมือนเดิม)
        'gc_lottery' => [2, 100],     // เดิมลบ session หมดอายุทุก request — ที่นี่สุ่ม 2% พอ
        // salt ของ encrypt()/decrypt() เดิม (mcrypt RIJNDAEL_256 ECB) — ต้องเท่ากับค่าใน system_config.php ของ production
        'salt' => env('LEGACY_SESSION_SALT'),
    ],

    // legacy.csrf: host อื่นที่ยอมให้ POST มาได้ (คั่นด้วย ,) เช่น portal.jlcargoth.com — ปกติไม่ต้องตั้ง
    'trusted_origins' => env('LEGACY_TRUSTED_ORIGINS', ''),

    // clientIp(): เชื่อ X-Real-IP เฉพาะเมื่อ REMOTE_ADDR เป็น loopback/private หรืออยู่ในรายการนี้ (คั่นด้วย ,)
    'trusted_proxies' => env('LEGACY_TRUSTED_PROXIES', ''),

    // ระบบแบน IP เดิม (check_jail_ip_v3 ทำงานทุก request ของเว็บเดิม)
    'jail' => [
        'enabled' => env('LEGACY_JAIL_ENABLED', true),
    ],

    // log แบบเดิม (goLog/goLogMessage เขียน logs/YYYY-MM-DD.log ใน webroot เดิม)
    // dev: storage/logs/legacy · production: ชี้ไปโฟลเดอร์ logs ของเว็บเดิมได้ถ้าต้องการรวมไฟล์เดียวกัน
    'log_dir' => env('LEGACY_LOG_DIR', storage_path('logs/legacy')),
    // บันทึกทุกคำสั่งที่ไม่ใช่ SELECT แบบ mysqlquerys() เดิม
    'log_queries' => env('LEGACY_LOG_QUERIES', true),

    // บัญชีดำมิจฉาชีพ: ไฟล์ system_blocklist.php เดิม (สร้างจากเครื่อง vr) — ใช้ไฟล์เดียวกันได้เลย
    // php-fpm ของ Laravel มี open_basedir = โฟลเดอร์โปรเจกต์ → bin/link-assets คัดลอกไฟล์มาไว้ใน storage/app/legacy ให้
    'blocklist_file' => env('LEGACY_BLOCKLIST_FILE', storage_path('app/legacy/system_blocklist.php')),
    'blocklist_hit_log' => env('LEGACY_BLOCKLIST_HIT_LOG', storage_path('logs/legacy/blocklist-hit.log')),

    // notify-server (socket.io) — secret อยู่ในไฟล์นอก repo เท่านั้น
    'notify' => [
        'site' => 'jlcargoth',
        'secret_file' => env('LEGACY_NOTIFY_SECRET_FILE'),          // prod: /home/admin/tmp/notify.secret
        'counts_file' => env('LEGACY_NOTIFY_COUNTS_FILE'),          // prod: /home/admin/tmp/notify/counts-jlcargoth.json
        'counts_max_age' => 30,
        'counts_stale_max' => 600,
    ],

    // อัปโหลดไฟล์: temp = upload/temp เดิม, store = ปลายทางถาวร (เดิม Azure container "upload" + copy ลง upload/)
    'upload' => [
        'temp_dir' => env('LEGACY_UPLOAD_TEMP_DIR', storage_path('app/legacy-upload/temp')),
        // local = เก็บในเครื่อง (dev บังคับ), azure = ส่งขึ้น Azure Blob จริง (production เท่านั้น)
        'driver' => env('LEGACY_UPLOAD_DRIVER', 'local'),
        'local_dir' => env('LEGACY_UPLOAD_LOCAL_DIR', storage_path('app/legacy-upload/upload')),
        'azure' => [
            'account' => env('LEGACY_AZURE_ACCOUNT'),
            'key' => env('LEGACY_AZURE_KEY'),
            'key_file' => env('LEGACY_AZURE_KEY_FILE'),
            'container' => env('LEGACY_AZURE_CONTAINER', 'upload'),
        ],
        'shrink_max' => 1920,
        'shrink_quality' => 82,
    ],

    // Redis ภายนอกที่เว็บเดิมใช้ (readRedis/writeRedis) — dev ปิด แล้วใช้ cache ของ Laravel แทน
    'redis' => [
        'enabled' => env('LEGACY_REDIS_ENABLED', false),
        'host' => env('LEGACY_REDIS_HOST'),
        'port' => env('LEGACY_REDIS_PORT', 6379),
        'password' => env('LEGACY_REDIS_PASSWORD'),
        'prefix' => 'jl',
    ],

    // บริการภายนอกอื่นๆ (getcache/savecache, LINE push) — dev ห้ามเรียกจริง
    'external' => [
        'enabled' => env('LEGACY_EXTERNAL_ENABLED', false),
        'cache_api' => env('LEGACY_CACHE_API_URL'),
        'line_channel_token' => env('LEGACY_LINE_CHANNEL_TOKEN'),
    ],

    // กลุ่ม shop: endpoint ที่ crawler/cron เรียกด้วย token (_api/*) — เดิมฝัง token ในโค้ด ที่นี่อ่านจาก .env เท่านั้น
    // ว่าง = ปิด endpoint (ตอบว่างเหมือนส่ง token ผิด)
    'shop' => [
        'order_api_token' => env('LEGACY_SHOP_ORDER_API_TOKEN'),          // get_order_number_v2/v3, submit_order_number_v2
        'image_prefix_token' => env('LEGACY_SHOP_IMAGE_PREFIX_TOKEN'),    // updateImagePrefix
        'unban_token' => env('LEGACY_SHOP_UNBAN_TOKEN'),                  // unban
    ],

    // dev: ส่งต่อ URL ที่ยังไม่พอร์ตไปเว็บเดิม (เช่น http://127.0.0.1:8101) เพื่อให้เมนู/แจ้งเตือนใช้ได้
    // production ไม่ใช้ — nginx เป็นคนเลือกว่า URL ไหนไป Laravel
    'proxy_url' => env('LEGACY_PROXY_URL'),

    // ค่าที่ debug_mail เดิมใช้ (false = ทำงานจริง)
    'debug_mail' => false,
];
