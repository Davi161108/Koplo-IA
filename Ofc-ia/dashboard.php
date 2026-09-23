<?php

require_once "conexao.php";
require_once "protecao.php";

// Verifica se o usuário está logado
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION["usuario_id"];
$usuario_nome = $_SESSION["usuario_nome"] ?? "Usuário";

/* ==================================================
   FILTRO DE PERÍODO
================================================== */

$periodo = $_GET["periodo"] ?? "todo";

$periodos_validos = [
    "todo",
    "mes",
    "30dias",
    "3meses",
    "6meses",
    "ano"
];

if (!in_array($periodo, $periodos_validos, true)) {
    $periodo = "todo";
}

$data_inicio = null;
$data_fim = date("Y-m-d");
$agrupamento = "mes";

switch ($periodo) {

    case "mes":
        $data_inicio = date("Y-m-01");
        $data_fim = date("Y-m-t");
        $agrupamento = "mes";
        break;

    case "30dias":
        $data_inicio = date("Y-m-d", strtotime("-29 days"));
        $data_fim = date("Y-m-d");
        $agrupamento = "dia";
        break;

    case "3meses":
        $data_inicio = date("Y-m-01", strtotime("-2 months"));
        $data_fim = date("Y-m-t");
        $agrupamento = "mes";
        break;

    case "6meses":
        $data_inicio = date("Y-m-01", strtotime("-5 months"));
        $data_fim = date("Y-m-t");
        $agrupamento = "mes";
        break;

    case "ano":
        $data_inicio = date("Y-01-01");
        $data_fim = date("Y-12-31");
        $agrupamento = "mes";
        break;

    case "todo":
    default:
        $data_inicio = null;
        $data_fim = null;
        $agrupamento = "mes";
        break;
}


/* ==================================================
   TEXTO DO PERÍODO
================================================== */

$periodo_label = "Todo o período";

switch ($periodo) {
    case "mes":
        $periodo_label = "Este mês";
        break;

    case "30dias":
        $periodo_label = "Últimos 30 dias";
        break;

    case "3meses":
        $periodo_label = "Últimos 3 meses";
        break;

    case "6meses":
        $periodo_label = "Últimos 6 meses";
        break;

    case "ano":
        $periodo_label = "Este ano";
        break;
}


/* ==================================================
   CONDIÇÃO DE DATA
================================================== */

$filtro_data = "";
$tipos_filtro = "i";
$parametros_filtro = [$usuario_id];

if ($data_inicio !== null && $data_fim !== null) {
    $filtro_data = " AND data_transacao BETWEEN ? AND ?";
    $tipos_filtro = "iss";
    $parametros_filtro = [
        $usuario_id,
        $data_inicio,
        $data_fim
    ];
}


/* ==================================================
   TOTAL DE RECEITAS
================================================== */

$sql_receitas = "SELECT COALESCE(SUM(valor), 0) AS total
                 FROM transacoes
                 WHERE usuario_id = ?
                 AND tipo = 'receita'
                 $filtro_data";

$stmt = $conexao->prepare($sql_receitas);

if ($data_inicio !== null) {
    $stmt->bind_param(
        $tipos_filtro,
        $usuario_id,
        $data_inicio,
        $data_fim
    );
} else {
    $stmt->bind_param(
        "i",
        $usuario_id
    );
}

$stmt->execute();

$resultado = $stmt->get_result();

$receitas = (float) $resultado->fetch_assoc()["total"];

$stmt->close();


/* ==================================================
   TOTAL DE DESPESAS
================================================== */

$sql_despesas = "SELECT COALESCE(SUM(valor), 0) AS total
                 FROM transacoes
                 WHERE usuario_id = ?
                 AND tipo = 'despesa'
                 $filtro_data";

$stmt = $conexao->prepare($sql_despesas);

if ($data_inicio !== null) {
    $stmt->bind_param(
        $tipos_filtro,
        $usuario_id,
        $data_inicio,
        $data_fim
    );
} else {
    $stmt->bind_param(
        "i",
        $usuario_id
    );
}

$stmt->execute();

$resultado = $stmt->get_result();

$despesas = (float) $resultado->fetch_assoc()["total"];

$stmt->close();


/* ==================================================
   SALDO
================================================== */

$saldo = $receitas - $despesas;


/* ==================================================
   ÚLTIMAS TRANSAÇÕES
   NÃO DEPENDEM DO FILTRO
================================================== */

$sql_transacoes = "SELECT
                        transacoes.id,
                        transacoes.descricao,
                        transacoes.valor,
                        transacoes.tipo,
                        transacoes.data_transacao,
                        categorias.nome AS categoria
                   FROM transacoes
                   INNER JOIN categorias
                       ON transacoes.categoria_id = categorias.id
                   WHERE transacoes.usuario_id = ?
                   ORDER BY transacoes.data_transacao DESC,
                            transacoes.id DESC
                   LIMIT 5";

