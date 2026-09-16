<?php

require_once "protecao.php";
require_once "conexao.php";

if (!isset($_SESSION["usuario_id"])) {
    die("Você precisa estar logado para acessar esta página.");
}

$usuario_id = $_SESSION["usuario_id"];
$usuario_nome = $_SESSION["usuario_nome"] ?? "Usuário";

$mensagem = "";
$erro = "";

// Busca os dados atuais do usuário
$sql = "SELECT nome, email FROM usuarios WHERE id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();

$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

$stmt->close();

if (!$usuario) {
    die("Usuário não encontrado.");
}

// Atualização do perfil
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["atualizar_perfil"])) {

    $nome = trim($_POST["nome"] ?? "");
    $email = trim($_POST["email"] ?? "");

    if ($nome === "" || $email === "") {
        $erro = "Preencha todos os campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Digite um e-mail válido.";
    } else {

        // Verifica se o e-mail já pertence a outro usuário
        $sql_verifica = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
        $stmt_verifica = $conexao->prepare($sql_verifica);
        $stmt_verifica->bind_param("si", $email, $usuario_id);
        $stmt_verifica->execute();

        $resultado_verifica = $stmt_verifica->get_result();

        if ($resultado_verifica->num_rows > 0) {

            $erro = "Este e-mail já está sendo utilizado.";

        } else {

            $sql_update = "UPDATE usuarios
                           SET nome = ?, email = ?
                           WHERE id = ?";

            $stmt_update = $conexao->prepare($sql_update);
            $stmt_update->bind_param(
                "ssi",
                $nome,
                $email,
                $usuario_id
            );

            if ($stmt_update->execute()) {

                $_SESSION["usuario_nome"] = $nome;

                $usuario["nome"] = $nome;
                $usuario["email"] = $email;
                $usuario_nome = $nome;

                $mensagem = "Dados atualizados com sucesso!";

            } else {
                $erro = "Não foi possível atualizar seus dados.";
            }

            $stmt_update->close();
        }

        $stmt_verifica->close();
    }
}

