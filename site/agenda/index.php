<?php
require __DIR__ . '/../admin/db.php';
require __DIR__ . '/../admin/permissoes.php';

if (usuarioAtual()) {
  header('Location: painel.php');
  exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pdo = getPDO();
  $email = trim($_POST['email'] ?? '');
  $senha = $_POST['senha'] ?? '';

  $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE email = ?');
  $stmt->execute([$email]);
  $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
    iniciarSessaoUsuario($usuario);
    header('Location: painel.php');
    exit;
  }
  $erro = 'E-mail ou senha incorretos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Agenda — Quarteto Gileade</title>
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" href="icon-192.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Agenda QG">
<meta name="theme-color" content="#080b14">
<style>
  :root {
    --navy: #080b14; --navy-2: #111827; --navy-3: #171f33;
    --gold: #d4af37; --cream: #f5f2ea; --muted: #a7adbd;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
    font-family: -apple-system, "Segoe UI", Inter, Arial, sans-serif;
    background: var(--navy); color: var(--cream); padding: 24px;
  }
  .card { width: 100%; max-width: 380px; }
  .logo { display: block; margin: 0 auto 28px; max-width: 200px; height: auto; }
  h1 { font-size: 20px; margin: 0 0 4px; text-align: center; }
  .subtitle { color: var(--muted); font-size: 14px; margin: 0 0 28px; text-align: center; }
  label { font-size: 13px; color: var(--muted); display: block; margin-bottom: 6px; }
  input[type=text], input[type=password] {
    width: 100%; background: var(--navy-2); border: 1px solid var(--navy-3); color: var(--cream);
    padding: 14px; border-radius: 10px; font-size: 16px; margin-bottom: 18px;
  }
  input:focus { outline: 1px solid var(--gold); }
  button {
    width: 100%; cursor: pointer; border: none; border-radius: 10px; font-size: 16px;
    padding: 15px; font-weight: 700; background: var(--gold); color: var(--navy);
  }
  .erro { color: #e07a7a; font-size: 13px; margin: 0 0 16px; text-align: center; }
  .esqueci { display: block; text-align: center; margin-top: 18px; color: var(--muted); font-size: 13px; text-decoration: none; }
  .esqueci:hover { color: var(--gold); }
</style>
</head>
<body>
  <div class="card">
    <img src="../assets/img/logo-branco.png" alt="Quarteto Gileade" class="logo">
    <h1>Área dos Integrantes</h1>
    <p class="subtitle">Entre pra ver a agenda de apresentações.</p>
    <?php if ($erro): ?><p class="erro"><?= htmlspecialchars($erro) ?></p><?php endif; ?>
    <form method="post">
      <label>E-mail</label>
      <input type="text" name="email" autocomplete="username" required>
      <label>Senha</label>
      <input type="password" name="senha" autocomplete="current-password" required>
      <button type="submit">Entrar</button>
    </form>
    <a class="esqueci" href="../admin/esqueci-senha.php">Esqueci minha senha</a>
  </div>
</body>
</html>
