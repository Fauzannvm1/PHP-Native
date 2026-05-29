<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kasir_db');
define('APP_NAME', 'Sistem Kasir');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die(
        '<div style="font-family:sans-serif;padding:40px;text-align:center;color:#dc2626;">'
        . '<h2>&#9888; Koneksi Database Gagal</h2>'
        . '<p>' . mysqli_connect_error() . '</p>'
        . '<p>Periksa konfigurasi DB_HOST, DB_USER, DB_PASS, dan DB_NAME di config.php</p>'
        . '</div>'
    );
}

mysqli_set_charset($conn, 'utf8mb4');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Paksa login. Redirect ke login.php jika belum login.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Format angka ke format Rupiah
 * Contoh: 15000 => "Rp 15.000"
 */
function formatRupiah(float|int|string $angka): string
{
    return 'Rp&nbsp;' . number_format((float)$angka, 0, ',', '.');
}

/**
 * Format angka ke format Rupiah tanpa HTML entity (untuk atribut / JS)
 */
function formatRupiahPlain(float|int|string $angka): string
{
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

/**
 * Generate nomor transaksi unik
 * Contoh: TRX-20240715-A3F9B
 */
function generateNoTransaksi(): string
{
    return 'TRX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}

/**
 * Simpan flash message sukses ke session
 */
function flashSuccess(string $msg): void
{
    $_SESSION['flash_success'] = $msg;
}

/**
 * Simpan flash message error ke session
 */
function flashError(string $msg): void
{
    $_SESSION['flash_error'] = $msg;
}

/**
 * Escape output HTML
 */
function e(string|null $str): string
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}