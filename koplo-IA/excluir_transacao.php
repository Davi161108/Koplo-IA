<?php

require_once "protecao.php";
require_once "conexao.php";

$usuario_id = $_SESSION["usuario_id"];


// Verifica se a requisição é POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Requisição inválida.");
}


// Valida o token CSRF
validar_csrf();


// Verifica se recebeu o ID
$id = $_POST["id"] ?? "";

if (
    $id === "" ||
    !ctype_digit($id) ||
    (int) $id <= 0
) {
    die("Transação não informada.");
}

$transacao_id = (int) $id;


// Exclui somente uma transação
// pertencente ao usuário logado
$sql = "DELETE FROM transacoes
        WHERE id = ?
        AND usuario_id = ?";

$stmt = $conexao->prepare($sql);

if (!$stmt) {
    die("Não foi possível preparar a exclusão.");
}


$stmt->bind_param(
    "ii",
    $transacao_id,
    $usuario_id
);


if ($stmt->execute()) {

    if ($stmt->affected_rows > 0) {

        $stmt->close();

        header("Location: transacoes.php");
        exit;

    } else {

        $stmt->close();

        die(
            "Transação não encontrada ou não pertence ao usuário."
        );
    }

} else {

    $stmt->close();

    die("Erro ao excluir transação.");
}