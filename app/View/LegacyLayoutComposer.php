<?php

namespace App\View;

use App\Support\Legacy\Legacy;
use Illuminate\View\View;

/**
 * ตัวแปรของ index.php เดิมที่ layout/เมนูใช้
 *   $page     = $_GET['page'] เดิม (ชื่อหน้าใน content.php) — controller ตั้งได้ด้วย ->with('page', 'tracking')
 *               หรือ Route::...->defaults('legacy_page', 'tracking'); ไม่ตั้ง = 'dashboard'
 *   $isMobile = $detect->isMobile()
 *   $lang     = ภาษา (th|en|cn)
 *   $user     = checkUser() (App\Models\User|null)
 */
class LegacyLayoutComposer
{
    public function compose(View $view): void
    {
        $data = $view->getData();
        $route = request()->route();
        $view->with([
            'page' => $data['page'] ?? ($route?->defaults['legacy_page'] ?? 'dashboard'),
            'isMobile' => $data['isMobile'] ?? Legacy::isMobile(),
            'lang' => $data['lang'] ?? Legacy::lang(),
            'user' => $data['user'] ?? Legacy::user(),
        ]);
    }
}
