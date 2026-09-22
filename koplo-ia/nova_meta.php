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

    $nome = ucfirst(strtolower(trim($_POST["nome"])));
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

        header("Location: metas.php");
        exit;
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
            position: relative;
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

        /* Usuário */
        .meta-container p {
            font-size: 14px;
            color: #70859a;
            margin-bottom: 30px;
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

        .meta-container input::placeholder {
            color: #a2adb7;
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

        .botao-fechar {
            position: absolute;
            top: 18px;
            right: 20px;
            text-decoration: none;
            color: #70859a;
            font-size: 24px;
            font-weight: 400;
            line-height: 1;
            cursor: pointer;
            transition: 0.2s;
        }

        .botao-fechar:hover {
            color: #102f49;
        }
    </style>
</head>

<body>

    <div class="meta-container">

        <a href="metas.php" class="botao-fechar">&times;</a>

        <h1>Nova Meta</h1>

        <?php if (isset($erro)): ?>
            <div class="mensagem-erro">
                <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

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
                required>

            <br><br>


            <label>Valor objetivo:</label>

            <input
                type="number"
                name="valor_objetivo"
                step="0.01"
                min="0.01"
                placeholder="0.00"
                required>

            <br><br>


            <label>Data limite:</label>

            <input
                type="date"
                name="data_limite">

            <br><br>


            <button type="submit">
                Criar meta
            </button>

        </form>
    </div>
</body>

</html>