$stmt = $conexao->prepare($sql_transacoes);

$stmt->bind_param(
    "i",
    $usuario_id
);

$stmt->execute();

$resultado_transacoes = $stmt->get_result();

$stmt->close();


/* ==================================================
   ANÁLISE — EVOLUÇÃO FINANCEIRA
================================================== */

$dados_evolucao = [];

$meses = [
    "01" => "Jan",
    "02" => "Fev",
    "03" => "Mar",
    "04" => "Abr",
    "05" => "Mai",
    "06" => "Jun",
    "07" => "Jul",
    "08" => "Ago",
    "09" => "Set",
    "10" => "Out",
    "11" => "Nov",
    "12" => "Dez"
];


/* ==================================================
   EVOLUÇÃO POR DIA — ÚLTIMOS 30 DIAS
================================================== */

if ($periodo === "30dias") {

    $sql_evolucao = "SELECT
                        data_transacao AS periodo,
                        SUM(
                            CASE
                                WHEN tipo = 'receita' THEN valor
                                ELSE 0
                            END
                        ) AS receitas,
                        SUM(
                            CASE
                                WHEN tipo = 'despesa' THEN valor
                                ELSE 0
                            END
                        ) AS despesas
                     FROM transacoes
                     WHERE usuario_id = ?
                     AND data_transacao BETWEEN ? AND ?
                     GROUP BY data_transacao
                     ORDER BY data_transacao ASC";

    $stmt = $conexao->prepare($sql_evolucao);

    $stmt->bind_param(
        "iss",
        $usuario_id,
        $data_inicio,
        $data_fim
    );

    $stmt->execute();

    $resultado_evolucao = $stmt->get_result();

    $dados_por_data = [];

    while ($linha = $resultado_evolucao->fetch_assoc()) {

        $dados_por_data[$linha["periodo"]] = [
            "receitas" => (float) $linha["receitas"],
            "despesas" => (float) $linha["despesas"]
        ];
    }

    $stmt->close();


    $inicio = new DateTime($data_inicio);
    $fim = new DateTime($data_fim);

    while ($inicio <= $fim) {

        $data_atual = $inicio->format("Y-m-d");

        $receita_dia =
            $dados_por_data[$data_atual]["receitas"]
            ?? 0;

        $despesa_dia =
            $dados_por_data[$data_atual]["despesas"]
            ?? 0;

        $dados_evolucao[] = [
            "mes" => $inicio->format("d/m"),
            "receitas" => $receita_dia,
            "despesas" => $despesa_dia,
            "saldo" => $receita_dia - $despesa_dia
        ];

        $inicio->modify("+1 day");
    }


/* ==================================================
   EVOLUÇÃO POR MÊS
================================================== */

} else {

    $sql_evolucao = "SELECT
                        DATE_FORMAT(data_transacao, '%Y-%m') AS mes,
                        SUM(
                            CASE
                                WHEN tipo = 'receita' THEN valor
                                ELSE 0
                            END
                        ) AS receitas,
                        SUM(
                            CASE
                                WHEN tipo = 'despesa' THEN valor
                                ELSE 0
                            END
                        ) AS despesas
                     FROM transacoes
                     WHERE usuario_id = ?";

    if ($data_inicio !== null) {
        $sql_evolucao .= "
                     AND data_transacao BETWEEN ? AND ?";
    }

    $sql_evolucao .= "
                     GROUP BY DATE_FORMAT(data_transacao, '%Y-%m')
                     ORDER BY mes ASC";


    $stmt = $conexao->prepare($sql_evolucao);

    if ($data_inicio !== null) {

        $stmt->bind_param(
            "iss",
            $usuario_id,
            $data_inicio,
            $data_fim
        );

    } else {

        $stmt->bind_param(
            "i",
            $usuario_id
        );
    }

    $stmt->execute();

    $resultado_evolucao = $stmt->get_result();

    $dados_por_mes = [];

    while ($linha = $resultado_evolucao->fetch_assoc()) {

        $dados_por_mes[$linha["mes"]] = [
            "receitas" => (float) $linha["receitas"],
            "despesas" => (float) $linha["despesas"]
        ];
    }

    $stmt->close();


    /*
       Para períodos definidos, cria todos os meses
       mesmo quando não existem transações.
    */

    if ($periodo !== "todo") {

        $inicio = new DateTime(
            date("Y-m-01", strtotime($data_inicio))
        );

        $fim = new DateTime(
            date("Y-m-01", strtotime($data_fim))
        );

        while ($inicio <= $fim) {

            $chave_mes = $inicio->format("Y-m");

            $receita_mes =
                $dados_por_mes[$chave_mes]["receitas"]
                ?? 0;

            $despesa_mes =
                $dados_por_mes[$chave_mes]["despesas"]
                ?? 0;

            $dados_evolucao[] = [
                "mes" =>
                    $meses[$inicio->format("m")]
                    . " "
                    . $inicio->format("Y"),

                "receitas" => $receita_mes,

                "despesas" => $despesa_mes,

                "saldo" =>
                    $receita_mes - $despesa_mes
            ];

            $inicio->modify("+1 month");
        }

    } else {

        foreach ($dados_por_mes as $mes => $dados) {

            $ano = substr($mes, 0, 4);
            $numero_mes = substr($mes, 5, 2);

            $receita_mes = $dados["receitas"];
            $despesa_mes = $dados["despesas"];

            $dados_evolucao[] = [
                "mes" =>
                    $meses[$numero_mes]
                    . " "
                    . $ano,

                "receitas" => $receita_mes,

                "despesas" => $despesa_mes,

                "saldo" =>
                    $receita_mes - $despesa_mes
            ];
        }
    }
}


