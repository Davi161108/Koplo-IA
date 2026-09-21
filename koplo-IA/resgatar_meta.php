<?php

session_start();

require_once "conexao.php";

if (!isset($_SESSION["usuario_id"])) {

    die("Você precisa estar logado para resgatar o dinheiro.");
}

$usuario_id = $_SESSION["usuario_id"];

$origem = $_GET["origem"] ?? "metas";

if ($origem == "historico") {
    $pagina_voltar = "historico_metas.php";
} else {
    $pagina_voltar = "metas.php";
}

if (!isset($_GET["id"])) {

    die("Meta não informada.");
}

$meta_id = $_GET["id"];


/* Busca a meta */

$sql = "SELECT *
        FROM metas
        WHERE id = ?
        AND usuario_id = ?";

$stmt = $conexao->prepare($sql);

$stmt->bind_param(
    "ii",
    $meta_id,
    $usuario_id
);

$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows !== 1) {

    die("Meta não encontrada.");
}

$meta = $resultado->fetch_assoc();

$stmt->close();


/* Se o formulário foi enviado */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $acao = $_POST["acao"];

    $valor_resgate = $_POST["valor_resgate"];

    if ($valor_resgate <= 0) {

        $erro = "Não há dinheiro disponível para resgatar.";
    } elseif ($valor_resgate > $meta["valor_atual"]) {

        $erro = "O valor do resgate não pode ser maior que o saldo da meta.";
    } else {

        $novo_valor = $meta["valor_atual"] - $valor_resgate;

        $sql = "UPDATE metas
                SET valor_atual = ?
                WHERE id = ?
                AND usuario_id = ?";

        $stmt = $conexao->prepare($sql);

        $stmt->bind_param(
            "dii",
            $novo_valor,
            $meta_id,
            $usuario_id
        );

        if ($stmt->execute()) {

            header("Location: metas.php");
            exit;
        } else {

            $erro = "Erro ao resgatar dinheiro: " . $conexao->error;
        }

        $stmt->close();
    }
}


?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <title>Resgatar dinheiro - Koplo</title>

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

        /* Informações da meta */

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

        .meta-container input:focus {
            border-color: #2cc493;
            box-shadow: 0 0 0 2px rgba(44, 196, 147, 0.12);
        }

        /* Botões */

        .botoes-resgate {
            display: flex;
            gap: 10px;
        }

        .botoes-resgate button {
            flex: 1;
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

        .botoes-resgate button:hover {
            background: #25ad82;
        }

        /* Botão Resgatar tudo */

        .btn-resgatar-tudo {
            background: #ffffff !important;
            color: #2cc493 !important;
            border: 1px solid #2cc493 !important;
        }

        .btn-resgatar-tudo:hover {
            background: #f0fbf7 !important;
        }

        /* Aviso de erro */

        .mensagem-erro {
            background: #fff1f1;
            border: 1px solid #f3caca;
            color: #c0392b;
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
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

        <a href="<?php echo $pagina_voltar; ?>" class="botao-fechar">&times;</a>

        <h1>Resgatar dinheiro</h1>

        <?php if (isset($erro)): ?>

            <div class="mensagem-erro">

                <?php echo htmlspecialchars($erro); ?>

            </div>

        <?php endif; ?>

        <p>

            Meta:
            <?php echo htmlspecialchars($meta["nome"]); ?>

            <br><br>

            Saldo atual:
            R$ <?php echo number_format($meta["valor_atual"], 2, ",", "."); ?>

        </p>

        <form method="POST">

            <label>Valor a resgatar:</label>

            <input
                type="number"
                name="valor_resgate"
                step="0.01"
                min="0.01"
                max="<?php echo $meta["valor_atual"]; ?>"
                placeholder="0,00"
                required>

            <div class="botoes-resgate">

                <button
                    type="submit"
                    name="acao"
                    value="parcial">
                    Resgatar dinheiro
                </button>

                <button
                    type="button"
                    class="btn-resgatar-tudo"
                    onclick="resgatarTudo()">
                    Resgatar tudo
                </button>

            </div>
        </form>
    </div>

    <script>
        function resgatarTudo() {

            const campo = document.querySelector('input[name="valor_resgate"]');

            campo.value = "<?php echo $meta["valor_atual"]; ?>";

        }
    </script>

</body>

</html>