// Alteração de senha
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["alterar_senha"])) {

    $senha_atual = $_POST["senha_atual"] ?? "";
    $nova_senha = $_POST["nova_senha"] ?? "";
    $confirmar_senha = $_POST["confirmar_senha"] ?? "";

    if ($senha_atual === "" || $nova_senha === "" || $confirmar_senha === "") {

        $erro = "Preencha todos os campos da senha.";

    } elseif (strlen($nova_senha) < 6) {

        $erro = "A nova senha deve ter pelo menos 6 caracteres.";

    } elseif ($nova_senha !== $confirmar_senha) {

        $erro = "A confirmação da senha não corresponde.";

    } else {

        $sql_senha = "SELECT senha FROM usuarios WHERE id = ?";
        $stmt_senha = $conexao->prepare($sql_senha);
        $stmt_senha->bind_param("i", $usuario_id);
        $stmt_senha->execute();

        $resultado_senha = $stmt_senha->get_result();
        $dados_senha = $resultado_senha->fetch_assoc();

        $stmt_senha->close();

        if (!$dados_senha || !password_verify($senha_atual, $dados_senha["senha"])) {

            $erro = "A senha atual está incorreta.";

        } else {

            $senha_hash = password_hash(
                $nova_senha,
                PASSWORD_DEFAULT
            );

            $sql_update_senha = "UPDATE usuarios
                                 SET senha = ?
                                 WHERE id = ?";

            $stmt_update_senha = $conexao->prepare($sql_update_senha);
            $stmt_update_senha->bind_param(
                "si",
                $senha_hash,
                $usuario_id
            );

            if ($stmt_update_senha->execute()) {
                $mensagem = "Senha alterada com sucesso!";
            } else {
                $erro = "Não foi possível alterar a senha.";
            }

            $stmt_update_senha->close();
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

    <title>Configurações - Koplo</title>

    <link
        rel="stylesheet"
        href="dashboard.css"
    >

    <style>

        .settings-header {
            margin-bottom: 24px;
        }

        .settings-title {
            font-size: 27px;
            font-weight: 700;
            color: #17212b;
            margin-bottom: 6px;
        }

        .settings-subtitle {
            font-size: 14px;
            color: #7b8991;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 20px;
            max-width: 900px;
        }

        .settings-card {
            background: #ffffff;
            border: 1px solid #e8edef;
            border-radius: 11px;
            box-shadow: 0 2px 8px rgba(23, 33, 43, 0.035);
            overflow: hidden;
        }

        .settings-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 20px 22px;
            border-bottom: 1px solid #edf1f2;
        }

        .settings-card-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #eaf8f3;
            color: #31b984;
            flex-shrink: 0;
        }

        .settings-card-icon svg {
            width: 18px;
            height: 18px;
        }

        .settings-card-title {
            font-size: 14px;
            font-weight: 700;
            color: #263640;
        }

        .settings-card-description {
            margin-top: 3px;
            font-size: 12px;
            color: #8a969c;
        }

        .settings-card-body {
            padding: 23px 22px;
        }

        .settings-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px 18px;
        }

        .settings-field-full {
            grid-column: 1 / -1;
        }

        .settings-label {
            display: block;
            margin-bottom: 8px;
            color: #42515b;
            font-size: 13px;
            font-weight: 650;
        }

        .settings-input {
            width: 100%;
            height: 44px;
            padding: 0 13px;
            border: 1px solid #dfe5e7;
            border-radius: 7px;
            background: #ffffff;
            color: #33434c;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }

        .settings-input:hover {
            border-color: #cbd5d9;
        }

        .settings-input:focus {
            border-color: #31b984;
            box-shadow: 0 0 0 3px rgba(49, 185, 132, 0.09);
        }

        .settings-input::placeholder {
            color: #a9b2b7;
        }

        .settings-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 22px;
        }

        .settings-button {
            height: 42px;
            padding: 0 17px;
            border: none;
            border-radius: 7px;
            background: #31b984;
            color: #ffffff;
            font-family: inherit;
            font-size: 13px;
            font-weight: 650;
            cursor: pointer;
            transition:
                background 0.2s ease,
                transform 0.15s ease;
        }

        .settings-button:hover {
            background: #28a978;
        }

        .settings-button:active {
            transform: translateY(1px);
        }

        .password-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px 18px;
        }

        .password-current {
            grid-column: 1 / -1;
        }

        .settings-message {
            max-width: 900px;
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 7px;
            font-size: 13px;
            font-weight: 500;
        }

        .settings-message.success {
            background: #eaf8f3;
            border: 1px solid #c9eddf;
            color: #218963;
        }

        .settings-message.error {
            background: #fff1f1;
            border: 1px solid #f2d1d1;
            color: #b84b4b;
        }

        .logout-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .logout-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logout-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #f9eeee;
            color: #c65b5b;
            flex-shrink: 0;
        }

        .logout-icon svg {
            width: 18px;
            height: 18px;
        }

        .logout-title {
            font-size: 14px;
            font-weight: 700;
            color: #263640;
        }

        .logout-description {
            margin-top: 3px;
            font-size: 12px;
            color: #8a969c;
        }

        .logout-button {
            height: 40px;
            padding: 0 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e4cccc;
            border-radius: 7px;
            background: #ffffff;
            color: #b84b4b;
            font-family: inherit;
            font-size: 13px;
            font-weight: 650;
            cursor: pointer;
            transition:
                background 0.2s ease,
                border-color 0.2s ease;
        }

        .logout-button:hover {
            background: #fff6f6;
            border-color: #dcb6b6;
        }

        @media (max-width: 800px) {

            .settings-grid {
                max-width: 100%;
            }

            .settings-card-header {
                padding: 19px 20px;
            }

            .settings-card-body {
                padding: 21px 20px;
            }
        }

        @media (max-width: 600px) {

            .settings-title {
                font-size: 22px;
            }

            .settings-subtitle {
                font-size: 12px;
            }

            .settings-form,
            .password-form {
                grid-template-columns: 1fr;
            }

            .settings-field-full,
            .password-current {
                grid-column: auto;
            }

            .settings-card-header {
                padding: 17px 16px;
            }

            .settings-card-body {
                padding: 20px 16px;
            }

            .settings-actions {
                margin-top: 20px;
            }

            .settings-button {
                width: 100%;
            }

            .logout-card {
                align-items: flex-start;
                flex-direction: column;
            }

            .logout-button {
                width: 100%;
            }
        }

    </style>

</head>

<body>

