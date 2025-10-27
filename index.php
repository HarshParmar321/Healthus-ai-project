<?php include 'db.php'; session_start(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PHP CRUD App</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
</head>
<body>

<div class="container-xxl py-5">
  <div class="card shadow-lg">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h3 class="mb-0">PHP CRUD Application</h3>
      <button id="theme-toggle" type="button" class="btn btn-light btn-sm">Dark</button>
    </div>
    <div class="card-body">

      <?php if (!empty($_SESSION['flash'])): $flash = $_SESSION['flash']; unset($_SESSION['flash']); ?>
        <div aria-live="polite" aria-atomic="true" class="position-relative">
          <div class="toast-container position-absolute top-0 end-0 p-3">
            <div class="toast align-items-center text-bg-<?= htmlspecialchars($flash['type']) ?> border-0" id="appToast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000" data-bs-autohide="true">
              <div class="d-flex">
                <div class="toast-body">
                  <?= htmlspecialchars($flash['message']) ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- ADD FORM -->
      <form id="create-form" action="" method="POST" enctype="multipart/form-data" class="row g-3 mb-4" novalidate>
        <div class="col-md-3">
          <input type="text" name="name" class="form-control" placeholder="Enter Name" required>
        </div>
        <div class="col-md-3">
          <input type="email" name="email" class="form-control" placeholder="Enter Email" required>
        </div>
        <div class="col-md-2">
          <input type="number" name="age" class="form-control" placeholder="Age" min="1" max="120" required>
        </div>
        <div class="col-md-3">
          <input type="file" id="profile_image" name="profile_image" class="form-control" accept="image/png, image/jpeg, image/webp">
          <input type="hidden" id="cropped_image" name="cropped_image">
          <div class="mt-2 crop-area" id="crop-area">
            <div class="row g-2">
              <div class="col-8">
                <div class="border rounded p-2">
                  <img id="cropper-source" alt="Crop source" style="max-width:100%; display:block;">
                </div>
              </div>
              <div class="col-4 d-flex flex-column gap-2">
                <div class="preview-circle" id="preview-circle">Preview</div>
                <div class="d-grid gap-2">
                  <button type="button" id="btn-crop-apply" class="btn btn-outline-primary btn-sm">Use Photo</button>
                  <button type="button" id="btn-crop-cancel" class="btn btn-outline-secondary btn-sm">Cancel</button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-1 d-grid">
          <button type="submit" name="add" class="btn btn-success">Add</button>
        </div>
      </form>

      <!-- SEARCH / FILTER -->
      <?php
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $allowedSort = ['id','name','email','age'];
        $sortParam = isset($_GET['sort']) ? $_GET['sort'] : 'id';
        $sort = in_array($sortParam, $allowedSort, true) ? $sortParam : 'id';
        $dirParam = isset($_GET['dir']) ? strtolower($_GET['dir']) : 'desc';
        $dir = in_array($dirParam, ['asc','desc'], true) ? $dirParam : 'desc';
        $toggleDir = $dir === 'asc' ? 'desc' : 'asc';
      ?>
      <form class="row g-2 align-items-center mb-3" method="GET">
        <div class="col-sm-6 col-md-4">
          <input type="text" name="q" class="form-control" placeholder="Search by name or email" value="<?= htmlspecialchars($q) ?>">
        </div>
        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
        <input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">
        <div class="col-auto">
          <button class="btn btn-outline-secondary" type="submit">Search</button>
          <?php if ($q !== ''): ?>
          <a class="btn btn-link" href="?">Clear</a>
          <?php endif; ?>
        </div>
      </form>

      <?php
      // Add Record with validation and optional cropped image (PRG pattern)
      if (isset($_POST['add'])) {
          $errors = [];
          $name = trim($_POST['name'] ?? '');
          $email = trim($_POST['email'] ?? '');
          $age = (int)($_POST['age'] ?? 0);

          if ($name === '') { $errors[] = 'Name is required.'; }
          if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Valid email is required.'; }
          if ($age < 1 || $age > 120) { $errors[] = 'Age must be between 1 and 120.'; }

          $imageName = "";
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
                      }
                  }
              } else {
                  $errors[] = 'Image upload failed.';
              }
          }

          if (empty($errors)) {
              $stmt = $conn->prepare("INSERT INTO users (name, email, age, profile_image) VALUES (?, ?, ?, ?)");
              $stmt->bind_param('ssis', $name, $email, $age, $imageName);
              if ($stmt->execute()) {
                  $_SESSION['flash'] = ['type' => 'success', 'message' => 'Record added successfully'];
              } else {
                  $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Error while saving'];
              }
              $stmt->close();
          } else {
              $_SESSION['flash'] = ['type' => 'danger', 'message' => implode(', ', $errors)];
          }
          header('Location: index.php');
          exit;
      }
      ?>

      <!-- RECORD TABLE -->
      <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle text-center">
        <thead class="table-primary">
          <tr>
            <th>#</th>
            <th>Profile</th>
            <th>
              <a class="text-decoration-none" href="?q=<?= urlencode($q) ?>&sort=name&dir=<?= $sort==='name' ? $toggleDir : 'asc' ?>">Name
                <?php if ($sort==='name'): ?><small><?= strtoupper($dir) ?></small><?php endif; ?>
              </a>
            </th>
            <th>
              <a class="text-decoration-none" href="?q=<?= urlencode($q) ?>&sort=email&dir=<?= $sort==='email' ? $toggleDir : 'asc' ?>">Email
                <?php if ($sort==='email'): ?><small><?= strtoupper($dir) ?></small><?php endif; ?>
              </a>
            </th>
            <th>
              <a class="text-decoration-none" href="?q=<?= urlencode($q) ?>&sort=age&dir=<?= $sort==='age' ? $toggleDir : 'asc' ?>">Age
                <?php if ($sort==='age'): ?><small><?= strtoupper($dir) ?></small><?php endif; ?>
              </a>
            </th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $perPage = 5;
          $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
          $offset = ($page - 1) * $perPage;

          // Count with optional search
          $total = 0;
          if ($q !== '') {
            $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM users WHERE name LIKE ? OR email LIKE ?");
            $like = '%' . $q . '%';
            $stmt->bind_param('ss', $like, $like);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res) { $rowCnt = $res->fetch_assoc(); $total = (int)($rowCnt['c'] ?? 0); }
            $stmt->close();
          } else {
            if ($resCnt = $conn->query("SELECT COUNT(*) AS c FROM users")) { $total = (int)($resCnt->fetch_assoc()['c'] ?? 0); $resCnt->close(); }
          }

          $pages = max(1, (int)ceil($total / $perPage));

          $orderBy = in_array($sort, $allowedSort, true) ? $sort : 'id';
          $orderDir = $dir === 'asc' ? 'ASC' : 'DESC';

          if ($q !== '') {
            $sql = "SELECT * FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY $orderBy $orderDir LIMIT $perPage OFFSET $offset";
            $stmt = $conn->prepare($sql);
            $like = '%' . $q . '%';
            $stmt->bind_param('ss', $like, $like);
            $stmt->execute();
            $result = $stmt->get_result();
          } else {
            $sql = "SELECT * FROM users ORDER BY $orderBy $orderDir LIMIT $perPage OFFSET $offset";
            $result = $conn->query($sql);
          }

          if ($result && $result->num_rows > 0) {
              $i = 1;
              while ($row = $result->fetch_assoc()) {
                  $sn = $offset + $i++;
                  $id = (int)$row['id'];
                  $name = htmlspecialchars($row['name']);
                  $email = htmlspecialchars($row['email']);
                  $age = htmlspecialchars($row['age']);
                  $profile = isset($row['profile_image']) ? $row['profile_image'] : '';
                  $imgSrc = !empty($profile)
                      ? 'uploads/' . htmlspecialchars($profile)
                      : 'https://ui-avatars.com/api/?name=' . urlencode($row['name']) . '&background=0D8ABC&color=fff&size=60';

                  echo "
                    <tr>
                      <td>{$sn}</td>
                      <td><img src='{$imgSrc}' class='profile-pic' alt='Avatar' onerror=\"this.onerror=null;this.src='https://ui-avatars.com/api/?name=" . urlencode($row['name']) . "&background=0D8ABC&color=fff&size=60';\"></td>
                      <td>{$name}</td>
                      <td>{$email}</td>
                      <td><span class='badge text-bg-light border'>{$age}</span></td>
                      <td>
                        <div class='d-flex gap-2 justify-content-center'>
                          <a href='edit.php?id={$id}' class='btn btn-outline-primary btn-sm'>Edit</a>
                          <a href='delete.php?id={$id}' class='btn btn-outline-danger btn-sm' onclick='return confirm(\"Are you sure?\")'>Delete</a>
                        </div>
                      </td>
                    </tr>
                  ";
              }
          } else {
              echo "<tr><td colspan='6' class='text-muted py-4'>No records found</td></tr>";
          }
          ?>
        </tbody>
      </table>
      </div>

    </div>
  </div>
</div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
  <script src="assets/app.js"></script>

</body>
</html>
