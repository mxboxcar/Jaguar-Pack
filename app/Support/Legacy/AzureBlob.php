<?php

namespace App\Support\Legacy;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Azure Blob Storage ผ่าน REST + Shared Key (แทน MicrosoftAzure ServicesBuilder::createBlockBlob ของ _api/blockblob*.php เดิม)
 * ไม่ใช้ SDK: เซ็นเองตาม "Authorize with Shared Key" (x-ms-version 2021-12-02)
 *   https://learn.microsoft.com/rest/api/storageservices/authorize-with-shared-key
 *
 * ใช้: put() = Put Blob (BlockBlob), size() = Get Blob Properties (HEAD), delete() = Delete Blob
 * key อ่านตอนสร้าง (fromConfig) จาก legacy.upload.azure.key หรือ key_file — ไม่เก็บใน config cache ถ้าใช้ key_file
 */
final class AzureBlob
{
    public const VERSION = '2021-12-02';

    /** @param string $key account key แบบ base64 (ตามที่ Azure portal ให้มา) */
    public function __construct(
        private readonly string $account,
        private readonly string $key,
        private readonly string $container,
        private readonly ?string $endpoint = null,
        private readonly ?HttpFactory $http = null,
    ) {
        if ($account === '' || $key === '' || $container === '') {
            throw new RuntimeException('azure blob: account/key/container not configured');
        }
    }

    public static function fromConfig(): self
    {
        $c = (array) config('legacy.upload.azure', []);
        $key = trim((string) ($c['key'] ?? ''));
        $file = (string) ($c['key_file'] ?? '');
        if ($key === '' && $file !== '') {
            $key = is_readable($file) ? trim((string) @file_get_contents($file)) : '';
        }

        return new self((string) ($c['account'] ?? ''), $key, (string) ($c['container'] ?? 'upload'), ($c['endpoint'] ?? null) ?: null);
    }

    /** https://<account>.blob.core.windows.net/<container>/<blob> (ชื่อ blob encode แบบ RFC 3986) */
    public function url(string $blob): string
    {
        $base = rtrim($this->endpoint ?: 'https://'.$this->account.'.blob.core.windows.net', '/');

        return $base.$this->path($blob);
    }

    /** Put Blob (BlockBlob) — ทับของเดิมถ้ามี (เหมือน createBlockBlob) */
    public function put(string $blob, string $body, string $contentType = 'application/octet-stream'): Response
    {
        return $this->send('PUT', $blob, [
            'Content-Type' => $contentType,
            'Content-Length' => (string) strlen($body),
            'Content-MD5' => base64_encode(md5($body, true)),
            'x-ms-blob-type' => 'BlockBlob',
        ], $body);
    }

    /** Get Blob Properties: 0 = ไม่มี (404) หรือว่าง, >0 = ขนาด, -1 = เช็คไม่ได้ (เหมือน blob_guard_size เดิม) */
    public function size(string $blob): int
    {
        try {
            $r = $this->send('HEAD', $blob);
        } catch (\Throwable) {
            return -1;
        }
        if ($r->status() === 404) {
            return 0;
        }

        return $r->status() === 200 ? (int) $r->header('Content-Length') : -1;
    }

    public function delete(string $blob): Response
    {
        return $this->send('DELETE', $blob);
    }

    /** @param array<string,string> $headers */
    private function send(string $method, string $blob, array $headers = [], string $body = ''): Response
    {
        $headers['x-ms-date'] = gmdate('D, d M Y H:i:s \G\M\T');
        $headers['x-ms-version'] = self::VERSION;
        // Http client ของ Laravel ใส่ Content-Type: application/json เป็นค่าเริ่มต้น → กำหนดเองทุกครั้งแล้วเซ็นค่าที่ส่งจริง
        $type = $headers['Content-Type'] ?? 'application/octet-stream';
        $headers['Content-Type'] = $type;
        $headers['Content-Length'] = (string) strlen($body); // Guzzle ส่งค่าเดียวกันเอง (0 = ว่างใน StringToSign)
        $headers['Authorization'] = 'SharedKey '.$this->account.':'.self::sign($this->key, self::stringToSign($method, $headers, $this->resource($blob)));
        unset($headers['Content-Length']);

        return ($this->http ?? Http::getFacadeRoot())
            ->withHeaders($headers)
            ->withBody($body, $type)
            ->timeout(60)->connectTimeout(10)
            ->send($method, $this->url($blob));
    }

    private function path(string $blob): string
    {
        return '/'.rawurlencode($this->container).'/'.rawurlencode($blob);
    }

    /** CanonicalizedResource (ไม่มี query string ในงานนี้) */
    private function resource(string $blob): string
    {
        return '/'.$this->account.$this->path($blob);
    }

    /**
     * StringToSign ของ Blob service (version 2015-02-21 ขึ้นไป):
     * VERB, Content-Encoding, Content-Language, Content-Length (ว่างถ้า 0), Content-MD5, Content-Type, Date,
     * If-Modified-Since, If-Match, If-None-Match, If-Unmodified-Since, Range, CanonicalizedHeaders, CanonicalizedResource
     *
     * @param  array<string,string>  $headers
     * @param  array<string,string>  $query
     */
    public static function stringToSign(string $method, array $headers, string $canonicalResource, array $query = []): string
    {
        $h = [];
        foreach ($headers as $k => $v) {
            $h[strtolower($k)] = (string) $v;
        }
        $len = $h['content-length'] ?? '';
        if ($len === '0') {
            $len = '';
        }
        $std = [
            strtoupper($method),
            $h['content-encoding'] ?? '',
            $h['content-language'] ?? '',
            $len,
            $h['content-md5'] ?? '',
            $h['content-type'] ?? '',
            $h['date'] ?? '',
            $h['if-modified-since'] ?? '',
            $h['if-match'] ?? '',
            $h['if-none-match'] ?? '',
            $h['if-unmodified-since'] ?? '',
            $h['range'] ?? '',
        ];

        $ms = [];
        foreach ($h as $k => $v) {
            if (str_starts_with($k, 'x-ms-')) {
                $ms[$k] = $k.':'.trim(preg_replace('/\s+/', ' ', $v));
            }
        }
        ksort($ms, SORT_STRING);

        $res = $canonicalResource;
        $q = [];
        foreach ($query as $k => $v) {
            $q[strtolower((string) $k)] = (string) $v;
        }
        ksort($q, SORT_STRING);
        foreach ($q as $k => $v) {
            $res .= "\n".$k.':'.$v;
        }

        return implode("\n", $std)."\n".implode('', array_map(fn ($l) => $l."\n", $ms)).$res;
    }

    public static function sign(string $base64Key, string $stringToSign): string
    {
        $raw = base64_decode($base64Key, true);
        if ($raw === false) {
            throw new RuntimeException('azure blob: account key is not valid base64');
        }

        return base64_encode(hash_hmac('sha256', $stringToSign, $raw, true));
    }
}
