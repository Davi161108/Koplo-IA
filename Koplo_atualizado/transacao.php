<?php

require_once "conexao.php";
require_once "protecao.php";

// Verifica se o usuário está logado
if (!isset($_SESSION["usuario_id"])) {
    die("Você precisa estar logado para acessar esta página.");
}

$usuario_id = $_SESSION["usuario_id"];

// Se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $categoria_id = $_POST["categoria_id"];
    $descricao = $_POST["descricao"];
    $valor = $_POST["valor"];
    $tipo = $_POST["tipo"];
    $data_transacao = $_POST["data_transacao"];

    $sql = "INSERT INTO transacoes 
            (usuario_id, categoria_id, descricao, valor, tipo, data_transacao)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conexao->prepare($sql);

    $stmt->bind_param(
        "iisdss",
        $usuario_id,
        $categoria_id,
        $descricao,
        $valor,
        $tipo,
        $data_transacao
    );

    if ($stmt->execute()) {
        echo "Transação cadastrada com sucesso!";
    } else {
        echo "Erro ao cadastrar transação: " . $conexao->error;
    }

    $stmt->close();
}

// Busca as categorias cadastradas
$sql_categorias = "SELECT * FROM categorias ORDER BY nome";
$resultado_categorias = $conexao->query($sql_categorias);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Nova transação - Koplo</title>
</head>

<body>

    <h1>Nova transação</h1>

    <p>
        Usuário logado:
        <?php echo htmlspecialchars($_SESSION["usuario_nome"]); ?>
    </p>

    <form method="POST">

        <label>Tipo:</label>
        <select name="tipo" required>
            <option value="">Selecione</option>
            <option value="receita">Receita</option>
            <option value="despesa">Despesa</option>
        </select>

        <br><br>

        <label>Categoria:</label>
        <select name="categoria_id" required>

            <option value="">Selecione uma categoria</option>

            <?php while ($categoria = $resultado_categorias->fetch_assoc()) { ?>

                <option value="<?php echo $categoria["id"]; ?>">
                    <?php echo htmlspecialchars($categoria["nome"]); ?>
                </option>

            <?php } ?>

        </select>

        <br><br>

        <label>Descrição:</label>
        <input
            type="text"
            name="descricao"
            placeholder="Ex: Compra no mercado"
            required
        >

        <br><br>

        <label>Valor:</label>
        <input
            type="number"
            name="valor"
            step="0.01"
            min="0.01"
            placeholder="0.00"
            required
        >

        <br><br>

        <label>Data:</label>
        <input
            type="date"
            name="data_transacao"
            required
        >

        <br><br>

        <button type="submit">Cadastrar transação</button>

    </form>

</body>

</html>