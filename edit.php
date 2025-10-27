<?php session_start(); include 'db.php'; ?>

<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$result = $conn->query("SELECT * FROM users WHERE id={$id}");
$row = $result ? $result->fetch_assoc() : null;

if (!$row) {
    header("Location: index.php");
    exit;
}

if (isset($_POST['update'])) {
    $errors = [];
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $age = (int)($_POST['age'] ?? 0);

    if ($name === '') { $errors[] = 'Name is required.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Valid email is required.'; }
    if ($age < 1 || $age > 120) { $errors[] = 'Age must be between 1 and 120.'; }

    $imageName = $row['profile_image'];
    $replacedImage = false;
    if (!is_dir('uploads')) { @mkdir('uploads', 0777, true); }

    // Handle cropped base64 image first
    if (!empty($_POST['cropped_image'])) {
        $data = $_POST['cropped_image'];
        if (preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,/', $data, $m)) {
            $ext = $m[1] === 'jpeg' ? 'jpg' : ($m[1] === 'jpg' ? 'jpg' : ($m[1] === 'png' ? 'png' : 'webp'));
            $data = substr($data, strpos($data, ',') + 1);
            $bin = base64_decode($data);
            if ($bin !== false && strlen($bin) <= 2*1024*1024) { // 2MB limit
                $imageName = time() . '_cropped.' . $ext;
                file_put_contents('uploads/' . $imageName, $bin);
                $replacedImage = true;
            } else {
                $errors[] = 'Cropped image is too large or invalid.';
            }
        } else {
            $errors[] = 'Invalid cropped image format.';
        }
    } elseif (!empty($_FILES['profile_image']['name'])) {
        // Validate uploaded file
        if ($_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['profile_image']['size'] > 2*1024*1024) { // 2MB
                $errors[] = 'Image file too large (max 2MB).';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['profile_image']['tmp_name']);
                $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
                if (!isset($allowed[$mime])) {
                    $errors[] = 'Only PNG, JPG, or WEBP images are allowed.';
                } else {
                    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['profile_image']['name']));
                    $imageName = time() . '_' . $safeName;
                    @move_uploaded_file($_FILES['profile_image']['tmp_name'], 'uploads/' . $imageName);
                    $replacedImage = true;
                }
            }
        } else {
            $errors[] = 'Image upload failed.';
        }
    }

    // If user asked to remove current image and no new image is provided
    if (!$replacedImage && isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
        $imageName = '';
        $replacedImage = true; // to trigger deletion of old file below
    }

    if (empty($errors)) {
        // Delete old image if replaced
        if ($replacedImage && !empty($row['profile_image'])) {
            $old = 'uploads/' . $row['profile_image'];
            if (is_file($old)) { @unlink($old); }
        }

        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, age = ?, profile_image = ? WHERE id = ?");
        $stmt->bind_param('ssisi', $name, $email, $age, $imageName, $id);
        if ($stmt->execute()) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Record updated successfully'];
            header("Location: index.php");
            exit;
        } else {
            echo "<div class='alert alert-danger'>Error while updating.</div>";
        }
        $stmt->close();
    } else {
        echo "<div class='alert alert-danger'><ul class='mb-0'><li>" . implode('</li><li>', array_map('htmlspecialchars', $errors)) . "</li></ul></div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Record</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
</head>
<body class="edit">

<div class="container-xxl py-5">
  <div class="card shadow-lg">
    <div class="card-header bg-warning d-flex justify-content-between align-items-center">
      <h3 class="mb-0">Edit Record</h3>
      <button id="theme-toggle" type="button" class="btn btn-light btn-sm">Dark</button>
    </div>
    <div class="card-body">
      <form id="edit-form" method="POST" enctype="multipart/form-data" class="row g-3 needs-validation" novalidate>
        <div class="col-md-6">
          <label>Name</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($row['name']) ?>" required>
          <div class="invalid-feedback">Please enter a name.</div>
        </div>
        <div class="col-md-6">
          <label>Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($row['email']) ?>" required>
          <div class="invalid-feedback">Please enter a valid email.</div>
        </div>
        <div class="col-md-6">
          <label>Age</label>
          <input type="number" name="age" class="form-control" min="1" max="120" value="<?= htmlspecialchars($row['age']) ?>" required>
          <div class="invalid-feedback">Age must be between 1 and 120.</div>
        </div>
        <div class="col-md-6">
          <label>Profile Image</label>
          <input type="file" id="profile_image" name="profile_image" accept="image/png, image/jpeg, image/webp" class="form-control">
          <input type="hidden" id="cropped_image" name="cropped_image">
          <?php $imgSrc = !empty($row['profile_image']) ? 'uploads/' . htmlspecialchars($row['profile_image']) : 'https://ui-avatars.com/api/?name=' . urlencode($row['name']) . '&background=0D8ABC&color=fff&size=120'; ?>
          <div class="d-flex align-items-center gap-3 mt-2">
            <img id="current-avatar" src="<?= $imgSrc ?>" width="100" height="100" class="rounded-circle border" alt="Current Avatar" style="object-fit:cover;" onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?= urlencode($row['name']) ?> &background=0D8ABC&color=fff&size=120';">
            <div class="preview-circle" id="preview-circle">Preview</div>
          </div>
          <?php if (!empty($row['profile_image'])): ?>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
            <label class="form-check-label" for="remove_image">Remove current photo</label>
          </div>
          <?php endif; ?>
          <div class="mt-2 crop-area" id="crop-area">
            <div class="border rounded p-2">
              <img id="cropper-source" alt="Crop source" style="max-width:100%; display:block;">
            </div>
            <div class="d-flex gap-2 mt-2">
              <button type="button" id="btn-crop-apply" class="btn btn-outline-primary btn-sm">Use Photo</button>
              <button type="button" id="btn-crop-cancel" class="btn btn-outline-secondary btn-sm">Cancel</button>
            </div>
          </div>
        </div>
        <div class="col-12">
          <button type="submit" name="update" class="btn btn-success">Update</button>
          <a href="index.php" class="btn btn-secondary">Back</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script src="assets/app.js"></script>

</body>
</html>
