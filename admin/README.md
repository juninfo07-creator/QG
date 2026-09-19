# Painel administrativo (local)

Edita a agenda de datas que aparece no site, sem mexer em código. Só
funciona no computador onde for aberto — não é publicado na internet.

## Como usar

1. Abrir um terminal nesta pasta (`admin/`)
2. Na primeira vez, instalar as dependências: `npm install`
3. Rodar: `npm start`
4. Abrir no navegador: http://localhost:3456
5. Editar, reordenar ou remover datas e clicar em **Salvar agenda**

O painel grava direto em `../site/data/agenda.json`, que é o arquivo
que o site lê pra montar a seção "Próximas Agendas". Depois de salvar,
é só commitar e subir (`/salvar` ou `git push`) pra atualizar o site
publicado.
