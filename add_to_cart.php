<?php
session_start();
include 'konfig.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];

        // Check if the product is already in the cart
        $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // If the product is already in the cart, update the quantity
            $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("iii", $quantity, $user_id, $product_id);
        } else {
            // If the product is not in the cart, insert a new row
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $user_id, $product_id, $quantity);
        }

        if ($stmt->execute()) {
            echo "<script>alert('Barang berhasil ditambahkan ke keranjang!'); window.location.href='yourcart.php';</script>";
        } else {
            echo "<script>alert('Gagal menambahkan barang ke keranjang.');</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('Anda harus login terlebih dahulu.'); window.location.href='login.php';</script>";
    }
}
