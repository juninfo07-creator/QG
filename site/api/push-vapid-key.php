<?php
require __DIR__ . '/../admin/db.php';

header('Content-Type: text/plain; charset=utf-8');
$config = getConfig();
echo $config['vapid']['public_key'];
