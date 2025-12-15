<?php
require_once 'config/session.php';
require_once 'models/ProyekModel.php';
require_once 'models/TugasModel.php';
require_once 'models/UserModel.php';

requireLogin();
$currentRole = getCurrentUserRole();
$currentUserId = getCurrentUserId();

$proyekModel = new ProyekModel();
$tugasModel = new TugasModel();

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

if ($currentRole === 'Kepala Proyek' && $proyek['kepala_proyek_id'] != $currentUserId) {
    $_SESSION['error'] = 'Akses ditolak';
    header('Location: proyek.php');
    exit;
}

$tugas = $tugasModel->getByProyek($id);

// Handle Owner approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_project']) && $currentRole === 'Owner') {
    if ($proyekModel->approveByOwner($id)) {
        $_SESSION['success'] = 'Proyek berhasil di-ACC dan ditandai sebagai Completed';
        header('Location: proyek_detail.php?id=' . $id);
        exit;
    } else {
        $_SESSION['error'] = 'Tidak dapat ACC proyek. Pastikan semua tugas sudah di-ACC oleh Kepala Proyek.';
    }
}

$pageTitle = 'Detail Proyek';
include 'views/includes/header.php';
?>

<div class="main-layout">
    <?php include 'views/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Detail Proyek: <?php echo htmlspecialchars($proyek['nama_proyek']); ?></h2>
                <div class="d-flex gap-2">
                    <?php if ($currentRole === 'Owner'): ?>
                        <a href="proyek_edit.php?id=<?php echo $proyek['id']; ?>" class="btn btn-secondary btn-small">Edit</a>
                    <?php endif; ?>
                    <a href="proyek.php" class="btn btn-secondary btn-small">Kembali</a>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Nama Proyek:</strong></label>
                <p><?php echo htmlspecialchars($proyek['nama_proyek']); ?></p>
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Deskripsi:</strong></label>
                <p><?php echo nl2br(htmlspecialchars($proyek['deskripsi'] ?? '-')); ?></p>
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Status:</strong></label>
                <span class="badge badge-<?php 
                    echo match($proyek['status']) {
                        'Completed' => 'success',
                        'In Progress' => 'primary',
                        'On Hold' => 'warning',
                        default => 'info'
                    };
                ?>">
                    <?php echo htmlspecialchars($proyek['status']); ?>
                </span>
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Progress:</strong></label>
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $proyek['progress']; ?>%;">
                            <?php echo $proyek['progress']; ?>%
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Owner:</strong></label>
                <p><?php echo htmlspecialchars($proyek['owner_nama'] ?? '-'); ?></p>
            </div>
            
            <?php if ($proyek['kepala_proyek_nama']): ?>
                <div class="form-group">
                    <label class="form-label"><strong>Kepala Proyek:</strong></label>
                    <p><?php echo htmlspecialchars($proyek['kepala_proyek_nama']); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label class="form-label"><strong>Tanggal Mulai:</strong></label>
                <p><?php echo htmlspecialchars($proyek['tanggal_mulai'] ?? '-'); ?></p>
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Tanggal Selesai:</strong></label>
                <p><?php echo htmlspecialchars($proyek['tanggal_selesai'] ?? '-'); ?></p>
            </div>
            
            <?php if ($currentRole === 'Owner'): ?>
                <?php
                // Check if all tasks are approved
                $allTasksApproved = false;
                $totalTasks = count($tugas);
                $approvedTasks = count(array_filter($tugas, fn($t) => $t['status'] === 'Approved'));
                
                if ($totalTasks > 0 && $approvedTasks == $totalTasks) {
                    $allTasksApproved = true;
                }
                ?>
                
                <?php if ($proyek['status'] === 'Completed'): ?>
                    <div class="card mt-3">
                        <div class="alert alert-success">
                            <strong>✓ Proyek sudah di-ACC dan Completed.</strong> Semua tugas sudah disetujui dan proyek selesai.
                        </div>
                    </div>
                <?php elseif ($allTasksApproved && $proyek['status'] !== 'Completed'): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">ACC Proyek</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Semua tugas sudah di-ACC oleh Kepala Proyek. Silakan ACC proyek ini untuk menandai proyek sebagai Completed.
                            </p>
                            <form method="POST" action="">
                                <button type="submit" name="approve_project" value="1" class="btn btn-success" onclick="return confirm('ACC proyek ini? Proyek akan ditandai sebagai Completed.');">
                                    ✓ ACC Proyek (Set Completed)
                                </button>
                            </form>
                        </div>
                    </div>
                <?php elseif ($totalTasks > 0): ?>
                    <div class="card mt-3">
                        <div class="alert alert-info">
                            <strong>Info:</strong> Masih ada tugas yang belum di-ACC oleh Kepala Proyek. 
                            (<?php echo $approvedTasks; ?> dari <?php echo $totalTasks; ?> tugas sudah di-ACC)
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
            <?php if ($currentRole === 'Kepala Proyek'): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Update Progress Proyek</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="refresh_progress" value="1">
                        <p class="text-muted">Klik tombol di bawah untuk memperbarui progress proyek berdasarkan tugas yang sudah di-ACC.</p>
                        <button type="submit" class="btn btn-primary" onclick="return confirm('Update progress proyek berdasarkan tugas yang sudah di-ACC?');">
                            🔄 Refresh Progress Proyek
                        </button>
                    </form>
                </div>
            </div>
            
            <?php
            // Handle progress refresh
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh_progress'])) {
                $proyekModel->updateProgress($proyek['id']);
                $_SESSION['success'] = 'Progress proyek berhasil diupdate';
                header('Location: proyek_detail.php?id=' . $proyek['id']);
                exit;
            }
            ?>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Tugas Proyek</h2>
                    <a href="tugas_tambah.php?proyek_id=<?php echo $proyek['id']; ?>" class="btn btn-primary btn-small">Tambah Tugas</a>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama Tugas</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Karyawan</th>
                            <th>Deadline</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tugas)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada tugas</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tugas as $t): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($t['nama_tugas']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo match($t['status']) {
                                                'Approved' => 'success',
                                                'Completed' => 'info',
                                                'Revisi' => 'warning',
                                                'In Progress' => 'primary',
                                                default => 'warning'
                                            };
                                        ?>">
                                            <?php 
                                            $statusText = $t['status'] === 'Approved' ? 'ACC' : $t['status'];
                                            echo htmlspecialchars($statusText); 
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress-container">
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $t['progress']; ?>%;">
                                                    <?php echo $t['progress']; ?>%
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($t['karyawan_nama'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($t['tanggal_deadline'] ?? '-'); ?></td>
                                    <td>
                                        <a href="tugas_detail.php?id=<?php echo $t['id']; ?>" class="btn btn-primary btn-small">Detail</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'views/includes/footer.php'; ?>

