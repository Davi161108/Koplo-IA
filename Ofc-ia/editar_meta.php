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

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f7f9;
            color: #102f49;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Card principal */
        .meta-container {
            width: 500px;
            background: #ffffff;
            border: 1px solid #e3e8ec;
            border-radius: 12px;
            padding: 35px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        /* Título */
        .meta-container h1 {
            font-size: 26px;
            color: #102f49;
            margin-bottom: 8px;
        }

        /* Labels */
        .meta-container label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #183b56;
            margin-bottom: 8px;
        }

        /* Inputs */
        .meta-container input {
            width: 100%;
            height: 45px;
            padding: 0 14px;
            border: 1px solid #dce3e8;
            border-radius: 8px;
            background: #ffffff;
            color: #183b56;
            font-size: 14px;
            outline: none;
            margin-bottom: 20px;
            transition: 0.2s;
        }

        .meta-container input:focus {
            border-color: #2cc493;
            box-shadow: 0 0 0 2px rgba(44, 196, 147, 0.12);
        }

        /* Botão */
        .meta-container button {
            width: 100%;
            height: 45px;
            border: none;
            border-radius: 8px;
            background: #2cc493;
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .meta-container button:hover {
            background: #25ad82;
        }
    </style>

    <div class="meta-container">

        <h1>Editar meta</h1>

        <?php if (isset($erro)): ?>
            <div class="mensagem-erro">
                <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <label>Nome da meta:</label>
            <input
                type="text"
                name="nome"
                value="<?php echo htmlspecialchars($meta["nome"]); ?>"
                required>

            <label>Valor objetivo:</label>
            <input
                type="number"
                name="valor_objetivo"
                step="0.01"
                min="0.01"
                value="<?php echo $meta["valor_objetivo"]; ?>"
                required>

            <label>Valor atual:</label>
            <input
                type="number"
                name="valor_atual"
                step="0.01"
                min="0"
                value="<?php echo $meta["valor_atual"]; ?>"
                required>

            <label>Data limite:</label>
            <input
                type="date"
                name="data_limite"
                value="<?php echo $meta["data_limite"]; ?>">

            <button type="submit">
                Salvar alterações
            </button>

        </form>

    </div>

</body>

</html>