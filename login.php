<?php
session_start();
include 'konfig.php';  // Pastikan ini mengarah ke file koneksi database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $email = $_POST['email'];
  $password = $_POST['password'];

  // Cek koneksi ke database
  if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
  }

  // Query untuk mengambil data user berdasarkan email
  $stmt = $conn->prepare("SELECT id_user, email, password, role FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $stmt->store_result();

  // Jika email ditemukan
  if ($stmt->num_rows > 0) {
    $stmt->bind_result($id_user, $db_email, $db_password, $role);
    $stmt->fetch();

    // Verifikasi password
    if (password_verify($password, $db_password)) {
      // Set session untuk pengguna yang berhasil login
      $_SESSION['id_user'] = $id_user;
      $_SESSION['email'] = $db_email;
      $_SESSION['role'] = $role;

      // Redirect ke index.php jika login berhasil
      header("Location: index.php");
      exit;
    } else {
      echo "<script>alert('Password salah!');</script>";
    }
  } else {
    echo "<script>alert('Email tidak terdaftar!');</script>";
  }

  $stmt->close();
}
?>

<!doctype html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link href="https://fonts.googleapis.com/css?family=Roboto:300,400&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="nfonts/icomoon/style.css">
  <link rel="stylesheet" href="ncss/owl.carousel.min.css">
  <link rel="stylesheet" href="ncss/bootstrap.min.css">
  <link rel="stylesheet" href="ncss/styles.css">

  <title>Login #7</title>
</head>

<body>
  <div class="content">
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <img src="nimages/undraw_remotely_2j6y.svg" alt="Image" class="img-fluid">
        </div>
        <div class="col-md-6 contents">
          <div class="row justify-content-center">
            <div class="col-md-8">
              <div class="mb-4">
                <h3>Login</h3>
                <p class="mb-4">Masukan Email dan password yang sudah terdaftar.</p>
              </div>
              <form action="login.php" method="post">
                <div class="form-group first">
                  <label for="email">Email</label>
                  <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="form-group last mb-4">
                  <label for="password">Password</label>
                  <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <div class="d-flex mb-5 align-items-center">
                  <label class="control control--checkbox mb-0">
                    <span class="caption">Remember me</span>
                    <input type="checkbox" checked="checked" />
                    <div class="control__indicator"></div>
                  </label>
                  <span class="ml-auto"><a href="index.html" class="forgot-pass">Back</a></span>
                </div>
                <input type="submit" value="Log In" class="btn btn-block btn-primary">
                <span class="ml-auto">
                  Belum punya akun? <a href="regis.php" class="daftar">Daftar disini</a>
                </span>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="njs/jquery-3.3.1.min.js"></script>
  <script src="njs/popper.min.js"></script>
  <script src="njs/bootstrap.min.js"></script>
  <script src="njs/main.js"></script>
</body>

</html>