<?php

ini_set("session.use_only_cookies", "1");
ini_set("session.use_strict_mode", "1");

session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/",
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "httponly" => true,
    "samesite" => "Lax"
]);

session_start();


// Verifica se o usuário está logado
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}


// Gera o token CSRF
if (
    empty($_SESSION["csrf_token"]) ||
    !is_string($_SESSION["csrf_token"])
) {
    $_SESSION["csrf_token"] = bin2hex(
        random_bytes(32)
    );
}


// Retorna o token CSRF
function csrf_token()
{
    return $_SESSION["csrf_token"];
}


// Verifica o token CSRF enviado pelo formulário
function validar_csrf()
{
    if (
        !isset($_POST["csrf_token"]) ||
        !is_string($_POST["csrf_token"]) ||
        !hash_equals(
            $_SESSION["csrf_token"],
            $_POST["csrf_token"]
        )
    ) {
        die("Requisição inválida.");
    }
}


// Impede o navegador de utilizar páginas antigas
header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);

header(
    "Cache-Control: post-check=0, pre-check=0",
    false
);

header("Pragma: no-cache");
header("Expires: 0");