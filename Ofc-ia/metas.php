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
        ORDER BY data_limite ASC
        LIMIT 4";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();

$resultado_metas = $stmt->get_result();

$em_andamento = 0;
$concluidas = 0;
$sem_progresso = 0;

$sql_grafico = "SELECT valor_atual, valor_objetivo
                FROM metas
                WHERE usuario_id = ?";

$stmt_grafico = $conexao->prepare($sql_grafico);
$stmt_grafico->bind_param("i", $usuario_id);
$stmt_grafico->execute();

$resultado_grafico = $stmt_grafico->get_result();

$total_grafico = $resultado_grafico->num_rows;

while ($meta_grafico = $resultado_grafico->fetch_assoc()) {

    if ($meta_grafico["valor_objetivo"] > 0) {

        $progresso_grafico =
            ($meta_grafico["valor_atual"] / $meta_grafico["valor_objetivo"]) * 100;
    } else {

        $progresso_grafico = 0;
    }

    if ($progresso_grafico >= 100) {

        $concluidas++;
    } elseif ($progresso_grafico > 0) {

        $em_andamento++;
    } else {

        $sem_progresso++;
    }
}

if ($total_grafico > 0) {

    $porcentagem_andamento =
        ($em_andamento / $total_grafico) * 100;

    $porcentagem_concluidas =
        ($concluidas / $total_grafico) * 100;

    $porcentagem_sem_progresso =
        ($sem_progresso / $total_grafico) * 100;
} else {

    $porcentagem_andamento = 0;
    $porcentagem_concluidas = 0;
    $porcentagem_sem_progresso = 0;
}

$sql_total = "SELECT COUNT(*) AS total
              FROM metas
              WHERE usuario_id = ?";

$stmt_total = $conexao->prepare($sql_total);
$stmt_total->bind_param("i", $usuario_id);
$stmt_total->execute();

$resultado_total = $stmt_total->get_result();
$total_metas = $resultado_total->fetch_assoc()["total"];

