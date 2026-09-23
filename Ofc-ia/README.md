# Koplo - Gestão Financeira Inteligente

**Integrantes:** Davi Aguiar; Gabrielle Vitória
**Objetivo:** Desenvolver uma aplicação web de controle financeiro que não apenas registre receitas e despesas, mas que também forneça análises e conselhos personalizados utilizando Inteligência Artificial.

## Modelo de Inteligência Artificial Utilizado
**Modelo:** `Qwen/Qwen2.5-Coder-32B-Instruct`
**Link no Hugging Face:** (https://huggingface.co/Qwen/Qwen2.5-Coder-32B-Instruct)
**Como funciona:** A aplicação compila os dados financeiros do usuário (totais de receitas, despesas e saldo) e envia um prompt estruturado em formato JSON via cURL para a Inference API do Hugging Face. O modelo do Google processa os números e retorna conselhos práticos e personalizados.

### Exemplo de Fluxo:
**Exemplo de Entrada (Prompt):** "O usuário teve R$ 5.000,00 de receita e R$ 4.800,00 de despesa. O saldo é R$ 200,00. Analise a situação em 3 tópicos."
**Exemplo de Resultado (Saída da IA):** "1. Cuidado com o limite: Suas despesas comprometeram 96% da sua renda. 2. Reveja gastos: Procure despesas não essenciais para cortar. 3. Fundo de emergência: Seu saldo atual de R$ 200,00 dificulta a criação de uma reserva sólida."

## 🚀 Como Executar o Projeto

1. **Clone o repositório:**
   ```bash
   git clone [https://github.com/Davi161108/Koplo-IA](https://github.com/Davi161108/Koplo-IA)