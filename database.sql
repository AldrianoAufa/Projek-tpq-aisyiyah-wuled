-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'guru', 'kepsek') NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Classes Table
CREATE TABLE IF NOT EXISTS kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(50) NOT NULL UNIQUE
);

-- Teacher-Class Relationship
CREATE TABLE IF NOT EXISTS guru_kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    kelas_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE
);

-- Students Table
CREATE TABLE IF NOT EXISTS siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    login_code VARCHAR(50) NOT NULL UNIQUE,
    kelas_id INT,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Academic Year Table
CREATE TABLE IF NOT EXISTS tahun_akademik (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(50) NOT NULL, -- e.g., "2023/2024"
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT 0
);

-- Holidays Table
CREATE TABLE IF NOT EXISTS libur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    deskripsi VARCHAR(255),
    tahun_akademik_id INT,
    FOREIGN KEY (tahun_akademik_id) REFERENCES tahun_akademik(id) ON DELETE CASCADE
);

-- Other Fees Master Data
CREATE TABLE IF NOT EXISTS biaya_lain_master (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    jumlah DECIMAL(10, 2) NOT NULL
);

-- Daily SPP Payments (Ikhsan)
CREATE TABLE IF NOT EXISTS pembayaran_spp_harian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    tanggal DATE NOT NULL,
    jumlah DECIMAL(10, 2) NOT NULL,
    user_id INT, -- Who recorded the payment
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Weekly SPP Payments (Infaq)
CREATE TABLE IF NOT EXISTS pembayaran_spp_mingguan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    minggu_ke INT NOT NULL, -- 1-5
    bulan INT NOT NULL, -- 1-12
    tahun INT NOT NULL, -- Year
    jumlah DECIMAL(10, 2) NOT NULL,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Other Fees Payments
CREATE TABLE IF NOT EXISTS pembayaran_biaya_lain (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    biaya_lain_id INT NOT NULL,
    jumlah DECIMAL(10, 2) NOT NULL,
    tanggal DATE NOT NULL,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (biaya_lain_id) REFERENCES biaya_lain_master(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert Default Admin
INSERT IGNORE INTO users (username, password, role, name) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator'); 
-- Password is 'password'
