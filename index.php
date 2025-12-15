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
$userModel = new UserModel();

// Get statistics based on role
$totalProyek = 0;
$totalTugas = 0;
$proyekSelesai = 0;
$tugasSelesai = 0;

if ($currentRole === 'Owner') {
    // Owner full access: aggregate from all projects and tasks
    $allProyek = $proyekModel->getAll();
    $allTugas = $tugasModel->getAll();
    $totalProyek = count($allProyek);
    $totalTugas = count($allTugas);
    $proyekSelesai = count(array_filter($allProyek, fn($p) => $p['status'] === 'Completed'));
    $tugasSelesai = count(array_filter($allTugas, fn($t) => in_array($t['status'], ['Completed', 'Approved'])));
} elseif ($currentRole === 'Kepala Proyek') {
    $allProyek = $proyekModel->getByKepalaProyek($currentUserId);
    $allTugas = $tugasModel->getByKepalaProyek($currentUserId);
    $totalProyek = count($allProyek);
    $totalTugas = count($allTugas);
    $proyekSelesai = count(array_filter($allProyek, fn($p) => $p['status'] === 'Completed'));
    $tugasSelesai = count(array_filter($allTugas, fn($t) => in_array($t['status'], ['Completed', 'Approved'])));
} elseif ($currentRole === 'Karyawan') {
    $allTugas = $tugasModel->getByKaryawan($currentUserId);
    $totalTugas = count($allTugas);
    $tugasSelesai = count(array_filter($allTugas, fn($t) => in_array($t['status'], ['Completed', 'Approved'])));
}

// Force refresh progress for all projects when dashboard loads
// This ensures dashboard always shows latest progress
if ($currentRole !== 'Karyawan') {
    foreach ($allProyek ?? [] as $p) {
        $proyekModel->updateProgress($p['id']);
    }
}

$pageTitle = 'Dashboard';
include 'views/includes/header.php';
?>

<div class="main-layout">
    <?php include 'views/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <h1>Dashboard - <?php echo htmlspecialchars($_SESSION['user_nama']); ?></h1>
        
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
        
        <div class="stats-grid">
            <?php if ($currentRole !== 'Karyawan'): ?>
                <div class="stat-card">
                    <h3><?php echo $totalProyek; ?></h3>
                    <p>Total Proyek</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $proyekSelesai; ?></h3>
                    <p>Proyek Selesai</p>
                </div>
            <?php endif; ?>
            
            <div class="stat-card">
                <h3><?php echo $totalTugas; ?></h3>
                <p>Total Tugas</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $tugasSelesai; ?></h3>
                <p>Tugas Selesai</p>
            </div>
        </div>
        
        <?php if ($currentRole !== 'Karyawan'): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Proyek Terkini</h2>
                    <?php if ($currentRole === 'Owner'): ?>
                        <a href="proyek_tambah.php" class="btn btn-primary btn-small">Tambah Proyek</a>
                    <?php endif; ?>
                </div>
                
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
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $displayProyek = array_slice($allProyek ?? [], 0, 5);
                        foreach ($displayProyek as $proyek): 
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($proyek['nama_proyek']); ?></td>
                                <td>
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
                                </td>
                                <td>
                                    <div class="progress-container">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $proyek['progress']; ?>%;">
                                                <?php echo $proyek['progress']; ?>%
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <?php if ($currentRole === 'Owner'): ?>
                                    <td><?php echo htmlspecialchars($proyek['owner_nama'] ?? '-'); ?></td>
                                <?php endif; ?>
                                <?php if ($currentRole === 'Owner'): ?>
                                    <td><?php echo htmlspecialchars($proyek['kepala_proyek_nama'] ?? '-'); ?></td>
                                <?php endif; ?>
                                <td>
                                    <a href="proyek_detail.php?id=<?php echo $proyek['id']; ?>" class="btn btn-primary btn-small">Detail</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Tugas Terkini</h2>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Tugas</th>
                        <th>Proyek</th>
                        <th>Status</th>
                        <?php if ($currentRole === 'Karyawan'): ?>
                            <th>Progress</th>
                        <?php endif; ?>
                        <?php if ($currentRole !== 'Karyawan'): ?>
                            <th>Karyawan</th>
                        <?php endif; ?>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $displayTugas = array_slice($allTugas ?? [], 0, 5);
                    foreach ($displayTugas as $tugas): 
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tugas['nama_tugas']); ?></td>
                            <td><?php echo htmlspecialchars($tugas['nama_proyek'] ?? '-'); ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo match($tugas['status']) {
                                        'Approved' => 'success',
                                        'Completed' => 'info',
                                        'Revisi' => 'warning',
                                        'In Progress' => 'primary',
                                        default => 'warning'
                                    };
                                ?>">
                                    <?php 
                                    $statusText = $tugas['status'] === 'Approved' ? 'ACC' : $tugas['status'];
                                    echo htmlspecialchars($statusText); 
                                    ?>
                                </span>
                            </td>
                            <?php if ($currentRole === 'Karyawan'): ?>
                                <td>
                                    <div class="progress-container">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $tugas['progress']; ?>%;">
                                                <?php echo $tugas['progress']; ?>%
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            <?php endif; ?>
                            <?php if ($currentRole !== 'Karyawan'): ?>
                                <td><?php echo htmlspecialchars($tugas['karyawan_nama'] ?? '-'); ?></td>
                            <?php endif; ?>
                            <td>
                                <a href="tugas_detail.php?id=<?php echo $tugas['id']; ?>" class="btn btn-primary btn-small">Detail</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'views/includes/footer.php'; ?>

