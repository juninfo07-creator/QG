<?php
// Gera um novo par de chaves VAPID (necessário pro Web Push).
// Rode uma vez: php admin/vapid_generate.php
// Copie o resultado pra dentro de config.php (bloco 'vapid').
// Já existe um par configurado neste projeto — só rode de novo se
// quiser trocar as chaves (isso invalida as inscrições já feitas).

if (php_sapi_name() !== 'cli') {
  http_response_code(403);
  exit('Acesso negado. Rode este script via linha de comando: php admin/vapid_generate.php');
}

require __DIR__ . '/vendor/autoload.php';

$keys = Minishlink\WebPush\VAPID::createVapidKeys();

echo "'vapid' => [\n";
echo "  'subject' => 'mailto:seu-email@exemplo.com',\n";
echo "  'public_key' => '" . $keys['publicKey'] . "',\n";
echo "  'private_key' => '" . $keys['privateKey'] . "',\n";
echo "],\n";
