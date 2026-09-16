<?php

session_start();

require_once "conexao.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST["email"];
    $senha = $_POST["senha"];

    $sql = "SELECT * FROM usuarios WHERE email = ?";

    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {

        $usuario = $resultado->fetch_assoc();

        if (password_verify($senha, $usuario["senha"])) {

            // Cria a sessão do usuário
            $_SESSION["usuario_id"] = $usuario["id"];
            $_SESSION["usuario_nome"] = $usuario["nome"];

            // Redireciona para o dashboard
            header("Location: dashboard.php");
            exit;

        } else {

            $erro = "Senha incorreta.";

        }

    } else {

        $erro = "Usuário não encontrado.";

    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Login - Koplo</title>
</head>

<body>

    <h1>Login do Koplo</h1>

    <?php if (isset($erro)) { ?>
        <p><?php echo htmlspecialchars($erro); ?></p>
    <?php } ?>

    <form method="POST">

        <label>E-mail:</label>
        <input type="email" name="email" required>

        <br><br>

        <label>Senha:</label>
        <input type="password" name="senha" required>

        <br><br>

        <button type="submit">Entrar</button>

    </form>

</body>

</html>
