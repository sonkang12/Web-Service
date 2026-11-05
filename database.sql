-- Database Marketplace untuk pengujian REST API
-- Impor lewat phpMyAdmin (tab Import) atau: mysql -u root < database.sql
-- CATATAN: jika database Soal 11 Anda memiliki nama tabel/kolom berbeda,
-- sesuaikan query pada folder api/ agar cocok dengan skema Anda.

CREATE DATABASE IF NOT EXISTS marketplace
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE marketplace;

DROP TABLE IF EXISTS detail_pesanan;
DROP TABLE IF EXISTS pesanan;
DROP TABLE IF EXISTS produk;
DROP TABLE IF EXISTS kategori;

CREATE TABLE kategori (
  id_kategori   INT AUTO_INCREMENT PRIMARY KEY,
  nama_kategori VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE produk (
  id_produk    INT AUTO_INCREMENT PRIMARY KEY,
  id_kategori  INT NOT NULL,
  nama_produk  VARCHAR(150) NOT NULL,
  deskripsi    TEXT,
  harga        DECIMAL(12,2) NOT NULL,
  stok         INT NOT NULL DEFAULT 0,
  gambar       VARCHAR(255) DEFAULT NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_produk_kategori FOREIGN KEY (id_kategori)
    REFERENCES kategori(id_kategori) ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pesanan (
  id_pesanan    INT AUTO_INCREMENT PRIMARY KEY,
  nama_pembeli  VARCHAR(100) NOT NULL,
  email         VARCHAR(100) NOT NULL,
  alamat        TEXT NOT NULL,
  total         DECIMAL(14,2) NOT NULL DEFAULT 0,
  status        ENUM('baru','dibayar','dikirim','selesai','batal') NOT NULL DEFAULT 'baru',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE detail_pesanan (
  id_detail    INT AUTO_INCREMENT PRIMARY KEY,
  id_pesanan   INT NOT NULL,
  id_produk    INT NOT NULL,
  jumlah       INT NOT NULL,
  harga_satuan DECIMAL(12,2) NOT NULL,
  subtotal     DECIMAL(14,2) NOT NULL,
  CONSTRAINT fk_detail_pesanan FOREIGN KEY (id_pesanan)
    REFERENCES pesanan(id_pesanan) ON DELETE CASCADE,
  CONSTRAINT fk_detail_produk FOREIGN KEY (id_produk)
    REFERENCES produk(id_produk)
) ENGINE=InnoDB;

INSERT INTO kategori (nama_kategori) VALUES
('Elektronik'), ('Fashion'), ('Buku'), ('Peralatan Rumah');

INSERT INTO produk (id_kategori, nama_produk, deskripsi, harga, stok, gambar) VALUES
(1, 'Headphone Bluetooth X1', 'Headphone nirkabel dengan baterai tahan 30 jam.', 350000, 25, 'headphone-x1.jpg'),
(1, 'Power Bank 10000 mAh', 'Power bank slim dengan dua port USB.', 185000, 40, 'powerbank.jpg'),
(2, 'Kaos Polos Katun', 'Kaos katun combed 30s, tersedia berbagai ukuran.', 75000, 100, 'kaos.jpg'),
(2, 'Jaket Hoodie Unisex', 'Hoodie fleece tebal dan nyaman.', 220000, 30, 'hoodie.jpg'),
(3, 'Pemrograman Web dengan PHP', 'Buku panduan PHP Native dan MySQLi untuk pemula.', 95000, 50, 'buku-php.jpg'),
(4, 'Rak Buku Minimalis', 'Rak 4 tingkat dari kayu MDF.', 450000, 10, 'rak.jpg');
