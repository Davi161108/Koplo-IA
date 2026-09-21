<?php

require_once "protecao.php";
require_once "conexao.php";

$usuario_id = $_SESSION["usuario_id"];
$usuario_nome = $_SESSION["usuario_nome"];

/*
|--------------------------------------------------------------------------
| FILTROS
|--------------------------------------------------------------------------
*/

$busca = trim($_GET["busca"] ?? "");
$tipo = $_GET["tipo"] ?? "";
$categoria_id = $_GET["categoria_id"] ?? "";
$data_inicio = $_GET["data_inicio"] ?? "";
$data_fim = $_GET["data_fim"] ?? "";

/*
|--------------------------------------------------------------------------
| CATEGORIAS
|--------------------------------------------------------------------------
*/

$sql_categorias = "SELECT id, nome
                   FROM categorias
                   ORDER BY nome ASC";

$resultado_categorias = $conexao->query($sql_categorias);

/*
|--------------------------------------------------------------------------
| BUSCA DAS TRANSAÇÕES
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            transacoes.id,
            categorias.nome AS categoria,
            transacoes.descricao,
            transacoes.valor,
            transacoes.tipo,
            transacoes.data_transacao
        FROM transacoes
        INNER JOIN categorias
            ON transacoes.categoria_id = categorias.id
        WHERE transacoes.usuario_id = ?";

$tipos_parametros = "i";
$parametros = [$usuario_id];

/*
|--------------------------------------------------------------------------
| PESQUISA
|--------------------------------------------------------------------------
*/

if ($busca !== "") {

    $sql .= " AND transacoes.descricao LIKE ?";

    $tipos_parametros .= "s";
    $parametros[] = "%" . $busca . "%";
}

/*
|--------------------------------------------------------------------------
| TIPO
|--------------------------------------------------------------------------
*/

if ($tipo === "receita" || $tipo === "despesa") {

    $sql .= " AND transacoes.tipo = ?";

    $tipos_parametros .= "s";
    $parametros[] = $tipo;
}

/*
|--------------------------------------------------------------------------
| CATEGORIA
|--------------------------------------------------------------------------
*/

if ($categoria_id !== "" && ctype_digit($categoria_id)) {

    $sql .= " AND transacoes.categoria_id = ?";

    $tipos_parametros .= "i";
    $parametros[] = (int) $categoria_id;
}

/*
|--------------------------------------------------------------------------
| DATA INICIAL
|--------------------------------------------------------------------------
*/

if ($data_inicio !== "") {

    $sql .= " AND transacoes.data_transacao >= ?";

    $tipos_parametros .= "s";
    $parametros[] = $data_inicio;
}

/*
|--------------------------------------------------------------------------
| DATA FINAL
|--------------------------------------------------------------------------
*/

if ($data_fim !== "") {

    $sql .= " AND transacoes.data_transacao <= ?";

    $tipos_parametros .= "s";
    $parametros[] = $data_fim;
}

$sql .= " ORDER BY
            transacoes.data_transacao DESC,
            transacoes.id DESC";

$stmt = $conexao->prepare($sql);

$stmt->bind_param(
    $tipos_parametros,
    ...$parametros
);

$stmt->execute();

$resultado = $stmt->get_result();

