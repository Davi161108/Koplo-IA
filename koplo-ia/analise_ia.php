<?php

require_once "protecao.php";
require_once "conexao.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ======================================================
// 1. HISTÓRICO DA CONVERSA
// ======================================================

if (!isset($_SESSION["historico_ia"])) {
    $_SESSION["historico_ia"] = [];
}


// ======================================================
// 2. NOVA CONVERSA
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["nova_conversa"])) {

    $_SESSION["historico_ia"] = [];

    header("Location: analise_ia.php");
    exit;
}


// ======================================================
// 3. TOKEN DO HUGGING FACE
// ======================================================

$caminho_env = dirname(__FILE__) . "/.env";

if (file_exists($caminho_env)) {

    $env = parse_ini_file($caminho_env);

    if ($env !== false) {
        $hf_token = trim($env["HF_TOKEN"] ?? "");
    } else {
        $hf_token = "";
    }

} else {
    $hf_token = "";
}


$erro_ia = "";


// ======================================================
// 4. QUANDO O USUÁRIO ENVIA UMA PERGUNTA
// ======================================================

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["enviar_pergunta"])) {

    $pergunta_usuario = trim($_POST["pergunta"] ?? "");


    if ($pergunta_usuario === "") {

        $erro_ia = "Digite uma pergunta.";

    } elseif ($hf_token === "") {

        $erro_ia = "Token do Hugging Face não encontrado.";

    } else {

        $usuario_id = $_SESSION["usuario_id"];


        // ==================================================
        // 5. BUSCAR TRANSAÇÕES
        // ==================================================

        $sql_transacoes = "
            SELECT
                t.valor,
                t.tipo,
                t.descricao,
                t.data_transacao,
                c.nome AS categoria

            FROM transacoes t

            LEFT JOIN categorias c
                ON t.categoria_id = c.id

            WHERE t.usuario_id = ?

            ORDER BY t.data_transacao DESC
        ";


        $stmt = $conexao->prepare($sql_transacoes);


        if ($stmt) {

            $stmt->bind_param("i", $usuario_id);

            $stmt->execute();

            $resultado = $stmt->get_result();

        } else {

            $resultado = false;

            $erro_ia = "Erro ao consultar as transações.";
        }


        // ==================================================
        // 6. ORGANIZAR TRANSAÇÕES
        // ==================================================

        $transacoes = [];

        $total_receitas = 0;

        $total_despesas = 0;

        $gastos_por_categoria = [];

        $maior_despesa = null;


        if ($resultado) {

            while ($linha = $resultado->fetch_assoc()) {

                $valor = (float) $linha["valor"];

                $tipo = $linha["tipo"];

                $categoria =
                    $linha["categoria"] ?? "Sem categoria";

                $descricao =
                    $linha["descricao"] ?? "Sem descrição";

                $data =
                    $linha["data_transacao"] ?? "";


                // ==========================================
                // RECEITA
                // ==========================================

                if ($tipo === "receita") {

                    $total_receitas += $valor;
                }


                // ==========================================
                // DESPESA
                // ==========================================

                else {

                    $total_despesas += $valor;


                    // --------------------------------------
                    // GASTOS POR CATEGORIA
                    // --------------------------------------

                    if (!isset($gastos_por_categoria[$categoria])) {

                        $gastos_por_categoria[$categoria] = 0;
                    }


                    $gastos_por_categoria[$categoria] += $valor;


                    // --------------------------------------
                    // MAIOR DESPESA
                    // --------------------------------------

                    if (
                        $maior_despesa === null ||
                        $valor > $maior_despesa["valor"]
                    ) {

                        $maior_despesa = [

                            "valor" => $valor,

                            "descricao" => $descricao,

                            "categoria" => $categoria,

                            "data" => $data
                        ];
                    }
                }


                // ==========================================
                // GUARDA A TRANSAÇÃO
                // ==========================================

                $transacoes[] = [

                    "valor" => $valor,

                    "tipo" => $tipo,

                    "descricao" => $descricao,

                    "data" => $data,

                    "categoria" => $categoria
                ];
            }
        }


        if ($stmt) {
            $stmt->close();
        }


        // ==================================================
        // 7. SALDO
        // ==================================================

        $saldo = $total_receitas - $total_despesas;


        // ==================================================
        // 8. ORGANIZAR CATEGORIAS
        // ==================================================

        arsort($gastos_por_categoria);


        // ==================================================
        // 9. BUSCAR METAS
        // ==================================================

        $sql_metas = "
            SELECT
                nome,
                valor_objetivo,
                valor_atual,
                data_limite

            FROM metas

            WHERE usuario_id = ?

            ORDER BY data_limite ASC
        ";


        $stmt_metas = $conexao->prepare($sql_metas);

        $metas = [];


        if ($stmt_metas) {

            $stmt_metas->bind_param("i", $usuario_id);

            $stmt_metas->execute();

            $resultado_metas = $stmt_metas->get_result();


            while ($meta = $resultado_metas->fetch_assoc()) {

                $valor_objetivo =
                    (float) $meta["valor_objetivo"];

                $valor_atual =
                    (float) $meta["valor_atual"];


                $percentual = 0;


                if ($valor_objetivo > 0) {

                    $percentual =
                        ($valor_atual / $valor_objetivo) * 100;
                }


                $metas[] = [

                    "nome" => $meta["nome"],

                    "valor_objetivo" =>
                        $valor_objetivo,

                    "valor_atual" =>
                        $valor_atual,

                    "data_limite" =>
                        $meta["data_limite"],

                    "percentual" =>
                        $percentual
                ];
            }


            $stmt_metas->close();
        }


        // ==================================================
        // 10. GASTOS POR CATEGORIA EM TEXTO
        // ==================================================

        $texto_categorias = "";


        if (count($gastos_por_categoria) > 0) {

            foreach (
                $gastos_por_categoria
                as $categoria => $valor
            ) {

                $valor_formatado = number_format(
                    $valor,
                    2,
                    ",",
                    "."
                );


                $texto_categorias .=
                    "- "
                    . $categoria
                    . ": R$ "
                    . $valor_formatado
                    . "\n";
            }

        } else {

            $texto_categorias =
                "Nenhum gasto por categoria.";
        }


        // ==================================================
        // 11. MAIOR DESPESA
        // ==================================================

        if ($maior_despesa !== null) {

            $maior_despesa_texto =
                "R$ "
                . number_format(
                    $maior_despesa["valor"],
                    2,
                    ",",
                    "."
                )
                . " | "
                . $maior_despesa["descricao"]
                . " | Categoria: "
                . $maior_despesa["categoria"]
                . " | Data: "
                . $maior_despesa["data"];

        } else {

            $maior_despesa_texto =
                "Nenhuma despesa cadastrada.";
        }


        // ==================================================
        // 12. TRANSFORMA TRANSAÇÕES EM TEXTO
        // ==================================================

        $texto_transacoes = "";


        if (count($transacoes) > 0) {

            foreach ($transacoes as $transacao) {

                $valor_formatado =
                    number_format(
                        $transacao["valor"],
                        2,
                        ",",
                        "."
                    );


                $texto_transacoes .=
                    "- "
                    . ucfirst($transacao["tipo"])
                    . " | R$ "
                    . $valor_formatado
                    . " | Descrição: "
                    . $transacao["descricao"]
                    . " | Categoria: "
                    . $transacao["categoria"]
                    . " | Data: "
                    . $transacao["data"]
                    . "\n";
            }

        } else {

            $texto_transacoes =
                "Nenhuma transação cadastrada.";
        }


        // ==================================================
        // 13. TRANSFORMA METAS EM TEXTO
        // ==================================================

        $texto_metas = "";


        if (count($metas) > 0) {

            foreach ($metas as $meta) {

                $objetivo =
                    number_format(
                        $meta["valor_objetivo"],
                        2,
                        ",",
                        "."
                    );


                $atual =
                    number_format(
                        $meta["valor_atual"],
                        2,
                        ",",
                        "."
                    );


                $percentual =
                    number_format(
                        $meta["percentual"],
                        1,
                        ",",
                        "."
                    );


                $texto_metas .=
                    "- Meta: "
                    . $meta["nome"]
                    . " | Objetivo: R$ "
                    . $objetivo
                    . " | Atual: R$ "
                    . $atual
                    . " | Progresso: "
                    . $percentual
                    . "% | Prazo: "
                    . $meta["data_limite"]
                    . "\n";
            }

        } else {

            $texto_metas =
                "Nenhuma meta cadastrada.";
        }


        // ==================================================
        // 14. NOME DO USUÁRIO
        // ==================================================

        $nome_usuario =
            $_SESSION["usuario_nome"] ?? "usuário";


        // ==================================================
        // 15. CONTEXTO DA IA
        // ==================================================

        $mensagem_sistema = "

Você é o Koplo IA, um assistente financeiro pessoal.

Seu objetivo é ajudar o usuário a entender e organizar suas finanças.

Responda sempre em português.

Seja claro, natural e objetivo.

Use os dados financeiros fornecidos abaixo.

NÃO invente informações.

NÃO invente transações.

NÃO invente valores.

NÃO invente categorias.

NÃO invente metas.

Se uma informação não estiver disponível, diga claramente que não possui essa informação.

Quando fizer cálculos, utilize os valores fornecidos.

Nunca revele tokens, senhas, credenciais ou informações internas do sistema.


==================================================
USUÁRIO
==================================================

Nome: {$nome_usuario}


==================================================
RESUMO FINANCEIRO
==================================================

Total de receitas:
R$ " . number_format(
            $total_receitas,
            2,
            ",",
            "."
        ) . "

Total de despesas:
R$ " . number_format(
            $total_despesas,
            2,
            ",",
            "."
        ) . "

