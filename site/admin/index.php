<?php
require __DIR__ . '/db.php';
require __DIR__ . '/permissoes.php';

if (usuarioAtual()) {
  header('Location: ' . (ehAdmin() ? 'painel.php' : '../agenda/painel.php'));
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
    header('Location: ' . ($usuario['role'] === 'admin' ? 'painel.php' : '../agenda/painel.php'));
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
<title>Login — Painel Quarteto Gileade</title>
<style>
  :root {
    --navy: #080b14;
    --navy-2: #111827;
    --navy-3: #171f33;
    --gold: #d4af37;
    --gold-light: #f0d78c;
    --cream: #f5f2ea;
    --muted: #a7adbd;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: -apple-system, "Segoe UI", Inter, Arial, sans-serif;
    background: var(--navy);
    color: var(--cream);
    padding: 20px;
  }
  .card {
    width: 100%;
    max-width: 360px;
    background: var(--navy-2);
    border: 1px solid var(--navy-3);
    border-radius: 12px;
    padding: 32px;
  }
  h1 { font-size: 20px; margin: 0 0 4px; }
  .subtitle { color: var(--muted); font-size: 13px; margin: 0 0 24px; }
  label { font-size: 12px; color: var(--muted); display: block; margin-bottom: 4px; }
  input[type=text], input[type=password] {
    width: 100%;
    background: var(--navy);
    border: 1px solid var(--navy-3);
    color: var(--cream);
    padding: 10px 12px;
    border-radius: 6px;
    font-size: 14px;
    margin-bottom: 16px;
  }
  input:focus { outline: 1px solid var(--gold); }
  button {
    width: 100%;
    cursor: pointer;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    padding: 11px;
    font-weight: 600;
    background: var(--gold);
    color: var(--navy);
  }
  .erro { color: #e07a7a; font-size: 13px; margin: 0 0 16px; }
  .esqueci { display: block; text-align: center; margin-top: 16px; color: var(--muted); font-size: 13px; text-decoration: none; }
  .esqueci:hover { color: var(--gold); }
</style>
</head>
<body>
  <div class="card">
    <h1>Painel — Quarteto Gileade</h1>
    <p class="subtitle">Entre para gerenciar a agenda.</p>
    <?php if ($erro): ?><p class="erro"><?= htmlspecialchars($erro) ?></p><?php endif; ?>
    <form method="post">
      <label>E-mail</label>
      <input type="text" name="email" autocomplete="username" required>
      <label>Senha</label>
      <input type="password" name="senha" autocomplete="current-password" required>
      <button type="submit">Entrar</button>
    </form>
    <a class="esqueci" href="esqueci-senha.php">Esqueci minha senha</a>
  </div>
</body>
</html>
