<?php
require __DIR__ . '/../admin/db.php';
require __DIR__ . '/../admin/permissoes.php';
require __DIR__ . '/../admin/push.php';

header('Content-Type: application/json; charset=utf-8');

$usuario = usuarioAtual();
if (!$usuario || $usuario['role'] !== 'admin') {
  http_response_code(401);
  echo json_encode(['erro' => 'Não autenticado como gestor.']);
  exit;
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM push_subscriptions WHERE usuario_id = ? AND valida = 1');
$stmt->execute([$usuario['id']]);
$qtd = (int) $stmt->fetchColumn();

if ($qtd === 0) {
  http_response_code(400);
  echo json_encode(['erro' => 'Você ainda não ativou notificações neste navegador. Entre em /agenda/ com sua conta e ative primeiro.']);
  exit;
}

enviarPushParaUsuarios([$usuario['id']], '🔔 Notificação de teste', 'Seu dispositivo está configurado corretamente para receber notificações.', '../agenda/painel.php');

echo json_encode(['ok' => true, 'dispositivos' => $qtd]);