/* ==================================================
   ANÁLISE — DESPESAS POR CATEGORIA
================================================== */

$sql_categorias = "SELECT
                        categorias.nome AS categoria,
                        SUM(transacoes.valor) AS total
                   FROM transacoes
                   INNER JOIN categorias
                       ON transacoes.categoria_id = categorias.id
                   WHERE transacoes.usuario_id = ?
                   AND transacoes.tipo = 'despesa'";

if ($data_inicio !== null) {
    $sql_categorias .= "
                   AND transacoes.data_transacao BETWEEN ? AND ?";
}

$sql_categorias .= "
                   GROUP BY categorias.id, categorias.nome
                   ORDER BY total DESC";

$stmt = $conexao->prepare($sql_categorias);

if ($data_inicio !== null) {

    $stmt->bind_param(
        "iss",
        $usuario_id,
        $data_inicio,
        $data_fim
    );

} else {

    $stmt->bind_param(
        "i",
        $usuario_id
    );
}

$stmt->execute();

$resultado_categorias = $stmt->get_result();

$dados_categorias = [];

while ($linha = $resultado_categorias->fetch_assoc()) {

    $dados_categorias[] = [
        "categoria" => $linha["categoria"],
        "total" => (float) $linha["total"]
    ];
}

$stmt->close();


/* ==================================================
   PERCENTUAL DA RECEITA COMPROMETIDA
================================================== */

