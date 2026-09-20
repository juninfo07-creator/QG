<?php
require __DIR__ . '/db.php';
require __DIR__ . '/mailer.php';

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pdo = getPDO();
  $email = trim($_POST['email'] ?? '');

  $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE email = ?');
  $stmt->execute([$email]);
  $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($usuario) {
    $token = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', time() + 3600);
    $upd = $pdo->prepare('UPDATE admin_users SET reset_token = ?, reset_expira = ? WHERE id = ?');
    $upd->execute([$token, $expira, $usuario['id']]);

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseDir = rtrim(str_replace('esqueci-senha.php', '', $_SERVER['PHP_SELF']), '/');
    $link = "{$scheme}://{$host}{$baseDir}/redefinir-senha.php?token={$token}";

    $corpo = "Olá,\n\nRecebemos um pedido para redefinir a senha do painel administrativo do Quarteto Gileade.\n\nPara criar uma nova senha, acesse o link abaixo (válido por 1 hora):\n{$link}\n\nSe você não pediu isso, pode ignorar este e-mail.";
    enviarEmail($usuario['email'], 'Redefinir senha — Painel Quarteto Gileade', $corpo);
  }

  // Mensagem genérica sempre, pra não revelar se o e-mail existe ou não.
  $mensagem = 'Se esse e-mail estiver cadastrado, enviamos um link de redefinição de senha para ele.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Esqueci minha senha — Painel Quarteto Gileade</title>
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
  input[type=text] {
    width: 100%; background: var(--navy); border: 1px solid var(--navy-3); color: var(--cream);
    padding: 10px 12px; border-radius: 6px; font-size: 14px; margin-bottom: 16px;
  }
  input:focus { outline: 1px solid var(--gold); }
  button {
    width: 100%; cursor: pointer; border: none; border-radius: 6px; font-size: 14px;
    padding: 11px; font-weight: 600; background: var(--gold); color: var(--navy);
  }
  .aviso { color: var(--gold); font-size: 13px; margin: 0 0 16px; line-height: 1.5; }
  .voltar { display: block; text-align: center; margin-top: 16px; color: var(--muted); font-size: 13px; text-decoration: none; }
  .voltar:hover { color: var(--gold); }
</style>
</head>
<body>
  <div class="card">
    <h1>Esqueci minha senha</h1>
    <p class="subtitle">Informe o e-mail cadastrado no painel.</p>
    <?php if ($mensagem): ?>
      <p class="aviso"><?= htmlspecialchars($mensagem) ?></p>
    <?php else: ?>
      <form method="post">
        <label>E-mail</label>
        <input type="text" name="email" required>
        <button type="submit">Enviar link de redefinição</button>
      </form>
    <?php endif; ?>
    <a class="voltar" href="index.php">Voltar para o login</a>
  </div>
</body>
</html>
