<?php

require_once "conexao.php";
require_once "protecao.php";

if (!isset($_SESSION["usuario_id"])) {
    die("Você precisa estar logado para acessar esta página.");
}

$usuario_id = $_SESSION["usuario_id"];

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

    <title>Histórico de metas - Koplo</title>

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
            padding: 40px;
        }

        .historico-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* CABEÇALHO */

        .topo {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .topo h1 {
            font-size: 26px;
        }

        .voltar {
            color: #2cc493;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .voltar:hover {
            color: #25ad82;
        }

        /* GRID */

        .metas-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        /* CARD */

        .meta-card {
            background: #ffffff;
            border: 1px solid #e3e8ec;
            border-radius: 12px;
            padding: 25px;
        }

        .meta-card h2 {
            font-size: 18px;
            color: #34444e;
            margin-bottom: 15px;
        }

        /* INFORMAÇÕES */

        .meta-info {
            color: #70859a;
            font-size: 14px;
            line-height: 1.8;
        }

        /* PROGRESSO */

        .progresso {
            margin-top: 15px;
            background: #e8edf0;
            height: 8px;
            border-radius: 10px;
            overflow: hidden;
        }

        .progresso-barra {
            height: 100%;
            background: #2cc493;
            border-radius: 10px;
        }

        /* AÇÕES */

        .acoes {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 20px;
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

        /* RESGATAR */

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

        /* EXCLUIR */

        .btn-excluir {
            color: #bd6868;
            background: #faf1f1;
        }

        .btn-excluir:hover {
            background: #ffe7e7;
            color: #c84d4d;
        }

        /* DEIXA OS 3 BOTÕES COM O MESMO ESPAÇO */

        .acoes .btn-editar,
        .acoes .btn-resgatar,
        .acoes .btn-excluir {
            flex: 1;
            text-align: center;
        }

        /* SEM METAS */

        .sem-metas {
            background: #ffffff;
            border: 1px solid #e3e8ec;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            color: #70859a;
        }

        /* RESPONSIVIDADE */

        @media (max-width: 700px) {

            .metas-grid {
                grid-template-columns: 1fr;
            }

            body {
                padding: 20px;
            }

            .topo {
                gap: 15px;
            }

        }
    </style>

</head>

<body>

    <div class="historico-container">

        <!-- CABEÇALHO -->

        <div class="topo">

            <h1>Histórico de metas</h1>

            <a href="metas.php" class="voltar">
                ← Voltar
            </a>

        </div>


        <?php if ($resultado_metas->num_rows > 0): ?>

            <div class="metas-grid">

                <?php while ($meta = $resultado_metas->fetch_assoc()): ?>

                    <?php

                    if ($meta["valor_objetivo"] > 0) {

                        $porcentagem =
                            ($meta["valor_atual"] / $meta["valor_objetivo"]) * 100;
                    } else {

                        $porcentagem = 0;
                    }

                    if ($porcentagem > 100) {
                        $porcentagem = 100;
                    }

                    ?>


                    <!-- CARD DA META -->

                    <div class="meta-card">

                        <h2>

                            <?php echo htmlspecialchars($meta["nome"]); ?>

                        </h2>


                        <div class="meta-info">

                            <div>

                                R$

                                <?php
                                echo number_format(
                                    $meta["valor_atual"],
                                    2,
                                    ",",
                                    "."
                                );
                                ?>

                                de

                                R$

                                <?php
                                echo number_format(
                                    $meta["valor_objetivo"],
                                    2,
                                    ",",
                                    "."
                                );
                                ?>

                            </div>


                            <div>

                                Progresso:

                                <?php
                                echo number_format(
                                    $porcentagem,
                                    0
                                );
                                ?>%

                            </div>


                            <?php if (!empty($meta["data_limite"])): ?>

                                <div>

                                    Data limite:

                                    <?php

                                    echo date(
                                        "d/m/Y",
                                        strtotime($meta["data_limite"])
                                    );

                                    ?>

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- BARRA DE PROGRESSO -->

                        <div class="progresso">

                            <div
                                class="progresso-barra"
                                style="width: <?php echo $porcentagem; ?>%;">
                            </div>

                        </div>


                        <!-- BOTÕES -->

                        <div class="acoes">

                            <a
                                href="editar_meta.php?id=<?php echo $meta["id"]; ?>"
                                class="btn-editar">

                                Editar

                            </a>


                            <a
                                href="resgatar_meta.php?id=<?php echo $meta["id"]; ?>&origem=historico" 
                                class="btn-resgatar">
                                
                                Resgatar
                            

                            </a>


                            <a
                                href="excluir_meta.php?id=<?php echo $meta["id"]; ?>"
                                class="btn-excluir"
                                onclick="return confirm('Tem certeza que deseja excluir esta meta?');">

                                Excluir

                            </a>

                        </div>

                    </div>


                <?php endwhile; ?>

            </div>


        <?php else: ?>


            <div class="sem-metas">

                Você ainda não possui nenhuma meta.

            </div>


        <?php endif; ?>

    </div>

</body>

</html>