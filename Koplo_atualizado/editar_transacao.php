<?php

require_once "protecao.php";
require_once "conexao.php";

$usuario_id = $_SESSION["usuario_id"];

// Verifica se recebeu o ID da transação
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Transação não informada.");
}

$transacao_id = (int) $_GET["id"];

// Busca a transação somente se pertencer ao usuário logado
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


// Se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $categoria_id = (int) $_POST["categoria_id"];
    $descricao = trim($_POST["descricao"]);
    $valor = (float) $_POST["valor"];
    $tipo = $_POST["tipo"];
    $data_transacao = $_POST["data_transacao"];

    // Valida o tipo
    if ($tipo !== "receita" && $tipo !== "despesa") {
        die("Tipo de transação inválido.");
    }

    // Valida o valor
    if ($valor <= 0) {
        die("O valor deve ser maior que zero.");
    }

    // Verifica se a categoria existe
    $sql_categoria = "SELECT id
                       FROM categorias
                       WHERE id = ?";

    $stmt_categoria = $conexao->prepare($sql_categoria);
    $stmt_categoria->bind_param("i", $categoria_id);
    $stmt_categoria->execute();

    $resultado_categoria = $stmt_categoria->get_result();

    if ($resultado_categoria->num_rows !== 1) {
        die("Categoria inválida.");
    }

    $stmt_categoria->close();


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

        $stmt->close();

        // Volta para a lista de transações
        header("Location: transacoes.php");
        exit;

    } else {

        echo "Erro ao atualizar a transação.";

        $stmt->close();
    }
}


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
                if ($transacao["tipo"] === "receita") {
                    echo "selected";
                }
                ?>>
                Receita
            </option>

            <option value="despesa"
                <?php
                if ($transacao["tipo"] === "despesa") {
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
            value="<?php echo htmlspecialchars($transacao["valor"]); ?>"
            required
        >

        <br><br>


        <label>Data:</label>

        <input
            type="date"
            name="data_transacao"
            value="<?php echo htmlspecialchars($transacao["data_transacao"]); ?>"
            required
        >

        <br><br>


        <button type="submit">
            Salvar alterações
        </button>

        <a href="transacoes.php">
            Cancelar
        </a>

    </form>

</body>

</html>