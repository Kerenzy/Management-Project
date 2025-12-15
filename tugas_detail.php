<?php
require_once 'config/session.php';
require_once 'models/TugasModel.php';
require_once 'models/DokumentasiModel.php';
require_once 'models/ProyekModel.php';

requireLogin();
$currentRole = getCurrentUserRole();
$currentUserId = getCurrentUserId();

$tugasModel = new TugasModel();
$dokumentasiModel = new DokumentasiModel();

$id = $_GET['id'] ?? 0;
$tugas = $tugasModel->getById($id);

if (!$tugas) {
    $_SESSION['error'] = 'Tugas tidak ditemukan';
    header('Location: tugas.php');
    exit;
}

// Check access
if ($currentRole === 'Karyawan' && $tugas['karyawan_id'] != $currentUserId) {
    $_SESSION['error'] = 'Akses ditolak';
    header('Location: tugas.php');
    exit;
}

if ($currentRole === 'Kepala Proyek' && $tugas['kepala_proyek_id'] != $currentUserId) {
    $_SESSION['error'] = 'Akses ditolak';
    header('Location: tugas.php');
    exit;
}

$dokumentasi = $dokumentasiModel->getByTugas($id);

// Handle task status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status']) && isset($_POST['status'])) {
        $status = $_POST['status'] ?? '';
        
        if ($currentRole === 'Kepala Proyek' && in_array($status, ['Approved', 'Rejected', 'Revisi'])) {
            $tugasModel->updateStatus($id, $status);
            
            // Always update project progress when status changes
            require_once 'models/ProyekModel.php';
            $proyekModel = new ProyekModel();
            $proyekModel->updateProgress($tugas['proyek_id']);
            
            $statusText = $status === 'Approved' ? 'ACC' : ($status === 'Rejected' ? 'Revisi' : 'Revisi');
            $_SESSION['success'] = "Tugas berhasil di-{$statusText}";
            header('Location: tugas_detail.php?id=' . $id);
            exit;
        } elseif ($currentRole === 'Karyawan' && $status === 'Completed') {
            // Validate: progress must be 100% before marking as completed
            if ($tugas['progress'] < 100) {
                $_SESSION['error'] = 'Progress harus 100% sebelum menandai tugas sebagai selesai';
                header('Location: tugas_detail.php?id=' . $id);
                exit;
            }
            
            // Set progress to 100% first
            $tugasModel->updateProgress($id, 100);
            // Then mark as Completed (waiting for approval)
            $tugasModel->updateStatus($id, 'Completed');
            
            // Update project progress
            require_once 'models/ProyekModel.php';
            $proyekModel = new ProyekModel();
            $proyekModel->updateProgress($tugas['proyek_id']);
            
            $_SESSION['success'] = 'Tugas ditandai sebagai selesai. Menunggu persetujuan Kepala Proyek.';
            header('Location: tugas_detail.php?id=' . $id);
            exit;
        }
    }
    
    if (isset($_POST['update_progress']) && $currentRole === 'Karyawan') {
        $progress = intval($_POST['progress'] ?? 0);
        $progress = max(0, min(100, $progress)); // Ensure between 0-100
        
        $tugasModel->updateProgress($id, $progress);
        
        // Update status to In Progress if progress > 0 and status is Pending or Revisi
        if ($progress > 0 && ($tugas['status'] === 'Pending' || $tugas['status'] === 'Revisi')) {
            $tugasModel->updateStatus($id, 'In Progress');
        }
        
        $_SESSION['success'] = 'Progress tugas berhasil diupdate';
        header('Location: tugas_detail.php?id=' . $id);
        exit;
    }
    
    // Handle file upload with validation
    if (isset($_FILES['file']) && $currentRole === 'Karyawan' && $tugas['karyawan_id'] == $currentUserId) {
        $file = $_FILES['file'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            // Validate file size (max 10MB)
            $maxSize = 10 * 1024 * 1024; // 10MB
            if ($file['size'] > $maxSize) {
                $_SESSION['error'] = 'Ukuran file terlalu besar. Maksimal 10MB.';
                header('Location: tugas_detail.php?id=' . $id);
                exit;
            }
            
            // Validate file type
            $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($extension, $allowedTypes)) {
                $_SESSION['error'] = 'Tipe file tidak diizinkan. Hanya: ' . implode(', ', $allowedTypes);
                header('Location: tugas_detail.php?id=' . $id);
                exit;
            }
            
            // Create upload directory if not exists
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $fileName = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $dokumentasiModel->create($id, $file['name'], $filePath, $file['size'], $currentUserId);
                $_SESSION['success'] = 'File berhasil diupload';
                header('Location: tugas_detail.php?id=' . $id);
                exit;
            } else {
                $_SESSION['error'] = 'Gagal mengupload file. Pastikan folder uploads dapat ditulis.';
            }
        } else {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File terlalu besar (melebihi upload_max_filesize)',
                UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (melebihi MAX_FILE_SIZE)',
                UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
                UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
                UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
                UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh extension PHP'
            ];
            $_SESSION['error'] = $errorMessages[$file['error']] ?? 'Error saat upload file';
        }
    }
}

$pageTitle = 'Detail Tugas';
include 'views/includes/header.php';
?>

