<?php

session_start();

require_once "conexao.php";
require_once "protecao.php";

// Verifica se o usuário está logado
if (!isset($_SESSION["usuario_id"])) {
    die("Você precisa estar logado para acessar esta página.");
}

$usuario_id = $_SESSION["usuario_id"];

// Se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = $_POST["nome"];
    $valor_objetivo = $_POST["valor_objetivo"];
    $data_limite = $_POST["data_limite"];

    $sql = "INSERT INTO metas
            (usuario_id, nome, valor_objetivo, valor_atual, data_limite)
            VALUES (?, ?, ?, 0, ?)";

    $stmt = $conexao->prepare($sql);

    $stmt->bind_param(
        "isds",
        $usuario_id,
        $nome,
        $valor_objetivo,
        $data_limite
    );

    if ($stmt->execute()) {

        echo "Meta criada com sucesso!";

    } else {

        echo "Erro ao criar meta: " . $conexao->error;

    }

    $stmt->close();
}

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <title>Nova Meta - Koplo</title>

</head>

<body>

    <h1>Nova Meta</h1>

    <p>
        Usuário:
        <?php echo htmlspecialchars($_SESSION["usuario_nome"]); ?>
    </p>

    <form method="POST">

        <label>Nome da meta:</label>

        <input
            type="text"
            name="nome"
            placeholder="Ex: Viagem"
            required
        >

        <br><br>


        <label>Valor objetivo:</label>

        <input
            type="number"
            name="valor_objetivo"
            step="0.01"
            min="0.01"
            placeholder="0.00"
            required
        >

        <br><br>


        <label>Data limite:</label>

        <input
            type="date"
            name="data_limite"
        >

        <br><br>


        <button type="submit">
            Criar meta
        </button>

    </form>

</body>

</html>