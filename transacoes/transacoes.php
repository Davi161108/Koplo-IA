<?php

session_start();

require_once "conexao.php";

// Verifica se o usuário está logado
if (!isset($_SESSION["usuario_id"])) {
    die("Você precisa estar logado para acessar esta página.");
}

$usuario_id = $_SESSION["usuario_id"];

// Busca somente as transações do usuário logado
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
        ORDER BY transacoes.data_transacao DESC";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();

$resultado = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Minhas transações - Koplo</title>
</head>

<body>

    <h1>Minhas transações</h1>

    <p>
        Usuário:
        <?php echo htmlspecialchars($_SESSION["usuario_nome"]); ?>
    </p>

    <?php if ($resultado->num_rows > 0) { ?>

        <table border="1" cellpadding="10">

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

                <?php while ($transacao = $resultado->fetch_assoc()) { ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($transacao["categoria"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($transacao["descricao"]); ?>
                        </td>

                        <td>
                            R$ <?php echo number_format($transacao["valor"], 2, ",", "."); ?>
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

                        <td>
                            <a href="editar_transacao.php?id=<?php echo $transacao["id"]; ?>">
                                Editar
                            </a>

                            <a
                                href="excluir_transacao.php?id=<?php echo $transacao["id"]; ?>"
                                onclick="return confirm('Tem certeza que deseja excluir esta transação?');"
                            >
                                Excluir
                            </a>
                        </td>

                    </tr>

                <?php } ?>

            </tbody>

        </table>

    <?php } else { ?>

        <p>Você ainda não possui nenhuma transação cadastrada.</p>

    <?php } ?>

</body>

</html>

<?php

$stmt->close();

?>