if ($receitas > 0) {

    $percentual_despesas =
        ($despesas / $receitas) * 100;

} else {

    $percentual_despesas = 0;
}

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <link
        rel="stylesheet"
        href="dashboard.css"
    >

    <title>Dashboard | Koplo</title>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>

        /* =====================================
           HEADER
        ===================================== */

        .page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .page-title {
            font-size: 25px;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #17212b;
            margin-bottom: 5px;
        }

        .page-subtitle {
            color: #87949c;
            font-size: 13px;
        }


        /* =====================================
           FILTRO
        ===================================== */

        .period-select {
            min-width: 155px;
            background: #ffffff;
            border: 1px solid #e2e7ea;
            border-radius: 7px;
            padding: 9px 13px;
            color: #53636d;
            font-size: 12px;
            font-family: inherit;
            outline: none;
            cursor: pointer;
            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }

        .period-select:hover {
            border-color: #cbd5d9;
        }

        .period-select:focus {
            border-color: #31b984;
            box-shadow:
                0 0 0 3px rgba(49, 185, 132, 0.09);
        }


        /* =====================================
           CARDS PRINCIPAIS
        ===================================== */

        .cards {
            display: grid;
            grid-template-columns: 1.35fr 1fr 1fr;
            gap: 16px;
            margin-bottom: 17px;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e9edef;
            border-radius: 10px;
            padding: 21px 22px;
            min-height: 128px;
            box-shadow:
                0 2px 8px rgba(15, 30, 40, 0.025);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .card-label {
            color: #7b8992;
            font-size: 12px;
            font-weight: 500;
        }

        .card-icon {
            width: 30px;
            height: 30px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-icon svg {
            width: 16px;
            height: 16px;
        }

        .saldo-icon {
            background: #e5f6ef;
            color: #16815d;
        }

        .receita-icon {
            background: #eaf1ff;
            color: #477ee8;
        }

        .despesa-icon {
            background: #fff0f0;
            color: #df5c5c;
        }

        .card-value {
            color: #18242d;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .card-description {
            margin-top: 7px;
            color: #9aa5ab;
            font-size: 11px;
        }


        /* =====================================
           GRID PRINCIPAL
        ===================================== */

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 17px;
            margin-bottom: 17px;
        }

        .section {
            background: #ffffff;
            border: 1px solid #e9edef;
            border-radius: 10px;
            padding: 21px;
            box-shadow:
                0 2px 8px rgba(15, 30, 40, 0.025);
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 19px;
        }

        .section-title {
            color: #26343d;
            font-size: 15px;
            font-weight: 650;
        }

        .section-link {
            color: #209a70;
            font-size: 11px;
            font-weight: 600;
        }

        .section-link:hover {
            color: #167956;
        }


        /* =====================================
           TRANSAÇÕES
        ===================================== */

        .transaction {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eef1f2;
        }

        .transaction:last-child {
            border-bottom: none;
        }

        .transaction-info {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .transaction-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .transaction-icon svg {
            width: 16px;
            height: 16px;
        }

        .transaction-icon.receita {
            background: #eaf6f1;
            color: #209b70;
        }

        .transaction-icon.despesa {
            background: #fff0f0;
            color: #d96262;
        }

        .transaction-name {
            color: #33434d;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .transaction-category {
            color: #96a1a8;
            font-size: 10px;
        }

        .transaction-value {
            font-size: 12px;
            font-weight: 650;
        }

        .valor-receita {
            color: #15946a;
        }

        .valor-despesa {
            color: #d95858;
        }


        /* =====================================
           RESUMO
        ===================================== */

        .summary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eef1f2;
        }

        .summary-row:last-of-type {
            border-bottom: none;
        }

        .summary-info {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .summary-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .dot-receita {
            background: #4d8bea;
        }

        .dot-despesa {
            background: #e86666;
        }

        .dot-saldo {
            background: #31b984;
        }

        .summary-label {
            color: #77858e;
            font-size: 12px;
        }

        .summary-value {
            color: #26343d;
            font-size: 13px;
            font-weight: 650;
        }


        /* =====================================
           BOTÃO
        ===================================== */

        .btn-primary {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            margin-top: 18px;
            padding: 11px 15px;
            background: #31b984;
            border: none;
            border-radius: 7px;
            color: #ffffff;
            font-size: 12px;
            font-weight: 600;
            transition: background 0.2s ease;
        }

        .btn-primary:hover {
            background: #269e70;
        }

        .btn-primary svg {
            width: 14px;
            height: 14px;
        }


        /* =====================================
           GRÁFICOS
        ===================================== */

        .analytics-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 17px;
            margin-bottom: 17px;
        }

        .chart-section {
            min-width: 0;
        }

        .chart-container {
            position: relative;
            width: 100%;
            height: 285px;
        }

        .chart-container-small {
            position: relative;
            width: 100%;
            height: 285px;
        }

        .chart-empty {
            height: 285px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #9aa5ab;
            font-size: 12px;
            padding: 20px;
        }


        /* =====================================
           INDICADORES
        ===================================== */

        .analysis-indicators {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 17px;
            margin-bottom: 17px;
        }

        .analysis-card {
            background: #ffffff;
            border: 1px solid #e9edef;
            border-radius: 10px;
            padding: 16px 18px;
            box-shadow:
                0 2px 8px rgba(15, 30, 40, 0.025);
        }

        .analysis-label {
            color: #87949c;
            font-size: 11px;
            margin-bottom: 8px;
        }

        .analysis-value {
            color: #26343d;
            font-size: 18px;
            font-weight: 700;
        }

        .analysis-description {
            color: #9aa5ab;
            font-size: 10px;
            margin-top: 5px;
        }


        /* =====================================
           ÁREA INFERIOR
        ===================================== */

        .bottom-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 17px;
        }


        /* =====================================
           BARRA DE PROGRESSO
        ===================================== */

        .progress-item {
            margin-bottom: 18px;
        }

        .progress-item:last-child {
            margin-bottom: 0;
        }

        .progress-top {
            display: flex;
            justify-content: space-between;
            margin-bottom: 7px;
        }

        .progress-name {
            color: #53616a;
            font-size: 11px;
            font-weight: 600;
        }

        .progress-percent {
            color: #84929a;
            font-size: 10px;
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: #edf1f2;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #31b984;
            border-radius: 10px;
        }


        /* =====================================
           ESTADO VAZIO
        ===================================== */

        .empty {
            color: #9aa5ab;
            font-size: 12px;
            padding: 20px 0;
        }


        /* =====================================
           RESPONSIVIDADE
        ===================================== */

        @media (max-width: 1050px) {

            .cards {
                grid-template-columns: 1fr 1fr;
            }

            .card:first-child {
                grid-column: span 2;
            }

            .analytics-grid {
                grid-template-columns: 1fr;
            }

            .analysis-indicators {
                grid-template-columns: 1fr 1fr;
            }
        }


        @media (max-width: 800px) {

            .dashboard-grid,
            .bottom-grid {
                grid-template-columns: 1fr;
            }

            .analysis-indicators {
                grid-template-columns: 1fr;
            }
        }


        @media (max-width: 600px) {

            .cards {
                grid-template-columns: 1fr;
            }

            .card:first-child {
                grid-column: auto;
            }

            .card-value {
                font-size: 22px;
            }

            .page-header {
                align-items: flex-start;
                gap: 15px;
                flex-direction: column;
            }

            .period-select {
                width: 100%;
            }

            .analytics-grid {
                gap: 14px;
            }

            .section {
                padding: 18px;
            }

            .chart-container,
            .chart-container-small,
            .chart-empty {
                height: 240px;
            }
        }

    </style>

