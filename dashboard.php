<?php

session_start();

require_once "conexao.php";

if (!isset($_SESSION["usuario_id"])) {
    die("Você precisa estar logado para acessar o dashboard.");
}

$usuario_id = $_SESSION["usuario_id"];

// ==============================
// TOTAL DE RECEITAS
// ==============================

$sql_receitas = "SELECT COALESCE(SUM(valor), 0) AS total
                 FROM transacoes
                 WHERE usuario_id = ?
                 AND tipo = 'receita'";

$stmt = $conexao->prepare($sql_receitas);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();

$resultado = $stmt->get_result();
$receitas = $resultado->fetch_assoc()["total"];

$stmt->close();


// ==============================
// TOTAL DE DESPESAS
// ==============================

$sql_despesas = "SELECT COALESCE(SUM(valor), 0) AS total
                 FROM transacoes
                 WHERE usuario_id = ?
                 AND tipo = 'despesa'";

$stmt = $conexao->prepare($sql_despesas);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();

$resultado = $stmt->get_result();
$despesas = $resultado->fetch_assoc()["total"];

$stmt->close();


// ==============================
// SALDO
// ==============================

$saldo = $receitas - $despesas;


// ==============================
// DESPESAS POR CATEGORIA
// ==============================

$sql_categorias = "SELECT
                        categorias.nome AS categoria,
                        SUM(transacoes.valor) AS total
                   FROM transacoes
                   INNER JOIN categorias
                       ON transacoes.categoria_id = categorias.id
                   WHERE transacoes.usuario_id = ?
                   AND transacoes.tipo = 'despesa'
                   GROUP BY categorias.id, categorias.nome
                   ORDER BY total DESC";

$stmt = $conexao->prepare($sql_categorias);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();

$resultado_categorias = $stmt->get_result();

$categorias = [];

while ($linha = $resultado_categorias->fetch_assoc()) {
    $categorias[] = $linha;
}

$stmt->close();


// ==============================
// ÚLTIMAS TRANSAÇÕES
// ==============================

$sql_ultimas = "SELECT
                    categorias.nome AS categoria,
                    transacoes.descricao,
                    transacoes.valor,
                    transacoes.tipo,
                    transacoes.data_transacao
                FROM transacoes
                INNER JOIN categorias
                    ON transacoes.categoria_id = categorias.id
                WHERE transacoes.usuario_id = ?
                ORDER BY transacoes.data_transacao DESC
                LIMIT 5";

$stmt = $conexao->prepare($sql_ultimas);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();

$resultado_ultimas = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard - Koplo</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }

        .topo {
            margin-bottom: 30px;
        }

        .topo h1 {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .topo p {
            color: #6b7280;
        }

        /* CARDS */

        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
        }

        .card h3 {
            font-size: 15px;
            color: #6b7280;
            margin-bottom: 12px;
        }

        .valor {
            font-size: 30px;
            font-weight: bold;
        }

        /* GRÁFICOS */

        .graficos {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .grafico-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
        }

        .grafico-card h2 {
            font-size: 20px;
            margin-bottom: 20px;
        }

        .grafico {
            height: 300px;
        }

        /* TRANSAÇÕES */

        .transacoes {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
        }

        .transacoes h2 {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            color: #6b7280;
            font-size: 14px;
        }

        .receita {
            font-weight: bold;
        }

        .despesa {
            font-weight: bold;
        }

        /* BOTÕES */

        .acoes {
            margin-top: 25px;
        }

        .acoes a {
            display: inline-block;
            text-decoration: none;
            background: #111827;
            color: white;
            padding: 12px 18px;
            border-radius: 8px;
            margin-right: 10px;
        }

        /* RESPONSIVO */

        @media (max-width: 800px) {

            .cards {
                grid-template-columns: 1fr;
            }

            .graficos {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 20px;
            }

            table {
                font-size: 14px;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <div class="topo">

        <h1>Dashboard</h1>

        <p>
            Olá,
            <?php echo htmlspecialchars($_SESSION["usuario_nome"]); ?>!
            Aqui está seu resumo financeiro.
        </p>

    </div>


    <!-- CARDS -->

    <div class="cards">

        <div class="card">

            <h3>Total de receitas</h3>

            <div class="valor">
                R$
                <?php echo number_format($receitas, 2, ",", "."); ?>
            </div>

        </div>


        <div class="card">

            <h3>Total de despesas</h3>

            <div class="valor">
                R$
                <?php echo number_format($despesas, 2, ",", "."); ?>
            </div>

        </div>


        <div class="card">

            <h3>Saldo</h3>

            <div class="valor">
                R$
                <?php echo number_format($saldo, 2, ",", "."); ?>
            </div>

        </div>

    </div>


    <!-- GRÁFICOS -->

    <div class="graficos">

        <div class="grafico-card">

            <h2>Despesas por categoria</h2>

            <div class="grafico">
                <canvas id="graficoCategorias"></canvas>
            </div>

        </div>


        <div class="grafico-card">

            <h2>Resumo financeiro</h2>

            <div class="grafico">
                <canvas id="graficoResumo"></canvas>
            </div>

        </div>

    </div>


    <!-- ÚLTIMAS TRANSAÇÕES -->

    <div class="transacoes">

        <h2>Últimas transações</h2>

        <table>

            <thead>

                <tr>
                    <th>Categoria</th>
                    <th>Descrição</th>
                    <th>Valor</th>
                    <th>Tipo</th>
                    <th>Data</th>
                </tr>

            </thead>

            <tbody>

            <?php while ($transacao = $resultado_ultimas->fetch_assoc()) { ?>

                <tr>

                    <td>
                        <?php echo htmlspecialchars($transacao["categoria"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($transacao["descricao"]); ?>
                    </td>

                    <td>
                        R$
                        <?php echo number_format($transacao["valor"], 2, ",", "."); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($transacao["tipo"]); ?>
                    </td>

                    <td>
                        <?php
                        echo date(
                            "d/m/Y",
                            strtotime($transacao["data_transacao"])
                        );
                        ?>
                    </td>

                </tr>

            <?php } ?>

            </tbody>

        </table>


        <div class="acoes">

            <a href="transacao.php">
                Nova transação
            </a>

            <a href="transacoes.php">
                Ver todas
            </a>

        </div>

    </div>

</div>


<script>

    // ==============================
    // DADOS DAS CATEGORIAS
    // ==============================

    const categorias = <?php echo json_encode(
        array_column($categorias, "categoria")
    ); ?>;

    const valoresCategorias = <?php echo json_encode(
        array_map("floatval", array_column($categorias, "total"))
    ); ?>;


    // ==============================
    // GRÁFICO DE CATEGORIAS
    // ==============================

    new Chart(document.getElementById("graficoCategorias"), {

        type: "doughnut",

        data: {

            labels: categorias,

            datasets: [{
                data: valoresCategorias
            }]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            plugins: {

                legend: {
                    position: "bottom"
                }

            }

        }

    });


    // ==============================
    // GRÁFICO RESUMO
    // ==============================

    new Chart(document.getElementById("graficoResumo"), {

        type: "bar",

        data: {

            labels: [
                "Receitas",
                "Despesas"
            ],

            datasets: [{

                label: "Valor",

                data: [
                    <?php echo $receitas; ?>,
                    <?php echo $despesas; ?>
                ]

            }]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            scales: {

                y: {
                    beginAtZero: true
                }

            },

            plugins: {

                legend: {
                    display: false
                }

            }

        }

    });

</script>

</body>

</html>

<?php

$stmt->close();

?>