<div class="layout">

    <?php include "sidebar.php"; ?>

    <main class="content">

        <!-- Topbar -->

        

        <!-- Cabeçalho -->

        <div class="settings-header">

            <h1 class="settings-title">
                Configurações
            </h1>

            <p class="settings-subtitle">
                Gerencie seus dados e as configurações da sua conta.
            </p>

        </div>

        <?php if ($mensagem !== ""): ?>

            <div class="settings-message success">
                <?= htmlspecialchars($mensagem); ?>
            </div>

        <?php endif; ?>

        <?php if ($erro !== ""): ?>

            <div class="settings-message error">
                <?= htmlspecialchars($erro); ?>
            </div>

        <?php endif; ?>

        <div class="settings-grid">

            <!-- Dados da conta -->

            <section class="settings-card">

                <div class="settings-card-header">

                    <div class="settings-card-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <circle cx="12" cy="8" r="3.5"></circle>
                            <path d="M5 20c.8-3.2 3.2-5 7-5s6.2 1.8 7 5"></path>
                        </svg>

                    </div>

                    <div>

                        <h2 class="settings-card-title">
                            Dados da conta
                        </h2>

                        <p class="settings-card-description">
                            Atualize suas informações pessoais.
                        </p>

                    </div>

                </div>

                <div class="settings-card-body">

                    <form method="POST">

                        <div class="settings-form">

                            <div class="settings-field-full">

                                <label
                                    class="settings-label"
                                    for="nome"
                                >
                                    Nome
                                </label>

                                <input
                                    class="settings-input"
                                    type="text"
                                    id="nome"
                                    name="nome"
                                    value="<?= htmlspecialchars($usuario["nome"]); ?>"
                                    required
                                >

                            </div>

                            <div class="settings-field-full">

                                <label
                                    class="settings-label"
                                    for="email"
                                >
                                    E-mail
                                </label>

                                <input
                                    class="settings-input"
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="<?= htmlspecialchars($usuario["email"]); ?>"
                                    required
                                >

                            </div>

                        </div>

                        <div class="settings-actions">

                            <button
                                type="submit"
                                name="atualizar_perfil"
                                class="settings-button"
                            >
                                Salvar alterações
                            </button>

                        </div>

                    </form>

                </div>

            </section>

            <!-- Segurança -->

            <section class="settings-card">

                <div class="settings-card-header">

                    <div class="settings-card-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <rect
                                x="5"
                                y="10"
                                width="14"
                                height="10"
                                rx="2"
                            ></rect>

                            <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>

                            <circle
                                cx="12"
                                cy="15"
                                r="1"
                            ></circle>

                        </svg>

                    </div>

                    <div>

                        <h2 class="settings-card-title">
                            Segurança
                        </h2>

                        <p class="settings-card-description">
                            Altere sua senha para manter sua conta protegida.
                        </p>

                    </div>

                </div>

                <div class="settings-card-body">

                    <form method="POST">

                        <div class="password-form">

                            <div class="password-current">

                                <label
                                    class="settings-label"
                                    for="senha_atual"
                                >
                                    Senha atual
                                </label>

                                <input
                                    class="settings-input"
                                    type="password"
                                    id="senha_atual"
                                    name="senha_atual"
                                    autocomplete="current-password"
                                    required
                                >

                            </div>

                            <div>

                                <label
                                    class="settings-label"
                                    for="nova_senha"
                                >
                                    Nova senha
                                </label>

                                <input
                                    class="settings-input"
                                    type="password"
                                    id="nova_senha"
                                    name="nova_senha"
                                    minlength="6"
                                    autocomplete="new-password"
                                    required
                                >

                            </div>

                            <div>

                                <label
                                    class="settings-label"
                                    for="confirmar_senha"
                                >
                                    Confirmar nova senha
                                </label>

                                <input
                                    class="settings-input"
                                    type="password"
                                    id="confirmar_senha"
                                    name="confirmar_senha"
                                    minlength="6"
                                    autocomplete="new-password"
                                    required
                                >

                            </div>

                        </div>

                        <div class="settings-actions">

                            <button
                                type="submit"
                                name="alterar_senha"
                                class="settings-button"
                            >
                                Alterar senha
                            </button>

                        </div>

                    </form>

                </div>

            </section>

            <!-- Sessão -->

            <section class="settings-card">

                <div class="settings-card-body logout-card">

                    <div class="logout-info">

                        <div class="logout-icon">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M10 17l5-5-5-5"></path>
                                <path d="M15 12H3"></path>
                                <path d="M21 3v18"></path>
                            </svg>

                        </div>

                        <div>

                            <h2 class="logout-title">
                                Encerrar sessão
                            </h2>

                            <p class="logout-description">
                                Saia da sua conta neste dispositivo.
                            </p>

                        </div>

                    </div>

                    <a
                        href="logout.php"
                        class="logout-button"
                    >
                        Sair da conta
                    </a>

                </div>

            </section>

        </div>

    </main>

</div>

<script>

function toggleProfileMenu() {

    const dropdown = document.getElementById("profileDropdown");

    dropdown.classList.toggle("show");
}

document.addEventListener("click", function(event) {

    const profileWrapper = document.querySelector(".profile-wrapper");
    const dropdown = document.getElementById("profileDropdown");

    if (
        profileWrapper &&
        !profileWrapper.contains(event.target)
    ) {
        dropdown.classList.remove("show");
    }

});

</script>

</body>

</html>