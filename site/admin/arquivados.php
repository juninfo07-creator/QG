<?php
require __DIR__ . '/db.php';
require __DIR__ . '/permissoes.php';
exigirAdmin();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Arquivados — Quarteto Gileade</title>
<style>
  :root {
    --bg: #f5f2ea; --card-a: #171a2b; --card-b: #ffffff; --border: #ddd6c2;
    --navy: #171a2b; --gold: #b8902a; --gold-bg: #d4af37; --muted: #6b6a63; --danger: #b83c3c;
  }
  * { box-sizing: border-box; }
  body { margin: 0; font-family: -apple-system, "Segoe UI", Inter, Arial, sans-serif; background: var(--bg); color: var(--navy); padding: 32px 20px 80px; }
  .wrap { max-width: 760px; margin: 0 auto; }
  .topo { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
  h1 { font-size: 22px; margin-bottom: 4px; }
  .subtitle { color: var(--muted); font-size: 14px; margin-bottom: 24px; }
  .voltar { color: var(--muted); font-size: 13px; text-decoration: none; }
  .voltar:hover { color: var(--gold); }

  .card { background: #fff; border: 1px solid var(--border); border-radius: 10px; padding: 16px; margin-bottom: 12px; }
  .card h3 { margin: 0 0 4px; font-size: 16px; }
  .subtexto { color: var(--muted); font-size: 13px; margin-bottom: 10px; }
  .financeiro { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 10px; }
  .financeiro div { background: var(--bg); border-radius: 8px; padding: 8px 10px; }
  .financeiro label { display: block; font-size: 11px; color: var(--muted); margin-bottom: 2px; }
  .financeiro strong { font-size: 14px; }
  .valor-bruto { color: #1f6fb2; }
  .valor-despesas { color: #9c2b2b; }
  .valor-liquido { color: #1c7a3c; }
  .concluido-em { font-size: 12px; color: var(--muted); }
  .btn-reabrir { background: transparent; color: var(--gold); border: 1px solid var(--gold); border-radius: 6px; font-size: 12px; padding: 6px 12px; cursor: pointer; font-weight: 600; }
  .vazio { color: var(--muted); text-align: center; padding: 60px 0; }

  .toast {
    position: fixed; left: 50%; top: 20px; transform: translate(-50%, -140%); z-index: 1000;
    display: flex; align-items: center; gap: 10px; max-width: calc(100vw - 40px);
    background: #ffffff; border: 1px solid var(--border); border-left: 4px solid var(--gold);
    color: var(--navy); padding: 14px 18px; border-radius: 8px; font-size: 14px; font-weight: 600;
    box-shadow: 0 10px 30px rgba(0,0,0,.15); transition: transform .25s ease;
  }
  .toast.show { transform: translate(-50%, 0); }
  .toast.ok { border-left-color: #3f9e5c; }
  .toast.err { border-left-color: var(--danger); }
</style>
</head>
<body>
<div class="wrap">
  <div class="topo">
    <div>
      <h1>Arquivados</h1>
      <p class="subtitle">Eventos já concluídos — continuam salvos, fora da agenda ativa.</p>
    </div>
    <a class="voltar" href="painel.php">← Voltar pra agenda</a>
  </div>

  <div id="lista">Carregando...</div>
</div>

<div class="toast" id="toast"><span id="toastIcone"></span> <span id="toastMsg"></span></div>

<script>
function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
function moeda(v) {
  v = Number(v || 0);
  return 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function formatarData(d) {
  if (!d) return '';
  var p = d.split('-');
  return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : d;
}
function formatarDataHora(dt) {
  if (!dt) return '';
  return dt.split(' ')[0].split('-').reverse().join('/');
}

var toast = document.getElementById('toast');
function setStatus(msg, tipo) {
  document.getElementById('toastIcone').textContent = tipo === 'ok' ? '✓' : tipo === 'err' ? '⚠' : '…';
  document.getElementById('toastMsg').textContent = msg;
  toast.className = 'toast show' + (tipo ? ' ' + tipo : '');
  if (tipo) setTimeout(function () { toast.classList.remove('show'); }, 4000);
}

function carregar() {
  fetch('../api/eventos.php?arquivados=1&_=' + Date.now())
    .then(function (res) { return res.json(); })
    .then(function (eventos) {
      var lista = document.getElementById('lista');
      if (!eventos.length) { lista.innerHTML = '<p class="vazio">Nenhum evento concluído ainda.</p>'; return; }
      lista.innerHTML = '';
      eventos.forEach(function (ev) {
        var card = document.createElement('div');
        card.className = 'card';
        card.innerHTML =
          '<h3>' + esc(ev.nome_evento) + '</h3>' +
          '<div class="subtexto">' + formatarData(ev.data) + (ev.cidade ? ' · ' + esc(ev.cidade) : '') + (ev.local ? ' · ' + esc(ev.local) : '') + '</div>' +
          '<div class="financeiro">' +
            '<div><label>Cachê Bruto</label><strong class="valor-bruto">' + moeda(ev.cache_bruto) + '</strong></div>' +
            '<div><label>Despesas</label><strong class="valor-despesas">' + moeda(ev.despesas) + '</strong></div>' +
            '<div><label>Cachê Líquido</label><strong class="valor-liquido">' + moeda(ev.cache_liquido) + '</strong></div>' +
          '</div>' +
          '<div class="concluido-em">Concluído em ' + formatarDataHora(ev.concluido_em) + '</div>' +
          '<div style="margin-top:10px;"><button class="btn-reabrir" data-id="' + ev.id + '">Reabrir evento</button></div>';

        card.querySelector('.btn-reabrir').addEventListener('click', function () {
          if (!confirm('Reabrir "' + ev.nome_evento + '"? Ele volta pra agenda como "A confirmar".')) return;
          setStatus('Reabrindo...');
          var payload = Object.assign({}, ev, { status: 'a_confirmar' });
          fetch('../api/eventos.php?id=' + ev.id, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
          })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (r) {
              if (r.ok) { setStatus('Evento reaberto.', 'ok'); carregar(); }
              else setStatus(r.data.erro || 'Erro ao reabrir.', 'err');
            })
            .catch(function () { setStatus('Erro de conexão.', 'err'); });
        });

        lista.appendChild(card);
      });
    })
    .catch(function () {
      document.getElementById('lista').innerHTML = '<p class="vazio">Não foi possível carregar os arquivados.</p>';
    });
}

carregar();
</script>
</body>
</html>