</head>


<body>

<div class="layout">


    <!-- =====================================
         SIDEBAR
    ====================================== -->

    <?php include "sidebar.php"; ?>


    <!-- =====================================
         CONTEÚDO
    ====================================== -->

    <main class="content">


        <!-- =====================================
             TOPBAR
        ====================================== -->

        <div class="topbar">

            <div class="topbar-right">

                <svg
                    class="notification"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >

                    <path
                        d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"
                    ></path>

                    <path d="M10 21h4"></path>

                </svg>


                <!-- PERFIL -->

                <div class="profile-wrapper">

                    <button
                        type="button"
                        class="profile"
                        id="profileButton"
                        aria-expanded="false"
                    >

                        <span class="profile-name">

                            <?php
                            echo htmlspecialchars($usuario_nome);
                            ?>

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
                            stroke-linejoin="round"
                        >

                            <path d="m6 9 6 6 6-6"></path>

                        </svg>

                    </button>


                    <!-- DROPDOWN -->

                    <div
                        class="profile-dropdown"
                        id="profileDropdown"
                    >

                        <!-- Trocar usuário -->

                        <a href="login.php">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >

                                <path
                                    d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"
                                ></path>

                                <path
                                    d="M10 17l5-5-5-5"
                                ></path>

                                <path
                                    d="M15 12H3"
                                ></path>

                            </svg>

                            <span>
                                Trocar usuário/cadastro
                            </span>

                        </a>


                        <!-- Separador -->

                        <div class="dropdown-divider"></div>


                        <!-- Sair -->

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

                                <path
                                    d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"
                                ></path>

                                <path
                                    d="M16 17l5-5-5-5"
                                ></path>

                                <path
                                    d="M21 12H9"
                                ></path>

                            </svg>

                            <span>
                                Sair
                            </span>

                        </a>

                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================
             HEADER
        ====================================== -->

        <header class="page-header">

            <div>

                <h1 class="page-title">

                    Olá,

                    <?php
                    echo htmlspecialchars($usuario_nome);
                    ?>

                </h1>

                <p class="page-subtitle">

                    Resumo da sua vida financeira

                </p>

            </div>


            <!-- FILTRO FUNCIONAL -->

            <form method="GET">

                <select
                    name="periodo"
                    class="period-select"
                    onchange="this.form.submit()"
                >

                    <option
                        value="todo"
                        <?php
                        echo $periodo === "todo"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Todo o período
                    </option>

                    <option
                        value="mes"
                        <?php
                        echo $periodo === "mes"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Este mês
                    </option>

                    <option
                        value="30dias"
                        <?php
                        echo $periodo === "30dias"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Últimos 30 dias
                    </option>

                    <option
                        value="3meses"
                        <?php
                        echo $periodo === "3meses"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Últimos 3 meses
                    </option>

                    <option
                        value="6meses"
                        <?php
                        echo $periodo === "6meses"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Últimos 6 meses
                    </option>

                    <option
                        value="ano"
                        <?php
                        echo $periodo === "ano"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Este ano
                    </option>

                </select>

            </form>

        </header>


        <!-- =====================================
             CARDS
        ====================================== -->

        <section class="cards">


            <!-- SALDO -->

            <div class="card">

                <div class="card-header">

                    <span class="card-label">
                        Saldo total
                    </span>

                    <div class="card-icon saldo-icon">

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
                                y="6"
                                width="18"
                                height="13"
                                rx="2"
                            ></rect>

                            <path d="M16 10h5"></path>

                            <circle
                                cx="16"
                                cy="13"
                                r="1"
                            ></circle>

                            <path d="M7 6V4h10v2"></path>

                        </svg>

                    </div>

                </div>


                <div class="card-value">

                    R$

                    <?php
                    echo number_format(
                        $saldo,
                        2,
                        ",",
                        "."
                    );
                    ?>

                </div>


                <div class="card-description">

                    <?php
                    echo htmlspecialchars($periodo_label);
                    ?>

                </div>

            </div>


            <!-- RECEITAS -->

            <div class="card">

                <div class="card-header">

                    <span class="card-label">
                        Receitas
                    </span>

                    <div class="card-icon receita-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >

                            <path d="M12 19V5"></path>

                            <path d="M6 11l6-6 6 6"></path>

                        </svg>

                    </div>

                </div>


                <div class="card-value">

                    R$

                    <?php
                    echo number_format(
                        $receitas,
                        2,
                        ",",
                        "."
                    );
                    ?>

                </div>


                <div class="card-description">
                    Total recebido no período
                </div>

            </div>


            <!-- DESPESAS -->

            <div class="card">

                <div class="card-header">

                    <span class="card-label">
                        Despesas
                    </span>

                    <div class="card-icon despesa-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >

                            <path d="M12 5v14"></path>

                            <path d="M6 13l6 6 6-6"></path>

                        </svg>

                    </div>

                </div>


                <div class="card-value">

                    R$

                    <?php
                    echo number_format(
                        $despesas,
                        2,
                        ",",
                        "."
                    );
                    ?>

                </div>


                <div class="card-description">
                    Total gasto no período
                </div>

            </div>

        </section>


        <!-- =====================================
             PRINCIPAL
        ====================================== -->

        <section class="dashboard-grid">


            <!-- TRANSAÇÕES -->

            <div class="section">

                <div class="section-header">

                    <h2 class="section-title">
                        Últimas transações
                    </h2>

                    <a
                        href="transacoes.php"
                        class="section-link"
                    >
                        Ver todas
                    </a>

                </div>


                <?php if ($resultado_transacoes->num_rows > 0) { ?>

                    <?php while (
                        $transacao =
                        $resultado_transacoes->fetch_assoc()
                    ) { ?>

                        <div class="transaction">


                            <div class="transaction-info">

                                <div
                                    class="transaction-icon
                                    <?php
                                    echo $transacao["tipo"];
                                    ?>"
                                >

                                    <?php if (
                                        $transacao["tipo"] == "receita"
                                    ) { ?>

                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >

                                            <path d="M12 19V5"></path>

                                            <path d="M6 11l6-6 6 6"></path>

                                        </svg>

                                    <?php } else { ?>

                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >

                                            <path d="M12 5v14"></path>

                                            <path d="M6 13l6 6 6-6"></path>

                                        </svg>

                                    <?php } ?>

                                </div>


                                <div>

                                    <div class="transaction-name">

                                        <?php
                                        echo htmlspecialchars(
                                            $transacao["descricao"]
                                        );
                                        ?>

                                    </div>


                                    <div class="transaction-category">

                                        <?php
                                        echo htmlspecialchars(
                                            $transacao["categoria"]
                                        );
                                        ?>

                                        ·

                                        <?php
                                        echo date(
                                            "d/m/Y",
                                            strtotime(
                                                $transacao["data_transacao"]
                                            )
                                        );
                                        ?>

                                    </div>

                                </div>

                            </div>


                            <div
                                class="transaction-value
                                <?php
                                echo $transacao["tipo"] == "receita"
                                    ? "valor-receita"
                                    : "valor-despesa";
                                ?>"
                            >

                                <?php
                                echo $transacao["tipo"] == "receita"
                                    ? "+ "
                                    : "- ";
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

                            </div>

                        </div>

                    <?php } ?>


                <?php } else { ?>


                    <div class="empty">
                        Nenhuma transação cadastrada.
                    </div>


                    <a
                        href="transacao.php"
                        class="btn-primary"
                    >
                        Nova transação
                    </a>


                <?php } ?>

            </div>


            <!-- RESUMO -->

            <div class="section">

                <div class="section-header">

                    <h2 class="section-title">
                        Resumo financeiro
                    </h2>

                </div>


                <!-- RECEITAS -->

                <div class="summary-row">

                    <div class="summary-info">

                        <span
                            class="summary-dot dot-receita"
                        ></span>

                        <span class="summary-label">
                            Receitas
                        </span>

                    </div>


                    <span class="summary-value">

                        R$

                        <?php
                        echo number_format(
                            $receitas,
                            2,
                            ",",
                            "."
                        );
                        ?>

                    </span>

                </div>


                <!-- DESPESAS -->

                <div class="summary-row">

                    <div class="summary-info">

                        <span
                            class="summary-dot dot-despesa"
                        ></span>

                        <span class="summary-label">
                            Despesas
                        </span>

                    </div>


                    <span class="summary-value">

                        R$

                        <?php
                        echo number_format(
                            $despesas,
                            2,
                            ",",
                            "."
                        );
                        ?>

                    </span>

                </div>


                <!-- SALDO -->

                <div class="summary-row">

                    <div class="summary-info">

                        <span
                            class="summary-dot dot-saldo"
                        ></span>

                        <span class="summary-label">
                            Saldo
                        </span>

                    </div>


                    <span class="summary-value">

                        R$

                        <?php
                        echo number_format(
                            $saldo,
                            2,
                            ",",
                            "."
                        );
                        ?>

                    </span>

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
                    >

                        <path d="M12 5v14"></path>

                        <path d="M5 12h14"></path>

                    </svg>

                    Nova transação

                </a>

            </div>

        </section>


        <!-- =====================================
             GRÁFICOS E ANÁLISES
        ====================================== -->

        <section class="analytics-grid">


            <!-- EVOLUÇÃO FINANCEIRA -->

            <div class="section chart-section">

                <div class="section-header">

                    <h2 class="section-title">
                        Evolução financeira
                    </h2>

                </div>


                <?php if (count($dados_evolucao) > 0) { ?>

                    <div class="chart-container">

                        <canvas id="evolucaoChart"></canvas>

                    </div>

                <?php } else { ?>

                    <div class="chart-empty">
                        Não há movimentações no período selecionado.
                    </div>

                <?php } ?>

            </div>


            <!-- DESPESAS POR CATEGORIA -->

            <div class="section chart-section">

                <div class="section-header">

                    <h2 class="section-title">
                        Despesas por categoria
                    </h2>

                </div>


                <?php if (count($dados_categorias) > 0) { ?>

                    <div class="chart-container-small">

                        <canvas id="categoriasChart"></canvas>

                    </div>

                <?php } else { ?>

                    <div class="chart-empty">
                        Não há despesas no período selecionado.
                    </div>

                <?php } ?>

            </div>

        </section>


        <!-- =====================================
             INDICADORES
        ====================================== -->

        <section class="analysis-indicators">


            <!-- RECEITA -->

            <div class="analysis-card">

                <div class="analysis-label">
                    Total recebido
                </div>

                <div class="analysis-value">

                    R$

                    <?php
                    echo number_format(
                        $receitas,
                        2,
                        ",",
                        "."
                    );
                    ?>

                </div>

                <div class="analysis-description">

                    No período selecionado

                </div>

            </div>


            <!-- DESPESAS -->

            <div class="analysis-card">

                <div class="analysis-label">
                    Total comprometido
                </div>

                <div class="analysis-value">

                    <?php
                    echo number_format(
                        $percentual_despesas,
                        1,
                        ",",
                        "."
                    );
                    ?>%

                </div>

                <div class="analysis-description">

                    Das receitas foram destinadas a despesas

                </div>

            </div>


            <!-- SALDO -->

            <div class="analysis-card">

                <div class="analysis-label">
                    Resultado financeiro
                </div>

                <div class="analysis-value">

                    R$

                    <?php
                    echo number_format(
                        $saldo,
                        2,
                        ",",
                        "."
                    );
                    ?>

                </div>

                <div class="analysis-description">

                    Receitas menos despesas

                </div>

            </div>

        </section>


        <!-- =====================================
             ÁREA INFERIOR
        ====================================== -->

        <section class="bottom-grid">


            <!-- METAS -->

            <div class="section">

                <div class="section-header">

                    <h2 class="section-title">
                        Metas financeiras
                    </h2>

                    <a
                        href="metas.php"
                        class="section-link"
                    >
                        Ver metas
                    </a>

                </div>


                <div class="progress-item">

                    <div class="progress-top">

                        <span class="progress-name">
                            Metas em breve
                        </span>

                        <span class="progress-percent">
                            --
                        </span>

                    </div>


                    <div class="progress-bar">

                        <div
                            class="progress-fill"
                            style="width: 0%;"
                        ></div>

                    </div>

                </div>


                <div class="empty">
                    O sistema de metas será adicionado aqui.
                </div>

            </div>


            <!-- KOPLO IA -->

            <div class="section">

                <div class="section-header">

                    <h2 class="section-title">
                        Análise financeira
                    </h2>

                    <a
                        href="#"
                        class="section-link"
                    >
                        Koplo IA
                    </a>

                </div>


                <div class="empty">

                    A análise inteligente das suas finanças
                    estará disponível em breve.

                </div>

            </div>

        </section>


    </main>

