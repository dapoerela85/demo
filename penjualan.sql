CREATE TABLE penjualan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tgl DATE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    instansi VARCHAR(100),
    nama_produk VARCHAR(150) NOT NULL,
    harga_produk DECIMAL(12, 2) NOT NULL, -- harga per item
    jumlah INT NOT NULL,
    total_harga DECIMAL(12, 2) GENERATED ALWAYS AS (harga_produk * jumlah) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;