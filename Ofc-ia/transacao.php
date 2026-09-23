<?php

require_once "protecao.php";
require_once "conexao.php";

$usuario_id = $_SESSION["usuario_id"];
$usuario_nome = $_SESSION["usuario_nome"];

$erros = [];
$sucesso = "";

$categoria_id = "";
$descricao = "";
$valor = "";
$tipo = "";
$data_transacao = "";

/* =========================================================
   PROCESSAMENTO DO FORMULÁRIO
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    validar_csrf();

    /* RECEBE OS DADOS */
    $categoria_id = $_POST["categoria_id"] ?? "";
    $descricao = trim($_POST["descricao"] ?? "");
    $valor = $_POST["valor"] ?? "";
    $tipo = $_POST["tipo"] ?? "";
    $data_transacao = $_POST["data_transacao"] ?? "";


    /* =====================================================
       VALIDAÇÃO DA CATEGORIA
    ===================================================== */

    if ($categoria_id === "" || !ctype_digit($categoria_id)) {

        $erros[] = "Selecione uma categoria válida.";
    } else {

        $categoria_id = (int) $categoria_id;

        if ($categoria_id <= 0) {
            $erros[] = "Selecione uma categoria válida.";
        }
    }


    /* =====================================================
       VALIDAÇÃO DA DESCRIÇÃO
    ===================================================== */

    if ($descricao === "") {

        $erros[] = "Informe uma descrição para a transação.";
    } elseif (mb_strlen($descricao) > 255) {

        $erros[] = "A descrição deve ter no máximo 255 caracteres.";
    }


    /* =====================================================
       VALIDAÇÃO DO VALOR
    ===================================================== */

    if ($valor === "") {

        $erros[] = "Informe o valor da transação.";
    } elseif (!is_numeric($valor)) {

        $erros[] = "Informe um valor válido.";
    } elseif ((float) $valor <= 0) {

        $erros[] = "O valor deve ser maior que zero.";
    }


    /* =====================================================
       VALIDAÇÃO DO TIPO
    ===================================================== */

    if ($tipo !== "receita" && $tipo !== "despesa") {

        $erros[] = "Selecione um tipo de transação válido.";
    }


    /* =====================================================
       VALIDAÇÃO DA DATA
    ===================================================== */

    if ($data_transacao === "") {

        $erros[] = "Informe a data da transação.";
    } else {

        $data_objeto = DateTime::createFromFormat(
            "Y-m-d",
            $data_transacao
        );

        $data_valida =
            $data_objeto &&
            $data_objeto->format("Y-m-d") === $data_transacao;

        if (!$data_valida) {
            $erros[] = "Informe uma data válida.";
        }
    }


    /* =====================================================
       VERIFICA SE A CATEGORIA EXISTE E É DO TIPO CORRETO
    ===================================================== */

    if (
        empty($erros) &&
        isset($categoria_id) &&
        isset($tipo)
    ) {

        $sql_categoria = "
            SELECT id
            FROM categorias
            WHERE id = ?
            AND tipo = ?
            LIMIT 1
        ";

        $stmt_categoria = $conexao->prepare($sql_categoria);

        if ($stmt_categoria) {

            $stmt_categoria->bind_param(
                "is",
                $categoria_id,
                $tipo
            );

            $stmt_categoria->execute();

            $resultado_categoria =
                $stmt_categoria->get_result();

            if ($resultado_categoria->num_rows === 0) {

                $erros[] =
                    "A categoria selecionada não corresponde ao tipo da transação.";
            }

            $stmt_categoria->close();
        } else {

            $erros[] =
                "Não foi possível validar a categoria.";
        }
    }


    /* =====================================================
       CADASTRA A TRANSAÇÃO
    ===================================================== */

    if (empty($erros)) {

        $valor = (float) $valor;

        $sql = "
            INSERT INTO transacoes
            (
                usuario_id,
                categoria_id,
                descricao,
                valor,
                tipo,
                data_transacao
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ";

        $stmt = $conexao->prepare($sql);

        if ($stmt) {

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

                $sucesso =
                    "Transação cadastrada com sucesso.";

                /* LIMPA OS CAMPOS */
                $categoria_id = "";
                $descricao = "";
                $valor = "";
                $tipo = "";
                $data_transacao = "";
            } else {

                $erros[] =
                    "Não foi possível cadastrar a transação.";
            }

            $stmt->close();
        } else {

            $erros[] =
                "Erro ao preparar o cadastro da transação.";
        }
    }
}


