<?php

namespace Tests\Feature\Integrations;

use App\Support\Legacy\AzureBlob;
use App\Support\Legacy\BlobStore;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Azure Blob REST (Shared Key) แทน SDK ของ _api/blockblob*.php — ไม่ยิงเครือข่ายจริง (Http::fake + preventStrayRequests)
 * key ใน test = fixture (base64 ของ "jl-fixture-key-not-a-secret-0123456789") ไม่ใช่ key จริง
 */
class AzureBlobTest extends TestCase
{
    private const KEY = 'amwtZml4dHVyZS1rZXktbm90LWEtc2VjcmV0LTAxMjM0NTY3ODk=';

    private string $tmp;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->tmp = storage_path('framework/testing/azure-'.bin2hex(random_bytes(4)));
        File::ensureDirectoryExists($this->tmp.'/temp');
        config([
            'legacy.upload.temp_dir' => $this->tmp.'/temp',
            'legacy.upload.local_dir' => $this->tmp.'/upload',
            'legacy.upload.driver' => 'azure',
            'legacy.upload.azure' => ['account' => 'blobjl', 'key' => self::KEY, 'key_file' => null, 'container' => 'upload'],
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmp);
        parent::tearDown();
    }

    /** StringToSign ตามเอกสาร Shared Key (Blob, version >= 2015-02-21) — ลายเซ็นคำนวณแยกด้วย openssl dgst -sha256 -mac HMAC */
    public function test_string_to_sign_and_signature_match_fixture(): void
    {
        $headers = [
            'Content-Type' => 'text/plain',
            'Content-Length' => '11',
            'Content-MD5' => 'XrY7u+Ae7tCTyyK7j1rNww==',
            'x-ms-version' => '2021-12-02',
            'x-ms-date' => 'Sun, 19 Oct 2026 08:00:00 GMT',
            'x-ms-blob-type' => 'BlockBlob',
        ];
        $sts = AzureBlob::stringToSign('PUT', $headers, '/blobjl/upload/zz%20a.txt');
        $this->assertSame(
            "PUT\n\n\n11\nXrY7u+Ae7tCTyyK7j1rNww==\ntext/plain\n\n\n\n\n\n\n"
            ."x-ms-blob-type:BlockBlob\nx-ms-date:Sun, 19 Oct 2026 08:00:00 GMT\nx-ms-version:2021-12-02\n"
            .'/blobjl/upload/zz%20a.txt',
            $sts
        );
        $this->assertSame('Nay3zQyWb8e+qx264weP2ZCCVgbBBo4tPZAVaoC5dkQ=', AzureBlob::sign(self::KEY, $sts));
    }

    public function test_string_to_sign_rules(): void
    {
        // Content-Length 0 = ว่าง, header x-ms-* ตัวเล็ก/เรียงตามชื่อ/ตัดช่องว่าง, query เรียงและตัวเล็ก (ตัวอย่างในเอกสาร: ?comp=metadata)
        $sts = AzureBlob::stringToSign('delete', ['Content-Length' => '0', 'X-MS-Version' => '2021-12-02', 'x-ms-date' => '  Sun, 19 Oct 2026 08:00:00 GMT '],
            '/myaccount/mycontainer', ['restype' => 'container', 'COMP' => 'metadata']);
        $this->assertSame(
            "DELETE\n\n\n\n\n\n\n\n\n\n\n\nx-ms-date:Sun, 19 Oct 2026 08:00:00 GMT\nx-ms-version:2021-12-02\n/myaccount/mycontainer\ncomp:metadata\nrestype:container",
            $sts
        );
    }