$total_transacoes = $resultado->num_rows;

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Transações - Koplo</title>

    <link
        rel="stylesheet"
        href="dashboard.css"
    >

    <style>

        /* =========================================
           CABEÇALHO DA PÁGINA
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

            margin-bottom: 5px;

            font-size: 25px;

            font-weight: 700;

            line-height: 1.2;

            color: #17212b;

            letter-spacing: -0.4px;

        }

        .page-subtitle {

            font-size: 13px;

            line-height: 1.5;

            color: #87949c;

        }

        /* =========================================
           BOTÃO PRINCIPAL
        ========================================= */

        .btn-primary {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            flex-shrink: 0;

            height: 38px;

            padding: 0 14px;

            border: 1px solid #31b984;

            border-radius: 7px;

            background: #31b984;

            color: #ffffff;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;

            transition:
                background 0.2s ease,
                border-color 0.2s ease,
                transform 0.2s ease;

        }

        .btn-primary svg {

            width: 16px;

            height: 16px;

            stroke: currentColor;

        }

        .btn-primary:hover {

            background: #29a978;

            border-color: #29a978;

        }

        .btn-primary:active {

            transform: translateY(1px);

        }

        /* =========================================
           CARD DE TRANSAÇÕES
        ========================================= */

        .transactions-card {

            overflow: hidden;

            background: #ffffff;

            border: 1px solid #e8edef;

            border-radius: 11px;

            box-shadow:
                0 2px 8px rgba(22, 35, 43, 0.025);

        }

        /* =========================================
           CABEÇALHO DO CARD
        ========================================= */

        .transactions-card-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            min-height: 69px;

            padding: 0 22px;

            border-bottom: 1px solid #edf0f1;

        }

        .transactions-card-title {

            display: flex;

            align-items: center;

            gap: 11px;

        }

        .transactions-card-icon {

            display: flex;

            align-items: center;

            justify-content: center;

            width: 32px;

            height: 32px;

            flex-shrink: 0;

            border-radius: 7px;

            background: #e9f7f2;

            color: #249b73;

        }

        .transactions-card-icon svg {

            width: 17px;

            height: 17px;

            stroke: currentColor;

        }

        .transactions-card-heading {

            font-size: 13px;

            font-weight: 650;

            color: #26343d;

        }

        .transactions-count {

            font-size: 11px;

            color: #98a3a9;

        }

        /* =========================================
           FILTROS
        ========================================= */

        .filters {

            padding: 18px 22px;

            background: #fafbfb;

            border-bottom: 1px solid #edf0f1;

        }

        .filters-form {

            display: grid;

            grid-template-columns:
                minmax(190px, 1.5fr)
                minmax(130px, 1fr)
                minmax(150px, 1fr)
                minmax(135px, 1fr)
                minmax(135px, 1fr)
                auto;

            gap: 12px;

            align-items: end;

        }

        .filter-field {

            min-width: 0;

        }

        .filter-label {

            display: block;

            margin-bottom: 6px;

            color: #687780;

            font-size: 11px;

            font-weight: 650;

        }

        .filter-input,
        .filter-select {

            width: 100%;

            height: 40px;

            padding: 0 11px;

            border: 1px solid #dfe5e7;

            border-radius: 7px;

            background: #ffffff;

            color: #33434c;

            font-family: inherit;

            font-size: 13px;

            outline: none;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;

        }

        .filter-input:hover,
        .filter-select:hover {

            border-color: #cbd5d9;

        }

        .filter-input:focus,
        .filter-select:focus {

            border-color: #31b984;

            box-shadow:
                0 0 0 3px rgba(49, 185, 132, 0.09);

        }

        .filter-input::placeholder {

            color: #a9b2b7;

        }

        .filter-select {

            cursor: pointer;

        }

        .filter-actions {

            display: flex;

            align-items: center;

            gap: 7px;

        }

        .filter-button,
        .clear-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            height: 40px;

            padding: 0 13px;

            border-radius: 7px;

            font-family: inherit;

            font-size: 12px;

            font-weight: 650;

            text-decoration: none;

            cursor: pointer;

            white-space: nowrap;

            transition:
                background 0.2s ease,
                border-color 0.2s ease;

        }

        .filter-button {

            border: 1px solid #31b984;

            background: #31b984;

            color: #ffffff;

        }

        .filter-button:hover {

            background: #29a978;

            border-color: #29a978;

        }

        .clear-button {

            border: 1px solid #dfe5e7;

            background: #ffffff;

            color: #687780;

        }

        .clear-button:hover {

            background: #f5f7f8;

            border-color: #cbd5d9;

        }

        /* =========================================
           TABELA
        ========================================= */

        .table-wrapper {

            width: 100%;

            overflow-x: auto;

        }

        .transactions-table {

            width: 100%;

            min-width: 760px;

            border-collapse: collapse;

        }

        .transactions-table th {

            height: 47px;

            padding: 0 22px;

            background: #fafbfb;

            border-bottom: 1px solid #edf0f1;

            color: #89969e;

            font-size: 10px;

            font-weight: 700;

            text-align: left;

            text-transform: uppercase;

            letter-spacing: 0.55px;

            white-space: nowrap;

        }

        .transactions-table td {

            height: 68px;

            padding: 0 22px;

            border-bottom: 1px solid #f0f2f3;

            color: #53636d;

            font-size: 12px;

            vertical-align: middle;

        }

        .transactions-table tbody tr:last-child td {

            border-bottom: none;

        }

        .transactions-table tbody tr {

            transition: background 0.15s ease;

        }

        .transactions-table tbody tr:hover {

            background: #fcfdfd;

        }

        /* =========================================
           CATEGORIA
        ========================================= */

        .category-cell {

            display: flex;

            align-items: center;

            gap: 10px;

            min-width: 145px;

        }

        .category-icon {

            display: flex;

            align-items: center;

            justify-content: center;

            width: 31px;

            height: 31px;

            flex-shrink: 0;

            border-radius: 8px;

            background: #f1f5f4;

            color: #61736e;

        }

        .category-icon svg {

            width: 15px;

            height: 15px;

            stroke: currentColor;

        }

        .category-name {

            color: #3f4f59;

            font-weight: 550;

            white-space: nowrap;

        }

        /* =========================================
           DESCRIÇÃO
        ========================================= */

        .description {

            max-width: 270px;

            overflow: hidden;

            color: #53636d;

            white-space: nowrap;

            text-overflow: ellipsis;

        }

        /* =========================================
           TIPO
        ========================================= */

        .type-badge {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-width: 68px;

            height: 25px;

            padding: 0 9px;

            border-radius: 6px;

            font-size: 10px;

            font-weight: 650;

            white-space: nowrap;

        }

        .type-badge.receita {

            background: #e8f7f1;

            color: #218963;

        }

        .type-badge.despesa {

            background: #fff0f0;

            color: #c75a5a;

        }

        /* =========================================
           DATA
        ========================================= */

        .transaction-date {

            color: #7d8b93;

            white-space: nowrap;

        }

        /* =========================================
           VALOR
        ========================================= */

        .transaction-value {

            font-size: 12px;

            font-weight: 650;

            white-space: nowrap;

        }

        .transaction-value.receita {

            color: #24966d;

        }

        .transaction-value.despesa {

            color: #c95d5d;

        }

        /* =========================================
           AÇÕES
        ========================================= */

        .actions {

            display: flex;

            align-items: center;

            gap: 6px;

        }

        .actions form {

            display: flex;

            margin: 0;

            padding: 0;

        }

        .action-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            width: 30px;

            height: 30px;

            border: 1px solid transparent;

            border-radius: 7px;

            cursor: pointer;

            transition:
                background 0.2s ease,
                color 0.2s ease,
                border-color 0.2s ease;

        }

        .action-button svg {

            width: 14px;

            height: 14px;

            stroke: currentColor;

        }

        .action-edit {

            background: #f3f5f6;

            color: #687982;

        }

        .action-edit:hover {

            background: #e9f7f2;

            color: #249b73;

        }

        .action-delete {

            background: #f8f3f3;

            color: #b96b6b;

            border-color: #f0e1e1;

        }

        .action-delete:hover {

            background: #fff0f0;

            color: #c65353;

            border-color: #efd2d2;

        }

        /* =========================================
           ESTADO VAZIO
        ========================================= */

        .empty-state {

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            min-height: 330px;

            padding: 40px 20px;

            text-align: center;

        }

        .empty-state-icon {

            display: flex;

            align-items: center;

            justify-content: center;

            width: 54px;

            height: 54px;

            margin-bottom: 16px;

            border-radius: 12px;

            background: #edf7f4;

            color: #31a77c;

        }

        .empty-state-icon svg {

            width: 23px;

            height: 23px;

            stroke: currentColor;

        }

        .empty-state-title {

            margin-bottom: 6px;

            color: #34434c;

            font-size: 14px;

            font-weight: 650;

        }

        .empty-state-text {

            max-width: 330px;

            margin-bottom: 18px;

            color: #89969e;

            font-size: 12px;

            line-height: 1.5;

        }

        /* =========================================
           RESPONSIVIDADE
        ========================================= */

        @media (max-width: 1200px) {

            .filters-form {

                grid-template-columns:
                    1.5fr 1fr 1fr;

            }

            .filter-actions {

                grid-column: 1 / -1;

            }

        }

        @media (max-width: 800px) {

            .page-header {

                align-items: flex-start;

            }

            .transactions-card-header {

                padding: 0 18px;

            }

            .filters {

                padding: 17px 18px;

            }

            .filters-form {

                grid-template-columns:
                    1fr 1fr;

            }

            .filter-actions {

                grid-column: 1 / -1;

            }

            .transactions-table th,
            .transactions-table td {

                padding-left: 18px;

                padding-right: 18px;

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

            .btn-primary {

                width: 100%;

            }

            .filters-form {

                grid-template-columns: 1fr;

            }

            .filter-actions {

                display: grid;

                grid-template-columns: 1fr 1fr;

            }

            .filter-button,
            .clear-button {

                width: 100%;

            }

            .transactions-card-header {

                min-height: 64px;

                padding: 0 15px;

            }

            .transactions-card-heading {

                font-size: 12px;

            }

            .transactions-count {

                display: none;

            }

            .transactions-table {

                min-width: 730px;

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
                        stroke-linejoin="round"
                    >

                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>

                        <path d="M13.7 21a2 2 0 0 1-3.4 0"/>

                    </svg>

                </div>

                <!-- Perfil -->

                <div class="profile-wrapper">

                    <button
                        class="profile"
                        type="button"
                        id="profileButton"
                        aria-expanded="false"
                        aria-haspopup="true"
                    >

                        <span class="profile-name">

                            <?php
                            echo htmlspecialchars($usuario_nome);
                            ?>

                        </span>

                        <span class="profile-avatar">

                            <?php

                            echo strtoupper(
                                substr(
                                    htmlspecialchars($usuario_nome),
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
                            stroke-linejoin="round"
                        >

                            <polyline points="6 9 12 15 18 9"/>

                        </svg>

                    </button>

                    <div
                        class="profile-dropdown"
                        id="profileDropdown"
                    >

                        <a href="login.php">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >

                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>

                                <polyline points="10 17 15 12 10 7"/>

                                <line x1="15" y1="12" x2="3" y2="12"/>

                            </svg>

                            <span>
                                Trocar usuário
                            </span>

                        </a>

                        <div class="profile-divider"></div>

                        <a
                            href="logout.php"
                            class="logout-option"
                        >

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >

                                <path d="M10 17l5-5-5-5"/>

                                <path d="M15 12H3"/>

                                <path d="M21 19V5a2 2 0 0 0-2-2h-5"/>

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
                    Minhas transações
                </h1>

                <p class="page-subtitle">
                    Visualize e gerencie todas as suas movimentações financeiras.
                </p>

            </div>

            <a
                href="transacao.php"
                class="btn-primary"
            >

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >

                    <line x1="12" y1="5" x2="12" y2="19"/>

                    <line x1="5" y1="12" x2="19" y2="12"/>

                </svg>

                Nova transação

            </a>

        </div>

        <!-- =====================================
             CARD
        ====================================== -->

        <section class="transactions-card">

            <!-- Cabeçalho -->

            <div class="transactions-card-header">

                <div class="transactions-card-title">

                    <div class="transactions-card-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >

                            <rect
                                x="3"
                                y="4"
                                width="18"
                                height="16"
                                rx="2"
                            />

                            <line
                                x1="7"
                                y1="8"
                                x2="17"
                                y2="8"
                            />

                            <line
                                x1="7"
                                y1="12"
                                x2="13"
                                y2="12"
                            />

                            <line
                                x1="7"
                                y1="16"
                                x2="10"
                                y2="16"
                            />

                        </svg>

                    </div>

                    <div>

                        <div class="transactions-card-heading">
                            Histórico financeiro
                        </div>

                    </div>

                </div>

                <span class="transactions-count">

                    <?php

                    echo $total_transacoes;

                    echo $total_transacoes == 1
                        ? " transação"
                        : " transações";

                    ?>

                </span>

            </div>

            <!-- =================================
                 FILTROS
            ================================== -->

            <div class="filters">

                <form
                    method="GET"
                    class="filters-form"
                >

                    <div class="filter-field">

                        <label
                            class="filter-label"
                            for="busca"
                        >
                            Pesquisar
                        </label>

                        <input
                            type="search"
                            id="busca"
                            name="busca"
                            class="filter-input"
                            placeholder="Buscar por descrição..."
                            value="<?php echo htmlspecialchars($busca); ?>"
                        >

                    </div>

                    <div class="filter-field">

                        <label
                            class="filter-label"
                            for="tipo"
                        >
                            Tipo
                        </label>

                        <select
                            id="tipo"
                            name="tipo"
                            class="filter-select"
                        >

                            <option value="">
                                Todos
                            </option>

                            <option
                                value="receita"
                                <?php echo $tipo === "receita" ? "selected" : ""; ?>
                            >
                                Receitas
                            </option>

                            <option
                                value="despesa"
                                <?php echo $tipo === "despesa" ? "selected" : ""; ?>
                            >
                                Despesas
                            </option>

                        </select>

                    </div>

                    <div class="filter-field">

                        <label
                            class="filter-label"
                            for="categoria_id"
                        >
                            Categoria
                        </label>

                        <select
                            id="categoria_id"
                            name="categoria_id"
                            class="filter-select"
                        >

                            <option value="">
                                Todas
                            </option>

                            <?php while ($categoria = $resultado_categorias->fetch_assoc()) { ?>

                                <option
                                    value="<?php echo $categoria["id"]; ?>"
                                    <?php
                                    echo $categoria_id == $categoria["id"]
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

                    <div class="filter-field">

                        <label
                            class="filter-label"
                            for="data_inicio"
                        >
                            De
                        </label>

                        <input
                            type="date"
                            id="data_inicio"
                            name="data_inicio"
                            class="filter-input"
                            value="<?php echo htmlspecialchars($data_inicio); ?>"
                        >

                    </div>

                    <div class="filter-field">

                        <label
                            class="filter-label"
                            for="data_fim"
                        >
                            Até
                        </label>

                        <input
                            type="date"
                            id="data_fim"
                            name="data_fim"
                            class="filter-input"
                            value="<?php echo htmlspecialchars($data_fim); ?>"
                        >

                    </div>

                    <div class="filter-actions">

                        <button
                            type="submit"
                            class="filter-button"
                        >
                            Filtrar
                        </button>

                        <a
                            href="transacoes.php"
                            class="clear-button"
                        >
                            Limpar
                        </a>

                    </div>

                </form>

            </div>

            <?php if ($total_transacoes > 0) { ?>

                <!-- =================================
                     TABELA
                ================================== -->

                <div class="table-wrapper">

                    <table class="transactions-table">

                        <thead>

                            <tr>

                                <th>
                                    Categoria
                                </th>

                                <th>
                                    Descrição
                                </th>

                                <th>
                                    Tipo
                                </th>

                                <th>
                                    Data
                                </th>

                                <th>
                                    Valor
                                </th>

                                <th>
                                    Ações
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php while ($transacao = $resultado->fetch_assoc()) { ?>

                            <tr>

                                <!-- Categoria -->

                                <td>

                                    <div class="category-cell">

                                        <span class="category-icon">

                                            <svg
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.7"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >

                                                <circle
                                                    cx="12"
                                                    cy="12"
                                                    r="8"
                                                />

                                                <path d="M12 8v8"/>

                                                <path d="M9.5 10.5c0-1 1-1.5 2.5-1.5s2.5.5 2.5 1.5-1 1.5-2.5 1.5-2.5.5-2.5 1.5 1 1.5 2.5 1.5 2.5-.5 2.5-1.5"/>

                                            </svg>

                                        </span>

                                        <span class="category-name">

                                            <?php

                                            echo htmlspecialchars(
                                                $transacao["categoria"]
                                            );

                                            ?>

                                        </span>

                                    </div>

                                </td>

                                <!-- Descrição -->

                                <td>

                                    <div
                                        class="description"
                                        title="<?php echo htmlspecialchars($transacao["descricao"]); ?>"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $transacao["descricao"]
                                        );

                                        ?>

                                    </div>

                                </td>

                                <!-- Tipo -->

                                <td>

                                    <span
                                        class="type-badge <?php echo $transacao["tipo"]; ?>"
                                    >

                                        <?php

                                        echo $transacao["tipo"] === "receita"
                                            ? "Receita"
                                            : "Despesa";

                                        ?>

                                    </span>

                                </td>

                                <!-- Data -->

                                <td>

                                    <span class="transaction-date">

                                        <?php

                                        echo date(
                                            "d/m/Y",
                                            strtotime(
                                                $transacao["data_transacao"]
                                            )
                                        );

                                        ?>

                                    </span>

                                </td>

                                <!-- Valor -->

                                <td>

                                    <span
                                        class="transaction-value <?php echo $transacao["tipo"]; ?>"
                                    >

                                        <?php

                                        echo $transacao["tipo"] === "receita"
                                            ? "+"
                                            : "-";

                                        ?>

                                        R$

                                        <?php

                                        echo number_format(
                                            $transacao["valor"],
                                            2,
                                            ",",
                                            "."
                                        );

                                        ?>

                                    </span>

                                </td>

                                <!-- Ações -->

                                <td>

                                    <div class="actions">

                                        <!-- Editar -->

                                        <a
                                            href="editar_transacao.php?id=<?php echo $transacao["id"]; ?>"
                                            class="action-button action-edit"
                                            title="Editar transação"
                                            aria-label="Editar transação"
                                        >

                                            <svg
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.8"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >

                                                <path d="M12 20h9"/>

                                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>

                                            </svg>

                                        </a>

                                        <!-- Excluir -->

                                        <form
                                            method="POST"
                                            action="excluir_transacao.php"
                                            class="delete-form"
                                            onsubmit="return confirm('Deseja realmente excluir esta transação?');"
                                        >
                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?php echo $transacao["id"]; ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?php echo htmlspecialchars(csrf_token()); ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="action-button action-delete"
                                                title="Excluir transação"
                                                aria-label="Excluir transação"
                                            >

                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                >

                                                    <polyline points="3 6 5 6 21 6"/>

                                                    <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/>

                                                    <path d="M19 6l-1 15H6L5 6"/>

                                                    <line x1="10" y1="11" x2="10" y2="17"/>

                                                    <line x1="14" y1="11" x2="14" y2="17"/>

                                                </svg>

                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php } ?>

                        </tbody>

                    </table>

                </div>

            <?php } else { ?>

                <!-- =================================
                     ESTADO VAZIO
                ================================== -->

                <div class="empty-state">

                    <div class="empty-state-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >

                            <rect
                                x="3"
                                y="5"
                                width="18"
                                height="14"
                                rx="2"
                            />

                            <line
                                x1="7"
                                y1="9"
                                x2="17"
                                y2="9"
                            />

                            <line
                                x1="7"
                                y1="13"
                                x2="12"
                                y2="13"
                            />

                            <line
                                x1="7"
                                y1="16"
                                x2="10"
                                y2="16"
                            />

                        </svg>

                    </div>

                    <h2 class="empty-state-title">

                        <?php

                        if (
                            $busca !== "" ||
                            $tipo !== "" ||
                            $categoria_id !== "" ||
                            $data_inicio !== "" ||
                            $data_fim !== ""
                        ) {

                            echo "Nenhuma transação encontrada";

                        } else {

                            echo "Você ainda não possui transações";

                        }

                        ?>

                    </h2>

                    <p class="empty-state-text">

                        <?php

                        if (
                            $busca !== "" ||
                            $tipo !== "" ||
                            $categoria_id !== "" ||
                            $data_inicio !== "" ||
                            $data_fim !== ""
                        ) {

                            echo "Tente alterar os filtros ou limpar a pesquisa para visualizar outras transações.";

                        } else {

                            echo "Registre sua primeira movimentação financeira para começar a acompanhar suas finanças.";

                        }

                        ?>

                    </p>

                    <?php if (
                        $busca === "" &&
                        $tipo === "" &&
                        $categoria_id === "" &&
                        $data_inicio === "" &&
                        $data_fim === ""
                    ) { ?>

                        <a
                            href="transacao.php"
                            class="btn-primary"
                        >

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >

                                <line
                                    x1="12"
                                    y1="5"
                                    x2="12"
                                    y2="19"
                                />

                                <line
                                    x1="5"
                                    y1="12"
                                    x2="19"
                                    y2="12"
                                />

                            </svg>

                            Nova transação

                        </a>

                    <?php } ?>

                </div>

            <?php } ?>

        </section>

    </main>

</div>

<!-- =========================================
     SCRIPT DO PERFIL
========================================== -->

<script>

    const profileButton = document.getElementById("profileButton");
    const profileDropdown = document.getElementById("profileDropdown");
    const profileWrapper = document.querySelector(".profile-wrapper");

    profileButton.addEventListener("click", function (event) {
        event.stopPropagation();

        const aberto = profileWrapper.classList.toggle("open");

        profileButton.setAttribute(
            "aria-expanded",
            aberto
        );
    });

    document.addEventListener("click", function () {
        profileWrapper.classList.remove("open");

        profileButton.setAttribute(
            "aria-expanded",
            "false"
        );
    });

    profileDropdown.addEventListener("click", function (event) {
        event.stopPropagation();
    });

</script>

</body>

</html>

<?php

$stmt->close();

?>