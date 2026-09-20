<?php
require __DIR__ . '/../admin/permissoes.php';
session_destroy();
header('Location: index.php');