    public function test_upload_from_temp_puts_block_blob_with_valid_shared_key(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        file_put_contents($this->tmp.'/temp/20261019 abc.png', $png);
        Http::fake([
            '*' => fn (Request $r) => $r->method() === 'HEAD' ? Http::response('', 404) : Http::response('', 201),
        ]);

        $this->assertSame('', BlobStore::uploadFromTemp('../upload/temp/20261019 abc.png'));

        $sent = Http::recorded();
        $this->assertCount(2, $sent);
        [$head] = $sent[0];
        [$put] = $sent[1];
        $this->assertSame('HEAD', $head->method());
        $this->assertSame('PUT', $put->method());
        $this->assertSame('https://blobjl.blob.core.windows.net/upload/20261019%20abc.png', $put->url());
        $this->assertSame('BlockBlob', $put->header('x-ms-blob-type')[0]);
        $this->assertSame(AzureBlob::VERSION, $put->header('x-ms-version')[0]);
        $this->assertSame('image/png', $put->header('Content-Type')[0]);
        $this->assertSame(base64_encode(md5($png, true)), $put->header('Content-MD5')[0]);
        $this->assertSame($png, $put->body());

        foreach ([$head, $put] as $r) {
            $expected = AzureBlob::sign(self::KEY, AzureBlob::stringToSign($r->method(), array_filter([
                'Content-Type' => $r->header('Content-Type')[0] ?? '',
                'Content-Length' => (string) strlen($r->body()),
                'Content-MD5' => $r->header('Content-MD5')[0] ?? '',
                'x-ms-date' => $r->header('x-ms-date')[0],
                'x-ms-version' => $r->header('x-ms-version')[0],
                'x-ms-blob-type' => $r->header('x-ms-blob-type')[0] ?? null,
            ], fn ($v) => $v !== null), '/blobjl/upload/20261019%20abc.png'));
            $this->assertSame('SharedKey blobjl:'.$expected, $r->header('Authorization')[0], $r->method());
        }
    }

    public function test_existing_or_unknown_blob_is_not_overwritten(): void
    {
        file_put_contents($this->tmp.'/temp/a.txt', 'abc');
        Http::fake(['*' => Http::response('', 200, ['Content-Length' => '3'])]);
        $this->assertSame('exists', BlobStore::uploadFromTemp('a.txt'));
        Http::assertSentCount(1);

        // เช็คไม่ได้ (-1) = รอรอบหน้าเหมือน blob_guard_size เดิม
        Http::fake(['*' => Http::response('', 503)]);
        $this->assertSame('exists', BlobStore::uploadFromTemp('a.txt'));
    }

    public function test_register_style_upload_overwrites_without_head_and_reports_errors(): void
    {
        file_put_contents($this->tmp.'/temp/id.jpg', '<html><body><script>alert(1)</script></body></html>');
        Http::fake(['*' => Http::response('<?xml version="1.0"?><Error><Code>AuthenticationFailed</Code><Message>x</Message></Error>', 403)]);

        $this->assertSame('403: AuthenticationFailed<br />', BlobStore::uploadFromTemp('id.jpg', skipExisting: false));
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) => $r->method() === 'PUT'
            && $r->header('Content-Type')[0] === 'application/octet-stream'); // HTML ปลอมนามสกุล .jpg → octet-stream (ไม่ render บนโดเมนรูป)
    }

    public function test_temp_guard_and_local_driver(): void
    {
        Http::fake();
        $this->assertSame('temp empty', BlobStore::uploadFromTemp('missing.png'));
        file_put_contents($this->tmp.'/temp/empty.png', '');
        $this->assertSame('temp empty', BlobStore::uploadFromTemp('empty.png'));
        Http::assertNothingSent();

        config(['legacy.upload.driver' => 'local']);
        file_put_contents($this->tmp.'/temp/b.txt', 'b');
        $this->assertSame('', BlobStore::uploadFromTemp('../../b.txt'));
        $this->assertSame('b', file_get_contents($this->tmp.'/upload/b.txt'));
        $this->assertSame('exists', BlobStore::uploadFromTemp('b.txt'));
        Http::assertNothingSent();
    }

    public function test_key_file_and_missing_key(): void
    {
        $f = $this->tmp.'/k.key';
        file_put_contents($f, self::KEY."\n");
        config(['legacy.upload.azure.key' => null, 'legacy.upload.azure.key_file' => $f]);
        $this->assertSame('https://blobjl.blob.core.windows.net/upload/x.png', AzureBlob::fromConfig()->url('x.png'));

        config(['legacy.upload.azure.key_file' => $this->tmp.'/nope.key']);
        file_put_contents($this->tmp.'/temp/c.txt', 'c');
        Http::fake();
        $this->assertStringStartsWith('500: azure blob: account/key/container not configured', BlobStore::uploadFromTemp('c.txt'));
        Http::assertNothingSent();
    }
}
