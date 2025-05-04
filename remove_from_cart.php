<?php
session_start();
include 'konfig.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id > 0) {
    $conn->query("DELETE FROM cart WHERE id=$id");
}
header('Location: yourcart.php');
exit;
