<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/antrean.php';
require_once __DIR__ . '/crud_barber.php';
require_once __DIR__ . '/crud_layanan.php';
