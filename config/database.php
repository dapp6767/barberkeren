<?php
/**
 * ==============================================================================
 * KONFIGURASI DATABASE TERPUSAT
 * Barber Keren Application
 * ==============================================================================
 * Kredensial sensitif dimuat secara dinamis melalui file `.env` atau
 * `config/database.local.php` sehingga aman dan tidak akan ter-expose di GitHub.
 */

// Polyfill fungsi string untuk kompatibilitas versi PHP < 8.0 di shared hosting
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return $needle === '' || $needle === substr($haystack, -strlen($needle));
    }
}
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}

// 1. Fungsi Mandiri untuk Memuat File .env (Tanpa Ketergantungan Composer)
if (!function_exists('load_env_file')) {
    function load_env_file($path) {
        if (!file_exists($path) || !is_readable($path)) {
            return false;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            // Lewati komentar
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
                continue;
            }

            // Pisahkan key dan value
            if (str_contains($line, '=')) {
                list($key, $value) = explode('=', $line, 2);
                $key   = trim($key);
                $value = trim($value);

                // Hapus tanda kutip jika ada
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }

                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                }
                if (!array_key_exists($key, $_SERVER)) {
                    $_SERVER[$key] = $value;
                }
                if (function_exists('putenv')) {
                    @putenv("{$key}={$value}");
                }
            }
        }
        return true;
    }
}

// Muat .env jika ada di root proyek atau di folder config
$envRoot = dirname(__DIR__) . '/.env';
$envConfig = __DIR__ . '/.env';
if (file_exists($envRoot)) {
    load_env_file($envRoot);
} elseif (file_exists($envConfig)) {
    load_env_file($envConfig);
}

// 2. Helper untuk mengambil nilai Environment Variable
if (!function_exists('env_val')) {
    function env_val($key, $default = null) {
        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        return $default;
    }
}

// 3. Deteksi Lingkungan (Lokal vs Hosting)
$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$isCli    = (php_sapi_name() === 'cli');
$isLocal  = (
    $isCli ||
    empty($httpHost) ||
    $httpHost === 'localhost' ||
    $httpHost === '127.0.0.1' ||
    $httpHost === '::1' ||
    str_contains($httpHost, 'localhost:') ||
    str_contains($httpHost, '127.0.0.1:') ||
    str_ends_with($httpHost, '.test') ||
    str_ends_with($httpHost, '.local') ||
    strtolower((string)env_val('APP_ENV', '')) === 'local'
);

// 4. Periksa apakah ada file override PHP lokal (config/database.local.php)
$localConfigPath = __DIR__ . '/database.local.php';
$localConfig = [];
if (file_exists($localConfigPath)) {
    $included = include $localConfigPath;
    if (is_array($included)) {
        $localConfig = $included;
    }
}

// 5. Tentukan Kredensial Database
$host     = $localConfig['host']     ?? env_val('DB_HOST', $isLocal ? '127.0.0.1' : 'localhost');
$port     = $localConfig['port']     ?? env_val('DB_PORT', '3306');
$dbname   = $localConfig['dbname']   ?? env_val('DB_NAME', 'barber_db');
$username = $localConfig['username'] ?? env_val('DB_USER', $isLocal ? 'root' : '');
$password = $localConfig['password'] ?? env_val('DB_PASS', '');

// 6. Inisialisasi Koneksi PDO
try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Set Zona Waktu Default
    date_default_timezone_set('Asia/Jakarta');
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    if ($isLocal) {
        die("<h3>Koneksi Database Gagal (Lokal)</h3>" .
            "<p><b>Pesan:</b> " . htmlspecialchars($e->getMessage()) . "</p>" .
            "<p>Pastikan MySQL di Laragon / XAMPP sudah berjalan dan file <code>.env</code> sudah sesuai.</p>");
    } else {
        error_log("Database connection error: " . $e->getMessage());
        die("<h3>Koneksi Database Gagal</h3>" .
            "<p>Tidak dapat terhubung ke server database hosting.</p>" .
            "<p><b>Petunjuk:</b> Pastikan Anda telah membuat file <code>.env</code> atau <code>config/database.local.php</code> di hosting dengan kredensial database yang sesuai dari panel hosting Anda.</p>");
    }
}