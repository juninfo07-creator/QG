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

$pdo = getPDO();
$limite = min(50, max(1, (int) ($_GET['limite'] ?? 20)));
$stmt = $pdo->prepare('SELECT id, evento_id, tipo, titulo, mensagem, criado_em FROM notificacoes ORDER BY criado_em DESC, id DESC LIMIT ' . $limite);
$stmt->execute();
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
