<?php
session_start();
include 'konfig.php';


// Guest cart: gunakan session jika belum login
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $result = $conn->query("SELECT cart.id, cart.product_id, cart.quantity, produk.nama_produk FROM cart JOIN produk ON cart.product_id = produk.id WHERE cart.user_id=$user_id");
    $cart_items = [];
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
    }
} else {
    // Cart di session (guest)
    $cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Cart</title>
</head>
<body>
    <h2>Your Cart</h2>
    <a href="index.php">Kembali ke Produk</a><br><br>
    <table border="1" cellpadding="8">
        <tr>
            <th>Nama Produk</th>
            <th>Quantity</th>
            <th>Aksi</th>
        </tr>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php foreach ($cart_items as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                <td><?= $row['quantity'] ?></td>
                <td><a href="remove_from_cart.php?id=<?= $row['id'] ?>">Hapus</a></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <?php foreach ($cart_items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['nama_produk']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td><a href="remove_from_cart.php?session=1&product_id=<?= $item['product_id'] ?>">Hapus</a></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</body>
</html>
