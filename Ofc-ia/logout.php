<?php

session_start();


// Remove todas as variáveis da sessão
$_SESSION = [];


// Remove o cookie da sessão
if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"] ?? "",
        $params["secure"],
        $params["httponly"]
    );
}


// Destrói completamente a sessão
session_destroy();


// Volta para o login
header("Location: login.php");
exit;