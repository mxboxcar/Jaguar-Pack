{{-- แถว ไม้กันกระแทก / QC / (SR: ถ่ายรูป เมื่อ $takeapic) / ประเภท ของ grid เดิม — $item, $typeName --}}
@php use App\Domain\Tracking\Php74; @endphp
                                    <tr>
                                        <td class="text-right left">{!! res('wood_protect') !!} : </td>
                                        <td style="padding-left:3px" class="text-left">
                                        {!! Php74::eq($item->is_craft, 0) ? "<span class='text-danger'>".res('disable').'</span>' : "<span class='text-success'>".res('active').'</span>' !!}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-right left">{!! res('qc') !!} : </td>
                                        <td style="padding-left:3px" class="text-left">
                                        {!! Php74::eq($item->is_qc, 0) ? "<span class='text-danger'>".res('disable').'</span>' : "<span class='text-success'>".res('active').'</span>' !!}</td>
                                    </tr>
@if ($takeapic ?? false)
                                    {{-- SR: ถ่ายรูป (grid ลูกค้า/แอดมิน — หน้ารวมรหัสไม่มี) --}}
                                    <tr>
                                        <td class="text-right left">ถ่ายรูป: </td>
                                        <td style="padding-left:3px" class="text-left">
                                        {!! Php74::eq($item->is_takeapic ?? null, 0) ? "<span class='text-danger'>".res('disable').'</span>' : "<span class='text-success'>".res('active').'</span>' !!}</td>
                                    </tr>
@endif
                                    <tr>
                                        <td class="text-right left">{!! res('type') !!} : </td>
                                        <td style="padding-left:3px" class="text-left">
                                        {{ $typeName }}
                                       </td>
                                    </tr>
