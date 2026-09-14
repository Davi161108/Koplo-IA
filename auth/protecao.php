<?php

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

// Impede cache da página protegida
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