Saldo:
R$ " . number_format(
            $saldo,
            2,
            ",",
            "."
        ) . "


==================================================
GASTOS POR CATEGORIA
==================================================

{$texto_categorias}


==================================================
MAIOR DESPESA
==================================================

{$maior_despesa_texto}


==================================================
TRANSAÇÕES DO USUÁRIO
==================================================

{$texto_transacoes}


==================================================
METAS DO USUÁRIO
==================================================

{$texto_metas}


==================================================
COMO RESPONDER
==================================================

Quando o usuário perguntar sobre gastos, utilize as transações e os gastos por categoria.

Quando perguntar qual categoria possui mais gastos, utilize os gastos por categoria.

Quando perguntar qual foi a maior despesa, utilize a informação de maior despesa.

Quando perguntar sobre metas, utilize os dados das metas.

Quando perguntar sobre receitas, despesas ou saldo, utilize o resumo financeiro.

Quando houver necessidade de cálculo, faça o cálculo utilizando os dados fornecidos.

Não diga que acessou diretamente o banco de dados.

Apenas utilize os dados fornecidos pelo sistema.

";


        // ==================================================
        // 16. MONTA AS MENSAGENS
        // ==================================================

        $mensagens_para_api = [];


        // Sistema

        $mensagens_para_api[] = [

            "role" => "system",

            "content" => $mensagem_sistema
        ];


        // Histórico

        foreach (
            $_SESSION["historico_ia"]
            as $mensagem
        ) {

            $mensagens_para_api[] = [

                "role" =>
                    $mensagem["role"],

                "content" =>
                    $mensagem["content"]
            ];
        }


        // Pergunta atual

        $mensagens_para_api[] = [

            "role" => "user",

            "content" => $pergunta_usuario
        ];


        // ==================================================
        // 17. HUGGING FACE
        // ==================================================

        $url =
            "https://router.huggingface.co/v1/chat/completions";


        $modelo =
            "Qwen/Qwen2.5-Coder-32B-Instruct";


        $data = [

            "model" => $modelo,

            "messages" =>
                $mensagens_para_api,

            "max_tokens" => 500,

            "temperature" => 0.6,

            "stream" => false
        ];


        // ==================================================
        // 18. CURL
        // ==================================================

        $ch = curl_init($url);


        curl_setopt_array($ch, [

            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_POST => true,

            CURLOPT_POSTFIELDS =>
                json_encode($data),

            CURLOPT_HTTPHEADER => [

                "Authorization: Bearer "
                . $hf_token,

                "Content-Type: application/json"
            ],

            CURLOPT_TIMEOUT => 60,

            CURLOPT_CONNECTTIMEOUT => 15
        ]);


        $resposta = curl_exec($ch);


        $http_code =
            curl_getinfo(
                $ch,
                CURLINFO_HTTP_CODE
            );


        $curl_error =
            curl_error($ch);


        curl_close($ch);


        // ==================================================
        // 19. VERIFICAR RESPOSTA
        // ==================================================

        if ($resposta === false) {

            $erro_ia =
                "Erro ao conectar com a IA: "
                . $curl_error;

        } else {

            $resposta_json =
                json_decode(
                    $resposta,
                    true
                );


            if (
                $http_code >= 200 &&
                $http_code < 300 &&
                isset(
                    $resposta_json["choices"][0]["message"]["content"]
                )
            ) {

                $resposta_ia =
                    trim(
                        $resposta_json["choices"][0]["message"]["content"]
                    );


                // ==================================================
                // 20. SALVAR PERGUNTA
                // ==================================================

                $_SESSION["historico_ia"][] = [

                    "role" => "user",

                    "content" =>
                        $pergunta_usuario
                ];


                // ==================================================
                // 21. SALVAR RESPOSTA
                // ==================================================

                $_SESSION["historico_ia"][] = [

                    "role" => "assistant",

                    "content" =>
                        $resposta_ia
                ];


                // ==================================================
                // 22. LIMITAR HISTÓRICO
                // ==================================================

                if (
                    count(
                        $_SESSION["historico_ia"]
                    ) > 20
                ) {

                    $_SESSION["historico_ia"] =
                        array_slice(
                            $_SESSION["historico_ia"],
                            -20
                        );
                }


            } else {

                $mensagem_erro_api = "";


                if (
                    isset(
                        $resposta_json["error"]
                    )
                ) {

                    if (
                        is_array(
                            $resposta_json["error"]
                        )
                    ) {

                        $mensagem_erro_api =
                            $resposta_json["error"]["message"]
                            ??
                            json_encode(
                                $resposta_json["error"]
                            );

                    } else {

                        $mensagem_erro_api =
                            $resposta_json["error"];
                    }

                } else {

                    $mensagem_erro_api =
                        $resposta;
                }


                $erro_ia =
                    "Erro da IA (HTTP {$http_code}): "
                    .
                    htmlspecialchars(
                        $mensagem_erro_api
                    );
            }
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

    <title>Koplo IA</title>


    <link
        rel="stylesheet"
        href="dashboard.css"
    >


    <style>

        .chat-card {

            background: #ffffff;

            border-radius: 20px;

            box-shadow:
                0 4px 20px rgba(0, 0, 0, 0.08);

            overflow: hidden;

            max-width: 1000px;

            margin: 30px auto;
        }


        .chat-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 20px 25px;

            border-bottom:
                1px solid #eeeeee;
        }


        .chat-header-left {

            display: flex;

            align-items: center;

            gap: 15px;
        }


        .ai-avatar {

            width: 48px;

            height: 48px;

            border-radius: 50%;

            background: #001f3f;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: bold;

            font-size: 18px;
        }


        .chat-title {

            font-size: 18px;

            font-weight: 700;

            color: #222;
        }


        .chat-status {

            font-size: 13px;

            color: #777;

            margin-top: 3px;
        }


        .nova-conversa-btn {

            border: 1px solid #ddd;

            background: white;

            color: #333;

            padding: 10px 15px;

            border-radius: 10px;

            cursor: pointer;

            font-size: 13px;

            transition: 0.2s;
        }


        .nova-conversa-btn:hover {

            background: #f5f5f5;
        }


        .chat-messages {

            height: 500px;

            overflow-y: auto;

            padding: 25px;

            background: #f8f9fb;

            display: flex;

            flex-direction: column;

            gap: 15px;
        }


        .message {

            display: flex;

            width: 100%;
        }


        .message.user {

            justify-content: flex-end;
        }


        .message.ai {

            justify-content: flex-start;
        }


        .message-bubble {

            max-width: 75%;

            padding: 13px 17px;

            border-radius: 16px;

            line-height: 1.5;

            font-size: 14px;

            white-space: pre-wrap;

            word-wrap: break-word;
        }


        .message.user .message-bubble {

            background: #001f3f;

            color: white;

            border-bottom-right-radius: 5px;
        }


        .message.ai .message-bubble {

            background: white;

            color: #333;

            border: 1px solid #eeeeee;

            border-bottom-left-radius: 5px;
        }


        .chat-input-area {

            padding: 20px;

            border-top:
                1px solid #eeeeee;

            background: white;
        }


        .chat-form {

            display: flex;

            gap: 10px;
        }


        .chat-input {

            flex: 1;

            border: 1px solid #ddd;

            border-radius: 12px;

            padding: 13px 15px;

            font-size: 14px;

            outline: none;
        }


        .chat-input:focus {

            border-color: #001f3f;
        }


        .chat-send {

            border: none;

            background: #001f3f;

            color: white;

            padding: 0 22px;

            border-radius: 12px;

            cursor: pointer;

            font-weight: 600;
        }


        .chat-send:hover {

            opacity: 0.9;
        }


        .erro-ia {

            margin: 15px 20px;

            padding: 12px 15px;

            background: #ffecec;

            color: #b00020;

            border-radius: 10px;

            font-size: 14px;
        }


        @media (max-width: 600px) {

            .chat-card {

                margin: 15px 10px;

                border-radius: 15px;
            }


            .chat-header {

                padding: 15px;
            }


            .chat-title {

                font-size: 16px;
            }


            .nova-conversa-btn {

                padding: 8px 10px;

                font-size: 12px;
            }


            .chat-messages {

                height: 450px;

                padding: 15px;
            }


            .message-bubble {

                max-width: 85%;

                font-size: 13px;
            }


            .chat-form {

                flex-direction: column;
            }


            .chat-send {

                height: 45px;
            }
        }

    </style>

