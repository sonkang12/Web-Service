# REST API Marketplace (PHP Native + MySQLi)

REST API untuk mengakses data aplikasi Web Marketplace agar dapat dipakai aplikasi mobile atau aplikasi pihak ketiga.

## A. Analisis dan Perancangan API

### a. Fungsi Web Service/API dan alasan diperlukan

Web Service/API adalah antarmuka yang memungkinkan satu aplikasi meminta dan mengirim data ke aplikasi lain melalui protokol HTTP, dengan format data standar (umumnya JSON). Pada marketplace, API berfungsi sebagai "pintu resmi" ke data produk, kategori, dan pesanan.

API diperlukan karena:

1. **Interoperabilitas**: aplikasi mobile (Android/iOS), toko di platform lain, atau sistem logistik tidak bisa mengakses database MySQL secara langsung; mereka berkomunikasi lewat API.
2. **Keamanan**: database tidak dibuka ke luar. API hanya menyediakan operasi yang diizinkan, lengkap dengan validasi input.
3. **Pemisahan tanggung jawab**: logika bisnis (cek stok, hitung total) berada di satu tempat, sehingga semua klien memperoleh perilaku yang sama.
4. **Independensi platform**: klien boleh memakai bahasa apa pun (Kotlin, Swift, JavaScript, Python) selama mampu mengirim HTTP request dan membaca JSON.
5. **Skalabilitas**: antarmuka web, mobile, dan mitra dapat dikembangkan terpisah tanpa mengubah database atau API.

### b. Rancangan Endpoint

| No | Method & Endpoint | Fungsi | Status sukses | Status error |
|----|-------------------|--------|---------------|--------------|
| 1 | `GET /api/produk.php` | Menampilkan seluruh produk (opsional `?kategori=`, `?q=`, `?page=`, `?limit=`) | 200 | 400, 405, 500 |
| 2 | `GET /api/produk.php?id=1` | Menampilkan detail produk | 200 | 400 (id tidak valid), 404 (tidak ditemukan) |
| 3 | `GET /api/kategori.php` | Menampilkan daftar kategori + jumlah produk | 200 | 405, 500 |
| 4 | `POST /api/pesanan.php` | Membuat pesanan baru | 201 | 400, 404, 409 (stok kurang), 422 |
| 5 | `GET /api/pesanan.php?id=1` | Melihat detail pesanan | 200 | 400, 404 |

### c. Struktur JSON Response

**Daftar produk** (`GET /api/produk.php`)

```json
{
  "status": "success",
  "total": 6,
  "page": 1,
  "limit": 20,
  "data": [
    {
      "id_produk": 1,
      "nama_produk": "Headphone Bluetooth X1",
      "deskripsi": "Headphone nirkabel dengan baterai tahan 30 jam.",
      "harga": 350000,
      "stok": 25,
      "gambar": "headphone-x1.jpg",
      "id_kategori": 1,
      "nama_kategori": "Elektronik"
    }
  ]
}
```

**Detail produk** (`GET /api/produk.php?id=1`)

```json
{
  "status": "success",
  "data": {
    "id_produk": 1,
    "nama_produk": "Headphone Bluetooth X1",
    "deskripsi": "Headphone nirkabel dengan baterai tahan 30 jam.",
    "harga": 350000,
    "stok": 25,
    "gambar": "headphone-x1.jpg",
    "id_kategori": 1,
    "nama_kategori": "Elektronik"
  }
}
```

**Error** (format seragam)

```json
{ "status": "error", "message": "Produk dengan id 999 tidak ditemukan." }
```

> Contoh di atas hanya ilustrasi struktur. Untuk laporan, gunakan response asli dari hasil pengujian Anda.

## B. Cara Menjalankan di XAMPP

1. Jalankan **Apache** dan **MySQL** di XAMPP Control Panel.
2. Salin folder proyek ini ke `C:\xampp\htdocs\marketplace-api` (nama folder bebas).
3. Buka `http://localhost/phpmyadmin` → tab **Import** → pilih `database.sql` → **Go**.
   *Jika Anda sudah punya database Soal 11, sesuaikan nama tabel/kolom pada file `api/*.php`.*
