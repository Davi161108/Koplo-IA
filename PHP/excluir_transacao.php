<?php

require_once "protecao.php";
require_once "conexao.php";

$usuario_id = $_SESSION["usuario_id"];

// Verifica se recebeu o ID da transação
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Transação não informada.");
}

$transacao_id = (int) $_GET["id"];

// Exclui somente se a transação pertencer ao usuário logado
$sql = "DELETE FROM transacoes
        WHERE id = ?
        AND usuario_id = ?";

$stmt = $conexao->prepare($sql);

$stmt->bind_param(
    "ii",
    $transacao_id,
    $usuario_id
);

if ($stmt->execute()) {

    if ($stmt->affected_rows > 0) {

        $stmt->close();

        // Volta para a lista de transações
        header("Location: transacoes.php");
        exit;

    } else {

        $stmt->close();

        die("Transação não encontrada ou não pertence ao usuário.");
    }

} else {

    $stmt->close();

    die("Erro ao excluir transação.");
}
