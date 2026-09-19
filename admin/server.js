const express = require('express');
const fs = require('fs/promises');
const path = require('path');

const PORT = 3456;
const HOST = '127.0.0.1';
const AGENDA_PATH = path.join(__dirname, '..', 'site', 'data', 'agenda.json');

const app = express();
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

function validarAgenda(body) {
  if (!Array.isArray(body)) return 'A agenda precisa ser uma lista de datas.';
  for (const item of body) {
    if (typeof item !== 'object' || item === null) return 'Cada data precisa ser um item válido.';
    if (typeof item.data !== 'string' || !item.data.trim()) return 'Toda data precisa ter o campo "data" preenchido.';
    if (typeof item.cidade !== 'string' || !item.cidade.trim()) return 'Toda data precisa ter o campo "cidade" preenchido.';
    if (typeof item.local !== 'string') return 'O campo "local" precisa ser texto (pode ficar em branco).';
  }
  return null;
}

app.get('/api/agenda', async (req, res) => {
  try {
    const conteudo = await fs.readFile(AGENDA_PATH, 'utf-8');
    res.type('json').send(conteudo);
  } catch (err) {
    res.status(500).json({ erro: 'Não foi possível ler a agenda: ' + err.message });
  }
});

app.put('/api/agenda', async (req, res) => {
  const erro = validarAgenda(req.body);
  if (erro) return res.status(400).json({ erro });

  const limpo = req.body.map((item) => ({
    data: item.data.trim(),
    cidade: item.cidade.trim(),
    local: item.local.trim(),
  }));

  try {
    await fs.writeFile(AGENDA_PATH, JSON.stringify(limpo, null, 2) + '\n', 'utf-8');
    res.json({ ok: true });
  } catch (err) {
    res.status(500).json({ erro: 'Não foi possível salvar a agenda: ' + err.message });
  }
});

app.listen(PORT, HOST, () => {
  console.log(`Painel administrativo rodando em http://${HOST}:${PORT}`);
  console.log('Esse endereço só funciona neste computador (não é acessível pela internet).');
});
