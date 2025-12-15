# Sistem Manajemen Proyek - PT Multidaya Mitra Sinergi (MMS)

Sistem manajemen proyek berbasis PHP native (tanpa framework) untuk mengelola proyek, tugas, dan dokumentasi dengan 4 role berbeda.

## Fitur

### 1. Authentication
- Login dengan email dan password
- Register dengan validasi password
- Password di-hash menggunakan `password_hash()`
- Session management untuk autentikasi

### 2. Role-based Access Control
- **Admin**: Full access ke semua data dan fitur
- **Owner**: Membuat proyek, assign ke Kepala Proyek, download laporan
- **Kepala Proyek**: Membuat tugas, assign ke Karyawan, approve/reject tugas, generate laporan PDF
- **Karyawan**: Update progress tugas, upload dokumentasi, tandai tugas selesai

### 3. Manajemen Proyek
- CRUD Proyek (Owner membuat proyek)
- Assign proyek ke Kepala Proyek
- Tracking progress proyek (persentase)
- Status proyek: Planning, In Progress, On Hold, Completed, Cancelled

### 4. Manajemen Tugas
- Kepala Proyek membuat tugas dan assign ke Karyawan
- Karyawan update progress dan upload dokumentasi
- Kepala Proyek approve/reject tugas yang selesai
- Tracking progress per tugas

### 5. Dokumentasi
- Karyawan dapat upload file dokumentasi tugas
- Download dokumentasi
- Tracking file upload

### 6. Laporan
- Kepala Proyek generate laporan proyek (format HTML/PDF)
- Owner dan Admin dapat download laporan
- Laporan berisi detail proyek dan daftar tugas

## Struktur Folder

```
App managemen proyek/
├── config/
│   ├── database.php          # Konfigurasi database
│   ├── database.sql          # SQL schema
│   └── session.php          # Session management
├── controllers/
│   ├── AuthController.php   # Controller untuk authentication
│   └── ReportController.php # Controller untuk generate laporan
├── models/
│   ├── UserModel.php        # Model untuk users
│   ├── ProyekModel.php      # Model untuk proyek
│   ├── TugasModel.php       # Model untuk tugas
│   ├── DokumentasiModel.php # Model untuk dokumentasi
│   └── LaporanModel.php     # Model untuk laporan
├── views/
│   └── includes/
│       ├── header.php       # Header template
│       ├── sidebar.php      # Sidebar template
│       └── footer.php       # Footer template
├── assets/
│   └── css/
│       └── style.css        # Styling dengan tema #FF2800
├── uploads/                 # Folder untuk file upload
│   └── reports/             # Folder untuk laporan PDF
├── index.php                # Dashboard
├── login.php                # Halaman login
├── register.php             # Halaman register
├── logout.php               # Logout handler
├── proyek.php               # List proyek
├── proyek_tambah.php        # Tambah proyek
├── proyek_detail.php        # Detail proyek
├── proyek_edit.php          # Edit proyek
├── tugas.php                # List tugas
├── tugas_tambah.php         # Tambah tugas
├── tugas_detail.php         # Detail tugas
├── laporan.php              # Laporan proyek
├── users.php                # Manajemen user (Admin)
└── download_file.php        # Download file handler
```

## Instalasi

### 1. Persyaratan
- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Web server (Apache/Nginx)

### 2. Setup Database

1. Buat database baru:
```sql
CREATE DATABASE mms_project;
```

2. Import schema database:
```bash
mysql -u root -p mms_project < config/database.sql
```

Atau jalankan query SQL di file `config/database.sql`

### 3. Konfigurasi

Edit file `config/database.php` untuk menyesuaikan kredensial database:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mms_project');
```

### 4. Setup Folder Uploads

Pastikan folder `uploads` dan `uploads/reports` dapat ditulis:

```bash
chmod 755 uploads
mkdir -p uploads/reports
chmod 755 uploads/reports
```

### 5. Akses Aplikasi

- Login default Admin:
  - Email: `admin@mms.com`
  - Password: `admin123`

## Penggunaan

### Admin
- Mengakses semua data proyek, tugas, dan user
- Manage users

### Owner
1. Login sebagai Owner
2. Buat proyek baru di menu "Tambah Proyek"
3. Assign proyek ke Kepala Proyek
4. Download laporan proyek

### Kepala Proyek
1. Login sebagai Kepala Proyek
2. Lihat proyek yang di-assign
3. Buat tugas untuk proyek
4. Assign tugas ke Karyawan
5. Approve/Reject tugas yang selesai
6. Generate laporan proyek (PDF)

### Karyawan
1. Login sebagai Karyawan
2. Lihat tugas yang di-assign
3. Update progress tugas
4. Upload dokumentasi tugas
5. Tandai tugas sebagai selesai

## Tema dan Desain

- Warna utama: **#FF2800** (merah)
- Warna sekunder: **#FFFFFF** (putih)
- Desain minimalis dan modern
- Dashboard dengan progress bar
- Responsive design

## Keamanan

- Password di-hash menggunakan `password_hash()`
- Prepared statements untuk mencegah SQL injection
- Session management untuk autentikasi
- Role-based access control
- File upload validation

## Catatan

- Untuk production, disarankan menggunakan library PDF seperti TCPDF untuk generate laporan PDF yang lebih baik
- Install TCPDF: `composer require tecnickcom/tcpdf`
- Pastikan konfigurasi file upload size di PHP (`upload_max_filesize`, `post_max_size`)

## Lisensi

Proyek ini dibuat untuk PT Multidaya Mitra Sinergi (MMS).

