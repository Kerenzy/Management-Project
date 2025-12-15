<?php
require_once 'config/session.php';
require_once 'models/ProyekModel.php';
require_once 'models/UserModel.php';

requireLogin();
checkRole(['Owner']);

$currentUserId = getCurrentUserId();
$proyekModel = new ProyekModel();
$userModel = new UserModel();

$kepalaProyekList = $userModel->getAllUsers('Kepala Proyek');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_proyek = trim($_POST['nama_proyek'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $kepala_proyek_id = !empty($_POST['kepala_proyek_id']) ? intval($_POST['kepala_proyek_id']) : null;
    $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
    $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
    
    // Validation
    if (empty($nama_proyek)) {
        $_SESSION['error'] = 'Nama proyek harus diisi';
    } elseif (!empty($tanggal_selesai) && !empty($tanggal_mulai) && $tanggal_selesai < $tanggal_mulai) {
        $_SESSION['error'] = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai';
    } else {
        if ($proyekModel->create($nama_proyek, $deskripsi, $currentUserId, $kepala_proyek_id, $tanggal_mulai, $tanggal_selesai)) {
            $_SESSION['success'] = 'Proyek berhasil ditambahkan';
            header('Location: proyek.php');
            exit;
        } else {
            $_SESSION['error'] = 'Gagal menambahkan proyek';
        }
    }
}

$pageTitle = 'Tambah Proyek';
include 'views/includes/header.php';
?>

<div class="main-layout">
    <?php include 'views/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Tambah Proyek Baru</h2>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Nama Proyek *</label>
                    <input type="text" name="nama_proyek" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="deskripsi" class="form-control"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Kepala Proyek</label>
                    <select name="kepala_proyek_id" class="form-control">
                        <option value="">Pilih Kepala Proyek</option>
                        <?php foreach ($kepalaProyekList as $kp): ?>
                            <option value="<?php echo $kp['id']; ?>"><?php echo htmlspecialchars($kp['nama']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" class="form-control">
                </div>
                
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="proyek.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php include 'views/includes/footer.php'; ?>

