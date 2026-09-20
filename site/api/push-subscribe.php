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
$p256dh = $body['keys']['p256dh'] ?? null;
$auth = $body['keys']['auth'] ?? null;

if (!$endpoint || !$p256dh || !$auth) {
  http_response_code(400);
  echo json_encode(['erro' => 'Inscrição de push inválida.']);
  exit;
}

$pdo = getPDO();
$userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);

$stmt = $pdo->prepare('SELECT id, usuario_id FROM push_subscriptions WHERE endpoint = ?');
$stmt->execute([$endpoint]);
$existente = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existente) {
  // Mesmo endpoint pode ter sido registrado antes por outro login neste
  // aparelho — atualiza pra pertencer ao usuário atual.
  $pdo->prepare('UPDATE push_subscriptions SET usuario_id = ?, p256dh = ?, auth = ?, user_agent = ?, valida = 1, atualizado_em = CURRENT_TIMESTAMP WHERE id = ?')
    ->execute([$usuario['id'], $p256dh, $auth, $userAgent, $existente['id']]);
} else {
  $pdo->prepare('INSERT INTO push_subscriptions (usuario_id, endpoint, p256dh, auth, user_agent) VALUES (?, ?, ?, ?, ?)')
    ->execute([$usuario['id'], $endpoint, $p256dh, $auth, $userAgent]);
}

echo json_encode(['ok' => true]);
