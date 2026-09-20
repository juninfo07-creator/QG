<?php
// Rode uma vez (php admin/migrate_eventos.php ou pelo navegador) pra migrar
// a tabela antiga "agenda" pra "eventos". Não faz nada se "eventos" já tiver
// registros — seguro de rodar de novo por engano.

require __DIR__ . '/db.php';

// Precisa rodar tanto via CLI (local) quanto via navegador (produção, sem
// acesso SSH) — por isso exige login de gestor quando chamado pela web.
if (php_sapi_name() !== 'cli') {
  require __DIR__ . '/permissoes.php';
  exigirAdmin();
}

$pdo = getPDO();

$totalEventos = (int) $pdo->query('SELECT COUNT(*) FROM eventos')->fetchColumn();
if ($totalEventos > 0) {
  echo "A tabela eventos já tem $totalEventos registro(s). Nada foi migrado.\n";
  exit;
}

$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
if ($driver === 'sqlite') {
  $tabelas = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='agenda'")->fetchAll();
  $existeAgenda = count($tabelas) > 0;
} else {
  $existeAgenda = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'agenda'")->fetchColumn() > 0;
}

if (!$existeAgenda) {
  echo "Tabela agenda não existe. Nada pra migrar.\n";
  exit;
}

$rows = $pdo->query('SELECT * FROM agenda ORDER BY ordem ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
if (!$rows) {
  echo "Tabela agenda está vazia. Nada pra migrar.\n";
  exit;
}

$meses = [
  'JAN' => 1, 'FEV' => 2, 'MAR' => 3, 'ABR' => 4, 'MAI' => 5, 'JUN' => 6,
  'JUL' => 7, 'AGO' => 8, 'SET' => 9, 'OUT' => 10, 'NOV' => 11, 'DEZ' => 12,
];
$anoAtual = (int) date('Y');

function parseData($label, $meses, $ano) {
  // Ex.: "5-6 SET", "16 SET", "4 OUT · manhã"
  $extra = null;
  if (strpos($label, '·') !== false) {
    [$label, $extraRaw] = array_map('trim', explode('·', $label, 2));
    $extra = $extraRaw;
  }
  if (!preg_match('/^(\d+)(?:-(\d+))?\s+([A-ZÇ]{3})/u', trim($label), $m)) {
    return [null, null, $extra];
  }
  $diaIni = (int) $m[1];
  $diaFim = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : null;
  $mes = $meses[$m[3]] ?? null;
  if (!$mes) return [null, null, $extra];

  $dataIni = sprintf('%04d-%02d-%02d', $ano, $mes, $diaIni);
  $dataFim = $diaFim ? sprintf('%04d-%02d-%02d', $ano, $mes, $diaFim) : null;
  return [$dataIni, $dataFim, $extra];
}

$stmt = $pdo->prepare('INSERT INTO eventos (nome_evento, data, data_fim, horario, local, cidade, status, obs_banda, ordem) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');

$importados = 0;
foreach ($rows as $i => $row) {
  [$dataIni, $dataFim, $extra] = parseData($row['data_label'], $meses, $anoAtual);
  if (!$dataIni) {
    // não deu pra interpretar a data — usa hoje como placeholder e deixa
    // marcado na observação pra o gestor corrigir depois.
    $dataIni = date('Y-m-d');
    $extra = trim(($extra ?? '') . ' [data original: ' . $row['data_label'] . ', revisar]');
  }
  $nomeEvento = 'Apresentação em ' . $row['cidade'];
  $stmt->execute([
    $nomeEvento,
    $dataIni,
    $dataFim,
    $extra,
    $row['local'],
    $row['cidade'],
    'confirmado',
    $extra ? trim($extra) : null,
    $i,
  ]);
  $importados++;
}

echo "Migrado(s) $importados evento(s) de 'agenda' pra 'eventos'.\n";
echo "Revise as datas no painel — a interpretação do texto antigo é só uma aproximação.\n";
