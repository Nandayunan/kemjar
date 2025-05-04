<?php
session_start();
include 'konfig.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$secretKey = "MyVerySecretKey1234567890abcdef";

if ($conn) {
    // Ambil data keranjang pengguna
    $stmt = $conn->prepare("SELECT cart.product_id, cart.quantity, products.harga FROM cart JOIN products ON cart.product_id = products.id_product WHERE cart.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Mulai transaksi
    $conn->begin_transaction();

    try {
        while ($row = $result->fetch_assoc()) {
            $product_id = $row['product_id'];
            $quantity = $row['quantity'];
            $total_price = $row['harga'] * $quantity;

            // Masukkan data ke tabel transaction_history
            $stmt_insert = $conn->prepare("INSERT INTO transaction_history (user_id, product_id, quantity, total_price, transaction_date) VALUES (?, ?, ?, ?, NOW())");
            $stmt_insert->bind_param("iiid", $user_id, $product_id, $quantity, $total_price);
            $stmt_insert->execute();
        }

        // Kosongkan keranjang setelah checkout
        $stmt_clear = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt_clear->bind_param("i", $user_id);
        $stmt_clear->execute();

        // Commit transaksi
        $conn->commit();

        echo "<script>alert('Checkout berhasil!'); window.location.href='yourcart.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Checkout gagal: " . $e->getMessage() . "'); window.location.href='yourcart.php';</script>";
    }
} else {
    echo "<script>alert('Koneksi database gagal.'); window.location.href='yourcart.php';</script>";
}
?>
