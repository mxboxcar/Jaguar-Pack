<?php

namespace Tests\Feature;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * routes/modules/*.php = 1 ไฟล์ต่อ 1 กลุ่ม
 *  - ชื่อไฟล์ต้องเป็นหนึ่งใน 13 กลุ่ม (worktree ทดลอง zz-* ห้าม merge เข้า main)
 *  - method+URI เดียวกันห้ามอยู่หลายไฟล์ (Laravel ให้ไฟล์ที่โหลดทีหลังทับเงียบๆ → หน้าของอีกกลุ่มหายตอน merge)
 */
class RouteModulesTest extends TestCase
{
    private const GROUPS = [
        'tracking', 'shop', 'order', 'payment', 'balance', 'money-move', 'points',
        'lot', 'warehouse', 'users', 'messaging', 'reports', 'settings',
    ];

    public function test_route_files_are_known_groups(): void
    {
        $files = array_map(fn ($f) => basename($f, '.php'), glob(base_path('routes/modules/*.php')) ?: []);
        $this->assertSame([], array_values(array_diff($files, self::GROUPS)), 'routes/modules มีไฟล์ที่ไม่ใช่ 13 กลุ่ม');
    }

    public function test_no_route_is_defined_by_two_files(): void
    {
        $files = [base_path('routes/legacy.php'), base_path('routes/web.php'), ...(glob(base_path('routes/modules/*.php')) ?: [])];
        $owner = [];
        $dupes = [];
        $original = Route::getFacadeRoot();
        try {
            foreach ($files as $file) {
                $router = new Router($this->app['events'], $this->app);
                Route::swap($router);
                $router->group([], $file);
                foreach ($router->getRoutes() as $route) {
                    foreach (array_diff($route->methods(), ['HEAD']) as $m) {
                        $key = $m.' '.$route->uri();
                        $name = basename(dirname($file)).'/'.basename($file);
                        if (isset($owner[$key]) && $owner[$key] !== $name) {
                            $dupes[] = "$key: {$owner[$key]} + $name";
                        }
                        $owner[$key] ??= $name;
                    }
                }
            }
        } finally {
            Route::swap($original);
        }
        $this->assertSame([], $dupes, 'route ซ้ำข้ามไฟล์');
    }
}
