<?php
// Fungsi bantu untuk menghasilkan response JSON yang konsisten.

function kirim_json(int $status, array $data): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function error_json(int $status, string $pesan): void
{
    kirim_json($status, ['status' => 'error', 'message' => $pesan]);
}

// Tangani preflight CORS dan batasi method yang diizinkan.
function izinkan_method(array $methods): void
{
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'OPTIONS') {
        kirim_json(200, ['status' => 'success', 'message' => 'OK']);
    }
    if (!in_array($method, $methods, true)) {
        header('Allow: ' . implode(', ', $methods));
        error_json(405, 'Method ' . $method . ' tidak diizinkan. Gunakan: ' . implode(', ', $methods));
    }
}

// Rapikan tipe data baris produk agar JSON memakai angka, bukan string.
function format_produk(array $row): array
{
    return [
        'id_produk'     => (int) $row['id_produk'],
        'nama_produk'   => $row['nama_produk'],
        'deskripsi'     => $row['deskripsi'],
        'harga'         => (float) $row['harga'],
        'stok'          => (int) $row['stok'],
        'gambar'        => $row['gambar'],
        'id_kategori'   => (int) $row['id_kategori'],
        'nama_kategori' => $row['nama_kategori'],
    ];
}
