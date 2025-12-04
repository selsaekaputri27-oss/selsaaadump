<?php
/* ============================================================
   GALERI FOTO PINK SOFT - BOOTSTRAP 5 - 1 FILE TANPA DATABASE
   ============================================================ */

session_start();

$ADMIN_USER = "selsa"; // admin username

// files / folders
$pass_file   = __DIR__ . "/password.txt";
$UPLOAD_DIR  = __DIR__ . "/uploads";

// ensure storage exists
if (!file_exists($pass_file)) file_put_contents($pass_file, "admin123");
if (!is_dir($UPLOAD_DIR))  mkdir($UPLOAD_DIR, 0775, true);

$ADMIN_PASS = trim(file_get_contents($pass_file));

/* -------------------- HELPERS -------------------- */
function load_images($dir){
    if (!is_dir($dir)) return [];
    $files = array_filter(scandir($dir), function($f) use($dir){
        return preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f);
    });
    usort($files, function($a,$b) use($dir){
        return filemtime($dir.'/'.$b) - filemtime($dir.'/'.$a);
    });
    return $files;
}

/* -------------------- AUTH & PASSWORD -------------------- */
// login
if (isset($_POST['login'])) {
    if (($_POST['username'] ?? '') === $ADMIN_USER && ($_POST['password'] ?? '') === $ADMIN_PASS) {
        $_SESSION['role'] = "admin";
        header("Location: ?page=admin");
        exit;
    } else {
        $error = "Username atau password salah.";
    }
}
// change password
if (isset($_POST['change_pass'])) {
    $old = $_POST['old'] ?? "";
    $new = $_POST['new'] ?? "";
    $new2 = $_POST['new2'] ?? "";
    if ($old !== $ADMIN_PASS) {
        $error = "Password lama salah.";
    } elseif ($new !== $new2) {
        $error = "Konfirmasi password tidak cocok.";
    } elseif (strlen($new) < 4) {
        $error = "Password baru minimal 4 karakter.";
    } else {
        file_put_contents($pass_file, $new);
        $ADMIN_PASS = $new;
        $success = "Password berhasil diubah!";
    }
}
// logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ?");
    exit;
}

/* -------------------- UPLOAD / EDIT / DELETE (ADMIN) -------------------- */
// upload new photo (admin)
if (isset($_POST['upload']) && ($_SESSION['role'] ?? '') === "admin") {
    $cap = trim($_POST['caption'] ?? '');
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $error = "Gagal upload foto.";
    } else {
        $f = $_FILES['photo'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $error = "Format file tidak diizinkan.";
        } else {
            $newName = time() . "_" . preg_replace('/[^a-zA-Z0-9._-]/','_',$f['name']);
            $target = $UPLOAD_DIR . "/" . $newName;
            if (move_uploaded_file($f['tmp_name'], $target)) {
                if ($cap !== "") file_put_contents($target . ".txt", $cap);
                $success = "Foto berhasil diunggah.";
            } else {
                $error = "Gagal menyimpan file.";
            }
        }
    }
}
// edit photo: replace file and/or update caption (admin)
if (isset($_POST['edit']) && ($_SESSION['role'] ?? '') === "admin") {
    $orig = basename($_POST['orig_file'] ?? '');
    if ($orig === '') { $error = "File asal tidak ditentukan."; }
    else {
        $origPath = $UPLOAD_DIR . "/" . $orig;
        if (!file_exists($origPath)) { $error = "File asli tidak ditemukan."; }
        else {
            // replace file if a new file provided
            if (isset($_FILES['replace']) && $_FILES['replace']['error'] === UPLOAD_ERR_OK) {
                $f = $_FILES['replace'];
                $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                    $error = "Format file pengganti tidak diizinkan.";
                } else {
                    $newName = time() . "_" . preg_replace('/[^a-zA-Z0-9._-]/','_',$f['name']);
                    $target = $UPLOAD_DIR . "/" . $newName;
                    if (move_uploaded_file($f['tmp_name'], $target)) {
                        // delete old file (and its caption file)
                        @unlink($origPath);
                        @unlink($origPath . ".txt");
                        $orig = $newName; // set new base name for caption handling
                        $success = "Foto berhasil diganti.";
                    } else $error = "Gagal menyimpan file pengganti.";
                }
            }
            // update caption
            $newcap = trim($_POST['new_caption'] ?? '');
            if ($newcap !== '') {
                file_put_contents($UPLOAD_DIR . "/" . $orig . ".txt", $newcap);
                $success = (!empty($success) ? $success . " " : "") . "Caption disimpan.";
            } elseif (isset($_POST['clear_caption'])) {
                @unlink($UPLOAD_DIR . "/" . $orig . ".txt");
                $success = (!empty($success) ? $success . " " : "") . "Caption dihapus.";
            }
        }
    }
}
// delete photo (admin)
if (isset($_POST['delete']) && ($_SESSION['role'] ?? '') === "admin") {
    $file = basename($_POST['file']);
    $path = $UPLOAD_DIR . "/" . $file;
    if (file_exists($path)) unlink($path);
    if (file_exists($path . ".txt")) unlink($path . ".txt");
    $success = "Foto berhasil dihapus.";
}

