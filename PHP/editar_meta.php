<?php

session_start();

require_once "conexao.php";

// Verifica se o usuário está logado
if (!isset($_SESSION["usuario_id"])) {
    die("Você precisa estar logado para acessar esta página.");
}

$usuario_id = $_SESSION["usuario_id"];

if (!isset($_GET["id"])) {
    die("Meta não informada.");
}

$meta_id = $_GET["id"];

// Se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nome = $_POST["nome"];
    $valor_objetivo = $_POST["valor_objetivo"];
    $valor_atual = $_POST["valor_atual"];
    $data_limite = $_POST["data_limite"];

    // Atualiza somente uma meta pertencente ao usuário logado
    $sql = "UPDATE metas
            SET nome = ?,
                valor_objetivo = ?,
                valor_atual = ?,
                data_limite = ?
            WHERE id = ?
            AND usuario_id = ?";

    $stmt = $conexao->prepare($sql);

    $stmt->bind_param(
        "sddsii",
        $nome,
        $valor_objetivo,
        $valor_atual,
        $data_limite,
        $meta_id,
        $usuario_id
    );

    if ($stmt->execute()) {
        header("Location: metas.php");
        exit;
    } else {
        echo "Erro ao atualizar: " . $conexao->error;
    }
}

// Busca os dados atuais da meta
$sql = "SELECT *
        FROM metas
        WHERE id = ?
        AND usuario_id = ?";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("ii", $meta_id, $usuario_id);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows !== 1) {
    die("Meta não encontrada.");
}

$meta = $resultado->fetch_assoc();

$stmt->close();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Editar meta - Koplo</title>
</head>

<body>

    <h1>Editar meta</h1>

    <form method="POST">

        <label>Nome da meta:</label>

        <input
            type="text"
            name="nome"
            value="<?php echo htmlspecialchars($meta["nome"]); ?>"
            required>

        <br><br>

        <label>Valor objetivo:</label>

        <input
            type="number"
            name="valor_objetivo"
            step="0.01"
            min="0.01"
            value="<?php echo $meta["valor_objetivo"]; ?>"
            required>

        <br><br>

        <label>Valor atual:</label>

        <input
            type="number"
            name="valor_atual"
            step="0.01"
            min="0"
            value="<?php echo $meta["valor_atual"]; ?>"
            required>

        <br><br>

        <label>Data limite:</label>

        <input
            type="date"
            name="data_limite"
            value="<?php echo $meta["data_limite"]; ?>">

        <br><br>

        <button type="submit">Salvar alterações</button>

    </form>

</body>

</html>