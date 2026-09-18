<?php

session_start();

require_once "conexao.php";

$erro = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÃO
    |--------------------------------------------------------------------------
    */

    if ($email === "") {

        $erro = "Informe seu e-mail.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = "Informe um e-mail válido.";

    } elseif ($senha === "") {

        $erro = "Informe sua senha.";

    }


    /*
    |--------------------------------------------------------------------------
    | AUTENTICAÇÃO
    |--------------------------------------------------------------------------
    */

    if ($erro === "") {

        $sql = "SELECT id, nome, email, senha
                FROM usuarios
                WHERE email = ?
                LIMIT 1";

        $stmt = $conexao->prepare($sql);

        if (!$stmt) {

            $erro = "Não foi possível realizar o login.";

        } else {

            $stmt->bind_param(
                "s",
                $email
            );

            $stmt->execute();

            $resultado = $stmt->get_result();

            $usuario = $resultado->fetch_assoc();


            if (
                $usuario &&
                password_verify(
                    $senha,
                    $usuario["senha"]
                )
            ) {

                session_regenerate_id(true);

                $_SESSION["usuario_id"] =
                    $usuario["id"];

                $_SESSION["usuario_nome"] =
                    $usuario["nome"];

                $stmt->close();

                header("Location: dashboard.php");
                exit;

            } else {

                $erro = "E-mail ou senha incorretos.";
            }

            $stmt->close();
        }
    }
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

    <title>Login - Koplo</title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;

            padding: 24px;

            background: #17212b;

            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;

            color: #26343d;
        }


        .login-container {
            width: 100%;
            max-width: 420px;
        }


        .login-brand {
            text-align: center;
            margin-bottom: 28px;
        }


        .logo {
            display: inline-flex;
            align-items: center;

            color: #ffff;

            font-size: 40px;
            font-weight: 700;
            letter-spacing: -1.5px;
        }


        .logo-mark {
            color: #31a77c;
        }


        .login-subtitle {
            margin-top: 8px;

            color: #81909a;

            font-size: 13px;
            line-height: 1.5;
        }


        .login-card {
            background: #ffffff;

            border: 1px solid #e6ebed;

            border-radius: 14px;

            padding: 32px;

            box-shadow:
                0 10px 35px rgba(20, 35, 45, 0.06);
        }


        .login-title {
            margin-bottom: 6px;

            color: #26343d;

            font-size: 21px;
            font-weight: 650;
            letter-spacing: -0.4px;
        }


        .login-description {
            margin-bottom: 25px;

            color: #81909a;

            font-size: 13px;
            line-height: 1.5;
        }


        .form-group {
            margin-bottom: 18px;
        }


        .form-label {
            display: block;

            margin-bottom: 7px;

            color: #53636d;

            font-size: 12px;
            font-weight: 600;
        }


        .form-input {
            width: 100%;

            height: 44px;

            padding: 0 13px;

            border: 1px solid #dfe5e7;
            border-radius: 8px;

            outline: none;

            background: #ffffff;

            color: #26343d;

            font-family: inherit;
            font-size: 13px;

            transition:
                border-color 0.18s ease,
                box-shadow 0.18s ease;
        }


        .form-input:hover {
            border-color: #cbd5d8;
        }


        .form-input:focus {
            border-color: #31a77c;

            box-shadow:
                0 0 0 3px rgba(49, 167, 124, 0.10);
        }


        .form-input::placeholder {
            color: #a4afb4;
        }


        .error-message {
            display: flex;
            align-items: center;

            margin-bottom: 20px;

            padding: 11px 12px;

            border: 1px solid #f0d5d5;
            border-radius: 8px;

            background: #fff6f6;

            color: #c84d4d;

            font-size: 12px;
            line-height: 1.4;
        }


        .login-button {
            width: 100%;

            height: 44px;

            margin-top: 5px;

            border: none;
            border-radius: 8px;

            background: #31a77c;

            color: #ffffff;

            font-family: inherit;
            font-size: 13px;
            font-weight: 600;

            cursor: pointer;

            transition:
                background 0.18s ease,
                transform 0.18s ease,
                box-shadow 0.18s ease;
        }


        .login-button:hover {
            background: #29966f;

            box-shadow:
                0 5px 15px rgba(49, 167, 124, 0.18);
        }


        .login-button:active {
            transform: translateY(1px);
        }


        .login-footer {
            margin-top: 22px;

            text-align: center;

            color: #81909a;

            font-size: 12px;
        }


        .login-footer a {
            color: #29966f;

            font-weight: 600;

            text-decoration: none;
        }


        .login-footer a:hover {
            text-decoration: underline;
        }


        @media (max-width: 480px) {

            body {
                padding: 18px;
            }


            .login-card {
                padding: 25px 21px;
            }


            .logo {
                font-size: 27px;
            }

        }

    </style>

</head>

<body>


    <main class="login-container">


        <div class="login-brand">

            <div class="logo">
                K<span class="logo-mark">o</span>plo
            </div>

            <p class="login-subtitle">
                Seu controle financeiro, de forma simples.
            </p>

        </div>


        <section class="login-card">

            <h1 class="login-title">
                Bem-vindo de volta
            </h1>

            <p class="login-description">
                Entre na sua conta para continuar.
            </p>


            <?php if ($erro !== "") { ?>

                <div class="error-message">

                    <?php
                    echo htmlspecialchars($erro);
                    ?>

                </div>

            <?php } ?>


            <form method="POST">


                <div class="form-group">

                    <label
                        class="form-label"
                        for="email"
                    >
                        E-mail
                    </label>

                    <input
                        class="form-input"
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email); ?>"
                        placeholder="seu@email.com"
                        autocomplete="email"
                        required
                    >

                </div>


                <div class="form-group">

                    <label
                        class="form-label"
                        for="senha"
                    >
                        Senha
                    </label>

                    <input
                        class="form-input"
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Digite sua senha"
                        autocomplete="current-password"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="login-button"
                >
                    Entrar
                </button>


            </form>


            <div class="login-footer">

                Ainda não possui uma conta?

                <a href="cadastro.php">
                    Criar conta
                </a>

            </div>

        </section>


    </main>


</body>

</html>