/* -------------------- LOAD IMAGES -------------------- */
$images = load_images($UPLOAD_DIR);

?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>selsaadump</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Quicksand dari Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
* {
    font-family: 'Quicksand', sans-serif;
}
body{
    background:#ffeef6;
    font-family: 'Quicksand', sans-serif;
}
.pink-card{
    border-radius:12px;
    background:#fff;
    border:1px solid #f6b6d1;
    box-shadow:0 8px 20px rgba(255,150,190,0.2);
    font-family: 'Quicksand', sans-serif;
}
.pink-btn{
    background:#ff8fbc;
    color:#fff;
    font-family: 'Quicksand', sans-serif;
    font-weight: 600;
}
.pink-btn:hover{
    background:#ff7ab0;
    color:#fff;
}
.gallery-img{
    height:220px;
    object-fit:cover;
    border-radius:8px;
    cursor:pointer
}
.modal-img{
    max-height:70vh;
    object-fit:contain
}
.navbar-brand {
    font-family: 'Quicksand', sans-serif;
    font-weight: 700;
    font-size: 1.5rem;
}
h1, h2, h3, h4, h5, h6 {
    font-family: 'Quicksand', sans-serif;
    font-weight: 600;
}
.btn {
    font-family: 'Quicksand', sans-serif;
    font-weight: 500;
}
.form-control {
    font-family: 'Quicksand', sans-serif;
}
.modal-title {
    font-family: 'Quicksand', sans-serif;
    font-weight: 600;
}
.alert {
    font-family: 'Quicksand', sans-serif;
}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg" style="background:#ffb6d9">
  <div class="container">
    <a class="navbar-brand text-white fw-bold">selsaaadump</a>
    <div class="ms-auto">
        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <a href="?logout=1" class="btn btn-sm btn-danger">Logout</a>
        <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container py-4">

<?php if (!isset($_SESSION['role']) && ($_GET['page'] ?? '') !== "user"): ?>
    <!-- LOGIN PAGE -->
    <div class="row">
        <div class="col-md-5 mx-auto">
            <div class="p-4 pink-card">
                <h4 class="text-center">Login Admin</h4>

                <?php if (!empty($error)): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
                <?php if (!empty($success)): ?><div class="alert alert-success"><?=htmlspecialchars($success)?></div><?php endif; ?>

                <form method="post" class="mb-3">
                    <input name="username" class="form-control mb-2" placeholder="Username" required>
                    <input name="password" type="password" class="form-control mb-3" placeholder="Password" required>
                    <button name="login" class="btn pink-btn w-100">Masuk</button>
                </form>

                <details>
                    <summary><strong>Ubah Password Admin</strong></summary>
                    <form method="post" class="mt-3">
                        <input name="old" type="password" class="form-control mb-2" placeholder="Password lama" required>
                        <input name="new" type="password" class="form-control mb-2" placeholder="Password baru" required>
                        <input name="new2" type="password" class="form-control mb-2" placeholder="Ulangi password baru" required>
                        <button name="change_pass" class="btn pink-btn w-100">Simpan Password</button>
                    </form>
                </details>

                <hr>
                <a href="?page=user" class="btn btn-outline-pink w-100" style="border:1px solid #ff8fbc;color:#ff4c97">Masuk sebagai Pengunjung</a>
            </div>
        </div>
    </div>
    <?php exit; endif; ?>

