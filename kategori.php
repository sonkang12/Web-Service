<?php
// GET /api/kategori.php  -> daftar kategori beserta jumlah produknya
require_once __DIR__ . '/../koneksi.php';

izinkan_method(['GET']);

$sql = "SELECT k.id_kategori, k.nama_kategori, COUNT(p.id_produk) AS jumlah_produk
        FROM kategori k
        LEFT JOIN produk p ON p.id_kategori = k.id_kategori
        GROUP BY k.id_kategori, k.nama_kategori
        ORDER BY k.nama_kategori ASC";

$hasil = $conn->query($sql);
if (!$hasil) {
    error_json(500, 'Gagal mengambil data kategori.');
}

$data = [];
while ($row = $hasil->fetch_assoc()) {
    $data[] = [
        'id_kategori'   => (int) $row['id_kategori'],
        'nama_kategori' => $row['nama_kategori'],
        'jumlah_produk' => (int) $row['jumlah_produk'],
    ];
}

kirim_json(200, ['status' => 'success', 'total' => count($data), 'data' => $data]);
