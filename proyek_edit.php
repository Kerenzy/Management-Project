<?php
require_once 'config/session.php';
require_once 'models/ProyekModel.php';
require_once 'models/UserModel.php';

requireLogin();
checkRole(['Owner']);

$currentUserId = getCurrentUserId();
$currentRole = getCurrentUserRole();

$proyekModel = new ProyekModel();
$userModel = new UserModel();

$id = $_GET['id'] ?? 0;
$proyek = $proyekModel->getById($id);

if (!$proyek) {
    $_SESSION['error'] = 'Proyek tidak ditemukan';
    header('Location: proyek.php');
    exit;
}

// Check access
if ($currentRole === 'Owner' && $proyek['owner_id'] != $currentUserId) {
    $_SESSION['error'] = 'Akses ditolak';
    header('Location: proyek.php');
    exit;
}

$kepalaProyekList = $userModel->getAllUsers('Kepala Proyek');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_proyek = trim($_POST['nama_proyek'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status = $_POST['status'] ?? 'Planning';
    $progress = intval($_POST['progress'] ?? 0);
    $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
    $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
    
    if ($currentRole === 'Owner') {
        $kepala_proyek_id = $_POST['kepala_proyek_id'] ?? null;
        if (empty($kepala_proyek_id)) {
            $kepala_proyek_id = null;
        }
        if ($kepala_proyek_id != $proyek['kepala_proyek_id']) {
            $proyekModel->updateKepalaProyek($id, $kepala_proyek_id);
        }
    }
    
    if (empty($nama_proyek)) {
        $_SESSION['error'] = 'Nama proyek harus diisi';
    } else {
        // Note: Progress should be auto-calculated, but allow manual override for Owner (full access)
        if ($currentRole === 'Owner') {
            // Owner can manually set progress (opsional)
        } else {
            // For Owner, recalculate progress from tasks
            $proyekModel->updateProgress($id);
            $updatedProyek = $proyekModel->getById($id);
            $progress = $updatedProyek['progress'];
        }
        
        if ($proyekModel->update($id, $nama_proyek, $deskripsi, $status, $progress, $tanggal_mulai, $tanggal_selesai)) {
            $_SESSION['success'] = 'Proyek berhasil diupdate';
            header('Location: proyek_detail.php?id=' . $id);
            exit;
        } else {
            $_SESSION['error'] = 'Gagal mengupdate proyek';
        }
    }
}

$pageTitle = 'Edit Proyek';
include 'views/includes/header.php';
?>

<div class="main-layout">
    <?php include 'views/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Edit Proyek</h2>
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
                    <input type="text" name="nama_proyek" class="form-control" value="<?php echo htmlspecialchars($proyek['nama_proyek']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="deskripsi" class="form-control"><?php echo htmlspecialchars($proyek['deskripsi'] ?? ''); ?></textarea>
                </div>
                
                <?php if ($currentRole === 'Owner'): ?>
                    <div class="form-group">
                        <label class="form-label">Kepala Proyek</label>
                        <select name="kepala_proyek_id" class="form-control">
                            <option value="">Pilih Kepala Proyek</option>
                            <?php foreach ($kepalaProyekList as $kp): ?>
                                <option value="<?php echo $kp['id']; ?>" <?php echo $proyek['kepala_proyek_id'] == $kp['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($kp['nama']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="Planning" <?php echo $proyek['status'] === 'Planning' ? 'selected' : ''; ?>>Planning</option>
                        <option value="In Progress" <?php echo $proyek['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="On Hold" <?php echo $proyek['status'] === 'On Hold' ? 'selected' : ''; ?>>On Hold</option>
                        <option value="Completed" <?php echo $proyek['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="Cancelled" <?php echo $proyek['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Progress (%)</label>
                    <input type="number" name="progress" class="form-control" min="0" max="100" value="<?php echo $proyek['progress']; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control" value="<?php echo $proyek['tanggal_mulai'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" class="form-control" value="<?php echo $proyek['tanggal_selesai'] ?? ''; ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="proyek_detail.php?id=<?php echo $id; ?>" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php include 'views/includes/footer.php'; ?>

