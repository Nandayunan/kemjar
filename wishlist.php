<?php
session_start();
include 'konfig.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT wishlist.id, wishlist.product_id, produk.nama_produk FROM wishlist JOIN produk ON wishlist.product_id = produk.id WHERE wishlist.user_id=$user_id");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Wishlist</title>
</head>
<body>
    <h2>Wishlist</h2>
    <a href="index.php">Kembali ke Produk</a><br><br>
    <table border="1" cellpadding="8">
        <tr>
            <th>Nama Produk</th>
            <th>Aksi</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['nama_produk']) ?></td>
            <td><a href="remove_from_wishlist.php?id=<?= $row['id'] ?>">Hapus</a></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
