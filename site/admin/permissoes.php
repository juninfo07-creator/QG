<?php
if (session_status() === PHP_SESSION_NONE) {
  $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

function usuarioAtual() {
  return $_SESSION['usuario'] ?? null;
}

function iniciarSessaoUsuario($usuario) {
  session_regenerate_id(true);
  $_SESSION['usuario'] = [
    'id' => $usuario['id'],
    'email' => $usuario['email'],
    'nome' => $usuario['nome'] ?: $usuario['email'],
    'role' => $usuario['role'],
  ];
  // Compatibilidade com código antigo que só checava $_SESSION['logado'].
  $_SESSION['logado'] = true;
}

function exigirLogin($redirectUrl) {
  if (!usuarioAtual()) {
    header('Location: ' . $redirectUrl);
    exit;
  }
}

function ehAdmin() {
  $u = usuarioAtual();
  return $u && $u['role'] === 'admin';
}

function exigirAdmin($redirectUrl = null) {
  $u = usuarioAtual();
  if (!$u) {
    header('Location: ' . ($redirectUrl ?: 'index.php'));
    exit;
  }
  if ($u['role'] !== 'admin') {
    http_response_code(403);
    echo 'Acesso restrito ao gestor.';
    exit;
  }
}
