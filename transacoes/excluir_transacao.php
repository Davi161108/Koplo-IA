<?php

session_start();

require_once "conexao.php";

// Verifica se o usuário está logado
if (!isset($_SESSION["usuario_id"])) {
    die("Você precisa estar logado para excluir uma transação.");
}

$usuario_id = $_SESSION["usuario_id"];

// Verifica se recebeu o ID da transação
if (!isset($_GET["id"])) {
    die("Transação não informada.");
}

$transacao_id = $_GET["id"];

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
        echo "Transação excluída com sucesso!";
    } else {
        echo "Transação não encontrada ou não pertence ao usuário.";
    }

} else {

    echo "Erro ao excluir transação: " . $conexao->error;

}

$stmt->close();

?>

<br><br>

<a href="transacoes.php">
    Voltar para minhas transações
</a>
