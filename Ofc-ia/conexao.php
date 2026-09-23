<?php
// Tenta ler o arquivo acess.env
$env = parse_ini_file(__DIR__ . '/.env');

if (!$env) {
    die("Erro: Arquivo de configuração (acess.env) não encontrado ou inválido.");
}

$servidor = $env['DB_HOST'];
$usuario = $env['DB_USER'];
$senha = $env['DB_PASS'];
$banco = $env['DB_NAME'];

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    die("Erro na conexão: " . $conexao->connect_error);
}
?>