<?php

require '../vendor/autoload.php';

use App\Entity\Categoria;
use App\Session\Login;

//OBRIGA O USUÁRIO A ESTAR LOGADO
Login::requireLogin();

//Validação do post de criação de uma categoria
if (isset($_POST["nome"], $_POST["status"], $_POST["tipoFormulario"]) && $_POST["tipoFormulario"] == "cadastrarCategoria") {
    $objCategoria = new Categoria;
    $objCategoria->nome = $_POST["nome"];
    $objCategoria->disponivel = $_POST["status"];
    $objCategoria->cadastrar();
    header("Location: ?status=sucesso");
}

//Validação do post de edição de uma categoria
if (isset($_POST["nome"], $_POST["status"], $_POST["tipoFormulario"], $_GET['editar']) && $_POST["tipoFormulario"] == "editarCategoria" && is_numeric(($_GET['editar']))) {
    $objCategoria = Categoria::getCategoria(($_GET['editar']));
    if (!$objCategoria instanceof Categoria) {
        header("Location: ?status=erro");
        echo '<script>location.href="?status=erro";</script>';
        exit;
    }
    $objCategoria->nome = $_POST["nome"];
    $objCategoria->disponivel = $_POST["status"];
    $objCategoria->atualizar();
    header("Location: ?status=sucesso");
}

//Validação do post de exclusão de uma categoria
if (isset($_POST["tipoFormulario"], $_GET['excluir']) && $_POST["tipoFormulario"] == "excluirCategoria" && is_numeric(($_GET['excluir']))) {
    $objCategoria = Categoria::getCategoria(($_GET['excluir']));
    if (!$objCategoria instanceof Categoria) {
        header("Location: ?status=erro");
        echo '<script>location.href="?status=erro";</script>';
        exit;
    }
    $objCategoria->excluir();
    header("Location: ?status=sucesso");
}

//Leitura das categorias
$categorias = Categoria::getCategorias();
$quantidadeCategorias = count($categorias);
$resultadoCategorias = '';
foreach ($categorias as $categoria) {
    $resultadoCategorias .= '<tr class="' . ($categoria->disponivel == 1 ? 'table-success' : 'table-danger') . '"><td>' . $categoria->nome . '</td><td><div class="btn-group btn-group-sm" role="group"><a href="?editar=' . $categoria->id . '" role="button" class="btn btn-warning"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16"><path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z" /><path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z" /></svg>&nbsp;Editar</a><a href="?excluir=' . $categoria->id . '" role="button" class="btn btn-danger"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z" /><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z" /></svg>&nbsp;Excluir</a></div></td></tr>';
}
?>
<!doctype html>
<html lang="pt-Br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Matheus Gatti">
    <title>Fonte das Joias | Categorias</title>

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
    body.modal-open .supreme-container {
        -webkit-filter: blur(1px);
        -moz-filter: blur(1px);
        -o-filter: blur(1px);
        -ms-filter: blur(1px);
        filter: blur(1px);
    }
    </style>

</head>

