<?php
/**
 * Supabase Client — koneksi ke Supabase PostgREST & Storage API.
 *
 * Menggunakan GuzzleHTTP untuk komunikasi yang andal, dengan penanganan
 * token, retry otomatis, dan validasi respon yang ketat.
 *
 * @package XT4
 * @since 1.0.0
 */

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;

class SupabaseClient {
    private Client $http;
    private string $baseUrl;
    private string $anonKey;
    private string $serviceKey;
    private int $maxRetries = 3;

    /**
     * Inisialisasi client.
     *
     * @throws RuntimeException jika environment variable tidak lengkap.
     */
    public function __construct() {
        $this->baseUrl = rtrim(env('SUPABASE_URL'), '/');
        $this->anonKey = env('SUPABASE_ANON_KEY');
        $this->serviceKey = env('SUPABASE_SERVICE_KEY');

        if (empty($this->baseUrl) || empty($this->anonKey)) {
            throw new RuntimeException('Supabase credentials tidak ditemukan. Periksa .env');
        }

        $this->http = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 15,
            'connect_timeout' => 10,
            'http_errors' => false,
            'headers' => [
                'apikey' => $this->serviceKey ?: $this->anonKey,
                'Authorization' => 'Bearer ' . ($this->serviceKey ?: $this->anonKey),
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation'
            ]
        ]);
    }

    /**
     * Lakukan HTTP request dengan retry.
     *
     * @param string $method GET|POST|PATCH|DELETE
     * @param string $endpoint Path relatif ke base URL API (contoh: /rest/v1/posts)
     * @param array  $options Opsi Guzzle tambahan (json, query, etc.)
     * @return array Decoded JSON response
     * @throws RuntimeException
     */
    private function request(string $method, string $endpoint, array $options = []): array {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                $response = $this->http->request($method, $endpoint, $options);
                $statusCode = $response->getStatusCode();
                $body = (string) $response->getBody();

                if ($statusCode >= 200 && $statusCode < 300) {
                    return json_decode($body, true, 512, JSON_THROW_ON_ERROR) ?: [];
                }

                // Khusus 404 (not found) boleh dilempar sebagai array kosong
                if ($statusCode === 404) {
                    return [];
                }

                // Untuk error lain, lemparkan exception dengan detail
                $errorDetail = json_decode($body, true)['message'] ?? $body;
                throw new RuntimeException("Supabase API error [$statusCode]: $errorDetail");

            } catch (RequestException | GuzzleException $e) {
                $lastException = $e;
                $attempt++;
                if ($attempt >= $this->maxRetries) {
                    throw new RuntimeException(
                        "Gagal terhubung ke Supabase setelah {$this->maxRetries}x: " . $e->getMessage(),
                        $e->getCode(),
                        $e
                    );
                }
                usleep(500000); // tunggu 0.5 detik sebelum retry
            }
        }

        throw $lastException ?? new RuntimeException("Unknown error saat menghubungi Supabase.");
    }

    // ========== DATABASE (PostgREST) ==========

    /**
     * Ambil semua record dari tabel.
     *
     * @param string $table Nama tabel
     * @param array  $select Kolom yang di-select, default '*'
     * @param array  $filters Filter query (contoh: ['slug' => 'eq.hello'])
     * @param string $order Urutan (contoh: 'created_at.desc')
     * @return array
     */
    public function getAllRows(string $table, array $select = ['*'], array $filters = [], string $order = ''): array {
        $query = ['select' => implode(',', $select)];
        foreach ($filters as $col => $opVal) {
            $query[$col] = $opVal; // format: eq.hello, gte.10, dsb
        }
        if ($order) {
            $query['order'] = $order;
        }

        return $this->request('GET', "/rest/v1/$table", ['query' => $query]);
    }

    /**
     * Ambil satu record berdasarkan kondisi unik.
     *
     * @param string $table
     * @param array  $filters (contoh ['id' => 'eq.1'])
     * @return array|null
     */
    public function getRow(string $table, array $filters): ?array {
        $rows = $this->getAllRows($table, ['*'], $filters, '');
        return $rows[0] ?? null;
    }

    /**
     * Insert data baru.
     *
     * @param string $table
     * @param array  $data Associative array
     * @return array|null Data yang baru diinsert (jika Prefer: return=representation)
     */
    public function insert(string $table, array $data): ?array {
        $result = $this->request('POST', "/rest/v1/$table", ['json' => $data]);
        return $result[0] ?? null;
    }

    /**
     * Update record berdasarkan filter.
     *
     * @param string $table
     * @param array  $filters (contoh ['id' => 'eq.1'])
     * @param array  $data
     * @return array|null data setelah update
     */
    public function update(string $table, array $filters, array $data): ?array {
        $query = [];
        foreach ($filters as $col => $opVal) {
            $query[$col] = $opVal;
        }
        $result = $this->request('PATCH', "/rest/v1/$table", [
            'query' => $query,
            'json' => $data,
        ]);
        return $result[0] ?? null;
    }

    /**
     * Hapus record.
     *
     * @param string $table
     * @param array  $filters
     * @return bool
     */
    public function delete(string $table, array $filters): bool {
        $query = [];
        foreach ($filters as $col => $opVal) {
            $query[$col] = $opVal;
        }
        $this->request('DELETE', "/rest/v1/$table", ['query' => $query]);
        return true; // jika tidak exception, dianggap sukses
    }

    // ========== STORAGE (Supabase Storage) ==========

    /**
     * Upload file ke bucket tertentu.
     *
     * @param string $bucket Nama bucket (pastikan public)
     * @param string $path   Path di dalam bucket, misal: 'blog/2025/img.jpg'
     * @param string $fileContent Binary content file
     * @param string $contentType MIME type
     * @return string Public URL file
     * @throws RuntimeException
     */
    public function uploadFile(string $bucket, string $path, string $fileContent, string $contentType): string {
        $url = "/storage/v1/object/$bucket/" . rawurlencode($path);

        $response = $this->http->request('POST', $this->baseUrl . $url, [
            'headers' => [
                'Content-Type' => $contentType,
                'Authorization' => 'Bearer ' . ($this->serviceKey ?: $this->anonKey),
                'apikey' => $this->serviceKey ?: $this->anonKey,
                'x-upsert' => 'true', // overwrite jika sudah ada
            ],
            'body' => $fileContent,
            'timeout' => 30,
            'http_errors' => false,
        ]);

        $status = $response->getStatusCode();
        if ($status !== 200) {
            $errorBody = json_decode((string)$response->getBody(), true);
            throw new RuntimeException('Gagal upload file: ' . ($errorBody['message'] ?? 'Kode ' . $status));
        }

        // Public URL
        return $this->baseUrl . "/storage/v1/object/public/$bucket/" . rawurlencode($path);
    }

    /**
     * Hapus file dari storage.
     */
    public function deleteFile(string $bucket, string $path): bool {
        $url = "/storage/v1/object/$bucket/" . rawurlencode($path);
        $this->request('DELETE', $url);
        return true;
    }
}
