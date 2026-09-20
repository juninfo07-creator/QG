<?php
require __DIR__ . '/../admin/db.php';
require __DIR__ . '/../admin/permissoes.php';
require __DIR__ . '/../admin/push.php';

header('Content-Type: application/json; charset=utf-8');

function formatarDataBR($iso) {
  $p = explode('-', (string) $iso);
  return count($p) === 3 ? "{$p[2]}/{$p[1]}/{$p[0]}" : $iso;
}

function urlEvento($id) {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  return "{$scheme}://{$host}/agenda/evento.php?id={$id}";
}

function urlAgenda() {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  return "{$scheme}://{$host}/agenda/painel.php";
}

$STATUS_VALIDOS = ['a_confirmar', 'confirmado', 'concluido', 'cancelado'];
$STATUS_PAGAMENTO_VALIDOS = ['pendente', 'parcial', 'pago'];

function dataValida($d) {
  if ($d === null || $d === '') return true;
  $dt = DateTime::createFromFormat('Y-m-d', $d);
  return $dt !== false && $dt->format('Y-m-d') === $d;
}

// Valida status, status_pagamento, datas e valores financeiros. Retorna uma
// mensagem de erro (string) se algo estiver inválido, ou null se estiver tudo ok.
function validarCamposEvento($body) {
  global $STATUS_VALIDOS, $STATUS_PAGAMENTO_VALIDOS;

  if (isset($body['status']) && !in_array($body['status'], $STATUS_VALIDOS, true)) {
    return 'Status inválido.';
  }
  if (isset($body['status_pagamento']) && !in_array($body['status_pagamento'], $STATUS_PAGAMENTO_VALIDOS, true)) {
    return 'Status de pagamento inválido.';
  }
  if (!dataValida($body['data'] ?? null)) {
    return 'Data inválida. Use o formato AAAA-MM-DD.';
  }
  if (isset($body['data_fim']) && !dataValida($body['data_fim'])) {
    return 'Data final inválida. Use o formato AAAA-MM-DD.';
  }
  foreach (['cache_bruto', 'despesas'] as $campo) {
    if (isset($body[$campo]) && $body[$campo] !== '' && (float) $body[$campo] < 0) {
      return 'Valores financeiros não podem ser negativos.';
    }
  }
  return null;
}

$PUBLICOS = ['id', 'nome_evento', 'data', 'data_fim', 'horario', 'local', 'cidade', 'endereco', 'mapa_link', 'status'];
$BANDA = ['horario_saida_rv', 'horario_chegada', 'horario_som', 'horario_saida', 'transporte', 'hospedagem', 'equipamentos', 'obs_banda'];
$ADMIN = ['cache_bruto', 'despesas', 'forma_pagamento', 'status_pagamento', 'contratante_nome', 'contratante_telefone', 'contratante_email', 'obs_admin', 'concluido_em', 'ordem', 'criado_em', 'atualizado_em'];

// Cachê Líquido nunca é editável — é sempre calculado a partir do Bruto e das Despesas.
function comLiquido($row) {
  if (array_key_exists('cache_bruto', $row)) {
    $bruto = (float) ($row['cache_bruto'] ?? 0);
    $despesas = (float) ($row['despesas'] ?? 0);
    $row['cache_liquido'] = $bruto - $despesas;
  }
  return $row;
}

function camposPermitidos($usuario) {
  global $PUBLICOS, $BANDA, $ADMIN;
  $campos = $PUBLICOS;
  if ($usuario) {
    $campos = array_merge($campos, $BANDA);
    if ($usuario['role'] === 'admin') {
      $campos = array_merge($campos, $ADMIN);
    }
  }
  return $campos;
}

function filtrarEvento($row, $campos) {
  $out = [];
  foreach ($campos as $c) {
    if (array_key_exists($c, $row)) $out[$c] = $row[$c];
  }
  return $out;
}

