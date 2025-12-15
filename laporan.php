<?php
require_once 'config/session.php';
require_once 'models/LaporanModel.php';
require_once 'models/ProyekModel.php';
require_once 'models/TugasModel.php';
require_once 'controllers/ReportController.php';

requireLogin();
$currentRole = getCurrentUserRole();
$currentUserId = getCurrentUserId();

$laporanModel = new LaporanModel();
$proyekModel = new ProyekModel();

// Handle PDF generation
if (isset($_GET['generate']) && $currentRole === 'Kepala Proyek') {
    $proyek_id = $_GET['generate'] ?? 0;
    $reportController = new ReportController();
    $reportController->generateReport($proyek_id);
    exit;
}

// Handle report download (Owner full access + Kepala Proyek untuk proyeknya)
if (isset($_GET['download']) && ($currentRole === 'Owner' || $currentRole === 'Kepala Proyek')) {
    $laporan_id = $_GET['download'] ?? 0;
    $laporan = $laporanModel->getById($laporan_id);
    
    if ($laporan && file_exists($laporan['path_file'])) {
        $extension = strtolower(pathinfo($laporan['path_file'], PATHINFO_EXTENSION));
        
        // Determine content type based on file extension
        if ($extension === 'html') {
            // HTML file - output as HTML (can be printed to PDF)
            header('Content-Type: text/html; charset=UTF-8');
            header('Content-Disposition: inline; filename="' . $laporan['nama_file'] . '"');
            readfile($laporan['path_file']);
            exit;
        } elseif ($extension === 'pdf') {
            // Actual PDF file
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $laporan['nama_file'] . '"');
            readfile($laporan['path_file']);
            exit;
        } else {
            // Unknown type - try to read as text
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $laporan['nama_file'] . '"');
            readfile($laporan['path_file']);
            exit;
        }
    } else {
        $_SESSION['error'] = 'File laporan tidak ditemukan';
    }
}

// Get reports based on role
if ($currentRole === 'Owner') {
    // Owner now has full access: see all reports
    $laporan = $laporanModel->getAll();
} elseif ($currentRole === 'Owner') {
    $myProyek = $proyekModel->getByOwner($currentUserId);
    $laporan = [];
    foreach ($myProyek as $p) {
        $laporanProyek = $laporanModel->getByProyek($p['id']);
        $laporan = array_merge($laporan, $laporanProyek);
    }
} elseif ($currentRole === 'Kepala Proyek') {
    $myProyek = $proyekModel->getByKepalaProyek($currentUserId);
    $laporan = [];
    foreach ($myProyek as $p) {
        $laporanProyek = $laporanModel->getByProyek($p['id']);
        $laporan = array_merge($laporan, $laporanProyek);
    }
} else {
    $laporan = [];
}

$pageTitle = 'Laporan Proyek';
include 'views/includes/header.php';
?>

<div class="main-layout">
    <?php include 'views/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php if ($currentRole === 'Kepala Proyek'): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Generate Laporan Proyek</h2>
                </div>
                
                <form method="GET" action="">
                    <div class="form-group">
                        <label class="form-label">Pilih Proyek</label>
                        <select name="generate" class="form-control" required>
                            <option value="">Pilih Proyek</option>
                            <?php 
                            $myProyek = $proyekModel->getByKepalaProyek($currentUserId);
                            foreach ($myProyek as $p): 
                            ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nama_proyek']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Generate Laporan PDF</button>
                </form>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Daftar Laporan</h2>
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
                        <th>Nama File</th>
                        <th>Proyek</th>
                        <?php if ($currentRole === 'Owner'): ?>
                            <th>Kepala Proyek</th>
                        <?php endif; ?>
                        <th>Tanggal Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($laporan)): ?>
                        <tr>
                            <td colspan="<?php echo $currentRole === 'Owner' ? '4' : '3'; ?>" class="text-center">Tidak ada laporan</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($laporan as $l): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($l['nama_file']); ?></td>
                                <td><?php echo htmlspecialchars($l['nama_proyek'] ?? '-'); ?></td>
                                <?php if ($currentRole === 'Owner'): ?>
                                    <td><?php echo htmlspecialchars($l['kepala_proyek_nama'] ?? '-'); ?></td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars($l['created_at']); ?></td>
                                <td>
                                    <a href="laporan.php?download=<?php echo $l['id']; ?>" class="btn btn-primary btn-small">Download</a>
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

