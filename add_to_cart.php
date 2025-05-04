<?php
session_start();
include 'konfig.php';

// Guest cart support
$product_id = isset($_POST['produk_id']) ? intval($_POST['produk_id']) : 0;

if ($product_id > 0) {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $cek = $conn->query("SELECT * FROM cart WHERE user_id=$user_id AND product_id=$product_id");
        if ($cek->num_rows > 0) {
            $conn->query("UPDATE cart SET quantity = quantity + 1 WHERE user_id=$user_id AND product_id=$product_id");
        } else {
            $conn->query("INSERT INTO cart (user_id, product_id, quantity) VALUES ($user_id, $product_id, 1)");
        }
    } else {
        // Guest: pakai session
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += 1;
        } else {
            $_SESSION['cart'][$product_id] = 1;
        }
    }
}
header('Location: yourcart.php');
exit;
