<?php
require __DIR__ . '/../admin/db.php';
require __DIR__ . '/../admin/permissoes.php';

header('Content-Type: application/json; charset=utf-8');

$usuario = usuarioAtual();
if (!$usuario) {
  http_response_code(401);
  echo json_encode(['erro' => 'Não autenticado.']);
  exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$endpoint = $body['endpoint'] ?? null;
if (!$endpoint) {
  http_response_code(400);
  echo json_encode(['erro' => 'Informe o endpoint.']);
  exit;
}

$pdo = getPDO();
// Só remove se o endpoint pertencer ao usuário logado.
$pdo->prepare('DELETE FROM push_subscriptions WHERE endpoint = ? AND usuario_id = ?')
  ->execute([$endpoint, $usuario['id']]);

echo json_encode(['ok' => true]);
