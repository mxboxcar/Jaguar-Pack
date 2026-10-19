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
    'href_blob' => env('LEGACY_HREF_BLOB', 'https://image.srshippingsystem.com/upload/'),

    'project_name' => 'SRShippingSystem',
    'proj_name' => '',                      // SR เดิมไม่มี define('proj_name') → ค่าว่าง
    'admin_email' => 'ship2you@outlook.co.th',   // define ใน system_db.php เดิมของ SR
    'version' => '4.9',
    'cookie_path' => '/',

    // ชื่อ cookie เดิม (ต้องตรงเป๊ะ เว็บเดิมกับใหม่ใช้ร่วมกัน)
    'cookies' => [
        'main' => 'srshippingsystem',
        'role' => 'srshippingsystem-role',
        'lang' => 'srshippingsystem-lang',
        'menu' => 'srshippingsystem-menu',
        'admin_menu' => 'srshippingsystem-admin-menu',
        'cart' => 'srshippingsystem-cart',
        'portal' => 'srshippingsystem-portal',
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

    // legacy.csrf: host อื่นที่ยอมให้ POST มาได้ (คั่นด้วย ,) เช่น portal.srshippingsystem.com — ปกติไม่ต้องตั้ง
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
        'site' => 'srshippingsystem',
        'secret_file' => env('LEGACY_NOTIFY_SECRET_FILE'),          // prod: /home/admin/tmp/notify.secret
        'counts_file' => env('LEGACY_NOTIFY_COUNTS_FILE'),          // prod: /home/admin/tmp/notify/counts-srshippingsystem.json
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
        'prefix' => 'sr',
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

    // dev: ส่งต่อ URL ที่ยังไม่พอร์ตไปเว็บเดิม (เช่น http://127.0.0.1:8111) เพื่อให้เมนู/แจ้งเตือนใช้ได้
    // production ไม่ใช้ — nginx เป็นคนเลือกว่า URL ไหนไป Laravel
    'proxy_url' => env('LEGACY_PROXY_URL'),

    // ค่าที่ debug_mail เดิมใช้ (false = ทำงานจริง)
    'debug_mail' => false,

    // อีเมลของเว็บเดิม SR: sendmail() ใน system_config.php ส่งผ่าน Mailgun (domain mg.srshippingsystem.com)
    //   from = 'SRShippingSystem<no-reply@srshippingsystem.com>' ตายตัว — Mailer ทุกกลุ่มใช้ค่านี้ (ไม่ใช้ MAIL_FROM_* ของ Laravel)
    // แม่แบบ email_header.php / email_center.php / email_footer.php เดิมถูกอ่านด้วย file_get_contents (ไม่ execute) → ข้อความคงที่ (CRLF ตามไฟล์เดิม)
    //   ใช้: header.'<strong>'.$subject.'</strong>'.center.$body.footer
    'mail' => [
        'from_address' => env('LEGACY_MAIL_FROM_ADDRESS', 'no-reply@srshippingsystem.com'),
        'from_name' => env('LEGACY_MAIL_FROM_NAME', 'SRShippingSystem'),
        'header' => '<div style="font-family: arial,sans-serif;font-size:13px;max-width: 560px">',
        'center' => '<div style="text-indent: 30px;">',
        'footer' => "</div>\r\nขอแสดงความนับถือ<br />\r\nSRShippingSystem\r\n</div>",
    ],

    // _profile/pin (method=verified): ผิดครบ max_attempts ครั้งใน decay_minutes นาที ต่อ (ผู้ใช้ + IP) = ล็อก
    // และต่อผู้ใช้ทุก IP รวมกันครบ max_attempts_per_user = ล็อก (กันเปลี่ยน IP) — App\Domain\Users\PinThrottle
    'pin_throttle' => [
        'max_attempts' => (int) env('LEGACY_PIN_MAX_ATTEMPTS', 5),
        'max_attempts_per_user' => (int) env('LEGACY_PIN_MAX_ATTEMPTS_PER_USER', 10),
        'decay_minutes' => (int) env('LEGACY_PIN_DECAY_MINUTES', 15),
    ],

    /*
    | สิทธิ์ "กำพร้า" (ไม่มี role ใดถือใน role_on_detail ของ DB จริง → เดิมไม่มีใครเข้าได้แม้แอดมิน)
    | Laravel ให้สิทธิ์นั้นเพิ่มแก่ผู้ที่ถือสิทธิ์จริงที่ใกล้ที่สุดของงานเดียวกัน — ไม่แก้แถว role/permission ใน DB
    |   'สิทธิ์กำพร้า' => ['สิทธิ์ต้นทาง', ...]   (มีต้นทางอย่างน้อย 1 ตัว = ได้สิทธิ์กำพร้า)
    | - ทำงานชั้นเดียว: ต้นทางต้องเป็นสิทธิ์ที่ role ถือจริงใน DB (สิทธิ์ที่ได้จาก fallback ไม่ถูกส่งต่อ)
    | - role ที่ได้ติ๊กสิทธิ์กำพร้าจริงใน DB ภายหลัง ใช้ค่าใน DB (fallback แค่เพิ่ม ไม่เคยถอน)
    | - ปิดทั้งหมด: LEGACY_PERMISSION_FALLBACKS=false · เปลี่ยน/ลบรายการ: แก้ array นี้ (เหตุผลและ role ที่ได้ ดู docs/CONVENTIONS.md ข้อ 3.1)
    */
    'permission_fallbacks_enabled' => env('LEGACY_PERMISSION_FALLBACKS', true),
    // SR (admin_sr 2026-10-20): ไม่มีสิทธิ์กำพร้า — ทุกสิทธิ์ที่โค้ดตรวจมี role ถืออยู่ใน role_on_detail แล้ว
    // map ของ jl (admin_point ← admin_shop_cashback ฯลฯ) จะให้สิทธิ์เกินที่ SR ตั้งไว้ จึงว่าง (ดู docs/CONVENTIONS.md ข้อ 3.1)
    'permission_fallbacks' => [
    ],
];
