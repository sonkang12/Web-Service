<?php
// POST /api/pesanan.php        -> buat pesanan baru (body JSON)
// GET  /api/pesanan.php?id=1   -> lihat detail pesanan
require_once __DIR__ . '/../koneksi.php';

izinkan_method(['GET', 'POST']);

class PesananException extends Exception {}

// ===================== GET: detail pesanan =====================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['id']) || !ctype_digit((string) $_GET['id']) || (int) $_GET['id'] < 1) {
        error_json(400, 'Sertakan parameter id pesanan berupa angka positif, contoh: ?id=1');
    }
    $id = (int) $_GET['id'];

    $stmt = $conn->prepare("SELECT id_pesanan, nama_pembeli, email, alamat, total, status, created_at
                            FROM pesanan WHERE id_pesanan = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $pesanan = $stmt->get_result()->fetch_assoc();
    if (!$pesanan) {
        error_json(404, "Pesanan dengan id $id tidak ditemukan.");
    }

    $stmt = $conn->prepare("SELECT d.id_produk, p.nama_produk, d.jumlah, d.harga_satuan, d.subtotal
                            FROM detail_pesanan d
                            JOIN produk p ON p.id_produk = d.id_produk
                            WHERE d.id_pesanan = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while ($r = $res->fetch_assoc()) {
        $items[] = [
            'id_produk'    => (int) $r['id_produk'],
            'nama_produk'  => $r['nama_produk'],
            'jumlah'       => (int) $r['jumlah'],
            'harga_satuan' => (float) $r['harga_satuan'],
            'subtotal'     => (float) $r['subtotal'],
        ];
    }

    kirim_json(200, ['status' => 'success', 'data' => [
        'id_pesanan'   => (int) $pesanan['id_pesanan'],
        'nama_pembeli' => $pesanan['nama_pembeli'],
        'email'        => $pesanan['email'],
        'alamat'       => $pesanan['alamat'],
        'total'        => (float) $pesanan['total'],
        'status'       => $pesanan['status'],
        'created_at'   => $pesanan['created_at'],
        'items'        => $items,
    ]]);
}

// ===================== POST: buat pesanan =====================
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    error_json(400, 'Body request harus berupa JSON yang valid (Content-Type: application/json).');
}

$nama   = trim($input['nama_pembeli'] ?? '');
$email  = trim($input['email'] ?? '');
$alamat = trim($input['alamat'] ?? '');
$items  = $input['items'] ?? null;

if ($nama === '' || $email === '' || $alamat === '') {
    error_json(422, 'Field nama_pembeli, email, dan alamat wajib diisi.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    error_json(422, 'Format email tidak valid.');
}
if (!is_array($items) || count($items) === 0) {
    error_json(422, 'Field items wajib berupa array berisi minimal 1 produk.');
}

// Gabungkan item dengan id_produk yang sama dan validasi tipe data.
$keranjang = [];
foreach ($items as $item) {
    $idp = $item['id_produk'] ?? null;
    $jml = $item['jumlah'] ?? null;
    if (!is_int($idp) || $idp < 1 || !is_int($jml) || $jml < 1) {
        error_json(422, 'Setiap item harus memiliki id_produk dan jumlah berupa bilangan bulat positif.');
    }
    $keranjang[$idp] = ($keranjang[$idp] ?? 0) + $jml;
}

$conn->begin_transaction();
try {
    $total   = 0;
    $rincian = [];

    $stmtProduk = $conn->prepare("SELECT id_produk, nama_produk, harga, stok
                                  FROM produk WHERE id_produk = ? FOR UPDATE");
    foreach ($keranjang as $idp => $jml) {
        $stmtProduk->bind_param('i', $idp);
        $stmtProduk->execute();
        $p = $stmtProduk->get_result()->fetch_assoc();

        if (!$p) {
            throw new PesananException("Produk dengan id $idp tidak ditemukan.", 404);
        }
        if ((int) $p['stok'] < $jml) {
            throw new PesananException(
                "Stok produk '{$p['nama_produk']}' tidak mencukupi (tersisa {$p['stok']}).", 409
            );
        }
        $subtotal  = (float) $p['harga'] * $jml;
        $total    += $subtotal;
        $rincian[] = [
            'id_produk'    => (int) $p['id_produk'],
            'nama_produk'  => $p['nama_produk'],
            'jumlah'       => $jml,
            'harga_satuan' => (float) $p['harga'],
            'subtotal'     => $subtotal,
        ];
    }

    $stmt = $conn->prepare("INSERT INTO pesanan (nama_pembeli, email, alamat, total) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('sssd', $nama, $email, $alamat, $total);
    $stmt->execute();
    $idPesanan = $conn->insert_id;

    $stmtDetail = $conn->prepare("INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga_satuan, subtotal)
                                  VALUES (?, ?, ?, ?, ?)");
    $stmtStok = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");
    foreach ($rincian as $r) {
        $stmtDetail->bind_param('iiidd', $idPesanan, $r['id_produk'], $r['jumlah'], $r['harga_satuan'], $r['subtotal']);
        $stmtDetail->execute();
        $stmtStok->bind_param('ii', $r['jumlah'], $r['id_produk']);
        $stmtStok->execute();
    }

    $conn->commit();
} catch (PesananException $e) {
    $conn->rollback();
    error_json($e->getCode(), $e->getMessage());
} catch (Throwable $e) {
    $conn->rollback();
    error_json(500, 'Terjadi kesalahan saat menyimpan pesanan.');
}

kirim_json(201, [
    'status'  => 'success',
    'message' => 'Pesanan berhasil dibuat.',
    'data'    => [
        'id_pesanan'   => (int) $idPesanan,
        'nama_pembeli' => $nama,
        'email'        => $email,
        'alamat'       => $alamat,
        'total'        => $total,
        'status'       => 'baru',
        'items'        => $rincian,
    ],
]);
