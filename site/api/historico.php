<?php
require __DIR__ . '/../admin/db.php';
require __DIR__ . '/../admin/permissoes.php';

header('Content-Type: application/json; charset=utf-8');

$usuario = usuarioAtual();
if (!$usuario || $usuario['role'] !== 'admin') {
  http_response_code(401);
  echo json_encode(['erro' => 'Não autenticado como gestor.']);
  exit;
}

$eventoId = (int) ($_GET['evento_id'] ?? 0);
if (!$eventoId) {
  http_response_code(400);
  echo json_encode(['erro' => 'Informe evento_id.']);
  exit;
}

$pdo = getPDO();
$stmt = $pdo->prepare('SELECT descricao, autor_email, criado_em FROM eventos_historico WHERE evento_id = ? ORDER BY criado_em DESC, id DESC');
$stmt->execute([$eventoId]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
