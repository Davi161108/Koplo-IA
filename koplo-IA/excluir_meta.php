<?php

session_start();

require_once "conexao.php";

// Verifica se o usuário está logado
if (!isset($_SESSION["usuario_id"])) {
    die("Você precisa estar logado para excluir uma meta.");
}

$usuario_id = $_SESSION["usuario_id"];

// Verifica se recebeu o ID das metas
if (!isset($_GET["id"])) {
    die("Meta não informada.");
}

$metas_id = $_GET["id"];

// Exclui somente se a meta pertencer ao usuário logado
$sql = "DELETE FROM metas
        WHERE id = ?
        AND usuario_id = ?";

$stmt = $conexao->prepare($sql);

$stmt->bind_param(
    "ii",
    $metas_id,
    $usuario_id
);

if ($stmt->execute()) {

    if ($stmt->affected_rows > 0) {
        header("Location: metas.php");
        exit;
    } else {
        echo "Meta não encontrada ou não pertence ao usuário.";
    }
} else {
    echo "Erro ao excluir meta: " . $conexao->error;
}

$stmt->close();

?>

<br><br>

<a href="metas.php">
    Voltar para minhas metas
</a>