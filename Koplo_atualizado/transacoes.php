```php
<?php

require_once "protecao.php";
require_once "conexao.php";

$usuario_id = $_SESSION["usuario_id"];
$usuario_nome = $_SESSION["usuario_nome"];

/*
|--------------------------------------------------------------------------
| Busca as transações do usuário logado
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

        WHERE transacoes.usuario_id = ?

        ORDER BY transacoes.data_transacao DESC,
                 transacoes.id DESC";

$stmt = $conexao->prepare($sql);

$stmt->bind_param("i", $usuario_id);

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

    <title>Minhas transações - Koplo</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7f9;
            color: #24333d;
        }

        a {
            text-decoration: none;
        }

        /* =========================================================
           SIDEBAR
        ========================================================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 240px;
            height: 100vh;
            background: #092333;
            padding: 28px 16px;
            z-index: 100;
        }

        .logo {
            padding: 0 14px;
            margin-bottom: 42px;
        }

        .logo h1 {
            color: #ffffff;
            font-size: 25px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .logo span {
            color: #31c48d;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            height: 44px;
            padding: 0 13px;
            border-radius: 8px;
            color: #9eafb9;
            font-size: 13px;
            font-weight: 500;
            transition:
                background 0.2s ease,
                color 0.2s ease;
        }

        .menu a svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .menu a:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
        }

        .menu a.active {
            background: #12394b;
            color: #ffffff;
        }

        .menu a.active svg {
            color: #31c48d;
        }

        /* =========================================================
           CONTEÚDO
        ========================================================= */

        .main {
            margin-left: 240px;
            min-height: 100vh;
        }

        /* =========================================================
           TOPBAR
        ========================================================= */

        .topbar {
            height: 72px;
            background: #ffffff;
            border-bottom: 1px solid #e9edef;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 34px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification-button {
            width: 34px;
            height: 34px;
            border: none;
            background: transparent;
            color: #71818b;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border-radius: 7px;
            transition: background 0.2s ease;
        }

        .notification-button:hover {
            background: #f2f5f6;
        }

        .notification-button svg {
            width: 18px;
            height: 18px;
        }

        /* =========================================================
           PERFIL
        ========================================================= */

        .profile-wrapper {
            position: relative;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 9px;
            background: transparent;
            border: none;
            padding: 3px 5px;
            cursor: pointer;
            border-radius: 8px;
            font-family: inherit;
            transition: background 0.2s ease;
        }

        .profile:hover {
            background: #f1f4f5;
        }

        .profile-name {
            color: #42515b;
            font-size: 13px;
            font-weight: 600;
        }

        .profile-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #dceee7;
            color: #157553;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .profile-arrow {
            width: 13px;
            height: 13px;
            color: #81909a;
            transition: transform 0.2s ease;
        }

        .profile-wrapper.open .profile-arrow {
            transform: rotate(180deg);
        }

        .profile-dropdown {
            position: absolute;
            top: calc(100% + 9px);
            right: 0;
            width: 210px;
            background: #ffffff;
            border: 1px solid #e6ebed;
            border-radius: 9px;
            padding: 6px;
            box-shadow: 0 10px 30px rgba(20, 35, 45, 0.10);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-5px);
            transition:
                opacity 0.18s ease,
                transform 0.18s ease,
                visibility 0.18s ease;
            z-index: 1000;
        }

        .profile-wrapper.open .profile-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-dropdown a {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 10px 11px;
            border-radius: 7px;
            color: #53636d;
            font-size: 12px;
            font-weight: 500;
            transition:
                background 0.18s ease,
                color 0.18s ease;
        }

        .profile-dropdown a:hover {
            background: #f4f7f7;
            color: #26343d;
        }

        .profile-dropdown svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            color: #71818b;
        }

        .profile-dropdown a:hover svg {
            color: #31a77c;
        }

        .dropdown-divider {
            height: 1px;
            background: #edf0f1;
            margin: 4px 6px;
        }

        .profile-dropdown .logout-option {
            color: #d45d5d;
        }

        .profile-dropdown .logout-option svg {
            color: #d45d5d;
        }

        .profile-dropdown .logout-option:hover {
            background: #fff3f3;
            color: #c84d4d;
        }

        /* =========================================================
           CONTAINER
        ========================================================= */

        .container {
            padding: 34px;
            max-width: 1500px;
        }

        /* =========================================================
           CABEÇALHO DA PÁGINA
        ========================================================= */

        .page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 28px;
        }

        .page-title h2 {
            font-size: 25px;
            color: #26343d;
            font-weight: 700;
            margin-bottom: 7px;
            letter-spacing: -0.3px;
        }

        .page-title p {
            color: #82909a;
            font-size: 13px;
        }

        .new-transaction {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #31c48d;
            color: #ffffff;
            padding: 11px 17px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            transition:
                background 0.2s ease,
                transform 0.2s ease;
        }

        .new-transaction:hover {
            background: #28b17d;
            transform: translateY(-1px);
        }

        .new-transaction svg {
            width: 16px;
            height: 16px;
        }

        /* =========================================================
           CARD DA TABELA
        ========================================================= */

        .transactions-card {
            background: #ffffff;
            border: 1px solid #e8edef;
            border-radius: 11px;
            overflow: hidden;
        }

        .card-header {
            min-height: 68px;
            padding: 0 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #edf0f1;
        }

        .card-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header-icon {
            width: 32px;
            height: 32px;
            border-radius: 7px;
            background: #e9f7f2;
            color: #249b73;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-header-icon svg {
            width: 17px;
            height: 17px;
        }

        .card-header h3 {
            font-size: 14px;
            color: #34444e;
            font-weight: 600;
        }

        .transaction-count {
            color: #8a979f;
            font-size: 11px;
        }

        /* =========================================================
           TABELA
        ========================================================= */

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
        }

        .transactions-table th {
            height: 47px;
            padding: 0 20px;
            background: #fafbfb;
            color: #89969e;
            font-size: 10px;
            font-weight: 600;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            border-bottom: 1px solid #edf0f1;
            white-space: nowrap;
        }

        .transactions-table td {
            height: 68px;
            padding: 0 20px;
            border-bottom: 1px solid #f0f2f3;
            color: #53636d;
            font-size: 12px;
            white-space: nowrap;
        }

        .transactions-table tbody tr {
            transition: background 0.15s ease;
        }

        .transactions-table tbody tr:hover {
            background: #fafcfc;
        }

        .transactions-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* =========================================================
           CATEGORIA
        ========================================================= */

        .category-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .category-icon {
            width: 31px;
            height: 31px;
            border-radius: 7px;
            background: #f1f5f4;
            color: #61736e;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .category-icon svg {
            width: 15px;
            height: 15px;
        }

        .category-name {
            color: #394a54;
            font-weight: 600;
        }

        /* =========================================================
           DESCRIÇÃO
        ========================================================= */

        .description {
            color: #65747d;
            max-width: 240px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* =========================================================
           TIPO
        ========================================================= */

        .transaction-type {
            display: inline-flex;
            align-items: center;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
        }

        .transaction-type.receita {
            background: #e8f7f1;
            color: #218963;
        }

        .transaction-type.despesa {
            background: #fff0f0;
            color: #c75a5a;
        }

        /* =========================================================
           VALOR
        ========================================================= */

        .transaction-value {
            font-weight: 700;
        }

        .value-receita {
            color: #24966d;
        }

        .value-despesa {
            color: #c95d5d;
        }

        /* =========================================================
           AÇÕES
        ========================================================= */

        .actions {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .action-button {
            width: 31px;
            height: 31px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition:
                background 0.18s ease,
                color 0.18s ease;
        }

        .action-button svg {
            width: 15px;
            height: 15px;
        }

        .action-edit {
            color: #687982;
            background: #f3f5f6;
        }

        .action-edit:hover {
            background: #e8f6f1;
            color: #24966d;
        }

        .action-delete {
            color: #bd6868;
            background: #faf1f1;
        }

        .action-delete:hover {
            background: #ffe7e7;
            color: #c84d4d;
        }

        /* =========================================================
           ESTADO VAZIO
        ========================================================= */

        .empty-state {
            padding: 65px 20px;
            text-align: center;
        }

        .empty-icon {
            width: 54px;
            height: 54px;
            margin: 0 auto 17px;
            border-radius: 12px;
            background: #edf7f4;
            color: #31a77c;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .empty-icon svg {
            width: 25px;
            height: 25px;
        }

        .empty-state h3 {
            color: #3c4c55;
            font-size: 15px;
            margin-bottom: 7px;
        }

        .empty-state p {
            color: #8b979e;
            font-size: 12px;
            margin-bottom: 20px;
        }

        /* =========================================================
           RESPONSIVIDADE
        ========================================================= */

        @media (max-width: 1050px) {

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;
            }

            .container {
                padding: 28px;
            }

            .transactions-table {
                min-width: 720px;
            }

        }

        @media (max-width: 800px) {

            .sidebar {
                width: 70px;
                padding: 25px 10px;
            }

            .logo {
                padding: 0;
                text-align: center;
                margin-bottom: 35px;
            }

            .logo h1 {
                font-size: 19px;
            }

            .menu a {
                justify-content: center;
                padding: 0;
            }

            .menu a span {
                display: none;
            }

            .menu a svg {
                width: 19px;
                height: 19px;
            }

            .main {
                margin-left: 70px;
            }

            .topbar {
                padding: 0 22px;
            }

            .container {
                padding: 25px 20px;
            }

        }

        @media (max-width: 600px) {

            .topbar {
                height: 64px;
            }

            .profile-name {
                display: none;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .new-transaction {
                width: 100%;
                justify-content: center;
            }

            .container {
                padding: 22px 14px;
            }

            .card-header {
                padding: 0 16px;
            }

            .transaction-count {
                display: none;
            }

        }

    </style>

</head>

<body>

    <!-- =========================================================
         SIDEBAR
    ========================================================= -->

    <aside class="sidebar">

        <div class="logo">
            <h1>Koplo<span>.</span></h1>
        </div>

        <nav class="menu">

            <a href="dashboard.php">

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                </svg>

                <span>Dashboard</span>

            </a>

            <a href="transacoes.php" class="active">

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M4 5h16"></path>
                    <path d="M4 9h16"></path>
                    <path d="M4 13h10"></path>
                    <path d="M4 17h8"></path>
                    <path d="M17 14v6"></path>
                    <path d="M14 17h6"></path>
                </svg>

                <span>Transações</span>

            </a>

            <a href="transacao.php">

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M12 8v8"></path>
                    <path d="M8 12h8"></path>
                </svg>

                <span>Nova transação</span>

            </a>

            <a href="#">

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 18.3 6.4 21.2l1.1-6.2L3 9.6l6.2-.9L12 3z"></path>
                </svg>

                <span>Metas</span>

            </a>

            <a href="#">

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M12 3a9 9 0 1 0 9 9"></path>
                    <path d="M12 7v5l3 2"></path>
                    <path d="M17 3v5h5"></path>
                </svg>

                <span>Koplo IA</span>

            </a>

            <a href="#">

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"></path>
                    <path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-1.8 1.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V20h-2.5v-.1a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1-1.8-1.8.1-.1A1.7 1.7 0 0 0 8.1 15a1.7 1.7 0 0 0-1.5-1H6v-2.5h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1L9 6.7l.1.1a1.7 1.7 0 0 0 1.9.3 1.7 1.7 0 0 0 1-1.5V5h2.5v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1 1.8 1.8-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.5 1h.1V13.5h-.1a1.7 1.7 0 0 0-1.5 1.5z"></path>
                </svg>

                <span>Configurações</span>

            </a>

        </nav>

    </aside>


    <!-- =========================================================
         CONTEÚDO PRINCIPAL
    ========================================================= -->

    <main class="main">

        <!-- TOPBAR -->

        <header class="topbar">

            <div class="topbar-right">

                <button
                    type="button"
                    class="notification-button"
                    aria-label="Notificações"
                >

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
                        <path d="M10 21h4"></path>
                    </svg>

                </button>


                <!-- PERFIL -->

                <div class="profile-wrapper">

                    <button
                        type="button"
                        class="profile"
                        id="profileButton"
                        aria-expanded="false"
                    >

                        <span class="profile-name">
                            <?php echo htmlspecialchars($usuario_nome); ?>
                        </span>

                        <div class="profile-avatar">

                            <?php
                            echo strtoupper(
                                substr($usuario_nome, 0, 1)
                            );
                            ?>

                        </div>

                        <svg
                            class="profile-arrow"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="m6 9 6 6 6-6"></path>
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
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                                <path d="M10 17l5-5-5-5"></path>
                                <path d="M15 12H3"></path>
                            </svg>

                            <span>Trocar usuário/cadastro</span>

                        </a>


                        <div class="dropdown-divider"></div>


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
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <path d="M16 17l5-5-5-5"></path>
                                <path d="M21 12H9"></path>
                            </svg>

                            <span>Sair</span>

                        </a>

                    </div>

                </div>

            </div>

        </header>


        <!-- CONTAINER -->

        <div class="container">


            <!-- CABEÇALHO -->

            <div class="page-header">

                <div class="page-title">

                    <h2>Minhas transações</h2>

                    <p>
                        Visualize e gerencie todas as suas movimentações financeiras.
                    </p>

                </div>


                <a
                    href="transacao.php"
                    class="new-transaction"
                >

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M12 5v14"></path>
                        <path d="M5 12h14"></path>
                    </svg>

                    Nova transação

                </a>

            </div>


            <!-- TABELA -->

            <section class="transactions-card">


                <div class="card-header">

                    <div class="card-header-left">

                        <div class="card-header-icon">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M4 5h16"></path>
                                <path d="M4 9h16"></path>
                                <path d="M4 13h10"></path>
                                <path d="M4 17h8"></path>
                            </svg>

                        </div>

                        <h3>Histórico financeiro</h3>

                    </div>


                    <span class="transaction-count">

                        <?php echo $total_transacoes; ?>

                        <?php
                        echo $total_transacoes == 1
                            ? " transação"
                            : " transações";
                        ?>

                    </span>

                </div>


                <?php if ($resultado->num_rows > 0) { ?>


                    <div class="table-wrapper">

                        <table class="transactions-table">

                            <thead>

                                <tr>

                                    <th>Categoria</th>

                                    <th>Descrição</th>

                                    <th>Tipo</th>

                                    <th>Data</th>

                                    <th>Valor</th>

                                    <th>Ações</th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php while ($transacao = $resultado->fetch_assoc()) { ?>


                                    <tr>


                                        <!-- CATEGORIA -->

                                        <td>

                                            <div class="category-cell">

                                                <div class="category-icon">

                                                    <svg
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="1.8"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    >
                                                        <circle
                                                            cx="12"
                                                            cy="12"
                                                            r="8"
                                                        ></circle>

                                                        <path d="M12 8v8"></path>

                                                        <path d="M9.5 10.5c0-1 1-1.5 2.5-1.5s2.5.5 2.5 1.5-1 1.5-2.5 1.5-2.5.5-2.5 1.5 1 1.5 2.5 1.5 2.5-.5 2.5-1.5"></path>
                                                    </svg>

                                                </div>

                                                <span class="category-name">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $transacao["categoria"]
                                                    );
                                                    ?>

                                                </span>

                                            </div>

                                        </td>


                                        <!-- DESCRIÇÃO -->

                                        <td>

                                            <div class="description">

                                                <?php
                                                echo htmlspecialchars(
                                                    $transacao["descricao"]
                                                );
                                                ?>

                                            </div>

                                        </td>


                                        <!-- TIPO -->

                                        <td>

                                            <?php if ($transacao["tipo"] === "receita") { ?>

                                                <span class="transaction-type receita">
                                                    Receita
                                                </span>

                                            <?php } else { ?>

                                                <span class="transaction-type despesa">
                                                    Despesa
                                                </span>

                                            <?php } ?>

                                        </td>


                                        <!-- DATA -->

                                        <td>

                                            <?php

                                            echo date(
                                                "d/m/Y",
                                                strtotime(
                                                    $transacao["data_transacao"]
                                                )
                                            );

                                            ?>

                                        </td>


                                        <!-- VALOR -->

                                        <td>

                                            <span
                                                class="
                                                    transaction-value
                                                    <?php
                                                    echo $transacao["tipo"] === "receita"
                                                        ? "value-receita"
                                                        : "value-despesa";
                                                    ?>
                                                "
                                            >

                                                <?php

                                                echo $transacao["tipo"] === "receita"
                                                    ? "+ "
                                                    : "- ";

                                                echo "R$ " . number_format(
                                                    $transacao["valor"],
                                                    2,
                                                    ",",
                                                    "."
                                                );

                                                ?>

                                            </span>

                                        </td>


                                        <!-- AÇÕES -->

                                        <td>

                                            <div class="actions">


                                                <a
                                                    href="editar_transacao.php?id=<?php echo $transacao["id"]; ?>"
                                                    class="action-button action-edit"
                                                    title="Editar transação"
                                                >

                                                    <svg
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="1.8"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    >
                                                        <path d="M12 20h9"></path>
                                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4 11.5-11.5z"></path>
                                                    </svg>

                                                </a>


                                                <a
                                                    href="excluir_transacao.php?id=<?php echo $transacao["id"]; ?>"
                                                    class="action-button action-delete"
                                                    title="Excluir transação"
                                                    onclick="return confirm('Tem certeza que deseja excluir esta transação?');"
                                                >

                                                    <svg
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="1.8"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    >
                                                        <path d="M3 6h18"></path>
                                                        <path d="M8 6V4h8v2"></path>
                                                        <path d="M19 6l-1 14H6L5 6"></path>
                                                        <path d="M10 11v5"></path>
                                                        <path d="M14 11v5"></path>
                                                    </svg>

                                                </a>


                                            </div>

                                        </td>


                                    </tr>


                                <?php } ?>


                            </tbody>

                        </table>

                    </div>


                <?php } else { ?>


                    <!-- ESTADO VAZIO -->

                    <div class="empty-state">

                        <div class="empty-icon">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M4 5h16"></path>
                                <path d="M4 9h16"></path>
                                <path d="M4 13h10"></path>
                                <path d="M4 17h8"></path>
                            </svg>

                        </div>

                        <h3>Nenhuma transação cadastrada</h3>

                        <p>
                            Comece registrando sua primeira movimentação financeira.
                        </p>

                        <a
                            href="transacao.php"
                            class="new-transaction"
                        >

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M12 5v14"></path>
                                <path d="M5 12h14"></path>
                            </svg>

                            Nova transação

                        </a>

                    </div>


                <?php } ?>


            </section>


        </div>

    </main>


    <!-- =========================================================
         JAVASCRIPT
    ========================================================= -->

    <script>

        const profileButton =
            document.getElementById("profileButton");

        const profileWrapper =
            document.querySelector(".profile-wrapper");


        profileButton.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();

                const aberto =
                    profileWrapper.classList.toggle("open");

                profileButton.setAttribute(
                    "aria-expanded",
                    aberto ? "true" : "false"
                );

            }
        );


        document.addEventListener(
            "click",
            function (event) {

                if (!profileWrapper.contains(event.target)) {

                    profileWrapper.classList.remove("open");

                    profileButton.setAttribute(
                        "aria-expanded",
                        "false"
                    );

                }

            }
        );

    </script>


</body>

</html>

<?php

$stmt->close();

?>
```
