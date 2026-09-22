<?php

function getConfig() {
  return require __DIR__ . '/config.php';
}

function columnExists(PDO $pdo, $driver, $table, $column) {
  if ($driver === 'sqlite') {
    $stmt = $pdo->query("PRAGMA table_info($table)");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
      if (strcasecmp($col['name'], $column) === 0) return true;
    }
    return false;
  }
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
  $stmt->execute([$table, $column]);
  return (int) $stmt->fetchColumn() > 0;
}

function getPDO() {
  $config = getConfig();
  $db = $config['db'];

  if ($db['driver'] === 'sqlite') {
    $pdo = new PDO('sqlite:' . $db['sqlite_path']);
  } else {
    $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $db['user'], $db['pass']);
  }
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

  if ($driver === 'sqlite') {
    // Tabela legada (mantida só pra permitir a migração pontual pra "eventos").
    $pdo->exec('CREATE TABLE IF NOT EXISTS agenda (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      data_label TEXT NOT NULL,
      cidade TEXT NOT NULL,
      local TEXT NOT NULL,
      ordem INTEGER NOT NULL DEFAULT 0
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS admin_users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      email TEXT NOT NULL UNIQUE,
      senha_hash TEXT NOT NULL,
      reset_token TEXT,
      reset_expira TEXT
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS eventos (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      nome_evento TEXT NOT NULL,
      data TEXT NOT NULL,
      data_fim TEXT,
      horario TEXT,
      local TEXT NOT NULL,
      cidade TEXT NOT NULL,
      endereco TEXT,
      mapa_link TEXT,
      status TEXT NOT NULL DEFAULT "a_confirmar",
      horario_saida_rv TEXT,
      horario_chegada TEXT,
      horario_som TEXT,
      horario_saida TEXT,
      transporte TEXT,
      hospedagem TEXT,
      equipamentos TEXT,
      obs_banda TEXT,
      cache_bruto TEXT,
      despesas TEXT,
      forma_pagamento TEXT,
      status_pagamento TEXT NOT NULL DEFAULT "pendente",
      contratante_nome TEXT,
      contratante_telefone TEXT,
      contratante_email TEXT,
      obs_admin TEXT,
      concluido_em TEXT,
      ordem INTEGER NOT NULL DEFAULT 0,
      criado_em TEXT DEFAULT CURRENT_TIMESTAMP,
      atualizado_em TEXT DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS eventos_historico (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      evento_id INTEGER NOT NULL,
      descricao TEXT NOT NULL,
      autor_email TEXT,
      criado_em TEXT DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS notificacoes (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      evento_id INTEGER,
      tipo TEXT NOT NULL DEFAULT "info",
      titulo TEXT NOT NULL,
      mensagem TEXT,
      criado_em TEXT DEFAULT CURRENT_TIMESTAMP
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS push_subscriptions (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      usuario_id INTEGER NOT NULL,
      endpoint TEXT NOT NULL UNIQUE,
      p256dh TEXT NOT NULL,
      auth TEXT NOT NULL,
      user_agent TEXT,
      valida INTEGER NOT NULL DEFAULT 1,
      criado_em TEXT DEFAULT CURRENT_TIMESTAMP,
      atualizado_em TEXT DEFAULT CURRENT_TIMESTAMP,
      ultimo_uso TEXT
    )');
  } else {
    $pdo->exec('CREATE TABLE IF NOT EXISTS agenda (
      id INT AUTO_INCREMENT PRIMARY KEY,
      data_label VARCHAR(50) NOT NULL,
      cidade VARCHAR(150) NOT NULL,
      local VARCHAR(255) NOT NULL,
      ordem INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS admin_users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      email VARCHAR(190) NOT NULL UNIQUE,
      senha_hash VARCHAR(255) NOT NULL,
      reset_token VARCHAR(64),
      reset_expira DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS eventos (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nome_evento VARCHAR(200) NOT NULL,
      data DATE NOT NULL,
      data_fim DATE NULL,
      horario VARCHAR(20) NULL,
      local VARCHAR(255) NOT NULL,
      cidade VARCHAR(150) NOT NULL,
      endereco VARCHAR(255) NULL,
      mapa_link VARCHAR(500) NULL,
      status VARCHAR(20) NOT NULL DEFAULT "a_confirmar",
      horario_saida_rv VARCHAR(20) NULL,
      horario_chegada VARCHAR(20) NULL,
      horario_som VARCHAR(20) NULL,
      horario_saida VARCHAR(20) NULL,
      transporte VARCHAR(255) NULL,
      hospedagem VARCHAR(255) NULL,
      equipamentos TEXT NULL,
      obs_banda TEXT NULL,
      cache_bruto DECIMAL(10,2) NULL,
      despesas DECIMAL(10,2) NULL,
      forma_pagamento VARCHAR(100) NULL,
      status_pagamento VARCHAR(20) NOT NULL DEFAULT "pendente",
      contratante_nome VARCHAR(150) NULL,
      contratante_telefone VARCHAR(30) NULL,
      contratante_email VARCHAR(150) NULL,
      obs_admin TEXT NULL,
      concluido_em TIMESTAMP NULL,
      ordem INT NOT NULL DEFAULT 0,
      criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS eventos_historico (
      id INT AUTO_INCREMENT PRIMARY KEY,
      evento_id INT NOT NULL,
      descricao VARCHAR(500) NOT NULL,
      autor_email VARCHAR(190) NULL,
      criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX (evento_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS notificacoes (
      id INT AUTO_INCREMENT PRIMARY KEY,
      evento_id INT NULL,
      tipo VARCHAR(20) NOT NULL DEFAULT "info",
      titulo VARCHAR(150) NOT NULL,
      mensagem VARCHAR(500) NULL,
      criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS push_subscriptions (
      id INT AUTO_INCREMENT PRIMARY KEY,
      usuario_id INT NOT NULL,
      endpoint VARCHAR(500) NOT NULL,
      p256dh VARCHAR(255) NOT NULL,
      auth VARCHAR(255) NOT NULL,
      user_agent VARCHAR(255) NULL,
      valida TINYINT(1) NOT NULL DEFAULT 1,
      criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      ultimo_uso TIMESTAMP NULL,
      UNIQUE KEY endpoint_unq (endpoint(255)),
      INDEX (usuario_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
  }

  // Migração aditiva: adiciona role/nome em admin_users sem quebrar contas existentes.
  if (!columnExists($pdo, $driver, 'admin_users', 'role')) {
    $pdo->exec("ALTER TABLE admin_users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'admin'");
  }
  if (!columnExists($pdo, $driver, 'admin_users', 'nome')) {
    $pdo->exec('ALTER TABLE admin_users ADD COLUMN nome VARCHAR(150)');
  }

  // Financeiro: "Cachê" vira "Cachê Bruto" (mesma coluna, renomeada, sem perder valores).
  if (columnExists($pdo, $driver, 'eventos', 'cache') && !columnExists($pdo, $driver, 'eventos', 'cache_bruto')) {
    if ($driver === 'sqlite') {
      $pdo->exec('ALTER TABLE eventos RENAME COLUMN cache TO cache_bruto');
    } else {
      $pdo->exec('ALTER TABLE eventos CHANGE cache cache_bruto DECIMAL(10,2) NULL');
    }
  }
  if (!columnExists($pdo, $driver, 'eventos', 'despesas')) {
    $pdo->exec($driver === 'sqlite'
      ? 'ALTER TABLE eventos ADD COLUMN despesas TEXT'
      : 'ALTER TABLE eventos ADD COLUMN despesas DECIMAL(10,2) NULL');
  }
  if (!columnExists($pdo, $driver, 'eventos', 'concluido_em')) {
    $pdo->exec($driver === 'sqlite'
      ? 'ALTER TABLE eventos ADD COLUMN concluido_em TEXT'
      : 'ALTER TABLE eventos ADD COLUMN concluido_em TIMESTAMP NULL');
  }
  if (!columnExists($pdo, $driver, 'eventos', 'horario_saida_rv')) {
    $pdo->exec($driver === 'sqlite'
      ? 'ALTER TABLE eventos ADD COLUMN horario_saida_rv TEXT'
      : 'ALTER TABLE eventos ADD COLUMN horario_saida_rv VARCHAR(20) NULL');
  }
  // "Data" segue sendo a data real (organização interna, ordenação,
  // previsão financeira). "Data no site" é texto livre, digitado pelo
  // gestor, e é o que aparece na agenda pública.
  if (!columnExists($pdo, $driver, 'eventos', 'data_site')) {
    $pdo->exec($driver === 'sqlite'
      ? 'ALTER TABLE eventos ADD COLUMN data_site TEXT'
      : 'ALTER TABLE eventos ADD COLUMN data_site VARCHAR(100) NULL');
  }
  if (!columnExists($pdo, $driver, 'eventos', 'pastor_presidente')) {
    $pdo->exec($driver === 'sqlite'
      ? 'ALTER TABLE eventos ADD COLUMN pastor_presidente TEXT'
      : 'ALTER TABLE eventos ADD COLUMN pastor_presidente VARCHAR(150) NULL');
  }
  // Cachê Líquido: o cliente pediu pra preencher manualmente (não calcular
  // automaticamente Bruto - Despesas), então vira uma coluna própria.
  if (!columnExists($pdo, $driver, 'eventos', 'cache_liquido')) {
    $pdo->exec($driver === 'sqlite'
      ? 'ALTER TABLE eventos ADD COLUMN cache_liquido TEXT'
      : 'ALTER TABLE eventos ADD COLUMN cache_liquido DECIMAL(10,2) NULL');
  }

  return $pdo;
}
