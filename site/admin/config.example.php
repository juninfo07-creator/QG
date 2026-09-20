<?php
// Copie este arquivo para "config.php" e preencha com os dados reais.
// config.php NÃO deve ser enviado pro Git (contém senha).

return [
  'db' => [
    // No HostGator use 'mysql'. Localmente pode usar 'sqlite' pra testar sem
    // precisar instalar um servidor MySQL.
    'driver' => 'mysql',
    'host' => 'localhost',
    'name' => 'nome_do_banco',
    'user' => 'usuario_do_banco',
    'pass' => 'senha_do_banco',
    'sqlite_path' => __DIR__ . '/../data/agenda.sqlite',
  ],
  'vapid' => [
    // E-mail de contato exigido pelo protocolo VAPID (Web Push).
    'subject' => 'mailto:seu-email@exemplo.com',
    // Gere um par de chaves rodando: php admin/vapid_generate.php
    'public_key' => '',
    'private_key' => '',
  ],
];

// As contas de login do painel ficam na tabela admin_users do banco, não
// aqui. Pra criar a primeira conta, edite $adminEmail/$adminSenha em
// admin/seed.php e rode: php admin/seed.php
