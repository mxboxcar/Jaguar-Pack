{{-- dropdown สถานะของ _admin/_withdrawpoint/grid.php และ _admin/_shop_cashback/grid.php เดิม (เทียบ == แบบ 7.4) --}}
@php use App\Domain\Points\Php74; $f = $s['filterPhp']; $sel = fn ($v) => $f !== null && Php74::eq($f, $v) ? "selected='selected'" : ''; @endphp
                    <select name="role" class="form-control " id="status" onchange="GenerateWhere()">
                        <option {!! $sel('-2') !!} value="-2">- {!! res('all') !!} -</option>  
                        <option {!! $sel('-1') !!} value="-1">{!! res('cancel') !!}</option>    
                        <option {!! $sel('0') !!} value="0">{!! res('no_paid') !!}</option>   
                        <option {!! $sel('1') !!} value="1">{!! res('wait_for_approve') !!}</option>   
                        <option {!! $sel('2') !!} value="2">{!! res('paid') !!}</option>   
                    </select>
