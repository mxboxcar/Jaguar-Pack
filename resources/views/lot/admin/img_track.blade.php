{{-- SR _admin/_lot/load_image.php / save_quickfile.php เดิม: รูปพัสดุเล็กข้างเลข tracking (เดิมเปิดแท็บใหม่ — ตัด target blank ตามเจ้าของ) --}}
@foreach ($list as $img)
<a style="float:right" class="fancybox" href="{{ legacy_blob($img) }}">
            <img src="{{ legacy_blob($img) }}" style="max-width: 20px;max-height: 20px;margin-left:2px;">
        </a>@endforeach
