<?php
include 'konfig.php'; // File koneksi database

// Fungsi enkripsi AES-256 yang memisahkan IV
function encryptAES256($data, $key)
{
  $cipher = "aes-256-cbc";
  $ivlen = openssl_cipher_iv_length($cipher);
  $iv = openssl_random_pseudo_bytes($ivlen);
  $ciphertext = openssl_encrypt($data, $cipher, $key, 0, $iv);
  return ['iv' => $iv, 'ciphertext' => $ciphertext];
}

$secretKey = "MyVerySecretKey1234567890abcdef";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Enkripsi tiap data
  $enc_nama   = encryptAES256($_POST['nama'], $secretKey);
  $enc_ttl    = encryptAES256($_POST['ttl'], $secretKey);
  $enc_no_hp  = encryptAES256($_POST['nomor'], $secretKey);
  $enc_alamat = encryptAES256($_POST['alamat'], $secretKey);
  $enc_email  = encryptAES256($_POST['email'], $secretKey);

  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $role = isset($_POST['role']) ? intval($_POST['role']) : 1;

  if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
  }

  // Tambahkan kolom IV saat menyimpan ke database
  $stmt = $conn->prepare("INSERT INTO users 
        (nama, iv_nama, ttl, iv_ttl, no_hp, iv_no_hp, alamat, iv_alamat, email, iv_email, password, role)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

  $stmt->bind_param(
    "sssssssssssi",
    $enc_nama['ciphertext'],
    $enc_nama['iv'],
    $enc_ttl['ciphertext'],
    $enc_ttl['iv'],
    $enc_no_hp['ciphertext'],
    $enc_no_hp['iv'],
    $enc_alamat['ciphertext'],
    $enc_alamat['iv'],
    $enc_email['ciphertext'],
    $enc_email['iv'],
    $password,
    $role
  );

  if ($stmt->execute()) {
    echo "<script>alert('Registrasi berhasil!'); window.location.href='login.php';</script>";
    exit;
  } else {
    echo "<script>alert('Registrasi gagal: " . $stmt->error . "');</script>";
  }

  $stmt->close();
  $conn->close();
}
?>


<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://fonts.googleapis.com/css?family=Roboto:300,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="nfonts/icomoon/style.css">
  <link rel="stylesheet" href="ncss/owl.carousel.min.css">
  <link rel="stylesheet" href="ncss/bootstrap.min.css">
  <link rel="stylesheet" href="ncss/styles.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <title>Registrasi MoveFast</title>
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
                <h3>Registrasi</h3>
                <p class="mb-4">Silahkan daftarkan akun anda.</p>
              </div>
              <form id="registrationForm" method="post" action="regis.php">
                <div class="form-group last mb-4">
                  <label for="nama">Nama Lengkap</label>
                  <input type="text" class="form-control" id="nama" name="nama" required>
                </div>
                <div class="form-group last mb-4">
                  <label for="ttl">Tempat Tanggal Lahir</label>
                  <input type="text" class="form-control" id="ttl" name="ttl" required>
                </div>
                <div class="form-group last mb-4">
                  <label for="nomor">No Handphone</label>
                  <input type="text" class="form-control" id="nomor" name="nomor" required>
                </div>
                <div class="form-group last mb-4">
                  <label for="alamat">Alamat</label>
                  <input type="text" class="form-control" id="alamat" name="alamat" required>
                </div>
                <div class="form-group last mb-4">
                  <label for="email">Email</label>
                  <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="form-group last mb-4">
                  <label for="password">Password</label>
                  <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <!-- Hidden role value -->
                <input type="hidden" name="role" value="1">

                <input type="submit" value="Daftar" class="btn btn-block btn-primary">
              </form>

              <div id="notification" class="alert" role="alert" style="display:none;"></div>
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