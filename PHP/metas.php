<?php

require_once "conexao.php";
require_once "protecao.php";

if (!isset($_SESSION["usuario_id"])) {
    die("Você precisa estar logado para acessar esta página.");
}

$usuario_id = $_SESSION["usuario_id"];
$usuario_nome = $_SESSION["usuario_nome"] ?? "Usuário";


// Busca as metas do usuário
$sql = "SELECT *
        FROM metas
        WHERE usuario_id = ?
        ORDER BY data_limite ASC";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();

$resultado_metas = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Metas - Koplo</title>
    <link rel="stylesheet" href="dashboard.css">


    <style>
        /* METAS - CONTEÚDO */

        .meta-container {
            padding: 0;
            max-width: 1500px;
        }


        /* CABEÇALHO */

        .page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 28px;
        }

        .page-title h1 {
            font-size: 25px;
            color: #26343d;
            font-weight: 700;
            margin: 0 0 7px;
            letter-spacing: -0.3px;
        }

        .page-title p {
            color: #82909a;
            font-size: 13px;
            margin: 0;
        }


        /* BOTÃO NOVA META */

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            background: #31c48d;
            color: #ffffff;

            padding: 11px 17px;
            border-radius: 7px;

            font-size: 12px;
            font-weight: 600;

            text-decoration: none;

            transition:
                background 0.2s ease,
                transform 0.2s ease;
        }

        .btn:hover {
            background: #28b17d;
            transform: translateY(-1px);
        }


        /* CARD DAS METAS */

        .section {
            background: #ffffff;
            border: 1px solid #e8edef;
            border-radius: 11px;
            overflow: hidden;
        }


        /*  TABELA */

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
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

        .table td {
            height: 68px;
            padding: 0 20px;

            border-bottom: 1px solid #f0f2f3;

            color: #53636d;
            font-size: 12px;

            white-space: nowrap;
        }

        .table tbody tr {
            transition: background 0.15s ease;
        }

        .table tbody tr:hover {
            background: #fafcfc;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }


        /* NOME DA META */

        .meta-nome {
            color: #394a54;
            font-weight: 600;
        }


        /* VALORES */

        .table td:nth-child(2),
        .table td:nth-child(3) {
            color: #53636d;
            font-weight: 500;
        }


        /* PROGRESSO */

        .progress-container {
            width: 150px;
            height: 8px;

            background: #e9edef;

            border-radius: 20px;
            overflow: hidden;

            margin-bottom: 6px;
        }

        .progress-bar {
            height: 100%;

            background: #31c48d;

            border-radius: 20px;

            transition: width 0.3s ease;
        }

        .progress-text {
            color: #82909a;
            font-size: 11px;
        }


        /* AÇÕES */

        .acoes {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-editar,
        .btn-excluir {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            padding: 7px 10px;

            border-radius: 7px;

            font-size: 11px;
            font-weight: 600;

            text-decoration: none;

            transition:
                background 0.18s ease,
                color 0.18s ease;
        }


        /* EDITAR */

        .btn-editar {
            color: #687982;
            background: #f3f5f6;
        }

        .btn-editar:hover {
            background: #e8f6f1;
            color: #24966d;
        }


        /* EXCLUIR */

        .btn-excluir {
            color: #bd6868;
            background: #faf1f1;
        }

        .btn-excluir:hover {
            background: #ffe7e7;
            color: #c84d4d;
        }


        /* ESTADO VAZIO */

        .empty {
            padding: 65px 20px;
            text-align: center;
        }

        .empty h3 {
            color: #3c4c55;
            font-size: 15px;
            margin: 0 0 7px;
        }

        .empty p {
            color: #8b979e;
            font-size: 12px;
            margin: 0 0 20px;
        }


        /* RESPONSIVIDADE */

        @media (max-width: 1050px) {

            .page-header {
                gap: 15px;
            }

            .table {
                min-width: 760px;
            }

            .section {
                overflow-x: auto;
            }

        }


        @media (max-width: 800px) {

            .page-header {
                align-items: flex-start;
            }

        }


        @media (max-width: 600px) {

            .page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                width: 100%;
            }

            .section {
                overflow-x: auto;
            }

            .table {
                min-width: 700px;
            }

            .empty {
                padding: 50px 20px;
            }

        }
    </style>



</head>


<body>


    <div class="layout">


        <!-- SIDEBAR -->

        <?php include "sidebar.php"; ?>


        <!-- CONTEÚDO -->

        <main class="content">


            <div class="topbar">

                <div class="topbar-right">

                    <svg
                        class="notification"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
                        <path d="M10 21h4"></path>
                    </svg>

                    <!-- PERFIL -->

                    <div class="profile-wrapper">

                        <button
                            type="button"
                            class="profile"
                            id="profileButton"
                            aria-expanded="false">

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

                            <!-- Seta -->

                            <svg
                                class="profile-arrow"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6"></path>
                            </svg>

                        </button>

                        <!-- DROPDOWN -->

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
                                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                                    <path d="M10 17l5-5-5-5"></path>
                                    <path d="M15 12H3"></path>
                                </svg>

                                <span>
                                    Trocar usuário/cadastro
                                </span>

                            </a>

                            <div class="dropdown-divider"></div>

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
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <path d="M16 17l5-5-5-5"></path>
                                    <path d="M21 12H9"></path>
                                </svg>

                                <span>
                                    Sair
                                </span>

                            </a>

                        </div>

                    </div>

                </div>

            </div>



            <!-- CABEÇALHO -->

            <div class="page-header">

                <div>

                    <h1>Metas financeiras</h1>

                    <p>
                        Acompanhe seus objetivos financeiros.
                    </p>

                </div>


                <a href="nova_meta.php" class="btn">
                    + Nova meta
                </a>

            </div>



            <!-- METAS -->

            <div class="section">


                <?php if ($resultado_metas->num_rows > 0): ?>


                    <table class="table">

                        <thead>

                            <tr>

                                <th>Meta</th>

                                <th>Valor objetivo</th>

                                <th>Valor atual</th>

                                <th>Progresso</th>

                                <th>Data limite</th>

                                <th>Ações</th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php while ($meta = $resultado_metas->fetch_assoc()): ?>


                                <?php

                                if ($meta["valor_objetivo"] > 0) {

                                    $progresso =
                                        ($meta["valor_atual"] / $meta["valor_objetivo"]) * 100;
                                } else {

                                    $progresso = 0;
                                }


                                if ($progresso > 100) {
                                    $progresso = 100;
                                }

                                ?>


                                <tr>


                                    <td>

                                        <div class="meta-nome">

                                            <?php
                                            echo htmlspecialchars($meta["nome"]);
                                            ?>

                                        </div>

                                    </td>


                                    <td>

                                        R$

                                        <?php
                                        echo number_format(
                                            $meta["valor_objetivo"],
                                            2,
                                            ",",
                                            "."
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        R$

                                        <?php
                                        echo number_format(
                                            $meta["valor_atual"],
                                            2,
                                            ",",
                                            "."
                                        );
                                        ?>

                                    </td>


                                    <td>


                                        <div class="progress-container">

                                            <div
                                                class="progress-bar"
                                                style="width: <?php echo $progresso; ?>%;"></div>

                                        </div>


                                        <div class="progress-text">

                                            <?php
                                            echo number_format(
                                                $progresso,
                                                0
                                            );
                                            ?>%

                                        </div>


                                    </td>


                                    <td>

                                        <?php

                                        if (!empty($meta["data_limite"])) {

                                            echo date(
                                                "d/m/Y",
                                                strtotime($meta["data_limite"])
                                            );
                                        } else {

                                            echo "Sem prazo";
                                        }

                                        ?>

                                    </td>


                                    <td>

                                        <div class="acoes">

                                            <a
                                                href="editar_meta.php?id=<?php echo $meta["id"]; ?>"
                                                class="btn-editar">
                                                Editar
                                            </a>


                                            <a
                                                href="excluir_meta.php?id=<?php echo $meta["id"]; ?>"
                                                class="btn-excluir">
                                                Excluir
                                            </a>

                                        </div>

                                    </td>


                                </tr>


                            <?php endwhile; ?>


                        </tbody>

                    </table>


                <?php else: ?>


                    <div class="empty">

                        <h3>Você ainda não possui metas.</h3>

                        <p>
                            Crie sua primeira meta financeira para começar.
                        </p>

                        <br>

                        <a href="nova_meta.php" class="btn">
                            Criar primeira meta
                        </a>

                    </div>


                <?php endif; ?>


            </div>


        </main>


    </div>

    <script>
        const profileButton =
            document.getElementById("profileButton");

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
                    aberto ? "true" : "false"
                );

            }
        );


        document.addEventListener(
            "click",
            function(event) {

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