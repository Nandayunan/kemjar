<?php
include 'konfig.php';

function decryptAES256($ciphertext, $iv, $key)
{
  $cipher = "aes-256-cbc";
  return openssl_decrypt($ciphertext, $cipher, $key, 0, $iv);
}

function findUserByEmail($inputEmail, $conn, $key)
{
  $stmt = $conn->prepare("SELECT * FROM users");
  $stmt->execute();
  $result = $stmt->get_result();

  while ($row = $result->fetch_assoc()) {
    $decryptedEmail = decryptAES256($row['email'], $row['iv_email'], $key);
    if ($decryptedEmail === $inputEmail) {
      return $row;
    }
  }

  return null;
}

$secretKey = "MyVerySecretKey1234567890abcdef";

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $inputEmail = $_POST['email'];
  $inputPassword = $_POST['password'];

  $user = findUserByEmail($inputEmail, $conn, $secretKey);

  if ($user) {
    if (password_verify($inputPassword, $user['password'])) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['role'] = $user['role'];
      $_SESSION['nama'] = decryptAES256($user['nama'], $user['iv_nama'], $secretKey);

      echo "<script>alert('Login berhasil!'); window.location.href='index.php';</script>";
      exit;
    } else {
      echo "<script>alert('Password salah!');</script>";
    }
  } else {
    echo "<script>alert('Email tidak terdaftar!');</script>";
  }
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
          <img src="img/undraw_remotely_2j6y.svg" alt="Image" class="img-fluid">
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