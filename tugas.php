<?php
require_once 'config/session.php';
require_once 'models/TugasModel.php';
require_once 'models/ProyekModel.php';

requireLogin();
$currentRole = getCurrentUserRole();
$currentUserId = getCurrentUserId();

$tugasModel = new TugasModel();

if ($currentRole === 'Owner') {
    // Owner full access: see all tasks
    $tugas = $tugasModel->getAll();
} elseif ($currentRole === 'Kepala Proyek') {
    $tugas = $tugasModel->getByKepalaProyek($currentUserId);
} elseif ($currentRole === 'Karyawan') {
    $tugas = $tugasModel->getByKaryawan($currentUserId);
} else {
    $_SESSION['error'] = 'Akses ditolak';
    header('Location: index.php');
    exit;
}

$pageTitle = 'Manajemen Tugas';
include 'views/includes/header.php';
?>

<div class="main-layout">
    <?php include 'views/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Daftar Tugas</h2>
                <?php if ($currentRole === 'Kepala Proyek'): ?>
                    <a href="tugas_tambah.php" class="btn btn-primary">Tambah Tugas</a>
                <?php endif; ?>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Tugas</th>
                        <th>Proyek</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <?php if ($currentRole !== 'Karyawan'): ?>
                            <th>Karyawan</th>
                        <?php endif; ?>
                        <?php if ($currentRole === 'Owner'): ?>
                            <th>Kepala Proyek</th>
                        <?php endif; ?>
                        <th>Deadline</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tugas)): ?>
                        <tr>
                            <td colspan="<?php echo $currentRole === 'Owner' ? '7' : ($currentRole === 'Karyawan' ? '5' : '6'); ?>" class="text-center">Tidak ada tugas</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tugas as $t): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($t['nama_tugas']); ?></td>
                                <td><?php echo htmlspecialchars($t['nama_proyek'] ?? '-'); ?></td>
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
                                <?php if ($currentRole !== 'Karyawan'): ?>
                                    <td><?php echo htmlspecialchars($t['karyawan_nama'] ?? '-'); ?></td>
                                <?php endif; ?>
                                <?php if ($currentRole === 'Owner'): ?>
                                    <td><?php echo htmlspecialchars($t['kepala_proyek_nama'] ?? '-'); ?></td>
                                <?php endif; ?>
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
    </div>
</div>

<?php include 'views/includes/footer.php'; ?>

