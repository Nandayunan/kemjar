<?php
session_start();
include 'konfig.php';

$cart_items = [];
$secretKey = "MyVerySecretKey1234567890abcdef";

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Check if the database connection is valid
    if ($conn) {
        // Use a prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT cart.id, cart.quantity, products.product, products.gambar, users.nama, users.iv_nama FROM cart JOIN products ON cart.product_id = products.id_product JOIN users ON cart.user_id = users.id_user WHERE cart.user_id = ?");

        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                // Decrypt the user's name
                $row['nama_user'] = decryptAES256($row['nama'], $row['iv_nama'], $secretKey);
                $cart_items[] = $row;
            }
            $stmt->close();
        } else {
            echo "<p>Error preparing the SQL statement: " . htmlspecialchars($conn->error) . "</p>";
        }
    } else {
        echo "<p>Database connection failed.</p>";
    }
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
    <title>Your Cart</title>
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

        .cart-header {
            margin-top: 50px;
            color: #dc3545;
        }

        .table thead {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
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
                        <a class="nav-link active" href="#">Your Cart</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Cart Section -->
    <div class="container">
        <h2 class="text-center cart-header">Your Cart</h2>
        <div class="text-end mb-3">
            <a href="index.php" class="btn btn-secondary">← Kembali ke Produk</a>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered text-center align-middle">
                <thead>
                    <tr>
                        <th>Nama User</th>
                        <th>Gambar Produk</th>
                        <th>Nama Produk</th>
                        <th>Quantity</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($cart_items)): ?>
                        <?php foreach ($cart_items as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nama_user']) ?></td>
                                <td><img src="img/<?= htmlspecialchars($row['gambar']) ?>" alt="Gambar Produk" style="width:60px;height:auto;"></td>
                                <td><?= htmlspecialchars($row['product']) ?></td>
                                <td><?= $row['quantity'] ?></td>
                                <td>
                                    <a href="remove_from_cart.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Hapus</a>
                                    <a href="edit_cart.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm ms-1">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">Keranjang kosong.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>