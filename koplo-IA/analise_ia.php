<?php
// Ativa exibição de erros temporariamente para depuração, se necessário
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once "protecao.php";
require_once "conexao.php";

$usuario_id = $_SESSION["usuario_id"];
$usuario_nome = $_SESSION["usuario_nome"];
$resultado_ia = "";
$erro_ia = "";

// 1. LER O TOKEN DE FORMA SEGURA DO .env
$caminho_env = dirname(__FILE__) . '/acess.env';
if (file_exists($caminho_env)) {
    $env = parse_ini_file($caminho_env);
    $hf_token = $env['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'] ?? '';
} else {
    $hf_token = '';
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["analisar"])) {
    
    if (empty($hf_token)) {
        $erro_ia = "Token da API não configurado corretamente no arquivo .env.";
    } else {
        // 2. BUSCAR DADOS FINANCEIROS DO USUÁRIO NO BANCO
        $sql_totais = "SELECT 
                        SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) AS total_receitas,
                        SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) AS total_despesas
                       FROM transacoes WHERE usuario_id = ?";
        
        $stmt = $conexao->prepare($sql_totais);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $dados = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $receitas = $dados['total_receitas'] ?? 0;
        $despesas = $dados['total_despesas'] ?? 0;
        $saldo = $receitas - $despesas;

        // 3. DEFINIÇÃO DA ENTRADA (PROMPT ESTRUTURADO)
        $prompt = "Atue como um consultor financeiro especialista. Analise os seguintes dados do usuário $usuario_nome: ";
        $prompt .= "Receitas Totais: R$ " . number_format($receitas, 2, ',', '.') . ", ";
        $prompt .= "Despesas Totais: R$ " . number_format($despesas, 2, ',', '.') . ", ";
        $prompt .= "Saldo Atual: R$ " . number_format($saldo, 2, ',', '.') . ". ";
        $prompt .= "Forneça uma análise concisa dividida em 3 tópicos práticos de melhoria financeira. Responda em português.";

        // 4. CONFIGURAÇÃO DA API DO HUGGING FACE (INFERENCE PROVIDERS)
        // Usando o modelo Qwen/Qwen2.5-Coder-32B-Instruct via router oficial do HF
        $url = "https://router.huggingface.co/hf-inference/models/Qwen/Qwen2.5-Coder-32B-Instruct";
        
        $data = [
            "inputs" => $prompt,
            "parameters" => [
                "max_new_tokens" => 300,
                "temperature" => 0.6,
                "return_full_text" => false
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $hf_token,
            "Content-Type: application/json"
        ]);

        $resposta_json = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 5. TRATAMENTO DA SAÍDA
        if ($http_code == 200) {
            $resposta = json_decode($resposta_json, true);
            
            // O formato de retorno pode variar dependendo do provider (array ou objeto)
            if (isset($resposta[0]['generated_text'])) {
                $resultado_ia = trim($resposta[0]['generated_text']);
            } elseif (isset($resposta['generated_text'])) {
                $resultado_ia = trim($resposta['generated_text']);
            } else {
                $resultado_ia = "Resposta recebida, mas em formato inesperado: " . htmlspecialchars($resposta_json);
            }
        } else {
            $erro_ia = "Erro na comunicação com a API (HTTP Code: $http_code). Verifique se o token no arquivo .env é válido.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koplo IA - Análise Inteligente</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .page-header { margin-bottom: 25px; }
        .page-title { font-size: 25px; font-weight: 700; color: #17212b; margin-bottom: 5px; }
        .page-subtitle { color: #87949c; font-size: 13px; }
        .ia-card { background: #ffffff; border: 1px solid #e9edef; border-radius: 10px; padding: 25px; box-shadow: 0 2px 8px rgba(15, 30, 40, 0.025); max-width: 850px; }
        .btn-analisar { background: #31b984; color: #fff; border: none; padding: 12px 20px; border-radius: 7px; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-analisar:hover { background: #269e70; }
        .resultado-box { margin-top: 25px; padding: 20px; background: #f8fafb; border-left: 4px solid #31b984; border-radius: 4px 8px 8px 4px; color: #26343d; font-size: 14px; line-height: 1.6; white-space: pre-wrap; }
        .erro-box { margin-top: 20px; padding: 15px; background: #fff1f1; border: 1px solid #f2d1d1; border-radius: 7px; color: #b84b4b; font-size: 13px; }
        .info-api-box { background: #f0f7f4; border: 1px solid #d4eadf; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; color: #216e53; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="layout">
        <?php include "sidebar.php"; ?>
        
        <main class="content">
            <header class="page-header">
                <div>
                    <h1 class="page-title">Koplo IA 🤖</h1>
                    <p class="page-subtitle">Assistente financeiro integrado via Inference Providers do Hugging Face.</p>
                </div>
            </header>

            <section class="ia-card">
                <div class="info-api-box">
                    <strong>Detalhes da API Utilizada:</strong><br>
                    • <strong>Nome do Modelo:</strong> <code>Qwen/Qwen2.5-Coder-32B-Instruct</code><br>
                    • <strong>Finalidade:</strong> Análise preditiva e conselhos práticos de gestão financeira pessoal.<br>
                    • <strong>Tipo de Entrada (Input):</strong> String contendo o resumo consolidado de receitas, despesas e saldo extraídos do banco de dados MySQL.<br>
                    • <strong>Tipo de Saída (Output):</strong> Texto formatado em tópicos gerado via inferência em nuvem.
                </div>

                <form method="POST">
                    <button type="submit" name="analisar" class="btn-analisar">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2v20"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        Gerar Análise com IA
                    </button>
                </form>

                <?php if ($erro_ia !== ""): ?>
                    <div class="erro-box"><?= htmlspecialchars($erro_ia) ?></div>
                <?php endif; ?>

                <?php if ($resultado_ia !== ""): ?>
                    <div class="resultado-box"><strong>Parecer da Inteligência Artificial:</strong><br><br><?= htmlspecialchars($resultado_ia) ?></div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>