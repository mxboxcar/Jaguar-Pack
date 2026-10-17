<?php

/*
| helper ระดับ global ของฐานร่วม (โหลดผ่าน composer autoload.files)
| ตั้งชื่อไม่ชนกับฟังก์ชันเดิม — ฟังก์ชันเดิมที่เหลืออยู่ใน App\Support\Legacy\* (ดู docs/CONVENTIONS.md)
*/

use App\Support\Legacy\Lang;
use App\Support\Legacy\Legacy;
use App\Support\Legacy\Permissions;

if (! function_exists('res')) {
    /** = $resource['key'] เดิม (ภาษาตาม cookie jlcargo-lang, ไม่มี key → '') — คืน HTML ดิบ ใช้ {!! res('x') !!} */
    function res(string $key, ?string $lang = null): string
    {
        return Lang::get($key, $lang);
    }
}

if (! function_exists('perm')) {
    /** = getPermission($module) เดิม; ส่งหลายตัว = OR */
    function perm(string ...$modules): bool
    {
        return app(Permissions::class)->any(...$modules);
    }
}

if (! function_exists('legacy_href')) {
    /** = href.'path' เดิม */
    function legacy_href(string $path = ''): string
    {
        return Legacy::href($path);
    }
}

if (! function_exists('legacy_blob')) {
    /** = href_blob.'file' เดิม */
    function legacy_blob(string $path = ''): string
    {
        return Legacy::hrefBlob($path);
    }
}
