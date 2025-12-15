<?php
require_once 'config/session.php';
require_once 'models/ProyekModel.php';
require_once 'models/UserModel.php';

requireLogin();
$currentRole = getCurrentUserRole();
$currentUserId = getCurrentUserId();

checkRole(['Owner', 'Kepala Proyek']);

$proyekModel = new ProyekModel();

if ($currentRole === 'Owner') {
    // Owner full access: see all projects
    $proyek = $proyekModel->getAll();
} elseif ($currentRole === 'Kepala Proyek') {
    $proyek = $proyekModel->getByKepalaProyek($currentUserId);
} else {
    // Redirect if unauthorized
    $_SESSION['error'] = 'Akses ditolak';
    header('Location: index.php');
    exit;
}

$pageTitle = 'Manajemen Proyek';
include 'views/includes/header.php';
?>

<div class="main-layout">
    <?php include 'views/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Daftar Proyek</h2>
                <?php if ($currentRole === 'Owner'): ?>
                    <a href="proyek_tambah.php" class="btn btn-primary">Tambah Proyek</a>
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
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Proyek</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <?php if ($currentRole === 'Owner'): ?>
                            <th>Owner</th>
                        <?php endif; ?>
                        <?php if ($currentRole === 'Owner'): ?>
                            <th>Kepala Proyek</th>
                        <?php endif; ?>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($proyek)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Tidak ada proyek</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($proyek as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['nama_proyek']); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo match($p['status']) {
                                            'Completed' => 'success',
                                            'In Progress' => 'primary',
                                            'On Hold' => 'warning',
                                            default => 'info'
                                        };
                                    ?>">
                                        <?php echo htmlspecialchars($p['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="progress-container">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $p['progress']; ?>%;">
                                                <?php echo $p['progress']; ?>%
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <?php if ($currentRole === 'Owner'): ?>
                                    <td><?php echo htmlspecialchars($p['owner_nama'] ?? '-'); ?></td>
                                <?php endif; ?>
                                <?php if ($currentRole === 'Owner'): ?>
                                    <td><?php echo htmlspecialchars($p['kepala_proyek_nama'] ?? '-'); ?></td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars($p['tanggal_mulai'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($p['tanggal_selesai'] ?? '-'); ?></td>
                                <td>
                                    <a href="proyek_detail.php?id=<?php echo $p['id']; ?>" class="btn btn-primary btn-small">Detail</a>
                                    <?php if ($currentRole === 'Owner'): ?>
                                        <a href="proyek_edit.php?id=<?php echo $p['id']; ?>" class="btn btn-secondary btn-small">Edit</a>
                                    <?php endif; ?>
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

