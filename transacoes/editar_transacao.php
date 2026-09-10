<?php

session_start();

require_once "conexao.php";

// Verifica se o usuário está logado
if (!isset($_SESSION["usuario_id"])) {
    die("Você precisa estar logado para acessar esta página.");
}

$usuario_id = $_SESSION["usuario_id"];

// Verifica se recebeu o ID da transação
if (!isset($_GET["id"])) {
    die("Transação não informada.");
}

$transacao_id = $_GET["id"];

// Se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $categoria_id = $_POST["categoria_id"];
    $descricao = $_POST["descricao"];
    $valor = $_POST["valor"];
    $tipo = $_POST["tipo"];
    $data_transacao = $_POST["data_transacao"];

    // Atualiza somente uma transação pertencente ao usuário logado
    $sql = "UPDATE transacoes
            SET categoria_id = ?,
                descricao = ?,
                valor = ?,
                tipo = ?,
                data_transacao = ?
            WHERE id = ?
            AND usuario_id = ?";

    $stmt = $conexao->prepare($sql);

    $stmt->bind_param(
        "isdssii",
        $categoria_id,
        $descricao,
        $valor,
        $tipo,
        $data_transacao,
        $transacao_id,
        $usuario_id
    );

    if ($stmt->execute()) {
        echo "Transação atualizada com sucesso!";
    } else {
        echo "Erro ao atualizar: " . $conexao->error;
    }

    $stmt->close();
}

// Busca os dados atuais da transação
$sql = "SELECT *
        FROM transacoes
        WHERE id = ?
        AND usuario_id = ?";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("ii", $transacao_id, $usuario_id);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows !== 1) {
    die("Transação não encontrada.");
}

$transacao = $resultado->fetch_assoc();

$stmt->close();

// Busca as categorias
$sql_categorias = "SELECT *
                   FROM categorias
                   ORDER BY nome";

$resultado_categorias = $conexao->query($sql_categorias);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Editar transação - Koplo</title>
</head>

<body>

    <h1>Editar transação</h1>

    <form method="POST">

        <label>Tipo:</label>

        <select name="tipo" required>

            <option value="receita"
                <?php
                if ($transacao["tipo"] == "receita") {
                    echo "selected";
                }
                ?>>
                Receita
            </option>

            <option value="despesa"
                <?php
                if ($transacao["tipo"] == "despesa") {
                    echo "selected";
                }
                ?>>
                Despesa
            </option>

        </select>

        <br><br>

        <label>Categoria:</label>

        <select name="categoria_id" required>

            <?php while ($categoria = $resultado_categorias->fetch_assoc()) { ?>

                <option
                    value="<?php echo $categoria["id"]; ?>"
                    <?php
                    if ($categoria["id"] == $transacao["categoria_id"]) {
                        echo "selected";
                    }
                    ?>
                >
                    <?php echo htmlspecialchars($categoria["nome"]); ?>
                </option>

            <?php } ?>

        </select>

        <br><br>

        <label>Descrição:</label>

        <input
            type="text"
            name="descricao"
            value="<?php echo htmlspecialchars($transacao["descricao"]); ?>"
            required
        >

        <br><br>

        <label>Valor:</label>

        <input
            type="number"
            name="valor"
            step="0.01"
            min="0.01"
            value="<?php echo $transacao["valor"]; ?>"
            required
        >

        <br><br>

        <label>Data:</label>

        <input
            type="date"
            name="data_transacao"
            value="<?php echo $transacao["data_transacao"]; ?>"
            required
        >

        <br><br>

        <button type="submit">Salvar alterações</button>

    </form>

</body>

</html>
