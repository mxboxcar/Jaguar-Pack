<?php

use Illuminate\Support\Facades\Route;

/*
| ไฟล์นี้อยู่ใต้ middleware group "web" (session/CSRF ของ Laravel) — ใช้เฉพาะหน้า dev ที่ไม่เกี่ยวกับเว็บเดิม
| login/logout/ภาษา อยู่ที่ routes/legacy.php, หน้าของแต่ละกลุ่มอยู่ที่ routes/modules/<group>.php
| (ทั้งสองแบบอยู่ใต้ group "legacy" = session เว็บเดิม)
*/

// ต้นแบบ /dev/tracking* ย้ายไป routes/legacy.php แล้ว (ต้องมี session เว็บเดิม: ดูได้เฉพาะของตัวเอง หรือแอดมินที่มีสิทธิ์ admin_tracking)
