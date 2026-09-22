<?php
require __DIR__ . '/../admin/db.php';
require __DIR__ . '/../admin/permissoes.php';
exigirLogin('index.php');
$id = (int) ($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detalhes do Evento — Quarteto Gileade</title>
<link rel="manifest" href="manifest.json">
<link rel="apple-touch-icon" href="icon-192.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Agenda QG">
<meta name="theme-color" content="#080b14">
<style>
  :root {
    --navy: #080b14; --navy-2: #111827; --navy-3: #171f33;
    --gold: #d4af37; --gold-light: #f0d78c; --cream: #f5f2ea; --muted: #a7adbd;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0; font-family: -apple-system, "Segoe UI", Inter, Arial, sans-serif;
    background: var(--navy); color: var(--cream); padding: 20px 16px 60px;
  }
  .voltar { color: var(--muted); font-size: 14px; text-decoration: none; display: inline-block; margin-bottom: 18px; }
  .voltar:hover { color: var(--gold); }
  .status-badge { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 100px; display: inline-block; margin-bottom: 12px; }
  .status-confirmado { background: #1c7a3c33; color: #7ee6a0; }
  .status-a_confirmar { background: #8a5a0033; color: #f0c26a; }
  .status-cancelado { background: #9c2b2b33; color: #f0a0a0; }
  h1 { font-size: 22px; margin: 0 0 6px; }
  .data-linha { color: var(--gold-light); font-size: 15px; margin: 0 0 20px; }

  .bloco { background: var(--navy-2); border: 1px solid var(--navy-3); border-radius: 14px; padding: 18px; margin-bottom: 14px; }
  .bloco h2 { font-size: 12px; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); margin: 0 0 12px; }
  .linha { display: flex; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--navy-3); font-size: 14px; }
  .linha:last-child { border-bottom: none; }
  .linha .rotulo { color: var(--muted); min-width: 130px; }
  .linha .valor { flex: 1; }

  .btn-mapa {
    display: block; text-align: center; background: var(--gold); color: var(--navy); font-weight: 700;
    padding: 14px; border-radius: 10px; text-decoration: none; margin-top: 6px;
  }
  .vazio { color: var(--muted); text-align: center; padding: 60px 0; }
</style>
</head>
<body>
  <a class="voltar" href="painel.php">← Voltar</a>
  <div id="conteudo">Carregando…</div>

<script>
var id = <?= json_encode($id) ?>;
function statusLabel(s) { return { confirmado: 'Confirmado', a_confirmar: 'A confirmar', cancelado: 'Cancelado' }[s] || s; }
function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
function escAttr(s) { return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;'); }

function linha(rotulo, valor) {
  if (!valor) return '';
  return '<div class="linha"><div class="rotulo">' + rotulo + '</div><div class="valor">' + esc(valor) + '</div></div>';
}

if (!id) {
  document.getElementById('conteudo').innerHTML = '<p class="vazio">Evento não encontrado.</p>';
} else {
  fetch('../api/eventos.php?id=' + id + '&_=' + Date.now())
    .then(function (res) { if (!res.ok) throw new Error(); return res.json(); })
    .then(function (e) {
      var html = '';
      html += '<span class="status-badge status-' + e.status + '">' + statusLabel(e.status) + '</span>';
      html += '<h1>' + esc(e.nome_evento) + '</h1>';
      var textoData = (e.data_site && e.data_site.trim()) ? e.data_site : (e.data + (e.data_fim ? ' a ' + e.data_fim : ''));
      html += '<p class="data-linha">' + esc(textoData) + (e.horario ? ' · ' + esc(e.horario) : '') + '</p>';

      html += '<div class="bloco"><h2>Local</h2>';
      html += linha('Local', e.local);
      html += linha('Cidade', e.cidade);
      html += linha('Endereço', e.endereco);
      html += linha('Pastor Presidente', e.pastor_presidente);
      html += '</div>';
      if (e.mapa_link && /^https?:\/\//i.test(e.mapa_link)) {
        html += '<a class="btn-mapa" href="' + escAttr(e.mapa_link) + '" target="_blank" rel="noopener">Abrir no mapa</a>';
      }

      var temInfoBanda = e.horario_saida_rv || e.horario_chegada || e.horario_som || e.horario_saida || e.transporte || e.hospedagem || e.equipamentos || e.obs_banda;
      if (temInfoBanda) {
        html += '<div class="bloco"><h2>Informações pra banda</h2>';
        html += linha('Horário de saída RV', e.horario_saida_rv);
        html += linha('Previsão de chegada', e.horario_chegada);
        html += linha('Passagem de som', e.horario_som);
        html += linha('Horário de saída', e.horario_saida);
        html += linha('Transporte', e.transporte);
        html += linha('Hospedagem', e.hospedagem);
        html += linha('Equipamentos', e.equipamentos);
        html += linha('Observações', e.obs_banda);
        html += '</div>';
      }

      document.getElementById('conteudo').innerHTML = html;
    })
    .catch(function () {
      document.getElementById('conteudo').innerHTML = '<p class="vazio">Não foi possível carregar esse evento.</p>';
    });
}
</script>
</body>
</html>
