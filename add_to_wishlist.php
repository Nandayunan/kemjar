<?php
session_start();
include 'konfig.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

if ($product_id > 0) {
    $cek = $conn->query("SELECT * FROM wishlist WHERE user_id=$user_id AND product_id=$product_id");
    if ($cek->num_rows == 0) {
        $conn->query("INSERT INTO wishlist (user_id, product_id) VALUES ($user_id, $product_id)");
    }
}
header('Location: wishlist.php');
exit;
