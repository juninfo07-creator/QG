<?php
// Copie este arquivo pra "ftp-config.php" (mesma pasta) e preencha com os
// dados reais. O "ftp-config.php" nunca é commitado — fica só nesse
// computador, pra eu (Claude) usar quando você pedir pra publicar na
// HostGator.

return [
  'host' => 'ftp.quartetogileade.com.br',
  'port' => 21,           // 21 = FTP normal, 22 = SFTP
  'protocolo' => 'ftp',   // 'ftp' ou 'sftp'
  'user' => '',
  'pass' => '',
  'remote_path' => 'public_html/', // pasta remota onde fica o site
];