<?php if (($_GET['page'] ?? '') === "user"): ?>
    <!-- USER GALLERY PAGE -->
    <div class="mb-4">
        <a href="javascript:history.back()" class="btn pink-btn mb-2">Kembali ke Halaman Sebelumnya</a>
        <h3>Galeri Foto</h3>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?=htmlspecialchars($success)?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?=htmlspecialchars($error)?></div>
        <?php endif; ?>
    </div>

    <?php if (empty($images)): ?>
        <p class="text-muted">Belum ada foto.</p>
    <?php endif; ?>

    <div class="row g-3">
        <?php foreach($images as $img):
            $path = "uploads/".$img;
            $caption = @file_get_contents($UPLOAD_DIR."/".$img.".txt");
            $modalId = "modal-".preg_replace('/[^a-zA-Z0-9]/', '', $img);
        ?>
        <div class="col-md-3">
            <div class="p-2 pink-card">
                <img src="<?=$path?>" class="gallery-img w-100 mb-2" 
                     data-bs-toggle="modal" data-bs-target="#<?=$modalId?>"
                     alt="<?=htmlspecialchars($caption)?>">
                <p class="small"><?=htmlspecialchars($caption)?></p>
                
                <a class="btn pink-btn w-100" href="<?=$path?>" download>Download</a>
            </div>
        </div>

        <!-- Modal per foto -->
        <div class="modal fade" id="<?=$modalId?>" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Detail Foto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body text-center">
                <img src="<?=$path?>" class="modal-img img-fluid w-100" style="max-height: 70vh;">
                <?php if ($caption): ?>
                    <p class="mt-3 fw-bold"><?=htmlspecialchars($caption)?></p>
                <?php endif; ?>
              </div>
              <div class="modal-footer">
                <a href="<?=$path?>" download class="btn pink-btn">Download Foto</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
              </div>
            </div>
          </div>
        </div>

        <?php endforeach; ?>
    </div>
    
    <?php exit; endif; ?>

<!-- ADMIN DASHBOARD -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Dashboard Admin</h3>
    <div>
        <a class="btn btn-sm btn-danger" href="?logout=1">Logout</a>
    </div>
</div>

<?php if (!empty($success)): ?><div class="alert alert-success"><?=htmlspecialchars($success)?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>

<!-- ADMIN: Upload -->
<div class="pink-card p-4 mb-4">
    <h5>Upload Foto</h5>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="photo" class="form-control mb-2" required>
        <input type="text" name="caption" class="form-control mb-2" placeholder="Caption (opsional)">
        <button class="btn pink-btn" name="upload">Unggah Foto</button>
    </form>
</div>

<h5 class="mb-3">Daftar Foto</h5>
<div class="row g-3">
    <?php foreach($images as $img):
        $path = "uploads/".$img;
        $caption = @file_get_contents($UPLOAD_DIR."/".$img.".txt");
    ?>
    <div class="col-md-3">
        <div class="p-2 pink-card">
            <img src="<?=$path?>" class="gallery-img w-100 mb-2">
            <p class="small"><?=htmlspecialchars($caption)?></p>

            <!-- Edit form (caption / replace file) -->
            <details class="mb-2">
                <summary class="small">Edit Foto</summary>
                <form method="post" enctype="multipart/form-data" class="mt-2">
                    <input type="hidden" name="orig_file" value="<?=htmlspecialchars($img)?>">
                    <label class="form-label small">Ganti Foto (opsional)</label>
                    <input type="file" name="replace" class="form-control mb-2">
                    <label class="form-label small">Caption</label>
                    <input type="text" name="new_caption" class="form-control mb-2" placeholder="Caption baru">
                    <div class="form-check mb-2">
                      <input class="form-check-input" type="checkbox" name="clear_caption" id="clear_<?=md5($img)?>">
                      <label class="form-check-label small" for="clear_<?=md5($img)?>">Hapus caption lama</label>
                    </div>
                    <button name="edit" class="btn btn-sm pink-btn w-100">Simpan Perubahan</button>
                </form>
            </details>

            <form method="post" onsubmit="return confirm('Hapus foto ini?');">
                <input type="hidden" name="file" value="<?=htmlspecialchars($img)?>">
                <button name="delete" class="btn btn-sm btn-danger w-100 mb-2">Hapus</button>
            </form>

        </div>
    </div>
    <?php endforeach; ?>
</div>

</div>

<!-- BOOTSTRAP JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>