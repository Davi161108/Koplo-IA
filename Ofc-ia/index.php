<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Koplo</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #17212b;
            color: #ffffff;
            width: 100%;
            height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;

            overflow: hidden;
        }

        .logo {
            font-family: Arial, sans-serif;
            font-size: 95px;
            font-weight: 700;
            color: #ffffff;

            overflow: hidden;
        }

        .logo span {
            display: inline-block;

            opacity: 0;
            transform: translateY(40px);

            transition:
                transform 1.3s ease-out,
                opacity 1.3s ease-out;
        }
    </style>
</head>

<body>

    <h1 class="logo" id="logo">Koplo</h1>


    <script>
        const logo = document.getElementById("logo");

        const texto = logo.textContent;

        logo.textContent = "";


        [...texto].forEach((letra, index) => {

            const span = document.createElement("span");

            span.textContent = letra;
            if (index === 1) {
                span.style.color = "#31a77c";
            }

            logo.appendChild(span);


            setTimeout(() => {

                span.style.opacity = "1";
                span.style.transform = "translateY(0)";

                if (index === texto.length - 1) {
                    setTimeout(() => {
                        window.location.href = "login.php";
                    }, 2000);
                }

            }, index * 350);

        });
    </script>

</body>

</html>