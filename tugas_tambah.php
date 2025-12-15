<?php
require_once 'config/session.php';
require_once 'models/TugasModel.php';
require_once 'models/ProyekModel.php';
require_once 'models/UserModel.php';

requireLogin();
checkRole(['Kepala Proyek']);

$currentUserId = getCurrentUserId();
$tugasModel = new TugasModel();
$proyekModel = new ProyekModel();
$userModel = new UserModel();

$proyek_id = $_GET['proyek_id'] ?? null;

// Get projects for this kepala proyek
$proyekList = $proyekModel->getByKepalaProyek($currentUserId);
$karyawanList = $userModel->getAllUsers('Karyawan');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proyek_id = $_POST['proyek_id'] ?? null;
    $nama_tugas = $_POST['nama_tugas'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $karyawan_id = $_POST['karyawan_id'] ?? null;
    $tanggal_deadline = $_POST['tanggal_deadline'] ?? '';
    
    if (empty($nama_tugas) || empty($proyek_id)) {
        $_SESSION['error'] = 'Nama tugas dan proyek harus diisi';
    } else {
        if ($tugasModel->create($proyek_id, $nama_tugas, $deskripsi, $currentUserId, $karyawan_id, $tanggal_deadline)) {
            // Update project progress after adding new task
            require_once 'models/ProyekModel.php';
            $proyekModel = new ProyekModel();
            $proyekModel->updateProgress($proyek_id);
            $_SESSION['success'] = 'Tugas berhasil ditambahkan';
            header('Location: tugas.php');
            exit;
        } else {
            $_SESSION['error'] = 'Gagal menambahkan tugas';
        }
    }
}

$pageTitle = 'Tambah Tugas';
include 'views/includes/header.php';
?>

<div class="main-layout">
    <?php include 'views/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Tambah Tugas Baru</h2>
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
                    <label class="form-label">Proyek *</label>
                    <select name="proyek_id" class="form-control" required>
                        <option value="">Pilih Proyek</option>
                        <?php foreach ($proyekList as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo $proyek_id == $p['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['nama_proyek']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nama Tugas *</label>
                    <input type="text" name="nama_tugas" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="deskripsi" class="form-control"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Assign ke Karyawan</label>
                    <select name="karyawan_id" class="form-control">
                        <option value="">Pilih Karyawan</option>
                        <?php foreach ($karyawanList as $k): ?>
                            <option value="<?php echo $k['id']; ?>"><?php echo htmlspecialchars($k['nama']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tanggal Deadline</label>
                    <input type="date" name="tanggal_deadline" class="form-control">
                </div>
                
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="tugas.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php include 'views/includes/footer.php'; ?>

