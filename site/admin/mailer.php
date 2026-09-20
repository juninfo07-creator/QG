<?php

function enviarEmail($para, $assunto, $corpoTexto) {
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $headers = "From: Quarteto Gileade <nao-responda@{$host}>\r\n";
  $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

  $enviado = @mail($para, $assunto, $corpoTexto, $headers);

  // Log só quando rodando no servidor embutido do PHP (desenvolvimento local).
  // Nunca grava em produção — esse arquivo fica dentro da pasta pública do
  // site e não deve conter tokens de redefinição de senha acessíveis por URL.
  if (php_sapi_name() === 'cli-server') {
    $logPath = __DIR__ . '/mail_outbox.log';
    $registro = date('Y-m-d H:i:s') . " | Para: {$para} | Assunto: {$assunto} | mail() retornou: " . ($enviado ? 'true' : 'false') . "\n{$corpoTexto}\n---\n";
    @file_put_contents($logPath, $registro, FILE_APPEND);
  }

  return $enviado;
}
