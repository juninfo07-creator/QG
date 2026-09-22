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
<title>Painel — Agenda do Quarteto Gileade</title>
<style>
  :root {
    --bg: #f5f2ea;
    --card-a: #171a2b;
    --card-b: #ffffff;
    --border: #ddd6c2;
    --navy: #171a2b;
    --gold: #b8902a;
    --gold-bg: #d4af37;
    --gold-light: #f0d78c;
    --muted: #6b6a63;
    --danger: #b83c3c;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0;
    font-family: -apple-system, "Segoe UI", Inter, Arial, sans-serif;
    background: var(--bg);
    color: var(--navy);
    padding: 32px 20px 100px;
  }
  .wrap { max-width: 760px; margin: 0 auto; }
  .logo-topo { display: block; margin: 0 auto 24px; max-width: 180px; height: auto; }
  .topo { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
  h1 { font-size: 22px; margin-bottom: 4px; }
  .subtitle { color: var(--muted); font-size: 14px; margin-bottom: 20px; }
  .links-topo { display: flex; gap: 14px; align-items: center; }
  .links-topo a { color: var(--muted); font-size: 13px; text-decoration: none; }
  .links-topo a:hover { color: var(--gold); }

  .card {
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 14px;
    background: var(--card-b);
  }
  .card.dark { background: var(--card-a); }
  .card.dark label { color: var(--gold-light); }
  .card.dark .subtexto { color: #b9bdd0; }
  .card.dark .cabecalho-evento h3 { color: #ffffff; }

  .cabecalho-evento { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; cursor: pointer; }
  .cabecalho-evento h3 { margin: 0 0 4px; font-size: 16px; }
  .subtexto { color: var(--muted); font-size: 13px; }
  .status-badge { font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 100px; white-space: nowrap; }
  .status-confirmado { background: #d9f2e0; color: #1c7a3c; }
  .status-a_confirmar { background: #fdecc8; color: #8a5a00; }
  .status-cancelado { background: #f8d7d7; color: #9c2b2b; }
  .status-concluido { background: #e0e0e0; color: #444; }

  .corpo-evento { display: none; margin-top: 16px; }
  .corpo-evento.aberto { display: block; }

  label { font-size: 12px; color: var(--muted); display: block; margin-bottom: 4px; margin-top: 10px; }
  input[type=text], input[type=date], input[type=time], input[type=number], input[type=email], input[type=tel], select, textarea {
    width: 100%;
    background: #fff;
    border: 1px solid var(--border);
    color: var(--navy);
    padding: 9px 10px;
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
  }
  textarea { resize: vertical; min-height: 60px; }
  .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 10px; }
  .grid3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0 10px; }

  details { margin-top: 14px; border-top: 1px solid var(--border); padding-top: 10px; }
  .card.dark details { border-top-color: #2c3150; }
  summary { cursor: pointer; font-weight: 600; font-size: 13px; color: var(--gold); }

  .acoes-evento { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px; }
  button {
    cursor: pointer; border: none; border-radius: 6px; font-size: 13px; padding: 9px 16px; font-weight: 600;
  }
  .btn-salvar { background: var(--gold-bg); color: var(--navy); }
  .btn-excluir { background: transparent; color: var(--danger); border: 1px solid var(--danger); }
  .btn-historico { background: transparent; color: var(--muted); border: 1px solid var(--border); }
  .card.dark .btn-historico { color: #b9bdd0; border-color: #2c3150; }

  .btn-add {
    width: 100%; background: #1c7a3c; color: #fff; border: 1px solid #1c7a3c;
    padding: 14px; margin: 8px 0 28px; font-size: 14px; font-weight: 700;
  }
  .btn-concluir { background: transparent; color: #1c7a3c; border: 1px solid #1c7a3c; }

  .valor-bruto { color: #1f6fb2; }
  .valor-despesas { color: #9c2b2b; }
  .valor-liquido { color: #1c7a3c; }
  .card.dark .valor-bruto { color: #7ec8f5; }
  .card.dark .valor-despesas { color: #f0a0a0; }
  .card.dark .valor-liquido { color: #7ee6a0; }

  .previsao {
    border: 1px solid var(--border); border-radius: 10px; padding: 16px; margin-bottom: 20px; background: #fff;
  }
  .previsao h2 { font-size: 14px; margin: 0 0 12px; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); }
  .previsao-linha { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 12px; }
  .previsao-linha select, .previsao-linha input[type=date] { width: auto; }
  .previsao-numeros { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
  .previsao-numeros div { background: var(--bg); border-radius: 8px; padding: 12px; }
  .previsao-numeros label { display: block; font-size: 12px; color: var(--muted); margin-bottom: 4px; }
  .previsao-numeros strong { font-size: 20px; }

  .divisor { border: none; border-top: 1px solid var(--border); margin: 24px 0; }

  .historico-lista { margin-top: 10px; font-size: 13px; color: var(--muted); display: none; }
  .historico-lista.aberto { display: block; }
  .historico-lista div { padding: 6px 0; border-bottom: 1px solid var(--border); }
  .card.dark .historico-lista div { border-bottom-color: #2c3150; }

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
  <img src="../assets/img/logo.png" alt="Quarteto Gileade" class="logo-topo">
  <div class="topo">
    <div>
      <h1>Painel administrativo - Quarteto Gileade</h1>
      <p class="subtitle">Cadastre e gerencie os eventos da banda.</p>
    </div>
    <div class="links-topo">
      <a href="arquivados.php">Arquivados</a>
      <a href="integrantes.php">Integrantes</a>
      <a href="#" id="btnPushTeste" title="Envia um push de teste pras suas próprias inscrições (ative em /agenda/ primeiro)">Testar notificação</a>
      <a href="logout.php">Sair</a>
    </div>
  </div>

  <div class="previsao">
    <h2>Previsão financeira</h2>
    <div class="previsao-linha">
      <select id="previsaoPeriodo">
        <option value="mes_atual">Este mês</option>
        <option value="proximo_mes">Próximo mês</option>
        <option value="ano_atual">Este ano</option>
        <option value="personalizado">Período personalizado</option>
      </select>
      <input type="date" id="previsaoInicio" style="display:none;">
      <input type="date" id="previsaoFim" style="display:none;">
    </div>
    <div class="previsao-numeros">
      <div><label>Cachê Bruto</label><strong id="previsaoBruto" class="valor-bruto">R$ 0,00</strong></div>
      <div><label>Despesas</label><strong id="previsaoDespesas" class="valor-despesas">R$ 0,00</strong></div>
      <div><label>Cachê Líquido</label><strong id="previsaoLiquido" class="valor-liquido">R$ 0,00</strong></div>
    </div>
  </div>

  <hr class="divisor">

  <button class="btn-add" id="btnAdd">+ Adicionar Evento</button>
  <div id="lista">Carregando...</div>
</div>

<div class="toast" id="toast"><span id="toastIcone"></span> <span id="toastMsg"></span></div>

<script>
var lista = document.getElementById('lista');
var toast = document.getElementById('toast');
var toastIcone = document.getElementById('toastIcone');
var toastMsg = document.getElementById('toastMsg');
var toastTimer = null;
var eventos = [];

function setStatus(msg, tipo) {
  var icones = { ok: '✓', err: '⚠' };
  toastIcone.textContent = icones[tipo] || '…';
  toastMsg.textContent = msg;
  toast.className = 'toast show' + (tipo ? ' ' + tipo : '');
  clearTimeout(toastTimer);
  if (tipo === 'ok' || tipo === 'err') {
    toastTimer = setTimeout(function () { toast.classList.remove('show'); }, 4000);
  }
}

function campo(label, name, tipo, valor, extra) {
  tipo = tipo || 'text';
  valor = valor == null ? '' : valor;
  extra = extra || '';
  // Sempre envolve label+campo num único elemento — assim, dentro de um
  // grid (2 ou 3 colunas), cada campo ocupa uma célula inteira em vez de
  // o label e o input caírem em células/linhas separadas.
  if (tipo === 'textarea') {
    return '<div class="campo-wrap"><label>' + label + '</label><textarea data-campo="' + name + '" ' + extra + '>' + esc(valor) + '</textarea></div>';
  }
  if (tipo === 'select') {
    return '<div class="campo-wrap"><label>' + label + '</label><select data-campo="' + name + '" ' + extra + '></select></div>';
  }
  return '<div class="campo-wrap"><label>' + label + '</label><input type="' + tipo + '" data-campo="' + name + '" value="' + escAttr(valor) + '" ' + extra + '></div>';
}

function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function escAttr(s) { return String(s).replace(/"/g, '&quot;'); }

function statusLabel(s) {
  return { confirmado: 'Confirmado', a_confirmar: 'A confirmar', cancelado: 'Cancelado', concluido: 'Concluído' }[s] || s;
}

function moeda(v) {
  v = Number(v || 0);
  return 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatarDataCurta(d) {
  if (!d) return '';
  var partes = d.split('-');
  if (partes.length !== 3) return d;
  return partes[2] + '/' + partes[1];
}

function dataParaBR(iso) {
  if (!iso) return '';
  var p = iso.split('-');
  return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : iso;
}

function renderEvento(ev, i) {
  var card = document.createElement('div');
  card.className = 'card ' + (i % 2 === 0 ? 'dark' : '');
  var novo = !ev.id;

  card.innerHTML =
    '<div class="cabecalho-evento" data-acao="toggle">' +
      '<div>' +
        '<h3>' + (esc(ev.nome_evento) || '(novo evento)') + '</h3>' +
        '<div class="subtexto">' + formatarDataCurta(ev.data) + (ev.cidade ? ' · ' + esc(ev.cidade) : '') + '</div>' +
      '</div>' +
      '<span class="status-badge status-' + (ev.status || 'a_confirmar') + '">' + statusLabel(ev.status || 'a_confirmar') + '</span>' +
    '</div>' +
    '<div class="corpo-evento ' + (novo ? 'aberto' : '') + '">' +
      '<div class="grid2">' +
        campo('Nome do evento', 'nome_evento', 'text', ev.nome_evento) +
        campo('Status', 'status', 'select', ev.status || 'a_confirmar') +
      '</div>' +
      '<div class="grid2">' +
        campo('Data (referência interna)', 'data', 'date', ev.data) +
        campo('Horário', 'horario', 'time', ev.horario) +
      '</div>' +
      campo('Data no site', 'data_site', 'text', ev.data_site || dataParaBR(ev.data), 'placeholder="Como a data deve aparecer no site (ex: 15-16 NOV)"') +
      '<div class="grid2">' +
        campo('Cidade (aparece no site)', 'cidade', 'text', ev.cidade) +
        campo('Pastor Presidente', 'pastor_presidente', 'text', ev.pastor_presidente) +
      '</div>' +
      campo('Local (aparece no site)', 'local', 'text', ev.local) +

      '<details>' +
        '<summary>Informações para os integrantes</summary>' +
        '<div class="grid2">' +
          campo('Horário de saída RV', 'horario_saida_rv', 'time', ev.horario_saida_rv) +
          campo('Previsão de chegada', 'horario_chegada', 'time', ev.horario_chegada) +
        '</div>' +
        campo('Transporte', 'transporte', 'text', ev.transporte) +
        campo('Hospedagem', 'hospedagem', 'text', ev.hospedagem) +
        campo('Observações', 'obs_banda', 'textarea', ev.obs_banda) +
      '</details>' +

      '<details>' +
        '<summary>Informações administrativas (só o gestor vê)</summary>' +
        '<div class="grid3">' +
          campo('Cachê Bruto (R$)', 'cache_bruto', 'number', ev.cache_bruto, 'step="0.01" min="0"') +
          campo('Despesas (R$)', 'despesas', 'number', ev.despesas, 'step="0.01" min="0"') +
          campo('Cachê Líquido (R$)', 'cache_liquido', 'number', ev.cache_liquido, 'step="0.01" min="0"') +
        '</div>' +
        campo('Status do pagamento', 'status_pagamento', 'select', ev.status_pagamento || 'pendente') +
        campo('Nome do contratante', 'contratante_nome', 'text', ev.contratante_nome) +
        '<div class="grid2">' +
          campo('Telefone do contratante', 'contratante_telefone', 'tel', ev.contratante_telefone) +
          campo('E-mail do contratante', 'contratante_email', 'email', ev.contratante_email) +
        '</div>' +
        campo('Observações administrativas', 'obs_admin', 'textarea', ev.obs_admin) +
      '</details>' +

      '<div class="acoes-evento">' +
        '<button class="btn-salvar" data-acao="salvar">Salvar</button>' +
        (novo || ev.status === 'concluido' ? '' : '<button class="btn-concluir" data-acao="concluir">Marcar como Concluído</button>') +
        (novo ? '' : '<button class="btn-excluir" data-acao="excluir">Excluir</button>') +
        (novo ? '' : '<button class="btn-historico" data-acao="historico">Ver histórico</button>') +
      '</div>' +
      (novo ? '' : '<div class="historico-lista" data-hist></div>') +
    '</div>';

  // preencher selects
  var selStatus = card.querySelector('[data-campo=status]');
  ['confirmado', 'a_confirmar', 'cancelado', 'concluido'].forEach(function (v) {
    var o = document.createElement('option'); o.value = v; o.textContent = statusLabel(v);
    if (v === (ev.status || 'a_confirmar')) o.selected = true;
    selStatus.appendChild(o);
  });
  var selPag = card.querySelector('[data-campo=status_pagamento]');
  if (selPag) {
    [['pendente', 'Pendente'], ['parcial', 'Parcial'], ['pago', 'Pago']].forEach(function (p) {
      var o = document.createElement('option'); o.value = p[0]; o.textContent = p[1];
      if (p[0] === (ev.status_pagamento || 'pendente')) o.selected = true;
      selPag.appendChild(o);
    });
  }

  card.querySelector('[data-acao=toggle]').addEventListener('click', function () {
    card.querySelector('.corpo-evento').classList.toggle('aberto');
  });

  card.querySelector('[data-acao=salvar]').addEventListener('click', function (e) {
    e.stopPropagation();
    var dados = {};
    card.querySelectorAll('[data-campo]').forEach(function (el) { dados[el.dataset.campo] = el.value; });
    var metodo = ev.id ? 'PUT' : 'POST';
    var url = '../api/eventos.php' + (ev.id ? '?id=' + ev.id : '');
    setStatus('Salvando...');
    fetch(url, { method: metodo, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(dados) })
      .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
      .then(function (r) {
        if (r.ok) { setStatus('Evento salvo.', 'ok'); carregar(); }
        else setStatus(r.data.erro || 'Erro ao salvar.', 'err');
      })
      .catch(function () { setStatus('Erro de conexão.', 'err'); });
  });

  var btnConcluir = card.querySelector('[data-acao=concluir]');
  if (btnConcluir) {
    btnConcluir.addEventListener('click', function (e) {
      e.stopPropagation();
      if (!confirm('Marcar "' + (ev.nome_evento || 'este evento') + '" como concluído? Ele sai da agenda ativa e vai pra Arquivados.')) return;
      var dados = {};
      card.querySelectorAll('[data-campo]').forEach(function (el) { dados[el.dataset.campo] = el.value; });
      dados.status = 'concluido';
      setStatus('Concluindo...');
      fetch('../api/eventos.php?id=' + ev.id, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(dados) })
        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
        .then(function (r) {
          if (r.ok) { setStatus('Evento concluído e movido pra Arquivados.', 'ok'); carregar(); }
          else setStatus(r.data.erro || 'Erro ao concluir.', 'err');
        })
        .catch(function () { setStatus('Erro de conexão.', 'err'); });
    });
  }

  var btnExcluir = card.querySelector('[data-acao=excluir]');
  if (btnExcluir) {
    btnExcluir.addEventListener('click', function (e) {
      e.stopPropagation();
      if (!confirm('Excluir "' + (ev.nome_evento || 'este evento') + '" da agenda? Essa ação não pode ser desfeita.')) return;
      setStatus('Excluindo...');
      fetch('../api/eventos.php?id=' + ev.id, { method: 'DELETE' })
        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
        .then(function (r) {
          if (r.ok) { setStatus('Evento excluído.', 'ok'); carregar(); }
          else setStatus(r.data.erro || 'Erro ao excluir.', 'err');
        })
        .catch(function () { setStatus('Erro de conexão.', 'err'); });
    });
  }

  var btnHist = card.querySelector('[data-acao=historico]');
  if (btnHist) {
    btnHist.addEventListener('click', function (e) {
      e.stopPropagation();
      var painel = card.querySelector('[data-hist]');
      if (painel.classList.contains('aberto')) { painel.classList.remove('aberto'); return; }
      fetch('../api/historico.php?evento_id=' + ev.id + '&_=' + Date.now())
        .then(function (res) { return res.json(); })
        .then(function (linhas) {
          painel.innerHTML = linhas.length
            ? linhas.map(function (l) { return '<div>' + l.criado_em + ' — ' + esc(l.descricao) + '</div>'; }).join('')
            : '<div>Sem histórico ainda.</div>';
          painel.classList.add('aberto');
        });
    });
  }

  return card;
}

function carregar() {
  fetch('../api/eventos.php?_=' + Date.now())
    .then(function (res) { return res.json(); })
    .then(function (data) {
      eventos = data;
      render();
    })
    .catch(function () { lista.innerHTML = '<p>Não foi possível carregar os eventos.</p>'; });
}

function render() {
  lista.innerHTML = '';
  if (!eventos.length) { lista.innerHTML = '<p style="color:var(--muted)">Nenhum evento cadastrado ainda.</p>'; return; }
  eventos.forEach(function (ev, i) { lista.appendChild(renderEvento(ev, i)); });
}

document.getElementById('btnAdd').addEventListener('click', function () {
  eventos.unshift({});
  render();
});

document.getElementById('btnPushTeste').addEventListener('click', function (e) {
  e.preventDefault();
  setStatus('Enviando notificação de teste...');
  fetch('../api/push-teste.php', { method: 'POST' })
    .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
    .then(function (r) {
      if (r.ok) setStatus('Notificação de teste enviada (' + r.data.dispositivos + ' dispositivo(s)).', 'ok');
      else setStatus(r.data.erro || 'Não foi possível enviar.', 'err');
    })
    .catch(function () { setStatus('Erro de conexão.', 'err'); });
});

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
      document.getElementById('previsaoBruto').textContent = moeda(r.bruto);
      document.getElementById('previsaoDespesas').textContent = moeda(r.despesas);
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
carregar();
</script>
</body>
</html>
