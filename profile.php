<?php
session_start();
include 'konfig.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fungsi enkripsi dengan IV yang benar
function encryptAES256($plaintext, $key) {
    $cipher = "aes-256-cbc";
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen); // Selalu menghasilkan 16 bytes untuk AES-256-CBC
    
    // Pastikan IV adalah 16 bytes
    if (strlen($iv) != 16) {
        error_log("IV length is not 16 bytes: " . strlen($iv));
        return false;
    }
    
    $ciphertext = openssl_encrypt($plaintext, $cipher, $key, 0, $iv);
    if ($ciphertext === false) {
        error_log("Encryption failed: " . openssl_error_string());
        return false;
    }
    
    // Encode IV dalam base64 untuk penyimpanan yang aman
    return [
        "ciphertext" => $ciphertext,
        "iv" => base64_encode($iv)  // IV sudah di-encode base64
    ];
}

// Fungsi dekripsi dengan penanganan IV yang benar
function decryptAES256($ciphertext, $iv_base64, $key) {
    if (empty($ciphertext) || empty($iv_base64)) {
        error_log("Empty ciphertext or IV");
        return '';
    }

    $cipher = "aes-256-cbc";
    
    // Decode IV dari base64
    $iv = base64_decode($iv_base64);
    
    // Pastikan IV adalah 16 bytes
    if (strlen($iv) != 16) {
        error_log("Decoded IV length is not 16 bytes: " . strlen($iv));
        return '';
    }
    
    $decrypted = openssl_decrypt($ciphertext, $cipher, $key, 0, $iv);
    if ($decrypted === false) {
        error_log("Decryption failed: " . openssl_error_string());
        return '';
    }
    
    return $decrypted;
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
    if (isset($_POST['delete'])) {
        // Hapus data kartu kredit
        $stmt = $conn->prepare("DELETE FROM payment_info WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            echo "<script>alert('Data kartu kredit berhasil dihapus!'); window.location.href='profile.php';</script>";
            exit;
        } else {
            echo "<script>alert('Gagal menghapus data!');</script>";
        }
    } else {
        $name = $_POST['name'];
        $card_number = $_POST['card_number'];
        $card_holder_name = $_POST['card_holder_name'];
        $expiry_date = $_POST['expiry_date'];
        $cvv = $_POST['cvv'];

        // Enkripsi data kartu kredit
        $encryptedCardNumber = encryptAES256($card_number, $secretKey);
        $encryptedCardHolderName = encryptAES256($card_holder_name, $secretKey);
        $encryptedExpiryDate = encryptAES256($expiry_date, $secretKey);
        $encryptedCVV = encryptAES256($cvv, $secretKey);

        // Debug encrypted values
        error_log("Encrypted card_number length: " . strlen($encryptedCardNumber['ciphertext']));
        error_log("IV length (base64): " . strlen($encryptedCardNumber['iv']));
        
        // IV sudah dalam format base64 dari fungsi encrypt
        $stmt = $conn->prepare("INSERT INTO payment_info (user_id, card_number, iv_card_number, card_holder_name, iv_card_holder_name, expiry_date, iv_expiry_date, cvv, iv_cvv) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE card_number = VALUES(card_number), iv_card_number = VALUES(iv_card_number), card_holder_name = VALUES(card_holder_name), iv_card_holder_name = VALUES(iv_card_holder_name), expiry_date = VALUES(expiry_date), iv_expiry_date = VALUES(iv_expiry_date), cvv = VALUES(cvv), iv_cvv = VALUES(iv_cvv)");
        
        $stmt->bind_param("issssssss", 
            $user_id, 
            $encryptedCardNumber['ciphertext'], 
            $encryptedCardNumber['iv'],  // IV sudah dalam base64
            $encryptedCardHolderName['ciphertext'], 
            $encryptedCardHolderName['iv'], 
            $encryptedExpiryDate['ciphertext'], 
            $encryptedExpiryDate['iv'], 
            $encryptedCVV['ciphertext'], 
            $encryptedCVV['iv']
        );
        
        if (!$stmt->execute()) {
            error_log("Error executing insert/update: " . $stmt->error);
        }

        // Enkripsi nama pengguna sebelum menyimpan ke database
        $encryptedName = encryptAES256($name, $secretKey);

        // Debugging: Periksa hasil enkripsi
        error_log("Encrypted Name: " . $encryptedName['ciphertext']);
        error_log("IV: " . bin2hex($encryptedName['iv']));

        // Update user name dengan nama yang sudah dienkripsi
        $stmt = $conn->prepare("UPDATE users SET nama = ?, iv_nama = ? WHERE id_user = ?");
        $stmt->bind_param("ssi", $encryptedName['ciphertext'], $encryptedName['iv'], $user_id);

        // Debugging: Periksa apakah query berhasil dijalankan
        if ($stmt->execute()) {
            error_log("Query berhasil dijalankan: Nama terenkripsi disimpan ke database.");
        } else {
            error_log("Query gagal: " . $stmt->error);
        }

        echo "<script>alert('Profil berhasil diperbarui!');</script>";
    }
}

