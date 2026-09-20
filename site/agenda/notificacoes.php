<?php
require __DIR__ . '/../admin/db.php';
require __DIR__ . '/../admin/permissoes.php';
exigirLogin('index.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Avisos — Quarteto Gileade</title>
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
    margin: 0; font-family: -apple-system, "Segoe UI", Inter, Arial, sans-serif;
    background: var(--navy); color: var(--cream); padding: 20px 16px 60px;
  }
  .voltar { color: var(--muted); font-size: 14px; text-decoration: none; display: inline-block; margin-bottom: 18px; }
  .voltar:hover { color: var(--gold); }
  h1 { font-size: 20px; margin: 0 0 20px; }
  .item {
    background: var(--navy-2); border: 1px solid var(--navy-3); border-left: 4px solid var(--gold);
    border-radius: 10px; padding: 14px; margin-bottom: 10px;
  }
  .item.cancelado { border-left-color: #c94f4f; }
  .item.novo { border-left-color: #3f9e5c; }
  .titulo { font-weight: 700; font-size: 14px; margin: 0 0 4px; }
  .msg { font-size: 13px; color: var(--muted); margin: 0 0 6px; }
  .quando { font-size: 11px; color: var(--muted); }
  .vazio { color: var(--muted); text-align: center; padding: 60px 0; }

  .push-card {
    background: var(--navy-2); border: 1px solid var(--navy-3); border-radius: 12px;
    padding: 16px; margin-bottom: 22px;
  }
  .push-card h2 { font-size: 15px; margin: 0 0 6px; }
  .push-card p { font-size: 13px; color: var(--muted); margin: 0 0 12px; line-height: 1.5; }
  .push-card button {
    cursor: pointer; border: none; border-radius: 8px; font-size: 14px; font-weight: 700;
    padding: 11px 18px; background: var(--gold); color: var(--navy);
  }
  .push-status-ok { color: #7ee6a0; font-size: 13px; font-weight: 600; }
  .push-status-erro { color: #f0a0a0; font-size: 13px; }
</style>
</head>
<body>
  <a class="voltar" href="painel.php">← Voltar</a>
  <h1>Avisos</h1>

  <div class="push-card" id="pushCard"></div>

  <div id="lista">Carregando…</div>

<script src="push.js?v=1"></script>
<script>
var pushCard = document.getElementById('pushCard');

function renderPushCard(status) {
  if (status === 'unsupported') {
    pushCard.innerHTML = '<p style="margin:0;">Seu navegador não é compatível com notificações no momento — você continua vendo todos os avisos aqui na tela.</p>';
    return;
  }
  if (status === 'precisa-instalar') {
    pushCard.innerHTML = '<h2>Receba avisos da agenda</h2><p>Para receber notificações no iPhone, primeiro adicione este site à Tela de Início (toque em compartilhar → "Adicionar à Tela de Início"), depois volte aqui.</p>';
    return;
  }
  if (status === 'denied') {
    pushCard.innerHTML = '<h2>Notificações bloqueadas</h2><p>Você bloqueou as notificações pra esse site. Pra ativar, habilite nas configurações de notificação do navegador/celular.</p>';
    return;
  }
  if (status === 'granted-com-inscricao') {
    pushCard.innerHTML = '<h2>Receba avisos da agenda</h2><p class="push-status-ok">✓ Notificações ativadas neste aparelho.</p>';
    return;
  }
  pushCard.innerHTML = '<h2>Receba avisos da agenda</h2><p>Ative as notificações pra ser avisado quando houver novos eventos, alterações ou cancelamentos.</p><button id="btnAtivarPush">Ativar notificações</button><p id="pushErro" class="push-status-erro"></p>';
  var btn = document.getElementById('btnAtivarPush');
  if (btn) {
    btn.addEventListener('click', function () {
      btn.disabled = true;
      btn.textContent = 'Ativando...';
      ativarNotificacoes()
        .then(function () { renderPushCard('granted-com-inscricao'); })
        .catch(function (e) {
          btn.disabled = false;
          btn.textContent = 'Ativar notificações';
          var msg = e && e.message === 'permissao-negada'
            ? 'Você não permitiu as notificações. Pode tentar de novo quando quiser.'
            : 'Não foi possível ativar agora. Tente novamente em instantes.';
          document.getElementById('pushErro').textContent = msg;
        });
    });
  }
}

if (typeof suportaPush === 'function') {
  statusNotificacoes().then(function (status) {
    if (status !== 'granted') { renderPushCard(status); return; }
    registrarServiceWorker()
      .then(function (reg) { return reg.pushManager.getSubscription(); })
      .then(function (sub) { renderPushCard(sub ? 'granted-com-inscricao' : 'default'); });
  });
}

var ICONES = { novo: '🔔', alterado: '✏️', cancelado: '⚠️', removido: '🗑️', info: '🔔' };
function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

fetch('../api/notificacoes.php?_=' + Date.now())
  .then(function (res) { return res.json(); })
  .then(function (itens) {
    var lista = document.getElementById('lista');
    if (!itens.length) { lista.innerHTML = '<p class="vazio">Nenhum aviso por enquanto.</p>'; return; }
    lista.innerHTML = itens.map(function (n) {
      return '<div class="item ' + n.tipo + '">' +
        '<div class="titulo">' + (ICONES[n.tipo] || '🔔') + ' ' + esc(n.titulo) + '</div>' +
        (n.mensagem ? '<div class="msg">' + esc(n.mensagem) + '</div>' : '') +
        '<div class="quando">' + esc(n.criado_em) + '</div>' +
        '</div>';
    }).join('');
  })
  .catch(function () {
    document.getElementById('lista').innerHTML = '<p class="vazio">Não foi possível carregar os avisos.</p>';
  });
</script>
</body>
</html>
