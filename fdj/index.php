<?php

require '../vendor/autoload.php';

use App\Session\Login;
use App\Entity\Usuario;

//OBRIGA O USUÁRIO A NÃO ESTAR LOGADO
Login::requireLogout();

if (isset($_POST['usuario'], $_POST['senha'])) {

    //BUSCA USUÁRIO POR USUÁRIO
    $objUsuario = Usuario::getUsuarioPorUsuario($_POST['usuario']);

    //VALIDA A INSTÂNCIA E A SENHA
    if (!$objUsuario instanceof Usuario or !password_verify($_POST['senha'], $objUsuario->senha)) {
        header('Location: ?status=erro');
        echo '<script>location.href="?status=erro";</script>';
        exit;
    }

    //LOGA O USUÁRIO
    Login::login($objUsuario);
}

?>
<!doctype html>
<html lang="pt-Br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Matheus Gatti">
    <title>Fonte das Joias | Entrar no Painel de Controle</title>

    <!-- Bootstrap core CSS -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CSS-->
    <link rel="stylesheet" href="/icons/bootstrap-icons.css">

    <!-- Favicons -->
    <link rel="apple-touch-icon" href="/images/LOGO.png" sizes="180x180">
    <link rel="icon" href="/images/LOGO.png" sizes="32x32" type="image/png">
    <link rel="icon" href="/images/LOGO.png" sizes="16x16" type="image/png">
    <link rel="manifest" href="/images/LOGO.png">
    <link rel="mask-icon" href="/images/LOGO.png">
    <link rel="icon" href="/images/LOGO.png">

    <style>
    html,
    body {
        height: 100%;
    }

    body {
        display: flex;
        align-items: center;
        padding-top: 40px;
        padding-bottom: 40px;
        background-color: #f5f5f5;
    }

    .form-signin {
        width: 100%;
        max-width: 330px;
        padding: 15px;
        margin: auto;
    }

    .form-signin .checkbox {
        font-weight: 400;
    }

    .form-signin .form-floating:focus-within {
        z-index: 2;
    }

    .form-signin input[type="text"] {
        margin-bottom: -1px;
        border-bottom-right-radius: 0;
        border-bottom-left-radius: 0;
    }

    .form-signin input[type="password"] {
        margin-bottom: 10px;
        border-top-left-radius: 0;
        border-top-right-radius: 0;
    }
    </style>

</head>

<body class="text-center">

    <main class="form-signin">
        <form method="post" autocomplete="off">
            <img class="mb-4" src="/images/LOGO TRANSPARENTE PEQUENA.png" width="100">
            <h1 class="h3 mb-3 fw-normal">Painel de Controle</h1>

            <div class="form-floating">
                <input name="usuario" required type="text" class="form-control" id="floatingInput"
                    placeholder="Usuário">
                <label for="floatingInput">Usuário</label>
            </div>
            <div class="form-floating">
                <input name="senha" required type="password" class="form-control" id="floatingPassword"
                    placeholder="senha">
                <label for="floatingPassword">Senha</label>
            </div>
            <button class="w-100 btn btn-lg btn-primary" type="submit">Entrar</button>
            <p class="mt-5 mb-3 text-muted">&copy; Fonte das Joias 2021</p>
        </form>
    </main>

    <script src="/js/bootstrap.bundle.min.js"></script>

    <?php

    if (isset($_GET["status"])) {

        $tituloNotificacao = '';
        $descricaoNotificacao = '';

        switch ($_GET['status']) {
            case 'erro':
                $tituloNotificacao = 'Erro!';
                $descricaoNotificacao = 'Usuário e/ou senha inválido(s).';
                break;
        }

        echo '<div class="modal modal-alert fade" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
                    aria-labelledby="staticBackdropLabel" aria-hidden="true" role="dialog" id="modalStatus">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content rounded-4 shadow">
                            <div class="modal-body p-4 text-center">
                                <h5 class="mb-0">' . $tituloNotificacao . '</h5>
                                <p class="mb-0">' . $descricaoNotificacao . '</p>
                            </div>
                            <div class="modal-footer flex-nowrap p-0">
                                <button type="button" class="btn btn-lg btn-link fs-6 text-decoration-none col-12 m-0 rounded-0"
                                    data-bs-target="#exampleModal3" data-bs-toggle="modal" data-bs-dismiss="modal">Fechar</button>
                            </div>
                        </div>
                    </div>
                </div>';


        echo "<script>var modalStatus = new bootstrap.Modal(document.getElementById('modalStatus'))</script>";
        echo "<script>modalStatus.show()</script>";
    }

    ?>

</body>

</html>