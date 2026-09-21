<?php

require_once "protecao.php";
require_once "conexao.php";

$usuario_id = $_SESSION["usuario_id"];

$erros = [];
$sucesso = "";

$categoria_id = "";
$descricao = "";
$valor = "";
$tipo = "";
$data_transacao = "";


// Verifica se recebeu o ID da transação
if (
    !isset($_GET["id"]) ||
    !ctype_digit($_GET["id"]) ||
    (int) $_GET["id"] <= 0
) {
    die("Transação não informada.");
}

$transacao_id = (int) $_GET["id"];


// Busca a transação somente se pertencer ao usuário logado
$sql = "SELECT *
        FROM transacoes
        WHERE id = ?
        AND usuario_id = ?";

$stmt = $conexao->prepare($sql);

if (!$stmt) {
    die("Erro ao preparar a consulta.");
}

$stmt->bind_param(
    "ii",
    $transacao_id,
    $usuario_id
);

$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows !== 1) {
    $stmt->close();
    die("Transação não encontrada.");
}

$transacao = $resultado->fetch_assoc();

$stmt->close();


// Preenche os valores iniciais com os dados da transação
$categoria_id = (string) $transacao["categoria_id"];
$descricao = $transacao["descricao"];
$valor = $transacao["valor"];
$tipo = $transacao["tipo"];
$data_transacao = $transacao["data_transacao"];


// Se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    validar_csrf();

    $categoria_id = trim($_POST["categoria_id"] ?? "");
    $descricao = trim($_POST["descricao"] ?? "");
    $valor = trim($_POST["valor"] ?? "");
    $tipo = $_POST["tipo"] ?? "";
    $data_transacao = $_POST["data_transacao"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÃO DA CATEGORIA
    |--------------------------------------------------------------------------
    */

    if (
        $categoria_id === "" ||
        !ctype_digit($categoria_id) ||
        (int) $categoria_id <= 0
    ) {
        $erros[] = "Selecione uma categoria.";
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÃO DA DESCRIÇÃO
    |--------------------------------------------------------------------------
    */

    if ($descricao === "") {

        $erros[] = "Informe uma descrição.";

    } elseif (mb_strlen($descricao) > 255) {

        $erros[] = "A descrição deve ter no máximo 255 caracteres.";
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÃO DO VALOR
    |--------------------------------------------------------------------------
    */

    if ($valor === "") {

        $erros[] = "Informe o valor.";

    } elseif (!is_numeric($valor)) {

        $erros[] = "Informe um valor válido.";

    } elseif ((float) $valor <= 0) {

        $erros[] = "O valor deve ser maior que zero.";
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÃO DO TIPO
    |--------------------------------------------------------------------------
    */

    if ($tipo !== "receita" && $tipo !== "despesa") {

        $erros[] = "Selecione um tipo de transação válido.";
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÃO DA DATA
    |--------------------------------------------------------------------------
    */

    if ($data_transacao === "") {

        $erros[] = "Informe a data da transação.";

    } else {

        $data_obj = DateTime::createFromFormat(
            "Y-m-d",
            $data_transacao
        );

        $data_valida =
            $data_obj &&
            $data_obj->format("Y-m-d") === $data_transacao;

        if (!$data_valida) {
            $erros[] = "Informe uma data válida.";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFICA SE A CATEGORIA EXISTE E COMBINA COM O TIPO
    |--------------------------------------------------------------------------
    */

    if (
        empty($erros) &&
        ctype_digit($categoria_id)
    ) {

        $categoria_id_int = (int) $categoria_id;

        $sql_categoria = "SELECT id
                          FROM categorias
                          WHERE id = ?
                          AND tipo = ?";

        $stmt_categoria = $conexao->prepare($sql_categoria);

        if (!$stmt_categoria) {

            $erros[] = "Não foi possível validar a categoria.";

        } else {

            $stmt_categoria->bind_param(
                "is",
                $categoria_id_int,
                $tipo
            );

            $stmt_categoria->execute();

            $resultado_categoria =
                $stmt_categoria->get_result();

            if ($resultado_categoria->num_rows !== 1) {

                $erros[] =
                    "A categoria selecionada não corresponde ao tipo da transação.";
            }

            $stmt_categoria->close();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ATUALIZAÇÃO
    |--------------------------------------------------------------------------
    */

    if (empty($erros)) {

        $categoria_id = (int) $categoria_id;
        $valor_decimal = (float) $valor;

        $sql = "UPDATE transacoes
                SET categoria_id = ?,
                    descricao = ?,
                    valor = ?,
                    tipo = ?,
                    data_transacao = ?
                WHERE id = ?
                AND usuario_id = ?";

        $stmt = $conexao->prepare($sql);

        if (!$stmt) {

            $erros[] =
                "Não foi possível preparar a atualização.";

        } else {

            $stmt->bind_param(
                "isdssii",
                $categoria_id,
                $descricao,
                $valor_decimal,
                $tipo,
                $data_transacao,
                $transacao_id,
                $usuario_id
            );

            if ($stmt->execute()) {

                $stmt->close();

                header("Location: transacoes.php");
                exit;

            } else {

                $erros[] =
                    "Erro ao atualizar a transação.";

                $stmt->close();
            }
        }
    }
}


// Busca as categorias
$sql_categorias = "SELECT *
                   FROM categorias
                   ORDER BY nome";

$resultado_categorias =
    $conexao->query($sql_categorias);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <title>Editar transação - Koplo</title>

</head>

<body>

    <h1>Editar transação</h1>


    <?php if (!empty($erros)) { ?>

        <div>

            <?php foreach ($erros as $erro) { ?>

                <p>
                    <?php echo htmlspecialchars($erro); ?>
                </p>

            <?php } ?>

        </div>

    <?php } ?>


    <?php if ($sucesso !== "") { ?>

        <p>
            <?php echo htmlspecialchars($sucesso); ?>
        </p>

    <?php } ?>


    <form method="POST"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars(csrf_token()); ?>"
        >

        <label>Tipo:</label>

        <select name="tipo" required>

            <option
                value="receita"
                <?php
                if ($tipo === "receita") {
                    echo "selected";
                }
                ?>
            >
                Receita
            </option>

            <option
                value="despesa"
                <?php
                if ($tipo === "despesa") {
                    echo "selected";
                }
                ?>
            >
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
                    if (
                        (string) $categoria["id"] ===
                        (string) $categoria_id
                    ) {
                        echo "selected";
                    }
                    ?>
                >

                    <?php
                    echo htmlspecialchars(
                        $categoria["nome"]
                    );
                    ?>

                </option>

            <?php } ?>

        </select>

        <br><br>


        <label>Descrição:</label>

        <input
            type="text"
            name="descricao"
            value="<?php echo htmlspecialchars($descricao); ?>"
            maxlength="255"
            required
        >

        <br><br>


        <label>Valor:</label>

        <input
            type="number"
            name="valor"
            step="0.01"
            min="0.01"
            value="<?php echo htmlspecialchars($valor); ?>"
            required
        >

        <br><br>


        <label>Data:</label>

        <input
            type="date"
            name="data_transacao"
            value="<?php echo htmlspecialchars($data_transacao); ?>"
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