// Fetch payment info
$stmt = $conn->prepare("SELECT card_number, iv_card_number, card_holder_name, iv_card_holder_name, expiry_date, iv_expiry_date, cvv, iv_cvv FROM payment_info WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$payment = $result->fetch_assoc();

// Debug fetched data
if ($payment) {
    error_log("Debug data from database:");
    error_log("Card Number: " . (empty($payment['card_number']) ? 'empty' : 'exists') . 
              ", IV: " . (empty($payment['iv_card_number']) ? 'empty' : 'exists'));
    error_log("Card Holder: " . (empty($payment['card_holder_name']) ? 'empty' : 'exists') . 
              ", IV: " . (empty($payment['iv_card_holder_name']) ? 'empty' : 'exists'));
    error_log("Expiry Date: " . (empty($payment['expiry_date']) ? 'empty' : 'exists') . 
              ", IV: " . (empty($payment['iv_expiry_date']) ? 'empty' : 'exists'));
    error_log("CVV: " . (empty($payment['cvv']) ? 'empty' : 'exists') . 
              ", IV: " . (empty($payment['iv_cvv']) ? 'empty' : 'exists'));
    
    // Try decrypting each field
    $decrypted_card_number = decryptAES256($payment['card_number'], $payment['iv_card_number'], $secretKey);
    $decrypted_holder_name = decryptAES256($payment['card_holder_name'], $payment['iv_card_holder_name'], $secretKey);
    $decrypted_expiry = decryptAES256($payment['expiry_date'], $payment['iv_expiry_date'], $secretKey);
    $decrypted_cvv = decryptAES256($payment['cvv'], $payment['iv_cvv'], $secretKey);
    
    error_log("Decrypted values:");
    error_log("Card Number: " . (!empty($decrypted_card_number) ? 'decrypted successfully' : 'decryption failed'));
    error_log("Holder Name: " . (!empty($decrypted_holder_name) ? 'decrypted successfully' : 'decryption failed'));
    error_log("Expiry: " . (!empty($decrypted_expiry) ? 'decrypted successfully' : 'decryption failed'));
    error_log("CVV: " . (!empty($decrypted_cvv) ? 'decrypted successfully' : 'decryption failed'));
}

// Modify the table display to show debug info if decryption fails
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
    <?php if ($payment): ?>
        <div id="viewMode">
            <h3>Informasi Kartu Kredit</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nomor Kartu Kredit</th>
                        <th>Nama Pemegang Kartu</th>
                        <th>Tanggal Kedaluwarsa</th>
                        <th>CVV</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php 
                            $decrypted = decryptAES256($payment['card_number'], $payment['iv_card_number'], $secretKey);
                            if (empty($decrypted)) {
                                echo "Error: Decryption failed";
                                error_log("Card number decryption failed. IV length: " . strlen(base64_decode($payment['iv_card_number'])));
                            } else {
                                echo htmlspecialchars($decrypted);
                            }
                        ?></td>
                        <td><?php 
                            $decrypted = decryptAES256($payment['card_holder_name'], $payment['iv_card_holder_name'], $secretKey);
                            if (empty($decrypted)) {
                                echo "Error: Decryption failed";
                                error_log("Card holder name decryption failed. IV length: " . strlen(base64_decode($payment['iv_card_holder_name'])));
                            } else {
                                echo htmlspecialchars($decrypted);
                            }
                        ?></td>
                        <td><?php 
                            $decrypted = decryptAES256($payment['expiry_date'], $payment['iv_expiry_date'], $secretKey);
                            if (empty($decrypted)) {
                                echo "Error: Decryption failed";
                                error_log("Expiry date decryption failed. IV length: " . strlen(base64_decode($payment['iv_expiry_date'])));
                            } else {
                                echo htmlspecialchars($decrypted);
                            }
                        ?></td>
                        <td><?php 
                            $decrypted = decryptAES256($payment['cvv'], $payment['iv_cvv'], $secretKey);
                            if (empty($decrypted)) {
                                echo "Error: Decryption failed";
                                error_log("CVV decryption failed. IV length: " . strlen(base64_decode($payment['iv_cvv'])));
                            } else {
                                echo htmlspecialchars($decrypted);
                            }
                        ?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-warning" onclick="showEditForm()">Edit</button>
                                <form method="post" style="display: inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data kartu kredit ini?');">
                                    <button type="submit" name="delete" class="btn btn-danger">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="editMode" style="display: none;">
            <form method="post">
                <div class="mb-3">
                    <label for="name" class="form-label">Nama</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['nama']) ?>" readonly>
                </div>
                <h3>Informasi Kartu Kredit</h3>
                <div class="mb-3">
                    <label for="card_number" class="form-label">Nomor Kartu Kredit</label>
                    <input type="text" class="form-control" id="card_number" name="card_number" value="<?= isset($payment['card_number']) ? htmlspecialchars(decryptAES256($payment['card_number'], $payment['iv_card_number'], $secretKey)) : '' ?>" required>
                </div>
                <div class="mb-3">
                    <label for="card_holder_name" class="form-label">Nama Pemegang Kartu</label>
                    <input type="text" class="form-control" id="card_holder_name" name="card_holder_name" value="<?= isset($payment['card_holder_name']) ? htmlspecialchars(decryptAES256($payment['card_holder_name'], $payment['iv_card_holder_name'], $secretKey)) : '' ?>" required>
                </div>
                <div class="mb-3">
                    <label for="expiry_date" class="form-label">Tanggal Kedaluwarsa</label>
                    <input type="date" class="form-control" id="expiry_date" name="expiry_date" value="<?= isset($payment['expiry_date']) ? htmlspecialchars(decryptAES256($payment['expiry_date'], $payment['iv_expiry_date'], $secretKey)) : '' ?>" required>
                </div>
                <div class="mb-3">
                    <label for="cvv" class="form-label">CVV</label>
                    <input type="text" class="form-control" id="cvv" name="cvv" value="<?= isset($payment['cvv']) ? htmlspecialchars(decryptAES256($payment['cvv'], $payment['iv_cvv'], $secretKey)) : '' ?>" required>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="hideEditForm()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- Form for new data -->
        <form method="post">
            <div class="mb-3">
                <label for="name" class="form-label">Nama</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['nama']) ?>" readonly>
            </div>
            <h3>Informasi Kartu Kredit</h3>
            <div class="mb-3">
                <label for="card_number" class="form-label">Nomor Kartu Kredit</label>
                <input type="text" class="form-control" id="card_number" name="card_number" value="" required>
            </div>
            <div class="mb-3">
                <label for="card_holder_name" class="form-label">Nama Pemegang Kartu</label>
                <input type="text" class="form-control" id="card_holder_name" name="card_holder_name" value="" required>
            </div>
            <div class="mb-3">
                <label for="expiry_date" class="form-label">Tanggal Kedaluwarsa</label>
                <input type="date" class="form-control" id="expiry_date" name="expiry_date" value="" required>
            </div>
            <div class="mb-3">
                <label for="cvv" class="form-label">CVV</label>
                <input type="text" class="form-control" id="cvv" name="cvv" value="" required>
            </div>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    <?php endif; ?>
    <div class="mb-3">
        <a href="index.php" class="btn btn-secondary">← Kembali ke Beranda</a>
    </div>
</div>
<script>
function showEditForm() {
    document.getElementById('viewMode').style.display = 'none';
    document.getElementById('editMode').style.display = 'block';
}

function hideEditForm() {
    document.getElementById('viewMode').style.display = 'block';
    document.getElementById('editMode').style.display = 'none';
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
