<?php
session_start();
include 'konfig.php';
$secretKey = "MyVerySecretKey1234567890abcdef";

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_id'], $_POST['quantity'])) {
    $cart_id = intval($_POST['cart_id']);
    $quantity = intval($_POST['quantity']);
    if ($quantity > 0) {
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("iii", $quantity, $cart_id, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
        header('Location: yourcart.php');
        exit;
    } else {
        $error = "Quantity harus lebih dari 0.";
    }
}

// Ambil data cart berdasarkan id
if (isset($_GET['id'])) {
    $cart_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT cart.id, cart.quantity, products.product, products.gambar FROM cart JOIN products ON cart.product_id = products.id_product WHERE cart.id = ? AND cart.user_id = ?");
    $stmt->bind_param("ii", $cart_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_item = $result->fetch_assoc();
    $stmt->close();
    if (!$cart_item) {
        die('Data cart tidak ditemukan atau bukan milik Anda.');
    }
} else {
    die('ID cart tidak ditemukan.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Edit Cart Item</h2>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="cart_id" value="<?= htmlspecialchars($cart_item['id']) ?>">
        <div class="mb-3">
            <label class="form-label">Nama Produk</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($cart_item['product']) ?>" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">Gambar Produk</label><br>
            <img src="img/<?= htmlspecialchars($cart_item['gambar']) ?>" alt="Gambar Produk" style="width:100px;height:auto;">
        </div>
        <div class="mb-3">
            <label class="form-label">Quantity</label>
            <input type="number" name="quantity" class="form-control" value="<?= htmlspecialchars($cart_item['quantity']) ?>" min="1" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="yourcart.php" class="btn btn-secondary">Batal</a>
    </form>
</div>
</body>
</html>
