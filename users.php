<?php
require_once 'config/session.php';
require_once 'models/UserModel.php';

requireLogin();
checkRole(['Owner']);

$userModel = new UserModel();

// Handle user update (full update: nama, email, password, role)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $userId = intval($_POST['user_id'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = trim($_POST['password'] ?? '');
    
    if ($userId && $userId != getCurrentUserId()) {
        // Validation
        if (empty($nama) || empty($email) || empty($role)) {
            $_SESSION['error'] = 'Nama, Email, dan Role harus diisi';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Format email tidak valid';
        } elseif (!empty($password) && strlen($password) < 6) {
            $_SESSION['error'] = 'Password minimal 6 karakter';
        } else {
            // Update user (password optional)
            if ($userModel->update($userId, $nama, $email, $role, !empty($password) ? $password : null)) {
                $_SESSION['success'] = 'Data user berhasil diupdate';
            } else {
                if ($userModel->emailExists($email, $userId)) {
                    $_SESSION['error'] = 'Email sudah digunakan oleh user lain';
                } else {
                    $_SESSION['error'] = 'Gagal mengupdate data user';
                }
            }
        }
    } else {
        $_SESSION['error'] = 'Tidak dapat mengubah data user sendiri';
    }
    header('Location: users.php');
    exit;
}

// Handle user role update (quick update via dropdown)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $userId = intval($_POST['user_id'] ?? 0);
    $newRole = $_POST['role'] ?? '';
    
    if ($userId && $userId != getCurrentUserId()) {
        $allowedRoles = ['Owner', 'Kepala Proyek', 'Karyawan'];
        if (in_array($newRole, $allowedRoles)) {
            if ($userModel->updateRole($userId, $newRole)) {
                $_SESSION['success'] = 'Hak akses user berhasil diupdate';
            } else {
                $_SESSION['error'] = 'Gagal mengupdate hak akses user';
            }
        } else {
            $_SESSION['error'] = 'Role tidak valid';
        }
    } else {
        $_SESSION['error'] = 'Tidak dapat mengubah hak akses user sendiri';
    }
    header('Location: users.php');
    exit;
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $userId = $_GET['delete'];
    if ($userId != getCurrentUserId()) {
        if ($userModel->delete($userId)) {
            $_SESSION['success'] = 'User berhasil dihapus';
        } else {
            $_SESSION['error'] = 'Gagal menghapus user';
        }
    } else {
        $_SESSION['error'] = 'Tidak dapat menghapus user sendiri';
    }
    header('Location: users.php');
    exit;
}

$users = $userModel->getAllUsers();

$pageTitle = 'Manajemen User';
include 'views/includes/header.php';
?>

<div class="main-layout">
    <?php include 'views/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Manajemen User</h2>
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
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr id="row-<?php echo $user['id']; ?>">
                            <td><?php echo htmlspecialchars($user['nama']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php if ($user['id'] == getCurrentUserId()): ?>
                                    <span class="badge badge-<?php 
                                        echo match($user['role']) {
                                            'Owner' => 'success',
                                            'Kepala Proyek' => 'info',
                                            default => 'warning'
                                        };
                                    ?>">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
                                <?php else: ?>
                                    <form method="POST" action="" style="display: inline-block;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="update_role" value="1">
                                        <select name="role" class="form-control" style="display: inline-block; width: auto; padding: 4px 8px; margin-right: 5px;" onchange="this.form.submit();">
                                            <option value="Owner" <?php echo $user['role'] === 'Owner' ? 'selected' : ''; ?>>Owner</option>
                                            <option value="Kepala Proyek" <?php echo $user['role'] === 'Kepala Proyek' ? 'selected' : ''; ?>>Kepala Proyek</option>
                                            <option value="Karyawan" <?php echo $user['role'] === 'Karyawan' ? 'selected' : ''; ?>>Karyawan</option>
                                        </select>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['id'] != getCurrentUserId()): ?>
                                    <button type="button" class="btn btn-primary btn-small" onclick="toggleEditForm(<?php echo $user['id']; ?>)">Edit</button>
                                    <a href="users.php?delete=<?php echo $user['id']; ?>" class="btn btn-secondary btn-small" onclick="return confirm('Yakin ingin menghapus user ini?');">Hapus</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($user['id'] != getCurrentUserId()): ?>
                        <tr id="edit-form-<?php echo $user['id']; ?>" style="display: none;">
                            <td colspan="4">
                                <div class="card" style="background: #f9f9f9; padding: 20px; margin: 10px 0;">
                                    <h4 style="margin-bottom: 15px;">Edit User: <?php echo htmlspecialchars($user['nama']); ?></h4>
                                    <form method="POST" action="">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="update_user" value="1">
                                        
                                        <div class="form-group">
                                            <label class="form-label">Nama</label>
                                            <input type="text" name="nama" class="form-control" value="<?php echo htmlspecialchars($user['nama']); ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Password (Kosongkan jika tidak ingin mengubah)</label>
                                            <input type="password" name="password" class="form-control" placeholder="Biarkan kosong untuk tidak mengubah password">
                                            <small style="color: #666;">Minimal 6 karakter jika ingin mengubah password</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Role</label>
                                            <select name="role" class="form-control" required>
                                                <option value="Owner" <?php echo $user['role'] === 'Owner' ? 'selected' : ''; ?>>Owner</option>
                                                <option value="Kepala Proyek" <?php echo $user['role'] === 'Kepala Proyek' ? 'selected' : ''; ?>>Kepala Proyek</option>
                                                <option value="Karyawan" <?php echo $user['role'] === 'Karyawan' ? 'selected' : ''; ?>>Karyawan</option>
                                            </select>
                                        </div>
                                        
                                        <div style="display: flex; gap: 10px;">
                                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                            <button type="button" class="btn btn-secondary" onclick="toggleEditForm(<?php echo $user['id']; ?>)">Batal</button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <script>
            function toggleEditForm(userId) {
                const formRow = document.getElementById('edit-form-' + userId);
                if (formRow.style.display === 'none') {
                    formRow.style.display = '';
                    // Hide other edit forms
                    document.querySelectorAll('[id^="edit-form-"]').forEach(function(row) {
                        if (row.id !== 'edit-form-' + userId) {
                            row.style.display = 'none';
                        }
                    });
                } else {
                    formRow.style.display = 'none';
                }
            }
            </script>
        </div>
    </div>
</div>

<?php include 'views/includes/footer.php'; ?>

