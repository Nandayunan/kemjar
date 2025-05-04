<?php
session_start();
include 'konfig.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Dekripsi nama pengguna
function decryptAES256($ciphertext, $iv, $key) {
    $cipher = "aes-256-cbc";
    return openssl_decrypt($ciphertext, $cipher, $key, 0, $iv);
}

// Enkripsi nama pengguna
function encryptAES256($plaintext, $key) {
    $cipher = "aes-256-cbc";
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
    $ciphertext = openssl_encrypt($plaintext, $cipher, $key, 0, $iv);
    return ["ciphertext" => $ciphertext, "iv" => $iv];
}

$secretKey = "MyVerySecretKey1234567890abcdef"; // Kunci rahasia untuk enkripsi dan dekripsi

// Dekripsi nama pengguna
$stmt = $conn->prepare("SELECT nama, iv_nama FROM users WHERE id_user = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user['nama'] = decryptAES256($user['nama'], $user['iv_nama'], $secretKey);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $card_number = $_POST['card_number'];
    $card_holder_name = $_POST['card_holder_name'];
    $expiry_date = $_POST['expiry_date'];
    $cvv = $_POST['cvv'];

    // Enkripsi nama pengguna sebelum menyimpan ke database
    $encryptedName = encryptAES256($name, $secretKey);

    // Update user name dengan nama yang sudah dienkripsi
    $stmt = $conn->prepare("UPDATE users SET nama = ?, iv_nama = ? WHERE id_user = ?");
    $stmt->bind_param("ssi", $encryptedName['ciphertext'], $encryptedName['iv'], $user_id);
    $stmt->execute();

    // Insert or update payment info
    $stmt = $conn->prepare("INSERT INTO payment_info (user_id, card_number, card_holder_name, expiry_date, cvv) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE card_number = VALUES(card_number), card_holder_name = VALUES(card_holder_name), expiry_date = VALUES(expiry_date), cvv = VALUES(cvv)");
    $stmt->bind_param("issss", $user_id, $card_number, $card_holder_name, $expiry_date, $cvv);
    $stmt->execute();

    echo "<script>alert('Profil berhasil diperbarui!');</script>";
}

// Fetch payment info
$stmt = $conn->prepare("SELECT card_number, card_holder_name, expiry_date, cvv FROM payment_info WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$payment = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Profil</h2>
    <form method="post">
        <div class="mb-3">
            <label for="name" class="form-label">Nama</label>
            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['nama']) ?>" required>
        </div>
        <h3>Informasi Kartu Kredit</h3>
        <div class="mb-3">
            <label for="card_number" class="form-label">Nomor Kartu Kredit</label>
            <input type="text" class="form-control" id="card_number" name="card_number" value="<?= htmlspecialchars($payment['card_number'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="card_holder_name" class="form-label">Nama Pemegang Kartu</label>
            <input type="text" class="form-control" id="card_holder_name" name="card_holder_name" value="<?= htmlspecialchars($payment['card_holder_name'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="expiry_date" class="form-label">Tanggal Kedaluwarsa</label>
            <input type="date" class="form-control" id="expiry_date" name="expiry_date" value="<?= htmlspecialchars($payment['expiry_date'] ?? '') ?>">
        </div>
        <div class="mb-3">
            <label for="cvv" class="form-label">CVV</label>
            <input type="text" class="form-control" id="cvv" name="cvv" value="<?= htmlspecialchars($payment['cvv'] ?? '') ?>">
        </div>
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
    <div class="mb-3">
        <a href="index.php" class="btn btn-secondary">← Kembali ke Beranda</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
