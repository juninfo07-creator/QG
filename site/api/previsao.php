<?php
require __DIR__ . '/../admin/db.php';
require __DIR__ . '/../admin/permissoes.php';

header('Content-Type: application/json; charset=utf-8');

// Resumo agregado (só somas) — visível pra gestor e integrantes, já que não
// expõe dados administrativos de nenhum evento individual.
$usuario = usuarioAtual();
if (!$usuario) {
  http_response_code(401);
  echo json_encode(['erro' => 'Não autenticado.']);
  exit;
}

$periodo = $_GET['periodo'] ?? 'mes_atual';
$hoje = date('Y-m-d');

switch ($periodo) {
  case 'proximo_mes':
    $inicio = date('Y-m-01', strtotime('first day of next month'));
    $fim = date('Y-m-t', strtotime('first day of next month'));
    break;
  case 'ano_atual':
    $inicio = date('Y-01-01');
    $fim = date('Y-12-31');
    break;
  case 'personalizado':
    $inicio = $_GET['inicio'] ?? $hoje;
    $fim = $_GET['fim'] ?? $hoje;
    break;
  case 'mes_atual':
  default:
    $inicio = date('Y-m-01');
    $fim = date('Y-m-t');
    break;
}

// Regra financeira definitiva: só Confirmado e Concluído entram no cálculo,
// independente da data ser passada ou futura dentro do período (um evento
// concluído já aconteceu, mas continua contando pro financeiro).
$pdo = getPDO();
$stmt = $pdo->prepare(
  "SELECT cache_bruto, despesas, cache_liquido FROM eventos
   WHERE status IN ('confirmado', 'concluido')
   AND data >= ? AND data <= ?"
);
$stmt->execute([$inicio, $fim]);
$linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$bruto = 0.0;
$despesas = 0.0;
$liquido = 0.0;
foreach ($linhas as $l) {
  $bruto += (float) ($l['cache_bruto'] ?? 0);
  $despesas += (float) ($l['despesas'] ?? 0);
  $liquido += (float) ($l['cache_liquido'] ?? 0);
}

$resposta = [
  'periodo' => $periodo,
  'inicio' => $inicio,
  'fim' => $fim,
  'quantidade' => count($linhas),
  'liquido' => $liquido,
];

// Cachê Bruto e Despesas são informação administrativa — integrantes
// recebem só o valor líquido.
if (ehAdmin()) {
  $resposta['bruto'] = $bruto;
  $resposta['despesas'] = $despesas;
}

echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