</div>


<script>


    /* =====================================
       PERFIL / TOPBAR
    ===================================== */

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

            if (
                !profileWrapper.contains(event.target)
            ) {

                profileWrapper.classList.remove("open");

                profileButton.setAttribute(
                    "aria-expanded",
                    "false"
                );

            }

        }
    );


    /* =====================================
       DADOS DOS GRÁFICOS
    ===================================== */

    const dadosEvolucao =
        <?php echo json_encode(
            $dados_evolucao,
            JSON_UNESCAPED_UNICODE
        ); ?>;


    const dadosCategorias =
        <?php echo json_encode(
            $dados_categorias,
            JSON_UNESCAPED_UNICODE
        ); ?>;


    /* =====================================
       GRÁFICO — EVOLUÇÃO FINANCEIRA
    ===================================== */

    const evolucaoCanvas =
        document.getElementById("evolucaoChart");


    if (
        evolucaoCanvas &&
        dadosEvolucao.length > 0
    ) {

        const labels =
            dadosEvolucao.map(
                item => item.mes
            );


        const receitas =
            dadosEvolucao.map(
                item => item.receitas
            );


        const despesas =
            dadosEvolucao.map(
                item => item.despesas
            );


        const saldos =
            dadosEvolucao.map(
                item => item.saldo
            );


        new Chart(
            evolucaoCanvas,
            {

                type: "line",

                data: {

                    labels: labels,

                    datasets: [

                        {

                            label: "Receitas",

                            data: receitas,

                            borderColor: "#4d8bea",

                            backgroundColor:
                                "rgba(77, 139, 234, 0.08)",

                            borderWidth: 2,

                            pointRadius: 3,

                            pointHoverRadius: 5,

                            tension: 0.35,

                            fill: false

                        },


                        {

                            label: "Despesas",

                            data: despesas,

                            borderColor: "#e86666",

                            backgroundColor:
                                "rgba(232, 102, 102, 0.08)",

                            borderWidth: 2,

                            pointRadius: 3,

                            pointHoverRadius: 5,

                            tension: 0.35,

                            fill: false

                        },


                        {

                            label: "Saldo",

                            data: saldos,

                            borderColor: "#31b984",

                            backgroundColor:
                                "rgba(49, 185, 132, 0.08)",

                            borderWidth: 2,

                            pointRadius: 3,

                            pointHoverRadius: 5,

                            tension: 0.35,

                            fill: false

                        }

                    ]

                },


                options: {

                    responsive: true,

                    maintainAspectRatio: false,


                    interaction: {

                        mode: "index",

                        intersect: false

                    },


                    plugins: {

                        legend: {

                            position: "bottom",

                            labels: {

                                usePointStyle: true,

                                pointStyle: "circle",

                                padding: 18,

                                font: {

                                    size: 11

                                }

                            }

                        },


                        tooltip: {

                            callbacks: {

                                label: function (context) {

                                    const valor =
                                        context.parsed.y ?? 0;

                                    return (

                                        " " +

                                        context.dataset.label +

                                        ": R$ " +

                                        valor.toLocaleString(
                                            "pt-BR",
                                            {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2
                                            }
                                        )

                                    );

                                }

                            }

                        }

                    },


                    scales: {

                        x: {

                            grid: {

                                display: false

                            },

                            ticks: {

                                color: "#8b979e",

                                font: {

                                    size: 10

                                },

                                maxRotation: 0

                            },

                            border: {

                                display: false

                            }

                        },


                        y: {

                            beginAtZero: true,

                            grid: {

                                color: "#eef1f2"

                            },

                            ticks: {

                                color: "#8b979e",

                                font: {

                                    size: 10

                                },

                                callback: function (value) {

                                    return "R$ " +

                                        Number(value).toLocaleString(
                                            "pt-BR",
                                            {
                                                notation: "compact",
                                                maximumFractionDigits: 1
                                            }
                                        );

                                }

                            },

                            border: {

                                display: false

                            }

                        }

                    }

                }

            }
        );

    }


    /* =====================================
       GRÁFICO — DESPESAS POR CATEGORIA
    ===================================== */

    const categoriasCanvas =
        document.getElementById("categoriasChart");


    if (
        categoriasCanvas &&
        dadosCategorias.length > 0
    ) {

        const labelsCategorias =
            dadosCategorias.map(
                item => item.categoria
            );


        const valoresCategorias =
            dadosCategorias.map(
                item => item.total
            );


        new Chart(
            categoriasCanvas,
            {

                type: "doughnut",

                data: {

                    labels: labelsCategorias,

                    datasets: [

                        {

                            data: valoresCategorias,

                            backgroundColor: [

                                "#31b984",
                                "#4d8bea",
                                "#e86666",
                                "#e5a94d",
                                "#8b78d8",
                                "#4ca6a8",
                                "#d47a9c",
                                "#87949c"

                            ],

                            borderWidth: 2,

                            borderColor: "#ffffff",

                            hoverOffset: 5

                        }

                    ]

                },


                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    cutout: "66%",


                    plugins: {

                        legend: {

                            position: "bottom",

                            labels: {

                                usePointStyle: true,

                                pointStyle: "circle",

                                padding: 13,

                                font: {

                                    size: 10

                                }

                            }

                        },


                        tooltip: {

                            callbacks: {

                                label: function (context) {

                                    const valor =
                                        context.parsed || 0;

                                    return (

                                        " " +

                                        context.label +

                                        ": R$ " +

                                        valor.toLocaleString(
                                            "pt-BR",
                                            {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2
                                            }
                                        )

                                    );

                                }

                            }

                        }

                    }

                }

            }
        );

    }

</script>


</body>

</html>