</head>


<body>


<div class="layout">


    <?php include "sidebar.php"; ?>


    <main class="content">


        <div class="chat-card">


            <!-- CABEÇALHO -->

            <div class="chat-header">


                <div class="chat-header-left">

                    <div class="ai-avatar">
                        K
                    </div>


                    <div>

                        <div class="chat-title">
                            Koplo IA
                        </div>

                        <div class="chat-status">
                            Assistente financeiro
                        </div>

                    </div>

                </div>


                <!-- NOVA CONVERSA -->

                <form method="POST">

                    <button
                        type="submit"
                        name="nova_conversa"
                        class="nova-conversa-btn"
                    >
                        Nova conversa
                    </button>

                </form>


            </div>


            <!-- MENSAGENS -->

            <div class="chat-messages">


                <?php if (empty($_SESSION["historico_ia"])): ?>


                    <div class="message ai">

                        <div class="message-bubble">

                            Olá! 👋

                            Sou a Koplo IA. Agora consigo analisar seus dados financeiros, como transações, categorias e metas.

                            Pode me perguntar sobre seus gastos, receitas ou objetivos!

                        </div>

                    </div>


                <?php else: ?>


                    <?php foreach (
                        $_SESSION["historico_ia"]
                        as $mensagem
                    ): ?>


                        <?php

                        $classe_mensagem =
                            $mensagem["role"] === "user"
                            ? "user"
                            : "ai";

                        ?>


                        <div
                            class="message <?= $classe_mensagem ?>"
                        >

                            <div class="message-bubble">

                                <?= htmlspecialchars(
                                    $mensagem["content"]
                                ) ?>

                            </div>

                        </div>


                    <?php endforeach; ?>


                <?php endif; ?>


            </div>


            <!-- ERRO -->

            <?php if ($erro_ia !== ""): ?>

                <div class="erro-ia">

                    <?= $erro_ia ?>

                </div>

            <?php endif; ?>


            <!-- INPUT -->

            <div class="chat-input-area">


                <form
                    method="POST"
                    class="chat-form"
                >

                    <input
                        type="text"
                        name="pergunta"
                        class="chat-input"
                        placeholder="Digite sua pergunta..."
                        autocomplete="off"
                    >


                    <button
                        type="submit"
                        name="enviar_pergunta"
                        class="chat-send"
                    >
                        Enviar
                    </button>

                </form>

            </div>


        </div>

    </main>

</div>


</body>

</html>