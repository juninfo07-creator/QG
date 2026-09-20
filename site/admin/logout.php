<?php
require __DIR__ . '/permissoes.php';
session_destroy();
header('Location: index.php');
