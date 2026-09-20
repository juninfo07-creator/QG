<?php
require_once __DIR__ . '/vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

function getWebPush() {
  $config = getConfig();
  $vapid = $config['vapid'];
  return new WebPush([
    'VAPID' => [
      'subject' => $vapid['subject'],
      'publicKey' => $vapid['public_key'],
      'privateKey' => $vapid['private_key'],
    ],
  ]);
}

// Envia uma notificação push pra uma lista de usuários (por id).
// $titulo / $mensagem vão no corpo da notificação; $url é pra onde o clique leva.
// Nunca deve deixar um erro/aviso vazar pra resposta HTTP de quem chamou
// (salvar um evento não pode quebrar por causa do envio de push).
function enviarPushParaUsuarios($usuarioIds, $titulo, $mensagem, $url) {
  ob_start();
  try {
    enviarPushParaUsuariosInterno($usuarioIds, $titulo, $mensagem, $url);
  } catch (\Throwable $e) {
    error_log('Push: erro inesperado - ' . $e->getMessage());
  } finally {
    $vazado = ob_get_clean();
    if ($vazado !== '') {
      error_log('Push: saída inesperada suprimida - ' . substr(strip_tags($vazado), 0, 300));
    }
  }
}

function enviarPushParaUsuariosInterno($usuarioIds, $titulo, $mensagem, $url) {
  $usuarioIds = array_filter(array_unique($usuarioIds));
  if (!$usuarioIds) return;

  $pdo = getPDO();
  $placeholders = implode(',', array_fill(0, count($usuarioIds), '?'));
  $stmt = $pdo->prepare("SELECT * FROM push_subscriptions WHERE valida = 1 AND usuario_id IN ($placeholders)");
  $stmt->execute(array_values($usuarioIds));
  $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if (!$subs) return;

  $payload = json_encode([
    'title' => $titulo,
    'body' => $mensagem,
    'url' => $url,
  ], JSON_UNESCAPED_UNICODE);

  try {
    $webPush = getWebPush();
  } catch (\Throwable $e) {
    error_log('Push: falha ao iniciar WebPush - ' . $e->getMessage());
    return;
  }

  foreach ($subs as $sub) {
    try {
      $subscription = Subscription::create([
        'endpoint' => $sub['endpoint'],
        'publicKey' => $sub['p256dh'],
        'authToken' => $sub['auth'],
      ]);
      $webPush->queueNotification($subscription, $payload);
    } catch (\Throwable $e) {
      error_log('Push: falha ao enfileirar - ' . $e->getMessage());
    }
  }

  try {
    foreach ($webPush->flush() as $report) {
      $endpoint = $report->getEndpoint();
      if ($report->isSuccess()) {
        $pdo->prepare('UPDATE push_subscriptions SET ultimo_uso = CURRENT_TIMESTAMP WHERE endpoint = ?')->execute([$endpoint]);
      } elseif ($report->isSubscriptionExpired()) {
        // Endpoint não existe mais (410/404) — remove pra não tentar de novo.
        $pdo->prepare('DELETE FROM push_subscriptions WHERE endpoint = ?')->execute([$endpoint]);
      } else {
        error_log('Push: envio falhou pra ' . $endpoint . ' - ' . $report->getReason());
      }
    }
  } catch (\Throwable $e) {
    error_log('Push: falha ao enviar - ' . $e->getMessage());
  }
}

function enviarPushParaIntegrantes($titulo, $mensagem, $url) {
  $pdo = getPDO();
  $ids = $pdo->query("SELECT id FROM admin_users WHERE role = 'integrante'")->fetchAll(PDO::FETCH_COLUMN);
  enviarPushParaUsuarios($ids, $titulo, $mensagem, $url);
}
