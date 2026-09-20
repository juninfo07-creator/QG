<?php
require __DIR__ . '/db.php';

$pdo = getPDO();
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$erro = '';
$sucesso = false;

$stmt = $pdo->prepare('SELECT * FROM admin_users WHERE reset_token = ?');
$stmt->execute([$token]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

$tokenValido = $usuario && $usuario['reset_expira'] && strtotime($usuario['reset_expira']) > time();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!$tokenValido) {
    $erro = 'Este link expirou ou já foi usado. Peça um novo em "Esqueci minha senha".';
  } else {
    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';
    if (strlen($senha) < 8) {
      $erro = 'A senha precisa ter pelo menos 8 caracteres.';
    } elseif ($senha !== $confirmar) {
      $erro = 'As senhas não são iguais.';
    } else {
      $hash = password_hash($senha, PASSWORD_DEFAULT);
      $upd = $pdo->prepare('UPDATE admin_users SET senha_hash = ?, reset_token = NULL, reset_expira = NULL WHERE id = ?');
      $upd->execute([$hash, $usuario['id']]);
      $sucesso = true;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Redefinir senha — Painel Quarteto Gileade</title>
<style>
  :root {
    --navy: #080b14; --navy-2: #111827; --navy-3: #171f33;
    --gold: #d4af37; --cream: #f5f2ea; --muted: #a7adbd;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
    font-family: -apple-system, "Segoe UI", Inter, Arial, sans-serif;
    background: var(--navy); color: var(--cream); padding: 20px;
  }
  .card { width: 100%; max-width: 360px; background: var(--navy-2); border: 1px solid var(--navy-3); border-radius: 12px; padding: 32px; }
  h1 { font-size: 20px; margin: 0 0 4px; }
  .subtitle { color: var(--muted); font-size: 13px; margin: 0 0 24px; }
  label { font-size: 12px; color: var(--muted); display: block; margin-bottom: 4px; }
  input[type=password] {
    width: 100%; background: var(--navy); border: 1px solid var(--navy-3); color: var(--cream);
    padding: 10px 12px; border-radius: 6px; font-size: 14px; margin-bottom: 16px;
  }
  input:focus { outline: 1px solid var(--gold); }
  button {
    width: 100%; cursor: pointer; border: none; border-radius: 6px; font-size: 14px;
    padding: 11px; font-weight: 600; background: var(--gold); color: var(--navy);
  }
  .erro { color: #e07a7a; font-size: 13px; margin: 0 0 16px; }
  .sucesso { color: #8fd19e; font-size: 13px; margin: 0 0 16px; }
  .voltar { display: block; text-align: center; margin-top: 16px; color: var(--muted); font-size: 13px; text-decoration: none; }
  .voltar:hover { color: var(--gold); }
</style>
</head>
<body>
  <div class="card">
    <h1>Redefinir senha</h1>
    <?php if ($sucesso): ?>
      <p class="sucesso">Senha alterada com sucesso.</p>
      <a class="voltar" href="index.php">Ir para o login</a>
    <?php elseif (!$tokenValido): ?>
      <p class="erro">Este link é inválido ou expirou.</p>
      <a class="voltar" href="esqueci-senha.php">Pedir um novo link</a>
    <?php else: ?>
      <p class="subtitle">Crie uma nova senha para <?= htmlspecialchars($usuario['email']) ?>.</p>
      <?php if ($erro): ?><p class="erro"><?= htmlspecialchars($erro) ?></p><?php endif; ?>
      <form method="post">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <label>Nova senha</label>
        <input type="password" name="senha" autocomplete="new-password" required>
        <label>Confirmar nova senha</label>
        <input type="password" name="confirmar" autocomplete="new-password" required>
        <button type="submit">Salvar nova senha</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
