<?php
require __DIR__ . '/db.php';
require __DIR__ . '/permissoes.php';
exigirAdmin();

$pdo = getPDO();
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $acao = $_POST['acao'] ?? '';

  if ($acao === 'criar') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    if ($nome === '' || $email === '' || strlen($senha) < 8) {
      $erro = 'Preencha nome, e-mail e uma senha com pelo menos 8 caracteres.';
    } else {
      try {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO admin_users (email, senha_hash, role, nome) VALUES (?, ?, ?, ?)');
        $stmt->execute([$email, $hash, 'integrante', $nome]);
        $sucesso = 'Integrante ' . $nome . ' cadastrado.';
      } catch (PDOException $e) {
        $erro = 'Não foi possível cadastrar (talvez esse e-mail já exista).';
      }
    }
  }

  if ($acao === 'remover') {
    $id = (int) ($_POST['id'] ?? 0);
    $pdo->prepare("DELETE FROM admin_users WHERE id = ? AND role = 'integrante'")->execute([$id]);
    $sucesso = 'Integrante removido.';
  }
}

$usuarios = $pdo->query("SELECT id, nome, email, role FROM admin_users ORDER BY role DESC, nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Integrantes — Painel Quarteto Gileade</title>
<style>
  :root {
    --bg: #f5f2ea; --card-a: #171a2b; --card-b: #ffffff; --border: #ddd6c2;
    --navy: #171a2b; --gold: #b8902a; --gold-bg: #d4af37; --muted: #6b6a63; --danger: #b83c3c;
  }
  * { box-sizing: border-box; }
  body { margin: 0; font-family: -apple-system, "Segoe UI", Inter, Arial, sans-serif; background: var(--bg); color: var(--navy); padding: 32px 20px 80px; }
  .wrap { max-width: 640px; margin: 0 auto; }
  .voltar { color: var(--muted); font-size: 13px; text-decoration: none; }
  .voltar:hover { color: var(--gold); }
  h1 { font-size: 22px; margin: 12px 0 24px; }
  .card { background: #fff; border: 1px solid var(--border); border-radius: 10px; padding: 18px; margin-bottom: 16px; }
  label { font-size: 12px; color: var(--muted); display: block; margin-bottom: 4px; }
  input[type=text], input[type=email], input[type=password] {
    width: 100%; background: #fff; border: 1px solid var(--border); color: var(--navy);
    padding: 9px 10px; border-radius: 6px; font-size: 14px; margin-bottom: 12px;
  }
  .row { display: flex; gap: 10px; }
  .row > div { flex: 1; }
  button {
    cursor: pointer; border: none; border-radius: 6px; font-size: 13px; padding: 10px 16px; font-weight: 600;
  }
  .btn-add { background: var(--gold-bg); color: var(--navy); }
  .btn-remove { background: transparent; color: var(--danger); border: 1px solid var(--danger); padding: 6px 12px; font-size: 12px; }
  .lista-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border); }
  .lista-item:last-child { border-bottom: none; }
  .tag { font-size: 11px; padding: 2px 8px; border-radius: 100px; font-weight: 600; }
  .tag-admin { background: #eee0c2; color: #7a5c10; }
  .tag-integrante { background: #dfe7f5; color: #2d4f8f; }
  .msg-ok { color: #3f9e5c; font-size: 13px; margin-bottom: 14px; }
  .msg-erro { color: var(--danger); font-size: 13px; margin-bottom: 14px; }
</style>
</head>
<body>
<div class="wrap">
  <a class="voltar" href="painel.php">← Voltar pra agenda</a>
  <h1>Integrantes com acesso</h1>

  <?php if ($sucesso): ?><p class="msg-ok"><?= htmlspecialchars($sucesso) ?></p><?php endif; ?>
  <?php if ($erro): ?><p class="msg-erro"><?= htmlspecialchars($erro) ?></p><?php endif; ?>

  <div class="card">
    <form method="post">
      <input type="hidden" name="acao" value="criar">
      <label>Nome</label>
      <input type="text" name="nome" required>
      <label>E-mail</label>
      <input type="email" name="email" required>
      <label>Senha provisória</label>
      <input type="password" name="senha" minlength="8" required>
      <button class="btn-add" type="submit">Cadastrar integrante</button>
    </form>
  </div>

  <div class="card">
    <?php foreach ($usuarios as $u): ?>
      <div class="lista-item">
        <div>
          <strong><?= htmlspecialchars($u['nome'] ?: $u['email']) ?></strong>
          <span class="tag <?= $u['role'] === 'admin' ? 'tag-admin' : 'tag-integrante' ?>"><?= $u['role'] === 'admin' ? 'Gestor' : 'Integrante' ?></span>
          <div style="color:var(--muted);font-size:13px;"><?= htmlspecialchars($u['email']) ?></div>
        </div>
        <?php if ($u['role'] !== 'admin'): ?>
          <form method="post" onsubmit="return confirm('Remover o acesso de <?= htmlspecialchars(addslashes($u['nome'] ?: $u['email'])) ?>?');">
            <input type="hidden" name="acao" value="remover">
            <input type="hidden" name="id" value="<?= $u['id'] ?>">
            <button class="btn-remove" type="submit">Remover</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>
