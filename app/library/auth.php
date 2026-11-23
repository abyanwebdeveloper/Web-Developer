<?php
/**
 * Autentikasi & proteksi halaman
 */
require_once __DIR__ . '/functions.php';

/** Token CSRF sederhana */
function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_valid()
{
    return isset($_POST['csrf']) && hash_equals(csrf_token(), $_POST['csrf']);
}

/** Wajib login untuk mengakses halaman */
function require_login()
{
    if (!is_login()) {
        redirect('/app/login.php');
    }
}

/** Proses login */
function attempt_login($conn, $username, $password)
{
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND aktif = 1 LIMIT 1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id_user'   => $user['id_user'],
            'username'  => $user['username'],
            'nama'      => $user['nama'],
            'hak_akses' => $user['hak_akses'],
        ];
        return true;
    }
    return false;
}

function logout()
{
    $_SESSION = [];
    session_destroy();
}
