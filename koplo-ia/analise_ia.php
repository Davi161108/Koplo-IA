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
        content="width=device-width, initial-scale=1.0">

    <title>Koplo IA</title>


    <link
        rel="stylesheet"
        href="dashboard.css">


    <style>
        /* ======================================================
       ESTRUTURA PRINCIPAL
    ====================================================== */
        .content {
            margin-left: 240px;
            width: calc(100% - 240px);
            min-height: 100vh;
            padding: 32px 40px;
            display: flex;
            flex-direction: column;
        }

        /* CARD DO CHAT (Estilo Painel Pro) */
        .chat-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 12px 32px -4px rgba(9, 35, 51, 0.04), 0 4px 12px -2px rgba(9, 35, 51, 0.02);
            overflow: hidden;
            max-width: 960px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 104px);
        }

        /* ======================================================
       CABEÇALHO
    ====================================================== */
        .chat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 28px;
            background: #ffffff;
            border-bottom: 1px solid #edf2f7;
        }

        .chat-header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .ai-avatar {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: linear-gradient(135deg, #092333 0%, #123c4e 100%);
            color: #31c48d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 19px;
            letter-spacing: -0.5px;
            box-shadow: 0 6px 16px rgba(9, 35, 51, 0.12);
            position: relative;
        }

        .chat-title-group {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .chat-title {
            font-size: 17px;
            font-weight: 700;
            color: #092333;
            letter-spacing: -0.4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .badge-ia {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            background: rgba(49, 196, 141, 0.12);
            color: #1e8e63;
            padding: 2px 8px;
            border-radius: 20px;
        }

        .chat-status {
            font-size: 12px;
            color: #647784;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Indicador Pulsante */
        .status-dot {
            width: 7px;
            height: 7px;
            background-color: #31c48d;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 2px rgba(49, 196, 141, 0.2);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(49, 196, 141, 0.4);
            }

            70% {
                box-shadow: 0 0 0 6px rgba(49, 196, 141, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(49, 196, 141, 0);
            }
        }

        .nova-conversa-btn {
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #092333;
            padding: 9px 18px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nova-conversa-btn:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        /* ======================================================
       ÁREA DE MENSAGENS
    ====================================================== */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 28px;
            background: #f5f7f9;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .chat-messages::-webkit-scrollbar {
            width: 5px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: transparent;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
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
            max-width: 72%;
            padding: 16px 20px;
            border-radius: 16px;
            line-height: 1.6;
            font-size: 14.5px;
            white-space: pre-line;
            word-wrap: break-word;
            letter-spacing: -0.1px;
        }

        /* Balão do Usuário */
        .message.user .message-bubble {
            background: #092333;
            color: #ffffff;
            border-bottom-right-radius: 4px;
            box-shadow: 0 4px 14px rgba(9, 35, 51, 0.08);
            border-left: 3px solid #31c48d;
        }

        /* Balão da IA */
        .message.ai .message-bubble {
            background: #ffffff;
            color: #17212b;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        }

        /* ======================================================
       CAMPO DE ENTRADA (DOCK FLUTUANTE)
    ====================================================== */
        .chat-input-area {
            padding: 20px 28px;
            border-top: 1px solid #edf2f7;
            background: #ffffff;
        }

        .chat-form {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: 6px 8px 6px 18px;
            transition: all 0.2s ease;
        }

        .chat-form:focus-within {
            background: #ffffff;
            border-color: #31c48d;
            box-shadow: 0 0 0 4px rgba(49, 196, 141, 0.12);
        }

        .chat-input {
            flex: 1;
            border: none;
            background: transparent;
            font-size: 14.5px;
            color: #17212b;
            outline: none;
            padding: 8px 0;
        }

        .chat-input::placeholder {
            color: #94a3b8;
        }

        .chat-send {
            border: none;
            background: #31c48d;
            color: #ffffff;
            height: 42px;
            padding: 0 22px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .chat-send:hover {
            background: #28a777;
            box-shadow: 0 4px 14px rgba(49, 196, 141, 0.3);
            transform: translateY(-1px);
        }

        .chat-send:active {
            transform: translateY(0);
        }

        /* ======================================================
       ALERTAS E ERROS
    ====================================================== */
        .erro-ia {
            margin: 0 28px 16px;
            padding: 12px 16px;
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ======================================================
       RESPONSIVIDADE
    ====================================================== */
        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                width: 100%;
                padding: 16px;
            }

            .chat-card {
                height: calc(100vh - 32px);
                border-radius: 16px;
            }

            .chat-header {
                padding: 16px 20px;
            }

            .chat-messages {
                padding: 20px 16px;
            }

            .message-bubble {
                max-width: 85%;
            }

            .chat-input-area {
                padding: 16px;
            }

            .chat-form {
                padding: 4px 6px 4px 14px;
            }
        }

        /* ======================================================
   ESTRUTURA DAS MENSAGENS E AVATARES
====================================================== */
        .message {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            width: 100%;
        }

        .message.user {
            justify-content: flex-end;
        }

        .message.ai {
            justify-content: flex-start;
        }

        .message-content {
            display: flex;
            flex-direction: column;
            max-width: 72%;
        }

        .message.user .message-content {
            align-items: flex-end;
        }

        .message.ai .message-content {
            align-items: flex-start;
        }

        /* Nome do Autor acima do balão */
        .message-author {
            font-size: 11px;
            font-weight: 600;
            color: #647784;
            margin-bottom: 4px;
            padding: 0 4px;
        }

        /* Base do Avatar */
        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-weight: 700;
            font-size: 14px;
            margin-top: 18px;
            /* Alinha com a caixa do balão */
        }

        /* Avatar da IA */
        .message-avatar.ai-icon {
            background: linear-gradient(135deg, #092333 0%, #123c4e 100%);
            color: #31c48d;
            border: 1px solid rgba(49, 196, 141, 0.2);
            box-shadow: 0 2px 8px rgba(9, 35, 51, 0.08);
        }

        /* Avatar do Usuário */
        .message-avatar.user-icon {
            background: #ffffff;
            color: #092333;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
        }

        .message-avatar.user-icon svg {
            width: 18px;
            height: 18px;
            stroke: #092333;
        }

        /* Balões de Fala Ajustados */
        .message-bubble {
            width: 100%;
            padding: 14px 18px;
            border-radius: 14px;
            line-height: 1.6;
            font-size: 14.5px;
            white-space: pre-wrap;
            word-wrap: break-word;
            letter-spacing: -0.1px;
        }

        .message.user .message-bubble {
            background: #092333;
            color: #ffffff;
            border-top-right-radius: 2px;
            border-left: 3px solid #31c48d;
            box-shadow: 0 4px 14px rgba(9, 35, 51, 0.08);
        }

        .message.ai .message-bubble {
            background: #ffffff;
            color: #17212b;
            border: 1px solid #e2e8f0;
            border-top-left-radius: 2px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
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
                            class="nova-conversa-btn">
                            Nova conversa
                        </button>

                    </form>


                </div>


                <!-- MENSAGENS -->

                <!-- MENSAGENS -->
                <div class="chat-messages">

                    <?php if (empty($_SESSION["historico_ia"])): ?>

                        <!-- MENSAGEM INICIAL DA IA -->
                        <div class="message ai">
                            <div class="message-avatar ai-icon">K</div>
                            <div class="message-content">
                                <span class="message-author">Koplo IA</span>
                                <div class="message-bubble">
                                    Olá! Sou a Koplo IA. <br>Agora consigo analisar seus dados financeiros, como transações, categorias e metas. Pode me perguntar sobre seus gastos, receitas ou objetivos!
                                </div>
                            </div>
                        </div>

                    <?php else: ?>

                        <?php foreach ($_SESSION["historico_ia"] as $mensagem): ?>

                            <?php if ($mensagem["role"] === "user"): ?>

                                <!-- MENSAGEM DO USUÁRIO -->
                                <div class="message user">
                                    <div class="message-content">
                                        <span class="message-author"><?= htmlspecialchars($_SESSION["usuario_nome"] ?? "Você") ?></span>
                                        <div class="message-bubble">
                                            <?= htmlspecialchars($mensagem["content"]) ?>
                                        </div>
                                    </div>
                                    <div class="message-avatar user-icon">
                                        <!-- Ícone vetorial SVG do Usuário -->
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                    </div>
                                </div>

                            <?php else: ?>

                                <!-- MENSAGEM DA IA -->
                                <div class="message ai">
                                    <div class="message-avatar ai-icon">K</div>
                                    <div class="message-content">
                                        <span class="message-author">Koplo IA</span>
                                        <div class="message-bubble">
                                            <?= htmlspecialchars(trim($mensagem["content"])) ?>
                                        </div>
                                    </div>
                                </div>

                            <?php endif; ?>

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
                        class="chat-form">

                        <input
                            type="text"
                            name="pergunta"
                            class="chat-input"
                            placeholder="Digite sua pergunta..."
                            autocomplete="off">


                        <button
                            type="submit"
                            name="enviar_pergunta"
                            class="chat-send">
                            Enviar
                        </button>

                    </form>

                </div>


            </div>

        </main>

    </div>


</body>

</html>
