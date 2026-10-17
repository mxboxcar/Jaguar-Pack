<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Settings\Input;
use App\Http\Controllers\Controller;
use App\Support\Legacy\Format;
use App\Support\Legacy\Pricing;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * POST /get_price — get_price.php เดิม (getPrice() ของ JS ใน layout)
 * สิทธิ์ view_bol ตามเดิม — ไม่มีโมดูลนี้ใน role_detail จึงไม่มีใครผ่าน (ทุกคนได้ ErrorBox สิทธิ์ = endpoint ตายเหมือนเดิม)
 */
class PriceController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $x = Pricing::getPrice(
            Input::php($request, 'user'), Input::php($request, 'type'), Input::php($request, 'weight'),
            Input::php($request, 'width'), Input::php($request, 'height'), Input::php($request, 'long'),
            Input::php($request, 'amount'),
        );

        return response((string) json_encode([
            'kg' => Format::numberformat($x['kg']),
            'cubic' => Format::numberformat($x['cubic']),
            'amount' => Format::numberformat($x['amount']),
        ]));
    }
}
