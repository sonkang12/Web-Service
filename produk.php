<?php
// GET /api/produk.php                 -> semua produk (opsional: ?kategori=1, ?q=kata, ?page=1&limit=10)
// GET /api/produk.php?id=1            -> detail satu produk
require_once __DIR__ . '/../koneksi.php';

izinkan_method(['GET']);

$sqlDasar = "SELECT p.id_produk, p.nama_produk, p.deskripsi, p.harga, p.stok, p.gambar,
                    p.id_kategori, k.nama_kategori
             FROM produk p
             JOIN kategori k ON k.id_kategori = p.id_kategori";

// ---- Detail produk ----
if (isset($_GET['id'])) {
    if (!ctype_digit((string) $_GET['id']) || (int) $_GET['id'] < 1) {
        error_json(400, 'Parameter id harus berupa angka positif.');
    }
    $id = (int) $_GET['id'];

    $stmt = $conn->prepare($sqlDasar . " WHERE p.id_produk = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        error_json(404, "Produk dengan id $id tidak ditemukan.");
    }
    kirim_json(200, ['status' => 'success', 'data' => format_produk($row)]);
}

// ---- Daftar produk (filter + paginasi) ----
$where  = [];
$tipe   = '';
$params = [];

if (isset($_GET['kategori'])) {
    if (!ctype_digit((string) $_GET['kategori'])) {
        error_json(400, 'Parameter kategori harus berupa angka.');
    }
    $where[]  = 'p.id_kategori = ?';
    $tipe    .= 'i';
    $params[] = (int) $_GET['kategori'];
}
if (isset($_GET['q']) && trim($_GET['q']) !== '') {
    $where[]  = 'p.nama_produk LIKE ?';
    $tipe    .= 's';
    $params[] = '%' . trim($_GET['q']) . '%';
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$page  = isset($_GET['page'])  && ctype_digit((string) $_GET['page'])  ? max(1, (int) $_GET['page']) : 1;
$limit = isset($_GET['limit']) && ctype_digit((string) $_GET['limit']) ? min(100, max(1, (int) $_GET['limit'])) : 20;
$offset = ($page - 1) * $limit;

// Total data
$stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM produk p" . $whereSql);
if ($params) {
    $stmtCount->bind_param($tipe, ...$params);
}
$stmtCount->execute();
$total = (int) $stmtCount->get_result()->fetch_assoc()['total'];

// Data halaman
$stmt = $conn->prepare($sqlDasar . $whereSql . " ORDER BY p.id_produk ASC LIMIT ? OFFSET ?");
$tipeData   = $tipe . 'ii';
$paramsData = array_merge($params, [$limit, $offset]);
$stmt->bind_param($tipeData, ...$paramsData);
$stmt->execute();
$hasil = $stmt->get_result();

$data = [];
while ($row = $hasil->fetch_assoc()) {
    $data[] = format_produk($row);
}

kirim_json(200, [
    'status' => 'success',
    'total'  => $total,
    'page'   => $page,
    'limit'  => $limit,
    'data'   => $data,
]);