function escolherIconeMeta($nome)
{

    $nome = strtolower($nome);

    if (
        str_contains($nome, "viagem") ||
        str_contains($nome, "viajar") ||
        str_contains($nome, "férias") ||
        str_contains($nome, "ferias") ||
        str_contains($nome, "paris") ||
        str_contains($nome, "praia")
    ) {
        return "plane";
    }

    if (
        str_contains($nome, "comprar") ||
        str_contains($nome, "roupa") ||
        str_contains($nome, "compras")
    ) {
        return "shopping-bag";
    }

    if (
        str_contains($nome, "casa") ||
        str_contains($nome, "apartamento")
    ) {
        return "house";
    }

    if (
        str_contains($nome, "carro") ||
        str_contains($nome, "moto")
    ) {
        return "car";
    }

    if (
        str_contains($nome, "notebook") ||
        str_contains($nome, "computador") ||
        str_contains($nome, "pc")
    ) {
        return "laptop";
    }

    if (
        str_contains($nome, "celular") ||
        str_contains($nome, "iphone") ||
        str_contains($nome, "telefone")
    ) {
        return "smartphone";
    }

    if (
        str_contains($nome, "faculdade") ||
        str_contains($nome, "curso") ||
        str_contains($nome, "estudo")
    ) {
        return "graduation-cap";
    }

    if (
        str_contains($nome, "emergência") ||
        str_contains($nome, "emergencia") ||
        str_contains($nome, "reserva")
    ) {
        return "piggy-bank";
    }

    if (
        str_contains($nome, "investimento") ||
        str_contains($nome, "investir")
    ) {
        return "trending-up";
    }

    if (
        str_contains($nome, "presente")
    ) {
        return "gift";
    }

    return "target";
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Metas - Koplo</title>

    <script src="https://unpkg.com/lucide@latest"></script>
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


        /* GRID DE METAS*/

        .metas-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            padding: 20px;
        }


        /* CARD DA META*/

        .meta-card {
            background: #ffffff;
            border: 1px solid #e8edef;
            border-radius: 11px;
            padding: 20px;

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .meta-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(20, 35, 45, 0.07);
        }


        /* CABEÇALHO DO CARD*/

        .meta-card-header {
            margin-bottom: 20px;
        }

        .meta-card-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .meta-icon {
            width: 40px;
            height: 40px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #e9f7f2;
            border-radius: 9px;

            font-size: 19px;
        }

        .meta-card-title h3 {
            margin: 0 0 4px;

            color: #34444e;
            font-size: 15px;
            font-weight: 700;
        }

        .meta-card-title span {
            color: #89969e;
            font-size: 11px;
        }


        /* VALORES */

        .meta-values {
            display: flex;
            justify-content: space-between;
            gap: 20px;

            margin-bottom: 20px;
        }

        .meta-values div {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .meta-values span {
            color: #89969e;
            font-size: 10px;
        }

        .meta-values strong {
            color: #34444e;
            font-size: 14px;
        }


        /* PROGRESSO */

        .meta-progress {
            margin-bottom: 18px;
        }

        .meta-progress-top {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-bottom: 7px;
        }

        .meta-progress-top span {
            color: #89969e;
            font-size: 11px;
        }

        .meta-progress-top strong {
            color: #24966d;
            font-size: 12px;
        }

        .meta-progress .progress-container {
            width: 100%;
            height: 8px;

            margin: 0;

            background: #e9edef;
            border-radius: 20px;
            overflow: hidden;
        }

        .meta-progress .progress-bar {
            height: 100%;

            background: #31c48d;
            border-radius: 20px;
        }


        /* DATA */

        .meta-date {
            display: flex;
            justify-content: space-between;
            align-items: center;

            padding-top: 15px;
            margin-bottom: 17px;

            border-top: 1px solid #f0f2f3;
        }

        .meta-date span {
            color: #89969e;
            font-size: 11px;
        }

        .meta-date strong {
            color: #53636d;
            font-size: 11px;
        }


        /* BOTÕES */

        .meta-actions {
            display: flex;
            gap: 8px;
        }

        .meta-actions .btn-editar,
        .meta-actions .btn-excluir {
            flex: 1;
            text-align: center;
        }


        /*  LAYOUT PRINCIPAL DAS METAS*/

        .metas-layout {
            display: grid;
            grid-template-columns: 34% 66%;
            gap: 18px;
            align-items: start;
        }


        /* COLUNA ESQUERDA*/

        .metas-resumo {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }


        /* CARDS DE RESUMO */

        .resumo-card {
            background: #ffffff;
            border: 1px solid #e8edef;
            border-radius: 11px;
            padding: 20px;
        }

        .resumo-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .resumo-header h2 {
            margin: 0;
            color: #34444e;
            font-size: 15px;
            font-weight: 700;
        }

        .ver-todos {
            color: #24966d;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
        }

        .ver-todos:hover {
            color: #1b805c;
        }


        /* HISTÓRICO */

        .historico-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 11px 0;
            border-bottom: 1px solid #f0f2f3;
        }

        .historico-item:last-child {
            border-bottom: none;
        }

        .historico-info {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .historico-icon svg {
            width: 16px;
            height: 16px;
            stroke-width: 2;
        }

        .historico-nome {
            color: #53636d;
            font-size: 12px;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .historico-percentual {
            color: #24966d;
            font-size: 12px;
            font-weight: 700;
        }


        /* GRÁFICO CARD */
        .grafico-card {
            background: #ffffff;
            border: 1px solid #e8edef;
            border-radius: 11px;
            padding: 20px;
        }



        /* COLUNA DAS METAS*/

        .metas-cards {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .meta-icon svg {
            width: 19px;
            height: 19px;
            stroke-width: 2;
        }


        /* BOTÃO RESGATAR */

        .btn-resgatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            padding: 7px 10px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            color: #24966d;
            background: #e9f7f2;
            transition:
                background 0.18s ease,
                color 0.18s ease;
        }

        .btn-resgatar:hover {
            background: #d8f1e8;
            color: #1b805c;
        }


        /* AJUSTE DOS BOTÕES */

        .meta-actions .btn-editar,
        .meta-actions .btn-resgatar,
        .meta-actions .btn-excluir {
            flex: 1;
        }


        /* HISTÓRICO VAZIO */

        .historico-vazio {
            color: #89969e;
            font-size: 12px;
            margin: 10px 0;
        }


        /* GRÁFICO */

        .grafico-placeholder {
            height: auto;
            min-height: 170px;
            border-top: 1px solid #f0f2f3;
            margin-top: 5px;
            padding-top: 20px;
            display: flex;
            align-items: center;
        }


        .grafico-barras {
            width: 100%;
        }


        .grafico-item {
            margin-bottom: 18px;
        }


        .grafico-item:last-child {
            margin-bottom: 0;
        }


        .grafico-item>span {
            display: block;
            color: #687982;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 7px;
        }


        .grafico-barra {
            width: 100%;
            height: 9px;
            background: #e9edef;
            border-radius: 20px;
            overflow: hidden;
        }


        .grafico-preenchimento {
            height: 100%;
            border-radius: 20px;
        }

        .grafico-preenchimento {
            height: 100%;
            border-radius: 10px;
        }

        .grafico-preenchimento.andamento {
            background: #31c48d;
        }

        .grafico-preenchimento.concluida {
            background: #24966d;
        }

        .grafico-preenchimento.sem-progresso {
            background: #cbd3d7;
        }


        /* RESPONSIVIDADE */

        @media (max-width: 1050px) {

            .page-header {
                gap: 15px;
            }

            .section {
                overflow-x: auto;
            }


            .metas-layout {
                grid-template-columns: 1fr;
            }

            .metas-resumo {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }

        }

        @media (max-width: 800px) {

            .page-header {
                align-items: flex-start;
            }

            .metas-grid {
                grid-template-columns: 1fr;
            }

            .metas-cards {
                grid-template-columns: 1fr;
            }

            .metas-resumo {
                grid-template-columns: 1fr;
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

            <div class="metas-layout">

                <!-- COLUNA ESQUERDA -->

                <div class="metas-resumo">

                    <!-- HISTÓRICO DE CONQUISTAS -->

                    <div class="resumo-card">

                        <div class="resumo-header">

                            <h2>Histórico de conquistas</h2>

                            <?php if ($total_metas > 4): ?>

                                <a href="historico_metas.php" class="ver-todos">
                                    Ver todos →
                                </a>

                            <?php endif; ?>

                        </div>


                        <?php

                        $resultado_historico = $conexao->query(
                            "SELECT *
                                FROM metas
                                WHERE usuario_id = $usuario_id
                                ORDER BY valor_atual DESC
                                LIMIT 4"
                        );

                        ?>


                        <?php if ($resultado_historico->num_rows > 0): ?>

                            <?php while ($historico = $resultado_historico->fetch_assoc()): ?>

                                <?php
                                $icone_meta = escolherIconeMeta($historico["nome"]);

                                if ($historico["valor_objetivo"] > 0) {

                                    $historico_progresso =
                                        ($historico["valor_atual"] / $historico["valor_objetivo"]) * 100;
                                } else {

                                    $historico_progresso = 0;
                                }

                                if ($historico_progresso > 100) {
                                    $historico_progresso = 100;
                                }

                                ?>


                                <div class="historico-item">

                                    <div class="historico-info">

                                        <div class="historico-icon">
                                            <i data-lucide="<?php echo $icone_meta; ?>"></i>
                                        </div>

                                        <span class="historico-nome">
                                            <?php echo htmlspecialchars($historico["nome"]); ?>
                                        </span>

                                    </div>

                                    <span class="historico-percentual">

                                        <?php
                                        echo number_format(
                                            $historico_progresso,
                                            0
                                        );
                                        ?>%

                                    </span>

                                </div>


                            <?php endwhile; ?>

                        <?php else: ?>

                            <p class="historico-vazio">
                                Nenhuma meta cadastrada.
                            </p>

                        <?php endif; ?>

                    </div>


                    <!-- GRÁFICO -->

                    <div class="grafico-card">

                        <div class="resumo-header">

                            <h2>Categorias de metas</h2>

                        </div>


                        <div class="grafico-placeholder">

                            <div class="grafico-barras">

                                <div class="grafico-item">

                                    <div class="grafico-legenda">
                                        <span>Em andamento</span>
                                        <strong><?php echo number_format($porcentagem_andamento, 0); ?>%</strong>
                                    </div>

                                    <div class="grafico-barra">
                                        <div
                                            class="grafico-preenchimento andamento"
                                            style="width: <?php echo $porcentagem_andamento; ?>%;"></div>
                                    </div>

                                </div>


                                <div class="grafico-item">

                                    <div class="grafico-legenda">
                                        <span>Concluídas</span>
                                        <strong><?php echo number_format($porcentagem_concluidas, 0); ?>%</strong>
                                    </div>

                                    <div class="grafico-barra">
                                        <div
                                            class="grafico-preenchimento concluida"
                                            style="width: <?php echo $porcentagem_concluidas; ?>%;"></div>
                                    </div>

                                </div>


                                <div class="grafico-item">

                                    <div class="grafico-legenda">
                                        <span>Sem progresso</span>
                                        <strong><?php echo number_format($porcentagem_sem_progresso, 0); ?>%</strong>
                                    </div>

                                    <div class="grafico-barra">
                                        <div
                                            class="grafico-preenchimento sem-progresso"
                                            style="width: <?php echo $porcentagem_sem_progresso; ?>%;">
                                        </div>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>
                </div>


                <!-- COLUNA DIREITA -->

                <div class="metas-cards">


                    <?php if ($resultado_metas->num_rows > 0): ?>


                        <?php while ($meta = $resultado_metas->fetch_assoc()): ?>


                            <?php
                            $icone_meta = escolherIconeMeta($meta["nome"]);

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


                            <div class="meta-card">

                                <div class="meta-card-header">

                                    <div class="meta-card-title">

                                        <div class="meta-icon">
                                            <i data-lucide="<?php echo $icone_meta; ?>"></i>
                                        </div>

                                        <div>

                                            <h3>
                                                <?php echo htmlspecialchars($meta["nome"]); ?>
                                            </h3>

                                            <span>
                                                Meta financeira
                                            </span>

                                        </div>

                                    </div>

                                </div>


                                <div class="meta-values">

                                    <div>

                                        <span>Valor objetivo</span>

                                        <strong>

                                            R$

                                            <?php

                                            echo number_format(
                                                $meta["valor_objetivo"],
                                                2,
                                                ",",
                                                "."
                                            );

                                            ?>

                                        </strong>

                                    </div>


                                    <div>

                                        <span>Valor atual</span>

                                        <strong>

                                            R$

                                            <?php

                                            echo number_format(
                                                $meta["valor_atual"],
                                                2,
                                                ",",
                                                "."
                                            );

                                            ?>

                                        </strong>

                                    </div>

                                </div>


                                <div class="meta-progress">

                                    <div class="meta-progress-top">

                                        <span>Progresso</span>

                                        <strong>

                                            <?php

                                            echo number_format(
                                                $progresso,
                                                0
                                            );

                                            ?>%

                                        </strong>

                                    </div>


                                    <div class="progress-container">

                                        <div
                                            class="progress-bar"
                                            style="width: <?php echo $progresso; ?>%;">
                                        </div>

                                    </div>

                                </div>


                                <div class="meta-date">

                                    <span>Data limite</span>

                                    <strong>

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

                                    </strong>

                                </div>


                                <div class="meta-actions">

                                    <a
                                        href="editar_meta.php?id=<?php echo $meta["id"]; ?>"
                                        class="btn-editar">

                                        Editar

                                    </a>


                                    <a 
                                        href="resgatar_meta.php?id=<?php echo $meta["id"]; ?>&origem=metas"
                                        class="btn-resgatar">

                                        Resgatar
                                    </a>



                                    <a
                                        href="excluir_meta.php?id=<?php echo $meta["id"]; ?>"
                                        class="btn-excluir">

                                        Excluir

                                    </a>

                                </div>


                            </div>


                        <?php endwhile; ?>

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

        lucide.createIcons();
    </script>



</body>

</html>