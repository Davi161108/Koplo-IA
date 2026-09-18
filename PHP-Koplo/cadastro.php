<?php

session_start();

require_once "conexao.php";

$erro = "";
$sucesso = "";

$nome = "";
$email = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";
    $confirmar_senha = $_POST["confirmar_senha"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÃO DO NOME
    |--------------------------------------------------------------------------
    */

    if ($nome === "") {

        $erro = "Informe seu nome.";

    } elseif (mb_strlen($nome) < 2) {

        $erro = "O nome deve ter pelo menos 2 caracteres.";

    } elseif (mb_strlen($nome) > 100) {

        $erro = "O nome deve ter no máximo 100 caracteres.";
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÃO DO E-MAIL
    |--------------------------------------------------------------------------
    */

    if ($erro === "") {

        if ($email === "") {

            $erro = "Informe seu e-mail.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $erro = "Informe um e-mail válido.";

        } elseif (mb_strlen($email) > 150) {

            $erro = "O e-mail deve ter no máximo 150 caracteres.";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÃO DA SENHA
    |--------------------------------------------------------------------------
    */

    if ($erro === "") {

        if ($senha === "") {

            $erro = "Informe uma senha.";

        } elseif (strlen($senha) < 6) {

            $erro = "A senha deve ter pelo menos 6 caracteres.";

        } elseif (strlen($senha) > 72) {

            $erro = "A senha deve ter no máximo 72 caracteres.";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CONFIRMAÇÃO DA SENHA
    |--------------------------------------------------------------------------
    */

    if ($erro === "") {

        if ($confirmar_senha === "") {

            $erro = "Confirme sua senha.";

        } elseif ($senha !== $confirmar_senha) {

            $erro = "As senhas não coincidem.";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFICA SE O E-MAIL JÁ EXISTE
    |--------------------------------------------------------------------------
    */

    if ($erro === "") {

        $sql_verificar = "SELECT id
                          FROM usuarios
                          WHERE email = ?
                          LIMIT 1";

        $stmt_verificar =
            $conexao->prepare($sql_verificar);

        if (!$stmt_verificar) {

            $erro =
                "Não foi possível verificar o cadastro.";

        } else {

            $stmt_verificar->bind_param(
                "s",
                $email
            );

            $stmt_verificar->execute();

            $resultado_verificar =
                $stmt_verificar->get_result();

            if ($resultado_verificar->num_rows > 0) {

                $erro =
                    "Este e-mail já está cadastrado.";
            }

            $stmt_verificar->close();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CADASTRO
    |--------------------------------------------------------------------------
    */

    if ($erro === "") {

        $senha_hash =
            password_hash(
                $senha,
                PASSWORD_DEFAULT
            );


        $sql = "INSERT INTO usuarios
                (nome, email, senha)
                VALUES (?, ?, ?)";

        $stmt = $conexao->prepare($sql);

        if (!$stmt) {

            $erro =
                "Não foi possível realizar o cadastro.";

        } else {

            $stmt->bind_param(
                "sss",
                $nome,
                $email,
                $senha_hash
            );


            if ($stmt->execute()) {

                $sucesso =
                    "Conta criada com sucesso! Agora você já pode entrar.";

                $nome = "";
                $email = "";

            } else {

                /*
                |--------------------------------------------------------------------------
                | TRATAMENTO DE E-MAIL DUPLICADO
                |--------------------------------------------------------------------------
                */

                if ($stmt->errno === 1062) {

                    $erro =
                        "Este e-mail já está cadastrado.";

                } else {

                    $erro =
                        "Não foi possível criar sua conta.";
                }
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

    <title>Cadastro - Koplo</title>


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


        .register-container {
            width: 100%;
            max-width: 420px;
        }


        .register-brand {
            text-align: center;
            margin-bottom: 28px;
        }


        .logo {
            display: inline-flex;
            align-items: center;

            color: #ffff;

            font-size: 30px;
            font-weight: 700;

            letter-spacing: -1.5px;
        }


        .logo-mark {
            color: #31a77c;
        }


        .register-subtitle {
            margin-top: 8px;

            color: #81909a;

            font-size: 13px;
            line-height: 1.5;
        }


        .register-card {
            background: #ffffff;

            border: 1px solid #e6ebed;

            border-radius: 14px;

            padding: 32px;

            box-shadow:
                0 10px 35px rgba(20, 35, 45, 0.06);
        }


        .register-title {
            margin-bottom: 6px;

            color: #26343d;

            font-size: 21px;
            font-weight: 650;

            letter-spacing: -0.4px;
        }


        .register-description {
            margin-bottom: 25px;

            color: #81909a;

            font-size: 13px;
            line-height: 1.5;
        }


        .form-group {
            margin-bottom: 17px;
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
            margin-bottom: 20px;

            padding: 11px 12px;

            border: 1px solid #f0d5d5;

            border-radius: 8px;

            background: #fff6f6;

            color: #c84d4d;

            font-size: 12px;

            line-height: 1.4;
        }


        .success-message {
            margin-bottom: 20px;

            padding: 11px 12px;

            border: 1px solid #d4eadf;

            border-radius: 8px;

            background: #f2faf6;

            color: #27805f;

            font-size: 12px;

            line-height: 1.4;
        }


        .register-button {
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


        .register-button:hover {
            background: #29966f;

            box-shadow:
                0 5px 15px rgba(49, 167, 124, 0.18);
        }


        .register-button:active {
            transform: translateY(1px);
        }


        .register-footer {
            margin-top: 22px;

            text-align: center;

            color: #81909a;

            font-size: 12px;
        }


        .register-footer a {
            color: #29966f;

            font-weight: 600;

            text-decoration: none;
        }


        .register-footer a:hover {
            text-decoration: underline;
        }


        .password-hint {
            margin-top: 6px;

            color: #9aa5aa;

            font-size: 11px;
        }


        @media (max-width: 480px) {

            body {
                padding: 18px;
            }


            .register-card {
                padding: 25px 21px;
            }


            .logo {
                font-size: 27px;
            }

        }

    </style>

</head>


<body>


    <main class="register-container">


        <div class="register-brand">

            <div class="logo">
                K<span class="logo-mark">o</span>plo
            </div>

            <p class="register-subtitle">
                Seu controle financeiro, de forma simples.
            </p>

        </div>


        <section class="register-card">

            <h1 class="register-title">
                Criar sua conta
            </h1>

            <p class="register-description">
                Preencha seus dados para começar a usar o Koplo.
            </p>


            <?php if ($erro !== "") { ?>

                <div class="error-message">

                    <?php
                    echo htmlspecialchars($erro);
                    ?>

                </div>

            <?php } ?>


            <?php if ($sucesso !== "") { ?>

                <div class="success-message">

                    <?php
                    echo htmlspecialchars($sucesso);
                    ?>

                </div>

            <?php } ?>


            <form method="POST">


                <div class="form-group">

                    <label
                        class="form-label"
                        for="nome"
                    >
                        Nome
                    </label>

                    <input
                        class="form-input"
                        type="text"
                        id="nome"
                        name="nome"
                        value="<?php echo htmlspecialchars($nome); ?>"
                        placeholder="Seu nome"
                        maxlength="100"
                        autocomplete="name"
                        required
                    >

                </div>


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
                        maxlength="150"
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
                        placeholder="Crie uma senha"
                        minlength="6"
                        maxlength="72"
                        autocomplete="new-password"
                        required
                    >

                    <p class="password-hint">
                        Mínimo de 6 caracteres.
                    </p>

                </div>


                <div class="form-group">

                    <label
                        class="form-label"
                        for="confirmar_senha"
                    >
                        Confirmar senha
                    </label>

                    <input
                        class="form-input"
                        type="password"
                        id="confirmar_senha"
                        name="confirmar_senha"
                        placeholder="Digite a senha novamente"
                        minlength="6"
                        maxlength="72"
                        autocomplete="new-password"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="register-button"
                >
                    Criar conta
                </button>


            </form>


            <div class="register-footer">

                Já possui uma conta?

                <a href="login.php">
                    Entrar
                </a>

            </div>


        </section>


    </main>


</body>

</html>