<?php
session_start();
session_unset(); // Menghapus semua variabel session
session_destroy(); // Menghapus session
header("Location: login.php");
exit;
