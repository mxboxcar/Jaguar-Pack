{{-- SR: รูปพัสดุ (tracking_image) ในช่อง "ไฟล์แนบ" ของ grid — เดิมเปิดแท็บใหม่ (target blank — ตัดออกตามเจ้าของ) --}}
@foreach ($list as $img)
<a style="margin:3px;float:left" class="fancybox" href="{{ legacy_blob($img) }}">
                                                <img src="{{ legacy_blob($img) }}" style="max-width: 50px;max-height: 33px;">
                                            </a>
@endforeach