4. Salin `config.example.php` menjadi `config.php`, lalu sesuaikan kredensial MySQL jika perlu (default XAMPP: user `root`, password kosong).
5. Uji di browser: `http://localhost/marketplace-api/api/produk.php`

### Struktur proyek

```
marketplace-api/
├── api/
│   ├── produk.php
│   ├── kategori.php
│   └── pesanan.php
├── config.example.php   (template; config.php tidak diunggah ke Git)
├── helpers.php
├── koneksi.php
├── database.sql
└── README.md
```

## C. Skenario Pengujian

Ganti `marketplace-api` sesuai nama folder Anda.

| No | Request | Hasil yang diharapkan |
|----|---------|-----------------------|
| 1 | `GET http://localhost/marketplace-api/api/produk.php` | 200, daftar produk |
| 2 | `GET http://localhost/marketplace-api/api/produk.php?id=1` | 200, detail produk 1 |
| 3 | `GET http://localhost/marketplace-api/api/produk.php?id=999` | 404, produk tidak ditemukan |
| 4 | `GET http://localhost/marketplace-api/api/produk.php?id=abc` | 400, id tidak valid |
| 5 | `GET http://localhost/marketplace-api/api/kategori.php` | 200, daftar kategori |
| 6 | `POST http://localhost/marketplace-api/api/pesanan.php` (body di bawah) | 201, pesanan tersimpan |
| 7 | POST dengan `jumlah` melebihi stok | 409, stok tidak mencukupi |
| 8 | POST dengan body kosong/bukan JSON | 400 |
| 9 | `GET http://localhost/marketplace-api/api/pesanan.php?id=1` | 200, detail pesanan |
| 10 | `DELETE .../api/produk.php` | 405, method tidak diizinkan |

**Postman untuk POST pesanan**: pilih method `POST`, tab **Body** → **raw** → **JSON**:

```json
{
  "nama_pembeli": "Budi Santoso",
  "email": "budi@example.com",
  "alamat": "Jl. Merdeka No. 10, Curup, Bengkulu",
  "items": [
    { "id_produk": 1, "jumlah": 2 },
    { "id_produk": 5, "jumlah": 1 }
  ]
}
```

Atau lewat curl:

```bash
curl -X POST http://localhost/marketplace-api/api/pesanan.php \
  -H "Content-Type: application/json" \
  -d '{"nama_pembeli":"Budi Santoso","email":"budi@example.com","alamat":"Jl. Merdeka No. 10","items":[{"id_produk":1,"jumlah":2}]}'
```

Setelah POST berhasil, buka phpMyAdmin dan tampilkan tabel `pesanan`, `detail_pesanan`, serta kolom `stok` pada `produk` (stok harus berkurang).

## D. Checklist Screenshot Dokumentasi

- [ ] XAMPP Control Panel: Apache dan MySQL berstatus *Running*
- [ ] Browser: URL `localhost/.../api/produk.php` beserta response JSON
- [ ] Browser: URL `...produk.php?id=1` beserta response JSON
- [ ] Browser/Postman: kasus error (id tidak ada → 404)
- [ ] Postman GET produk (tampilkan method, URL, status code, body response)
- [ ] Postman POST pesanan (tampilkan tab Body request dan response 201)
- [ ] phpMyAdmin: data baru pada tabel `pesanan` dan `detail_pesanan`
- [ ] phpMyAdmin: stok produk berkurang

## E. Unggah ke GitHub

```bash
cd C:\xampp\htdocs\marketplace-api
git init
git add .
git commit -m "REST API marketplace PHP Native + MySQLi"
git branch -M main
git remote add origin https://github.com/<username>/marketplace-api.git
git push -u origin main
```

Pastikan `config.php` tidak ikut terunggah (sudah diabaikan oleh `.gitignore`), lalu cantumkan URL repository pada laporan.

## Keamanan yang diterapkan

- Prepared statement MySQLi pada semua query (mencegah SQL injection)
- Validasi input (tipe data, format email, jumlah positif)
- Transaksi + `SELECT ... FOR UPDATE` saat membuat pesanan (stok konsisten, rollback jika gagal)
- Pesan error tidak membocorkan detail internal database
- Kredensial tidak disimpan di repository
