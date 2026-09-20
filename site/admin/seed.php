<?php
// Rode este arquivo UMA VEZ (via linha de comando: php admin/seed.php)
// pra: 1) importar as datas de data/agenda.json pro banco, e
//      2) criar a primeira conta de admin do painel.
// Depois disso, novas contas só são criadas rodando este script de novo
// (não existe cadastro público) — a senha, essa sim, o usuário troca sozinho
// pelo "Esqueci minha senha".

if (php_sapi_name() !== 'cli') {
  http_response_code(403);
  exit('Acesso negado. Rode este script via linha de comando: php admin/seed.php');
}

require __DIR__ . '/db.php';

$pdo = getPDO();

// --- Agenda ---
$totalAgenda = (int) $pdo->query('SELECT COUNT(*) FROM agenda')->fetchColumn();
if ($totalAgenda > 0) {
  echo "A tabela agenda já tem $totalAgenda registro(s). Nada foi importado.\n";
} else {
  $jsonPath = __DIR__ . '/../data/agenda.json';
  if (!file_exists($jsonPath)) {
    echo "Arquivo data/agenda.json não encontrado.\n";
  } else {
    $dados = json_decode(file_get_contents($jsonPath), true);
    if (!is_array($dados)) {
      echo "Não foi possível ler data/agenda.json.\n";
    } else {
      $stmt = $pdo->prepare('INSERT INTO agenda (data_label, cidade, local, ordem) VALUES (?, ?, ?, ?)');
      foreach ($dados as $i => $item) {
        $stmt->execute([$item['data'] ?? '', $item['cidade'] ?? '', $item['local'] ?? '', $i]);
      }
      echo 'Importado(s) ' . count($dados) . " registro(s) de agenda com sucesso.\n";
    }
  }
}

// --- Conta de admin inicial ---
// Ajuste e-mail/senha abaixo antes de rodar em produção, ou crie contas
// extras rodando este bloco de novo com dados diferentes.
$adminEmail = 'danieljuniormkt@gmail.com';
$adminSenha = 'Gileade123';

$totalAdmins = (int) $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
if ($totalAdmins > 0) {
  echo "Já existe(m) $totalAdmins conta(s) de admin. Nenhuma nova conta foi criada.\n";
} else {
  $hash = password_hash($adminSenha, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare('INSERT INTO admin_users (email, senha_hash) VALUES (?, ?)');
  $stmt->execute([$adminEmail, $hash]);
  echo "Conta de admin criada para {$adminEmail}.\n";
}
