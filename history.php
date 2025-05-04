<?php
session_start();
include 'konfig.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$secretKey = "MyVerySecretKey1234567890abcdef";

$history_items = [];

if ($conn) {
    // Ambil data dari tabel transaction_history
    $stmt = $conn->prepare("SELECT transaction_history.id_transaction, transaction_history.quantity, transaction_history.total_price, transaction_history.transaction_date, products.product, users.nama, users.iv_nama FROM transaction_history JOIN products ON transaction_history.product_id = products.id_product JOIN users ON transaction_history.user_id = users.id_user WHERE transaction_history.user_id = ? ORDER BY transaction_history.transaction_date DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Dekripsi nama pengguna
        $row['nama_user'] = decryptAES256($row['nama'], $row['iv_nama'], $secretKey);
        $history_items[] = $row;
    }
    $stmt->close();
} else {
    echo "<p>Database connection failed.</p>";
}

function decryptAES256($ciphertext, $iv, $key)
{
    $cipher = "aes-256-cbc";
    return openssl_decrypt($ciphertext, $cipher, $key, 0, $iv);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .navbar {
            background-color: #000;
        }

        .navbar-brand,
        .nav-link,
        .navbar-text {
            color: white !important;
        }

        .history-header {
            margin-top: 50px;
            color: #dc3545;
        }

        .table thead {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">Electro<span style="color: red;">.</span></a>
            <div class="collapse navbar-collapse justify-content-end">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="yourcart.php">Your Cart</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">History</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- History Section -->
    <div class="container">
        <h2 class="text-center history-header">History</h2>
        <div class="mb-3">
            <a href="index.php" class="btn btn-secondary">← Kembali ke Beranda</a>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered text-center align-middle">
                <thead>
                    <tr>
                        <th>Nama User</th>
                        <th>Nama Produk</th>
                        <th>Quantity</th>
                        <th>Total Harga</th>
                        <th>Tanggal Transaksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($history_items)): ?>
                        <?php foreach ($history_items as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nama_user']) ?></td>
                                <td><?= htmlspecialchars($row['product']) ?></td>
                                <td><?= $row['quantity'] ?></td>
                                <td>Rp<?= number_format($row['total_price'], 0, ',', '.') ?></td>
                                <td><?= htmlspecialchars($row['transaction_date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">Tidak ada riwayat transaksi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