<div class="main-layout">
    <?php include 'views/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Detail Tugas: <?php echo htmlspecialchars($tugas['nama_tugas']); ?></h2>
                <a href="tugas.php" class="btn btn-secondary btn-small">Kembali</a>
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
            
            <div class="form-group">
                <label class="form-label"><strong>Nama Tugas:</strong></label>
                <p><?php echo htmlspecialchars($tugas['nama_tugas']); ?></p>
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Proyek:</strong></label>
                <p><?php echo htmlspecialchars($tugas['nama_proyek'] ?? '-'); ?></p>
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Deskripsi:</strong></label>
                <p><?php echo nl2br(htmlspecialchars($tugas['deskripsi'] ?? '-')); ?></p>
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Status:</strong></label>
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
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Progress:</strong></label>
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $tugas['progress']; ?>%;">
                            <?php echo $tugas['progress']; ?>%
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Kepala Proyek:</strong></label>
                <p><?php echo htmlspecialchars($tugas['kepala_proyek_nama'] ?? '-'); ?></p>
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Karyawan:</strong></label>
                <p><?php echo htmlspecialchars($tugas['karyawan_nama'] ?? '-'); ?></p>
            </div>
            
            <div class="form-group">
                <label class="form-label"><strong>Deadline:</strong></label>
                <p><?php echo htmlspecialchars($tugas['tanggal_deadline'] ?? '-'); ?></p>
            </div>
            
            <?php if ($currentRole === 'Karyawan' && $tugas['karyawan_id'] == $currentUserId): ?>
                <div class="card mt-3">
                    <h3 class="card-title">Update Progress Tugas</h3>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label class="form-label">Progress (%)</label>
                            <input type="number" name="progress" class="form-control" min="0" max="100" value="<?php echo $tugas['progress']; ?>" required>
                        </div>
                        <button type="submit" name="update_progress" class="btn btn-primary">Update Progress</button>
                    </form>
                    
                    <?php if ($tugas['status'] === 'Revisi'): ?>
                        <div class="alert alert-warning mt-2">
                            <strong>Tugas perlu direvisi.</strong> Perbaiki tugas sesuai permintaan Kepala Proyek, lalu tandai selesai lagi.
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($tugas['status'] !== 'Completed' && $tugas['status'] !== 'Approved'): ?>
                        <form method="POST" action="" class="mt-2">
                            <input type="hidden" name="update_status" value="1">
                            <button type="submit" name="status" value="Completed" class="btn btn-success" onclick="return confirm('Tandai tugas ini sebagai selesai? Pastikan progress sudah 100% dan dokumentasi sudah diupload.');">
                                <?php echo $tugas['status'] === 'Revisi' ? 'Tandai Selesai (Setelah Revisi)' : 'Tandai Selesai'; ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                
                <div class="card mt-3">
                    <h3 class="card-title">Upload Dokumentasi</h3>
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label">File</label>
                            <input type="file" name="file" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </form>
                </div>
            <?php endif; ?>
            
            <?php if ($currentRole === 'Kepala Proyek'): ?>
                <?php if ($tugas['status'] === 'Completed'): ?>
                    <div class="card mt-3">
                        <h3 class="card-title">ACC/Revisi Tugas</h3>
                        <p class="text-muted">Tugas sudah ditandai selesai oleh Karyawan. Silakan ACC atau minta Revisi.</p>
                        <form method="POST" action="">
                            <input type="hidden" name="update_status" value="1">
                            <div style="display: flex; gap: 10px; margin-top: 10px;">
                                <button type="submit" name="status" value="Approved" class="btn btn-success" onclick="return confirm('ACC tugas ini? Progress proyek akan otomatis terupdate.');">
                                    ✓ ACC (Approve)
                                </button>
                                <button type="submit" name="status" value="Rejected" class="btn btn-warning" onclick="return confirm('Revisi tugas ini? Tugas akan dikembalikan ke Karyawan untuk diperbaiki.');">
                                    ↻ Revisi
                                </button>
                            </div>
                        </form>
                    </div>
                <?php elseif ($tugas['status'] === 'Revisi'): ?>
                    <div class="card mt-3">
                        <div class="alert alert-warning">
                            <strong>Tugas perlu direvisi.</strong> Karyawan akan memperbaiki tugas ini. Setelah selesai, status akan kembali ke "Completed" dan bisa di-ACC lagi.
                        </div>
                    </div>
                <?php elseif ($tugas['status'] === 'Approved'): ?>
                    <div class="card mt-3">
                        <div class="alert alert-success">
                            <strong>✓ Tugas sudah di-ACC.</strong> Tugas ini sudah disetujui dan progress proyek sudah terupdate.
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card mt-3">
                        <div class="alert alert-info">
                            <strong>Info:</strong> Tugas ini masih dalam status "<?php echo htmlspecialchars($tugas['status']); ?>". 
                            Anda bisa ACC/Revisi setelah Karyawan menandai tugas sebagai "Selesai" (Completed).
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Dokumentasi</h2>
            </div>
            
            <?php if (empty($dokumentasi)): ?>
                <p>Tidak ada dokumentasi</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama File</th>
                            <th>Ukuran</th>
                            <th>Uploaded By</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dokumentasi as $doc): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($doc['nama_file']); ?></td>
                                <td><?php echo number_format($doc['ukuran_file'] / 1024, 2); ?> KB</td>
                                <td><?php echo htmlspecialchars($doc['uploaded_by_nama'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($doc['created_at']); ?></td>
                                <td>
                                    <a href="download_file.php?id=<?php echo $doc['id']; ?>" class="btn btn-primary btn-small">Download</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'views/includes/footer.php'; ?>