function registrarHistorico($pdo, $eventoId, $descricao, $autorEmail) {
  $stmt = $pdo->prepare('INSERT INTO eventos_historico (evento_id, descricao, autor_email) VALUES (?, ?, ?)');
  $stmt->execute([$eventoId, $descricao, $autorEmail]);
}

function registrarNotificacao($pdo, $eventoId, $tipo, $titulo, $mensagem) {
  $stmt = $pdo->prepare('INSERT INTO notificacoes (evento_id, tipo, titulo, mensagem) VALUES (?, ?, ?, ?)');
  $stmt->execute([$eventoId, $tipo, $titulo, $mensagem]);
}

$pdo = getPDO();
$usuario = usuarioAtual();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $campos = camposPermitidos($usuario);
  $colunas = implode(', ', $campos);
  $ehAdmin = $usuario && $usuario['role'] === 'admin';

  if (!empty($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT $colunas FROM eventos WHERE id = ?");
    $stmt->execute([(int) $_GET['id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
      http_response_code(404);
      echo json_encode(['erro' => 'Evento não encontrado.']);
      exit;
    }
    echo json_encode($ehAdmin ? comLiquido($row) : $row, JSON_UNESCAPED_UNICODE);
    exit;
  }

  // Agenda "ativa" (site público, integrantes e painel principal do gestor)
  // mostra só A confirmar + Confirmado. "Concluído" só aparece em Arquivados.
  // Cancelado continua visível pro gestor no painel principal.
  if ($ehAdmin && !empty($_GET['arquivados'])) {
    $where = "status = 'concluido'";
  } elseif ($ehAdmin) {
    $where = "status != 'concluido'";
  } else {
    $where = "status IN ('a_confirmar', 'confirmado')";
  }

  $stmt = $pdo->query("SELECT $colunas FROM eventos WHERE $where ORDER BY data ASC, id ASC");
  $linhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if ($ehAdmin) $linhas = array_map('comLiquido', $linhas);
  echo json_encode($linhas, JSON_UNESCAPED_UNICODE);
  exit;
}

// Todas as escritas exigem gestor logado.
if (!$usuario || $usuario['role'] !== 'admin') {
  http_response_code(401);
  echo json_encode(['erro' => 'Não autenticado como gestor.']);
  exit;
}

$body = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST') {
  if (!is_array($body) || trim($body['nome_evento'] ?? '') === '' || trim($body['data'] ?? '') === '' || trim($body['local'] ?? '') === '' || trim($body['cidade'] ?? '') === '') {
    http_response_code(400);
    echo json_encode(['erro' => 'Preencha nome do evento, data, local e cidade.']);
    exit;
  }
  $erroValidacao = validarCamposEvento($body);
  if ($erroValidacao) {
    http_response_code(400);
    echo json_encode(['erro' => $erroValidacao]);
    exit;
  }

  $campos = array_merge(['nome_evento', 'data', 'data_fim', 'horario', 'local', 'cidade', 'endereco', 'mapa_link', 'status'], $GLOBALS['BANDA'], $GLOBALS['ADMIN']);
  $campos = array_diff($campos, ['id', 'criado_em', 'atualizado_em']);

  $colunas = [];
  $valores = [];
  $params = [];
  foreach ($campos as $c) {
    if (array_key_exists($c, $body)) {
      $colunas[] = $c;
      $valores[] = '?';
      $params[] = $body[$c] === '' ? null : $body[$c];
    }
  }
  if (!in_array('status', $colunas)) { $colunas[] = 'status'; $valores[] = '?'; $params[] = 'a_confirmar'; }
  if (!in_array('status_pagamento', $colunas)) { $colunas[] = 'status_pagamento'; $valores[] = '?'; $params[] = 'pendente'; }

  $sql = 'INSERT INTO eventos (' . implode(', ', $colunas) . ') VALUES (' . implode(', ', $valores) . ')';
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $novoId = (int) $pdo->lastInsertId();

  registrarHistorico($pdo, $novoId, 'Evento criado', $usuario['email']);
  registrarNotificacao($pdo, $novoId, 'novo', 'Novo evento adicionado', trim($body['nome_evento']) . ' — ' . trim($body['data']));

  if (($body['status'] ?? 'a_confirmar') !== 'cancelado') {
    $linha1 = formatarDataBR($body['data']) . (!empty($body['horario']) ? ' • ' . $body['horario'] : '');
    $linha2 = trim($body['local'] ?? '') . (!empty($body['cidade']) ? ' — ' . $body['cidade'] : '');
    enviarPushParaIntegrantes('🎸 Novo evento na agenda', trim($linha1 . "\n" . $linha2), urlEvento($novoId));
  }

  echo json_encode(['ok' => true, 'id' => $novoId]);
  exit;
}

if ($method === 'PUT') {
  $id = (int) ($_GET['id'] ?? 0);
  if (!$id) {
    http_response_code(400);
    echo json_encode(['erro' => 'Informe o id do evento.']);
    exit;
  }
  $stmt = $pdo->prepare('SELECT * FROM eventos WHERE id = ?');
  $stmt->execute([$id]);
  $atual = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$atual) {
    http_response_code(404);
    echo json_encode(['erro' => 'Evento não encontrado.']);
    exit;
  }
  if (!is_array($body) || trim($body['nome_evento'] ?? '') === '' || trim($body['data'] ?? '') === '' || trim($body['local'] ?? '') === '' || trim($body['cidade'] ?? '') === '') {
    http_response_code(400);
    echo json_encode(['erro' => 'Preencha nome do evento, data, local e cidade.']);
    exit;
  }
  $erroValidacao = validarCamposEvento($body);
  if ($erroValidacao) {
    http_response_code(400);
    echo json_encode(['erro' => $erroValidacao]);
    exit;
  }

  $todos = array_merge(['nome_evento', 'data', 'data_fim', 'horario', 'local', 'cidade', 'endereco', 'mapa_link', 'status'], $GLOBALS['BANDA'], $GLOBALS['ADMIN']);
  // concluido_em nunca vem do cliente — é definido automaticamente pelo servidor.
  $todos = array_diff($todos, ['id', 'criado_em', 'atualizado_em', 'concluido_em']);

  $rotulos = [
    'nome_evento' => 'Nome do evento', 'data' => 'Data', 'data_fim' => 'Data final',
    'horario' => 'Horário', 'local' => 'Local', 'cidade' => 'Cidade', 'endereco' => 'Endereço',
    'mapa_link' => 'Link do mapa', 'status' => 'Status',
    'horario_saida_rv' => 'Horário de saída RV', 'horario_chegada' => 'Previsão de chegada', 'horario_som' => 'Passagem de som', 'horario_saida' => 'Horário de saída',
    'transporte' => 'Transporte', 'hospedagem' => 'Hospedagem', 'equipamentos' => 'Equipamentos', 'obs_banda' => 'Observações pra banda',
    'cache_bruto' => 'Cachê Bruto', 'despesas' => 'Despesas', 'forma_pagamento' => 'Forma de pagamento', 'status_pagamento' => 'Status do pagamento',
    'contratante_nome' => 'Contratante', 'contratante_telefone' => 'Telefone do contratante', 'contratante_email' => 'E-mail do contratante',
    'obs_admin' => 'Observações administrativas',
  ];

  $sets = [];
  $params = [];
  $mudou = [];
  foreach ($todos as $c) {
    if (!array_key_exists($c, $body)) continue;
    $novo = $body[$c] === '' ? null : $body[$c];
    $velho = $atual[$c];
    if ((string) $novo !== (string) $velho) {
      $mudou[$c] = ['de' => $velho, 'para' => $novo];
    }
    $sets[] = "$c = ?";
    $params[] = $novo;
  }

  // Transição pra "Concluído" registra a data automaticamente (não editável pelo cliente).
  if (isset($mudou['status']) && $mudou['status']['para'] === 'concluido') {
    $sets[] = 'concluido_em = ?';
    $params[] = date('Y-m-d H:i:s');
  }

  $params[] = $id;
  $pdo->prepare('UPDATE eventos SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);

  $eventosSensiveis = ['data', 'horario', 'local', 'cidade', 'status'];
  $relevante = false;
  foreach ($mudou as $campo => $diff) {
    $rotulo = $rotulos[$campo] ?? $campo;
    // Comparação explícita com null/'' — um valor antigo "0" (ex.: despesas
    // zeradas) é uma informação válida e não deve ser tratado como vazio.
    $deTexto = ($diff['de'] !== null && $diff['de'] !== '') ? " de \"{$diff['de']}\"" : '';
    registrarHistorico($pdo, $id, "$rotulo alterado(a)$deTexto para \"{$diff['para']}\"", $usuario['email']);
    if (in_array($campo, $eventosSensiveis)) $relevante = true;
  }

  if (isset($mudou['status']) && $mudou['status']['para'] === 'cancelado') {
    registrarNotificacao($pdo, $id, 'cancelado', 'Evento cancelado', $atual['nome_evento'] . ' foi cancelado.');
  } elseif ($relevante) {
    registrarNotificacao($pdo, $id, 'alterado', 'Evento alterado', $atual['nome_evento'] . ' teve informações atualizadas.');
  }

  // Push: uma notificação por salvamento, priorizando o campo mais relevante.
  $dataAtualBR = formatarDataBR($body['data'] ?? $atual['data']);
  $bandaFields = ['horario_chegada', 'horario_som', 'horario_saida', 'transporte', 'hospedagem', 'equipamentos', 'obs_banda'];

  if (isset($mudou['status']) && $mudou['status']['para'] === 'cancelado') {
    enviarPushParaIntegrantes('❌ Agenda cancelada', "O evento de {$dataAtualBR} foi cancelado.", urlEvento($id));
  } elseif (isset($mudou['data'])) {
    enviarPushParaIntegrantes('📅 Data alterada', 'A agenda foi alterada para ' . formatarDataBR($mudou['data']['para']) . '.', urlEvento($id));
  } elseif (isset($mudou['horario'])) {
    enviarPushParaIntegrantes('⚠️ Agenda atualizada', "A agenda de {$dataAtualBR} teve o horário alterado para {$mudou['horario']['para']}.", urlEvento($id));
  } elseif (isset($mudou['local']) || isset($mudou['cidade'])) {
    enviarPushParaIntegrantes('📍 Local alterado', "O evento de {$dataAtualBR} terá um novo local.", urlEvento($id));
  } else {
    foreach ($bandaFields as $campo) {
      if (isset($mudou[$campo])) {
        enviarPushParaIntegrantes('📝 Nova informação sobre o show', "Foi adicionada uma nova informação ao evento de {$dataAtualBR}.", urlEvento($id));
        break;
      }
    }
  }

  echo json_encode(['ok' => true]);
  exit;
}

if ($method === 'DELETE') {
  $id = (int) ($_GET['id'] ?? 0);
  if (!$id) {
    http_response_code(400);
    echo json_encode(['erro' => 'Informe o id do evento.']);
    exit;
  }
  $stmt = $pdo->prepare('SELECT nome_evento, data FROM eventos WHERE id = ?');
  $stmt->execute([$id]);
  $evento = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$evento) {
    http_response_code(404);
    echo json_encode(['erro' => 'Evento não encontrado.']);
    exit;
  }
  $nome = $evento['nome_evento'];
  $pdo->prepare('DELETE FROM eventos WHERE id = ?')->execute([$id]);
  $pdo->prepare('DELETE FROM eventos_historico WHERE evento_id = ?')->execute([$id]);
  registrarNotificacao($pdo, null, 'removido', 'Evento removido', $nome . ' foi removido da agenda.');
  enviarPushParaIntegrantes('🗑️ Evento removido', $nome . ' (' . formatarDataBR($evento['data']) . ') foi removido da agenda.', urlAgenda());
  echo json_encode(['ok' => true]);
  exit;
}

http_response_code(405);
echo json_encode(['erro' => 'Método não suportado.']);