/* =========================================================
   BUSCA AS CATEGORIAS
========================================================= */

$sql_categorias = "
    SELECT id, nome, tipo
    FROM categorias
    ORDER BY nome ASC
";

$resultado_categorias =
    $conexao->query($sql_categorias);

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Nova transação - Koplo</title>

    <link
        rel="stylesheet"
        href="dashboard.css">

    <style>
        /* =========================================
           CABEÇALHO
        ========================================= */

        .page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
        }

        .page-header-content {
            min-width: 0;
        }

        .page-title {
            margin-bottom: 6px;
            font-size: 27px;
            font-weight: 700;
            line-height: 1.2;
            color: #17212b;
            letter-spacing: -0.5px;
        }

        .page-subtitle {
            font-size: 14px;
            line-height: 1.5;
            color: #87949c;
        }


        /* =========================================
           MENSAGENS
        ========================================= */

        .form-message {
            width: 100%;
            max-width: 850px;
            margin-bottom: 18px;
            padding: 13px 15px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.5;
        }

        .form-message.success {
            background: #eaf8f3;
            border: 1px solid #c8ebde;
            color: #207b5d;
        }

        .form-message.error {
            background: #fff3f3;
            border: 1px solid #f0d0d0;
            color: #bd4d4d;
        }

        .form-message ul {
            margin: 0;
            padding-left: 18px;
        }

        .form-message li+li {
            margin-top: 4px;
        }


        /* =========================================
           ÁREA DO FORMULÁRIO
        ========================================= */

        .transaction-form-wrapper {
            width: 100%;
            max-width: 850px;
            background: #ffffff;
            border: 1px solid #e8edef;
            border-radius: 11px;
            box-shadow:
                0 2px 8px rgba(22, 35, 43, 0.025);
            overflow: hidden;
        }


        /* =========================================
           CABEÇALHO DO CARD
        ========================================= */

        .form-card-header {
            display: flex;
            align-items: center;
            min-height: 72px;
            padding: 0 24px;
            border-bottom: 1px solid #edf0f1;
        }

        .form-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            margin-right: 11px;
            border-radius: 8px;
            background: #e9f7f2;
            color: #249b73;
        }

        .form-card-icon svg {
            width: 17px;
            height: 17px;
            stroke: currentColor;
        }

        .form-card-title {
            margin-bottom: 3px;
            color: #26343d;
            font-size: 14px;
            font-weight: 650;
        }

        .form-card-description {
            color: #98a3a9;
            font-size: 12px;
        }


        /* =========================================
           FORMULÁRIO
        ========================================= */

        .transaction-form {
            padding: 25px 24px 24px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-label {
            margin-bottom: 8px;
            color: #42515b;
            font-size: 13px;
            font-weight: 650;
        }

        .form-input,
        .form-select {
            width: 100%;
            height: 44px;
            padding: 0 13px;
            border: 1px solid #dfe5e7;
            border-radius: 7px;
            background: #ffffff;
            color: #33434c;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
        }

        .form-input::placeholder {
            color: #a9b2b7;
        }

        .form-input:hover,
        .form-select:hover {
            border-color: #cbd5d9;
        }

        .form-input:focus,
        .form-select:focus {
            border-color: #31b984;
            box-shadow:
                0 0 0 3px rgba(49, 185, 132, 0.09);
        }

        .form-select {
            cursor: pointer;
        }


        /* =========================================
           CAMPO VALOR
        ========================================= */

        .input-money-wrapper {
            position: relative;
        }

        .input-money-prefix {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #89969e;
            font-size: 13px;
            font-weight: 500;
            pointer-events: none;
        }

        .input-money {
            padding-left: 35px;
        }


        /* =========================================
           TIPO
        ========================================= */

        .type-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 9px;
        }

        .type-option {
            position: relative;
        }

        .type-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .type-option label {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 44px;
            border: 1px solid #dfe5e7;
            border-radius: 7px;
            background: #ffffff;
            color: #66757e;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition:
                border-color 0.2s ease,
                background 0.2s ease,
                color 0.2s ease;
        }

        .type-option label:hover {
            border-color: #cbd5d9;
            background: #fafcfc;
        }

        .type-option input:checked+label {
            border-color: #31b984;
            background: #eaf8f3;
            color: #218963;
        }


        /* =========================================
           RODAPÉ DO FORMULÁRIO
        ========================================= */

        .form-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #edf0f1;
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 38px;
            padding: 0 14px;
            border: 1px solid #dfe5e7;
            border-radius: 7px;
            background: #ffffff;
            color: #65747d;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition:
                background 0.2s ease,
                border-color 0.2s ease,
                color 0.2s ease;
        }

        .btn-secondary:hover {
            background: #f7f9f9;
            border-color: #d3dbde;
            color: #3f4e57;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 38px;
            padding: 0 15px;
            border: 1px solid #31b984;
            border-radius: 7px;
            background: #31b984;
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition:
                background 0.2s ease,
                border-color 0.2s ease;
        }

        .btn-primary svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
        }

        .btn-primary:hover {
            background: #29a978;
            border-color: #29a978;
        }


        /* =========================================
           RESPONSIVIDADE
        ========================================= */

        @media (max-width: 800px) {

            .transaction-form-wrapper {
                max-width: 100%;
            }

            .form-card-header {
                padding: 0 20px;
            }

            .transaction-form {
                padding: 22px 20px;
            }
        }


        @media (max-width: 600px) {

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                margin-bottom: 18px;
            }

            .page-title {
                font-size: 22px;
            }

            .page-subtitle {
                font-size: 12px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .form-group.full {
                grid-column: auto;
            }

            .form-card-header {
                min-height: 67px;
                padding: 0 16px;
            }

            .transaction-form {
                padding: 20px 16px;
            }

            .form-footer {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .form-footer a,
            .form-footer button {
                width: 100%;
            }
        }
    </style>

</head>


<body>

    <div class="layout">

        <?php require_once "sidebar.php"; ?>


        <main class="content">


            <!-- =====================================
             TOPBAR
        ====================================== -->

            <header class="topbar">

                <div class="topbar-right">

                    <!-- Notificação -->

                    <div class="notification">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round">

                            <path
                                d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" />

                            <path
                                d="M13.7 21a2 2 0 0 1-3.4 0" />

                        </svg>

                    </div>


                    <!-- Perfil -->

                    <div class="profile-wrapper">

                        <button
                            class="profile"
                            type="button"
                            id="profileButton"
                            aria-expanded="false"
                            aria-haspopup="true">

                            <span class="profile-name">

                                <?php
                                echo htmlspecialchars(
                                    $usuario_nome
                                );
                                ?>

                            </span>


                            <span class="profile-avatar">

                                <?php
                                echo strtoupper(
                                    substr(
                                        htmlspecialchars(
                                            $usuario_nome
                                        ),
                                        0,
                                        1
                                    )
                                );
                                ?>

                            </span>


                            <svg
                                class="profile-arrow"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">

                                <polyline
                                    points="6 9 12 15 18 9" />

                            </svg>

                        </button>


                        <div
                            class="profile-dropdown"
                            id="profileDropdown">

                            <a href="login.php">

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round">

                                    <path
                                        d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />

                                    <polyline
                                        points="10 17 15 12 10 7" />

                                    <line
                                        x1="15"
                                        y1="12"
                                        x2="3"
                                        y2="12" />

                                </svg>

                                <span>
                                    Trocar usuário
                                </span>

                            </a>


                            <div class="profile-divider"></div>


                            <a
                                href="logout.php"
                                class="logout-option">

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round">

                                    <path d="M10 17l5-5-5-5" />

                                    <path d="M15 12H3" />

                                    <path
                                        d="M21 19V5a2 2 0 0 0-2-2h-5" />

                                </svg>

                                <span>
                                    Sair
                                </span>

                            </a>

                        </div>

                    </div>

                </div>

            </header>


            <!-- =====================================
             CABEÇALHO DA PÁGINA
        ====================================== -->

            <div class="page-header">

                <div class="page-header-content">

                    <h1 class="page-title">
                        Nova transação
                    </h1>

                    <p class="page-subtitle">
                        Registre uma nova movimentação financeira.
                    </p>

                </div>

            </div>


            <!-- =====================================
             MENSAGENS
        ====================================== -->

            <?php if (!empty($sucesso)): ?>

                <div class="form-message success">

                    <?php
                    echo htmlspecialchars($sucesso);
                    ?>

                </div>

            <?php endif; ?>


            <?php if (!empty($erros)): ?>

                <div class="form-message error">

                    <ul>

                        <?php foreach ($erros as $erro): ?>

                            <li>
                                <?php
                                echo htmlspecialchars($erro);
                                ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <!-- =====================================
             FORMULÁRIO
        ====================================== -->

            <section class="transaction-form-wrapper">


                <!-- Cabeçalho do card -->

                <div class="form-card-header">

                    <div class="form-card-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round">

                            <rect
                                x="3"
                                y="5"
                                width="18"
                                height="14"
                                rx="2" />

                            <path d="M7 9h10" />

                            <path d="M7 13h5" />

                            <path d="M7 16h3" />

                        </svg>

                    </div>


                    <div>

                        <div class="form-card-title">
                            Dados da transação
                        </div>

                        <div class="form-card-description">
                            Preencha as informações abaixo para registrar a movimentação.
                        </div>

                    </div>

                </div>


                <!-- Formulário -->

                <form
                    method="POST"
                    class="transaction-form">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo htmlspecialchars(csrf_token()); ?>">

                    <div class="form-grid">


                        <!-- Tipo -->

                        <div class="form-group">

                            <label class="form-label">
                                Tipo de transação
                            </label>


                            <div class="type-options">

                                <div class="type-option">

                                    <input
                                        type="radio"
                                        name="tipo"
                                        id="tipo-receita"
                                        value="receita"
                                        required
                                        <?php
                                        echo $tipo === "receita"
                                            ? "checked"
                                            : "";
                                        ?>>

                                    <label for="tipo-receita">
                                        Receita
                                    </label>

                                </div>


                                <div class="type-option">

                                    <input
                                        type="radio"
                                        name="tipo"
                                        id="tipo-despesa"
                                        value="despesa"
                                        required
                                        <?php
                                        echo $tipo === "despesa"
                                            ? "checked"
                                            : "";
                                        ?>>

                                    <label for="tipo-despesa">
                                        Despesa
                                    </label>

                                </div>

                            </div>

                        </div>


                        <!-- Categoria -->

                        <div class="form-group">

                            <label
                                class="form-label"
                                for="categoria">
                                Categoria
                            </label>


                            <select
                                name="categoria_id"
                                id="categoria"
                                class="form-select"
                                required>

                                <option value="">
                                    Selecione uma categoria
                                </option>


                                <?php
                                while (
                                    $categoria =
                                    $resultado_categorias->fetch_assoc()
                                ) {
                                ?>

                                    <option
                                        value="<?php echo $categoria["id"]; ?>"
                                        data-tipo="<?php echo $categoria["tipo"];
                                        ?>"
                                        <?php
                                        echo (
                                            (string) $categoria_id ===
                                            (string) $categoria["id"]
                                        )
                                            ? "selected"
                                            : "";
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

                        </div>


                        <!-- Descrição -->

                        <div class="form-group full">

                            <label
                                class="form-label"
                                for="descricao">
                                Descrição
                            </label>


                            <input
                                type="text"
                                name="descricao"
                                id="descricao"
                                class="form-input"
                                placeholder="Ex: Compra no mercado"
                                autocomplete="off"
                                maxlength="255"
                                value="<?php
                                        echo htmlspecialchars($descricao);
                                        ?>"
                                required>

                        </div>


                        <!-- Valor -->

                        <div class="form-group">

                            <label
                                class="form-label"
                                for="valor">
                                Valor
                            </label>


                            <div class="input-money-wrapper">

                                <span class="input-money-prefix">
                                    R$
                                </span>


                                <input
                                    type="number"
                                    name="valor"
                                    id="valor"
                                    class="form-input input-money"
                                    step="0.01"
                                    min="0.01"
                                    placeholder="0,00"
                                    value="<?php
                                            echo htmlspecialchars(
                                                (string) $valor
                                            );
                                            ?>"
                                    required>

                            </div>

                        </div>


                        <!-- Data -->

                        <div class="form-group">

                            <label
                                class="form-label"
                                for="data">
                                Data
                            </label>


                            <input
                                type="date"
                                name="data_transacao"
                                id="data"
                                class="form-input"
                                value="<?php
                                        echo htmlspecialchars(
                                            $data_transacao
                                        );
                                        ?>"
                                required>

                        </div>

                    </div>


                    <!-- Rodapé -->

                    <div class="form-footer">

                        <a
                            href="transacoes.php"
                            class="btn-secondary">
                            Cancelar
                        </a>


                        <button
                            type="submit"
                            class="btn-primary">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">

                                <path d="M12 5v14" />

                                <path d="M5 12h14" />

                            </svg>

                            Cadastrar transação

                        </button>

                    </div>

                </form>

            </section>

        </main>

    </div>


    <!-- =========================================
     SCRIPT DO PERFIL
========================================== -->

    <script>
        const profileButton =
            document.getElementById("profileButton");

        const profileDropdown =
            document.getElementById("profileDropdown");

        const profileWrapper =
            document.querySelector(".profile-wrapper");


        profileButton.addEventListener(
            "click",
            function(event) {

                event.stopPropagation();

                const aberto =
                    profileWrapper.classList.toggle("open");

                profileButton.setAttribute(
                    "aria-expanded",
                    aberto
                );
            }
        );


        document.addEventListener(
            "click",
            function() {

                profileWrapper.classList.remove("open");

                profileButton.setAttribute(
                    "aria-expanded",
                    "false"
                );
            }
        );


        profileDropdown.addEventListener(
            "click",
            function(event) {

                event.stopPropagation();

            }
        );


        const tipoReceita = document.getElementById("tipo-receita");
        const tipoDespesa = document.getElementById("tipo-despesa");

        const categoria = document.getElementById("categoria");


        function atualizarCategorias() {

            const tipoSelecionado = document.querySelector(
                'input[name="tipo"]:checked'
            );

            if (!tipoSelecionado) {
                return;
            }

            const tipo = tipoSelecionado.value;


            Array.from(categoria.options).forEach(function(option) {

                if (option.value === "") {
                    option.style.display = "";
                    return;
                }

                if (option.dataset.tipo === tipo) {
                    option.style.display = "";
                } else {
                    option.style.display = "none";
                }

            });


            categoria.value = "";

        }


        tipoReceita.addEventListener(
            "change",
            atualizarCategorias
        );

        tipoDespesa.addEventListener(
            "change",
            atualizarCategorias
        );
    </script>


</body>

</html>