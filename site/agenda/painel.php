<?php
require __DIR__ . '/../admin/db.php';
require __DIR__ . '/../admin/permissoes.php';
exigirLogin('index.php');
$usuario = usuarioAtual();
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
    --gold: #d4af37; --gold-light: #f0d78c; --cream: #f5f2ea; --muted: #a7adbd;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0; font-family: -apple-system, "Segoe UI", Inter, Arial, sans-serif;
    background: var(--navy); color: var(--cream); padding: 20px 16px 90px;
  }
  .topo { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; }
  .topo-logo { height: 34px; }
  .topo-acoes { display: flex; gap: 16px; align-items: center; }
  .topo-acoes a { color: var(--muted); font-size: 13px; text-decoration: none; }
  .topo-acoes a:hover { color: var(--gold); }
  .saudacao { font-size: 15px; color: var(--muted); margin: 0 0 20px; }
  .saudacao strong { color: var(--cream); }

  .eyebrow { font-size: 12px; letter-spacing: .08em; text-transform: uppercase; color: var(--gold); font-weight: 700; margin: 0 0 10px; }

  .destaque {
    background: linear-gradient(160deg, var(--navy-2), var(--navy-3));
    border: 1px solid var(--gold);
    border-radius: 16px;
    padding: 22px;
    margin-bottom: 28px;
  }
  .destaque .data { font-family: Georgia, serif; font-size: 30px; color: var(--gold-light); line-height: 1; }
  .destaque .nome { font-size: 19px; font-weight: 700; margin: 10px 0 6px; }
  .destaque .info { color: var(--muted); font-size: 14px; margin: 2px 0; }
  .destaque .btn {
    display: inline-block; margin-top: 16px; background: var(--gold); color: var(--navy);
    font-weight: 700; font-size: 14px; padding: 12px 20px; border-radius: 10px; text-decoration: none;
  }

  .sem-eventos { color: var(--muted); text-align: center; padding: 40px 0; }

  .lista-titulo { font-size: 13px; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin: 0 0 12px; }
  .evento-card {
    display: flex; gap: 14px; align-items: center; background: var(--navy-2); border: 1px solid var(--navy-3);
    border-radius: 12px; padding: 14px; margin-bottom: 10px; text-decoration: none; color: var(--cream);
  }
  .evento-data { text-align: center; min-width: 48px; }
  .evento-data .dia { font-size: 20px; font-weight: 800; line-height: 1; }
  .evento-data .mes { font-size: 11px; color: var(--muted); text-transform: uppercase; }
  .evento-corpo { flex: 1; min-width: 0; }
  .evento-nome { font-weight: 700; font-size: 15px; margin: 0 0 3px; }
  .evento-local { font-size: 13px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .status-badge { font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 100px; white-space: nowrap; }
  .status-confirmado { background: #1c7a3c33; color: #7ee6a0; }
  .status-a_confirmar { background: #8a5a0033; color: #f0c26a; }
  .status-cancelado { background: #9c2b2b33; color: #f0a0a0; }

  .previsao {
    background: var(--navy-2); border: 1px solid var(--navy-3); border-radius: 14px;
    padding: 18px; margin-top: 28px;
  }
  .previsao h2 { font-size: 12px; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); margin: 0 0 12px; }
  .previsao select, .previsao input[type=date] {
    background: var(--navy); border: 1px solid var(--navy-3); color: var(--cream);
    padding: 8px 10px; border-radius: 8px; font-size: 13px; margin-right: 8px; margin-bottom: 12px;
  }
  .previsao-numeros { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
  .previsao-numeros div { background: var(--navy); border-radius: 8px; padding: 10px; }
  .previsao-numeros label { display: block; font-size: 11px; color: var(--muted); margin-bottom: 4px; }
  .previsao-numeros strong { font-size: 15px; }
  .valor-bruto { color: #7ec8f5; }
  .valor-despesas { color: #f0a0a0; }
  .valor-liquido { color: #7ee6a0; }
</style>
</head>
<body>
  <div class="topo">
    <img src="../assets/img/logo-branco.png" alt="Quarteto Gileade" class="topo-logo">
    <div class="topo-acoes">
      <a href="notificacoes.php">🔔 Avisos</a>
      <a href="logout.php">Sair</a>
    </div>
  </div>
  <p class="saudacao">Olá, <strong id="nomeUsuario"><?= htmlspecialchars($usuario['nome']) ?></strong></p>

  <div id="conteudo">Carregando agenda…</div>

  <div class="previsao">
    <h2>Previsão financeira</h2>
    <div>
      <select id="previsaoPeriodo">
        <option value="mes_atual">Este mês</option>
        <option value="proximo_mes">Próximo mês</option>
        <option value="ano_atual">Este ano</option>
        <option value="personalizado">Personalizado</option>
      </select>
      <input type="date" id="previsaoInicio" style="display:none;">
      <input type="date" id="previsaoFim" style="display:none;">
    </div>
    <div class="previsao-numeros" style="grid-template-columns: 1fr;">
      <div><label>Cachê Líquido</label><strong id="previsaoLiquido" class="valor-liquido">R$ 0,00</strong></div>
    </div>
  </div>

<script>
var MESES = ['JAN','FEV','MAR','ABR','MAI','JUN','JUL','AGO','SET','OUT','NOV','DEZ'];

function statusLabel(s) {
  return { confirmado: 'Confirmado', a_confirmar: 'A confirmar', cancelado: 'Cancelado' }[s] || s;
}

function partesData(d) {
  var p = (d || '').split('-');
  if (p.length !== 3) return null;
  return { dia: p[2], mes: MESES[parseInt(p[1], 10) - 1] || '', ano: p[0] };
}

function hojeISO() {
  var d = new Date();
  return d.toISOString().slice(0, 10);
}

function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

fetch('../api/eventos.php?_=' + Date.now())
  .then(function (res) { return res.json(); })
  .then(function (eventos) {
    var hoje = hojeISO();
    var futuros = eventos.filter(function (e) { return e.data >= hoje; }).sort(function (a, b) { return a.data.localeCompare(b.data); });
    var conteudo = document.getElementById('conteudo');

    if (!futuros.length) {
      conteudo.innerHTML = '<p class="sem-eventos">Nenhum evento agendado no momento.</p>';
      return;
    }

    var destaque = futuros[0];
    var resto = futuros.slice(1);
    // "Data" (interna) só serve pra ordenar/filtrar o que é próximo evento —
    // o que aparece pro integrante é sempre "Data no site" (data_site).
    var pd = partesData(destaque.data);
    var textoDestaque = (destaque.data_site && destaque.data_site.trim()) ? destaque.data_site : (pd ? pd.dia + ' ' + pd.mes : destaque.data);

    var html = '';
    html += '<p class="eyebrow">Próximo evento</p>';
    html += '<div class="destaque">';
    html += '<div class="data">' + esc(textoDestaque) + '</div>';
    html += '<div class="nome">' + esc(destaque.nome_evento) + '</div>';
    if (destaque.horario) html += '<div class="info">🕒 ' + esc(destaque.horario) + '</div>';
    html += '<div class="info">📍 ' + esc(destaque.local || '') + (destaque.cidade ? ' — ' + esc(destaque.cidade) : '') + '</div>';
    html += '<div class="info"><span class="status-badge status-' + destaque.status + '">' + statusLabel(destaque.status) + '</span></div>';
    html += '<a class="btn" href="evento.php?id=' + destaque.id + '">Ver detalhes</a>';
    html += '</div>';

    if (resto.length) {
      html += '<p class="lista-titulo">Próximos eventos</p>';
      resto.forEach(function (e) {
        var d = partesData(e.data);
        var textoResto = (e.data_site && e.data_site.trim()) ? e.data_site : null;
        html += '<a class="evento-card" href="evento.php?id=' + e.id + '">';
        if (textoResto) {
          html += '<div class="evento-data"><div class="dia" style="font-size:13px;">' + esc(textoResto) + '</div></div>';
        } else {
          html += '<div class="evento-data"><div class="dia">' + (d ? d.dia : '') + '</div><div class="mes">' + (d ? d.mes : '') + '</div></div>';
        }
        html += '<div class="evento-corpo"><div class="evento-nome">' + esc(e.nome_evento) + '</div><div class="evento-local">' + esc(e.local || '') + (e.cidade ? ' — ' + esc(e.cidade) : '') + '</div></div>';
        html += '<span class="status-badge status-' + e.status + '">' + statusLabel(e.status) + '</span>';
        html += '</a>';
      });
    }

    conteudo.innerHTML = html;
  })
  .catch(function () {
    document.getElementById('conteudo').innerHTML = '<p class="sem-eventos">Não foi possível carregar a agenda.</p>';
  });

function moeda(v) {
  v = Number(v || 0);
  return 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function carregarPrevisao() {
  var periodo = document.getElementById('previsaoPeriodo').value;
  var url = '../api/previsao.php?periodo=' + periodo + '&_=' + Date.now();
  if (periodo === 'personalizado') {
    var inicio = document.getElementById('previsaoInicio').value;
    var fim = document.getElementById('previsaoFim').value;
    if (!inicio || !fim) return;
    url += '&inicio=' + inicio + '&fim=' + fim;
  }
  fetch(url)
    .then(function (res) { return res.json(); })
    .then(function (r) {
      document.getElementById('previsaoLiquido').textContent = moeda(r.liquido);
    });
}

document.getElementById('previsaoPeriodo').addEventListener('change', function () {
  var personalizado = this.value === 'personalizado';
  document.getElementById('previsaoInicio').style.display = personalizado ? 'inline-block' : 'none';
  document.getElementById('previsaoFim').style.display = personalizado ? 'inline-block' : 'none';
  if (!personalizado) carregarPrevisao();
});
document.getElementById('previsaoInicio').addEventListener('change', carregarPrevisao);
document.getElementById('previsaoFim').addEventListener('change', carregarPrevisao);

carregarPrevisao();
</script>
</body>
</html>