<body>

    <header>
        <div class="navbar navbar-dark bg-dark shadow-sm">
            <div class="container">
                <a href="painel.php" class="navbar-brand d-flex align-items-center">
                    <strong>Fonte das Joias</strong>
                </a>
                <a href="sair.php" role="button" class="btn btn-outline-light btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-box-arrow-in-left" viewBox="0 0 16 16">
                        <path fill-rule="evenodd"
                            d="M10 3.5a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-2a.5.5 0 0 1 1 0v2A1.5 1.5 0 0 1 9.5 14h-8A1.5 1.5 0 0 1 0 12.5v-9A1.5 1.5 0 0 1 1.5 2h8A1.5 1.5 0 0 1 11 3.5v2a.5.5 0 0 1-1 0v-2z" />
                        <path fill-rule="evenodd"
                            d="M4.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H14.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3z" />
                    </svg>
                    Sair
                </a>
            </div>
        </div>
    </header>

    <main>

        <div class="container-fluid supreme-container">
            <div class="row mt-5 mb-3">
                <div class="col text-center">
                    <h3 class="display-6">CATEGORIAS</h3>
                    <a href="painel.php" role="button" class="btn btn-warning btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-arrow-return-left" viewBox="0 0 16 16">
                            <path fill-rule="evenodd"
                                d="M14.5 1.5a.5.5 0 0 1 .5.5v4.8a2.5 2.5 0 0 1-2.5 2.5H2.707l3.347 3.346a.5.5 0 0 1-.708.708l-4.2-4.2a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 8.3H12.5A1.5 1.5 0 0 0 14 6.8V2a.5.5 0 0 1 .5-.5z" />
                        </svg>
                        Voltar
                    </a>
                </div>
            </div>
            <div class="row py-3 bg-light justify-content-center">
                <div class="col-12 col-md-10 py-2 text-center">
                    <h6><?= $quantidadeCategorias ?> CATEGORIA(S) CADASTRADA(S)</h6>
                    <button type="button" class="btn btn-success btn-sm mt-2 mb-4" data-bs-toggle="modal"
                        data-bs-target="#cadastrarCategoria">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-folder-plus" viewBox="0 0 16 16">
                            <path
                                d="m.5 3 .04.87a1.99 1.99 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14H9v-1H2.826a1 1 0 0 1-.995-.91l-.637-7A1 1 0 0 1 2.19 4h11.62a1 1 0 0 1 .996 1.09L14.54 8h1.005l.256-2.819A2 2 0 0 0 13.81 3H9.828a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 6.172 1H2.5a2 2 0 0 0-2 2zm5.672-1a1 1 0 0 1 .707.293L7.586 3H2.19c-.24 0-.47.042-.683.12L1.5 2.98a1 1 0 0 1 1-.98h3.672z" />
                            <path
                                d="M13.5 10a.5.5 0 0 1 .5.5V12h1.5a.5.5 0 1 1 0 1H14v1.5a.5.5 0 1 1-1 0V13h-1.5a.5.5 0 0 1 0-1H13v-1.5a.5.5 0 0 1 .5-.5z" />
                        </svg>
                        Cadastrar Categoria
                    </button>
                    <div class="table-responsive text-center">
                        <table class="table table-striped table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th scope="col">Categoria</th>
                                    <th scope="col">Opções</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?= $resultadoCategorias ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <form method="post" autocomplete="off">
            <div class="modal fade" id="cadastrarCategoria" data-bs-backdrop="static" data-bs-keyboard="false"
                tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="staticBackdropLabel"><svg xmlns="http://www.w3.org/2000/svg"
                                    width="16" height="16" fill="currentColor" class="bi bi-folder-plus"
                                    viewBox="0 0 16 16">
                                    <path
                                        d="m.5 3 .04.87a1.99 1.99 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14H9v-1H2.826a1 1 0 0 1-.995-.91l-.637-7A1 1 0 0 1 2.19 4h11.62a1 1 0 0 1 .996 1.09L14.54 8h1.005l.256-2.819A2 2 0 0 0 13.81 3H9.828a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 6.172 1H2.5a2 2 0 0 0-2 2zm5.672-1a1 1 0 0 1 .707.293L7.586 3H2.19c-.24 0-.47.042-.683.12L1.5 2.98a1 1 0 0 1 1-.98h3.672z" />
                                    <path
                                        d="M13.5 10a.5.5 0 0 1 .5.5V12h1.5a.5.5 0 1 1 0 1H14v1.5a.5.5 0 1 1-1 0V13h-1.5a.5.5 0 0 1 0-1H13v-1.5a.5.5 0 0 1 .5-.5z" />
                                </svg>
                                Cadastrar Categoria
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="tipoFormulario" value="cadastrarCategoria">
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col">
                                        <div class="form-floating mb-3">
                                            <input type="text" name="nome" required class="form-control"
                                                id="floatingInput" placeholder="Nome da categoria">
                                            <label for="floatingInput">Nome da categoria</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <div class="form-floating form-floating-sm">
                                            <select class="form-select" required name="status" id="floatingSelect"
                                                aria-label="Status">
                                                <option value="1" selected>Disponível</option>
                                                <option value="0">Indisponível</option>
                                            </select>
                                            <label for="floatingSelect">Status</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-dark btn-sm"
                                data-bs-dismiss="modal">Fechar</button>
                            <button type="submit" class="btn btn-success btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                    class="bi bi-folder-plus" viewBox="0 0 16 16">
                                    <path
                                        d="m.5 3 .04.87a1.99 1.99 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14H9v-1H2.826a1 1 0 0 1-.995-.91l-.637-7A1 1 0 0 1 2.19 4h11.62a1 1 0 0 1 .996 1.09L14.54 8h1.005l.256-2.819A2 2 0 0 0 13.81 3H9.828a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 6.172 1H2.5a2 2 0 0 0-2 2zm5.672-1a1 1 0 0 1 .707.293L7.586 3H2.19c-.24 0-.47.042-.683.12L1.5 2.98a1 1 0 0 1 1-.98h3.672z" />
                                    <path
                                        d="M13.5 10a.5.5 0 0 1 .5.5V12h1.5a.5.5 0 1 1 0 1H14v1.5a.5.5 0 1 1-1 0V13h-1.5a.5.5 0 0 1 0-1H13v-1.5a.5.5 0 0 1 .5-.5z" />
                                </svg>
                                Cadastrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </main>


    <script src="/js/bootstrap.bundle.min.js"></script>

    <?php

    if (isset($_GET['editar']) and is_numeric(($_GET['editar']))) {
        $objCategoria = Categoria::getCategoria($_GET['editar']);
        if (!$objCategoria instanceof Categoria) {
            header("Location: ?status=erro");
            echo '<script>location.href="?status=erro";</script>';
            exit;
        }
        echo '<form method="post" autocomplete="off"><div class="modal fade" id="modalEditarCategoria" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-pencil-square" viewBox="0 0 16 16">
                            <path
                                d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z" />
                            <path fill-rule="evenodd"
                                d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z" />
                        </svg>
                        Editar Categoria
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="tipoFormulario" value="editarCategoria">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col">
                                <div class="form-floating mb-3">
                                    <input name="nome" type="text" class="form-control" id="floatingInput"
                                        value="' . $objCategoria->nome . '">
                                    <label for="floatingInput">Nome da categoria</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="form-floating form-floating-sm">
                                    <select name="status" class="form-select" id="floatingSelect" aria-label="Status">
                                    <option value="1" ' . ($objCategoria->disponivel == 1 ? 'selected' : '') . '>Disponível</option>
                                    <option value="0" ' . ($objCategoria->disponivel == 1 ? '' : 'selected') . '>Indisponível</option>
                                    </select>
                                    <label for="floatingSelect">Status</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark btn-sm"
                        data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-folder-check" viewBox="0 0 16 16">
                            <path
                                d="m.5 3 .04.87a1.99 1.99 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14H9v-1H2.826a1 1 0 0 1-.995-.91l-.637-7A1 1 0 0 1 2.19 4h11.62a1 1 0 0 1 .996 1.09L14.54 8h1.005l.256-2.819A2 2 0 0 0 13.81 3H9.828a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 6.172 1H2.5a2 2 0 0 0-2 2zm5.672-1a1 1 0 0 1 .707.293L7.586 3H2.19c-.24 0-.47.042-.683.12L1.5 2.98a1 1 0 0 1 1-.98h3.672z" />
                            <path
                                d="M15.854 10.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.707 0l-1.5-1.5a.5.5 0 0 1 .707-.708l1.146 1.147 2.646-2.647a.5.5 0 0 1 .708 0z" />
                        </svg>
                        Salvar
                    </button>
                </div>
            </div>
        </div>
    </div></form>';

        echo "<script>var modalEditarCategoria = new bootstrap.Modal(document.getElementById('modalEditarCategoria'))</script>";
        echo "<script>modalEditarCategoria.show()</script>";
    }

    if (isset($_GET['excluir']) and is_numeric(($_GET['excluir']))) {
        $objCategoria = Categoria::getCategoria($_GET['excluir']);
        if (!$objCategoria instanceof Categoria) {
            header("Location: ?status=erro");
            echo '<script>location.href="?status=erro";</script>';
            exit;
        }
        echo '<form method="post" autocomplete="off">
        <div class="modal modal-alert fade" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="staticBackdropLabel" aria-hidden="true" role="dialog" id="modalExcluirCategoria">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content rounded-4 shadow">
                    <div class="modal-body p-4 text-center">
                        <input type="hidden" name="tipoFormulario" value="excluirCategoria">
                        <h5 class="mb-0">Excluir categoria?</h5>
                        <p class="mb-0">Essa ação não poderá ser desfeita.</p>
                    </div>
                    <div class="modal-footer flex-nowrap p-0">
                        <button type="submit"
                            class="btn btn-lg btn-link fs-6 text-decoration-none col-6 m-0 rounded-0 border-right"><strong>Sim,
                                excluir</strong></button>
                        <button type="button" class="btn btn-lg btn-link fs-6 text-decoration-none col-6 m-0 rounded-0"
                            data-bs-dismiss="modal">Não, voltar</button>
                    </div>
                </div>
            </div>
        </div>
    </form>';

        echo "<script>var modalExcluir = new bootstrap.Modal(document.getElementById('modalExcluirCategoria'))</script>";
        echo "<script>modalExcluir.show()</script>";
    }

    if (isset($_GET["status"])) {

        $tituloNotificacao = '';
        $descricaoNotificacao = '';

        switch ($_GET['status']) {
            case 'sucesso':
                $tituloNotificacao = 'Sucesso!';
                $descricaoNotificacao = 'A operação foi realizada com êxito.';
                break;
            case 'erro':
                $tituloNotificacao = 'Erro!';
                $descricaoNotificacao = 'Ocorreu algum erro ao realizar a operação.';
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