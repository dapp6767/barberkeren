<?php
// Mencegah akses langsung ke direktori, redirect ke dashboard
require_once __DIR__ . '/../functions/helper.php';

if (function_exists('redirect')) {
    redirect('../pelanggan/dashboard.php');
} else {
    redirect('login.php');
}
?>