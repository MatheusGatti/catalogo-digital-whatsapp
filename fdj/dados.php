<?php

require '../vendor/autoload.php';

use App\Entity\FormaDePagamento;
use App\Entity\Entrega;
use App\Entity\Desconto;
use App\Session\Login;

//OBRIGA O USUÁRIO A ESTAR LOGADO
Login::requireLogin();


//Validação do post de criação de uma forma de pagamento
if (isset($_POST["nomeFormaDePagamento"], $_POST["statusFormaDePagamento"], $_POST["tipoFormulario"]) && $_POST["tipoFormulario"] == "cadastrarFormaDePagamento") {
    $objFormaDePagamento = new FormaDePagamento;
    $objFormaDePagamento->nome = $_POST["nomeFormaDePagamento"];
    $objFormaDePagamento->disponivel = $_POST["statusFormaDePagamento"];
    $objFormaDePagamento->cadastrar();
    header("Location: ?status=sucesso");
}

//Validação do get de disponibilizar/indisponibilizar uma forma de pagamento
if (isset($_GET["id"], $_GET["atualizarFormaDePagamento"]) and is_numeric(($_GET["id"]))) {
    $objFormaDePagamento = FormaDePagamento::getFormaDePagamento($_GET['id']);
    if (!$objFormaDePagamento instanceof FormaDePagamento) {
        header("Location: ?status=erro");
        exit;
    }
    if ($_GET['atualizarFormaDePagamento'] == 'disponivel') {
        $objFormaDePagamento->disponivel = 1;
        $objFormaDePagamento->atualizar();
        header("Location: ?status=sucesso");
    } else if ($_GET['atualizarFormaDePagamento'] == 'indisponivel') {
        $objFormaDePagamento->disponivel = 0;
        $objFormaDePagamento->atualizar();
        header("Location: ?status=sucesso");
    } else {
        header("Location: ?status=erro");
        exit;
    }
}

//Validação do get de excluir uma forma de pagamento
if (isset($_GET["excluirFormaDePagamento"]) and is_numeric(($_GET["excluirFormaDePagamento"]))) {
    $objFormaDePagamento = FormaDePagamento::getFormaDePagamento($_GET['excluirFormaDePagamento']);
    if (!$objFormaDePagamento instanceof FormaDePagamento) {
        header("Location: ?status=erro");
        exit;
    }
    $objFormaDePagamento->excluir();
    header("Location: ?status=sucesso");
}

//Leitura das formas de pagamento
$formasDePagamento = FormaDePagamento::getFormasDePagamento();
$resultadoFormasDePagamento = '';
foreach ($formasDePagamento as $formaDePagamento) {
    $resultadoFormasDePagamento .= '<li class="list-group-item d-flex justify-content-between align-items-center">' . $formaDePagamento->nome . '<div class="btn-group btn-group-sm"><a href="?atualizarFormaDePagamento=' . ($formaDePagamento->disponivel == 1 ? 'indisponivel' : 'disponivel') . '&id=' . $formaDePagamento->id . '" class="btn btn-warning">' . ($formaDePagamento->disponivel == 1 ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-slash" viewBox="0 0 16 16"><path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z" /><path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z" /><path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12-.708.708z" /></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>') . '</a><a href="?excluirFormaDePagamento=' . $formaDePagamento->id . '" class="btn btn-danger"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z" /><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z" /></svg></a></div></li>';
}

//Validação do post de criação de entrega
if (isset($_POST["nomeEntrega"], $_POST["statusEntrega"], $_POST["tipoFormulario"]) && $_POST["tipoFormulario"] == "cadastrarEntrega") {
    $objEntrega = new Entrega;
    $objEntrega->nome = $_POST["nomeEntrega"];
    $objEntrega->disponivel = $_POST["statusEntrega"];
    $objEntrega->cadastrar();
    header("Location: ?status=sucesso");
}

//Validação do get de disponibilizar/indisponibilizar entrega
if (isset($_GET["id"], $_GET["atualizarEntrega"]) and is_numeric(($_GET["id"]))) {
    $objEntrega = Entrega::getEntrega($_GET['id']);
    if (!$objEntrega instanceof Entrega) {
        header("Location: ?status=erro");
        exit;
    }
    if ($_GET['atualizarEntrega'] == 'disponivel') {
        $objEntrega->disponivel = 1;
        $objEntrega->atualizar();
        header("Location: ?status=sucesso");
    } else if ($_GET['atualizarEntrega'] == 'indisponivel') {
        $objEntrega->disponivel = 0;
        $objEntrega->atualizar();
        header("Location: ?status=sucesso");
    } else {
        header("Location: ?status=erro");
        exit;
    }
}

//Validação do get de excluir uma forma de pagamento
if (isset($_GET["excluirEntrega"]) and is_numeric(($_GET["excluirEntrega"]))) {
    $objEntrega = Entrega::getEntrega($_GET['excluirEntrega']);
    if (!$objEntrega instanceof Entrega) {
        header("Location: ?status=erro");
        exit;
    }
    $objEntrega->excluir();
    header("Location: ?status=sucesso");
}

//Leitura das entregas
$entregas = Entrega::getEntregas();
$resultadoEntregas = '';
foreach ($entregas as $entrega) {
    $resultadoEntregas .= '<li class="list-group-item d-flex justify-content-between align-items-center">' . $entrega->nome . '<div class="btn-group btn-group-sm"><a href="?atualizarEntrega=' . ($entrega->disponivel == 1 ? 'indisponivel' : 'disponivel') . '&id=' . $entrega->id . '" class="btn btn-warning">' . ($entrega->disponivel == 1 ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-slash" viewBox="0 0 16 16"><path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z" /><path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z" /><path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12-.708.708z" /></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>') . '</a><a href="?excluirEntrega=' . $entrega->id . '" class="btn btn-danger"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z" /><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z" /></svg></a></div></li>';
}

//Validação do post de criação de desconto
if (isset($_POST["porcentagemDesconto"], $_POST["quantidadeProdutos"], $_POST['statusDesconto'], $_POST['quantidadeUnica'], $_POST["tipoFormulario"]) && $_POST["tipoFormulario"] == "cadastrarDesconto") {
    $objDesconto = new Desconto;
    $objDesconto->porcentagem = $_POST["porcentagemDesconto"];
    $objDesconto->qnt_produtos = $_POST["quantidadeProdutos"];
    $objDesconto->qnt_unica = $_POST['quantidadeUnica'];
    $objDesconto->ativo = $_POST['statusDesconto'];
    $objDesconto->cadastrar();
    header("Location: ?status=sucesso");
}

//Validação do get de ativar/desativar desconto
if (isset($_GET["id"], $_GET["atualizarDesconto"]) and is_numeric(($_GET["id"]))) {
    $objDesconto = Desconto::getDesconto($_GET['id']);
    if (!$objDesconto instanceof Desconto) {
        header("Location: ?status=erro");
        exit;
    }
    if ($_GET['atualizarDesconto'] == 'ativado') {
        $objDesconto->ativo = 1;
        $objDesconto->atualizar();
        header("Location: ?status=sucesso");
    } else if ($_GET['atualizarDesconto'] == 'desativado') {
        $objDesconto->ativo = 0;
        $objDesconto->atualizar();
        header("Location: ?status=sucesso");
    } else {
        header("Location: ?status=erro");
        exit;
    }
}

//Validação do get de excluir um desconto
if (isset($_GET["excluirDesconto"]) and is_numeric(($_GET["excluirDesconto"]))) {
    $objDesconto = Desconto::getDesconto($_GET['excluirDesconto']);
    if (!$objDesconto instanceof Desconto) {
        header("Location: ?status=erro");
        exit;
    }
    $objDesconto->excluir();
    header("Location: ?status=sucesso");
}

//Leitura dos descontos
$descontos = Desconto::getDescontos();
$resultadoDescontos = '';
foreach ($descontos as $desconto) {
    $resultadoDescontos .= '<li class="list-group-item d-flex justify-content-between align-items-center">' . $desconto->porcentagem . '% em ' . ($desconto->qnt_unica == 1 ? $desconto->qnt_produtos . ' produto(s)' : $desconto->qnt_produtos . ' produto(s) ou mais') . '<div class="btn-group btn-group-sm"><a href="?atualizarDesconto=' . ($desconto->ativo == 1 ? 'desativado' : 'ativado') . '&id=' . $desconto->id . '" class="btn btn-warning">' . ($desconto->ativo == 1 ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-slash" viewBox="0 0 16 16"><path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z" /><path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z" /><path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12-.708.708z" /></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>') . '</a><a href="?excluirDesconto=' . $desconto->id . '" class="btn btn-danger"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z" /><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z" /></svg></a></div></li>';
}

?>
<!doctype html>
<html lang="pt-Br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Matheus Gatti">
    <title>Fonte das Joias | Dados</title>

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

    .list-group {
        max-height: 620px;
        margin-bottom: 10px;
        overflow: scroll;
        -webkit-overflow-scrolling: touch;
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
                    <h3 class="display-6">DADOS</h3>
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
                <div class="col-12 col-md-4 py-2">
                    <div class="text-center">
                        <h6>FORMAS DE PAGAMENTO</h6>
                        <button type="button" class="btn btn-success btn-sm mt-2 mb-4" data-bs-toggle="modal"
                            data-bs-target="#cadastrarFormaDePagamento">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-folder-plus" viewBox="0 0 16 16">
                                <path
                                    d="m.5 3 .04.87a1.99 1.99 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14H9v-1H2.826a1 1 0 0 1-.995-.91l-.637-7A1 1 0 0 1 2.19 4h11.62a1 1 0 0 1 .996 1.09L14.54 8h1.005l.256-2.819A2 2 0 0 0 13.81 3H9.828a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 6.172 1H2.5a2 2 0 0 0-2 2zm5.672-1a1 1 0 0 1 .707.293L7.586 3H2.19c-.24 0-.47.042-.683.12L1.5 2.98a1 1 0 0 1 1-.98h3.672z" />
                                <path
                                    d="M13.5 10a.5.5 0 0 1 .5.5V12h1.5a.5.5 0 1 1 0 1H14v1.5a.5.5 0 1 1-1 0V13h-1.5a.5.5 0 0 1 0-1H13v-1.5a.5.5 0 0 1 .5-.5z" />
                            </svg>
                            Cadastrar Forma de Pagamento
                        </button>
                        <ul class="list-group">
                            <?= $resultadoFormasDePagamento ?>
                        </ul>
                        <hr>
                        <h6>ENTREGAS</h6>
                        <button type="button" class="btn btn-success btn-sm mt-2 mb-4" data-bs-toggle="modal"
                            data-bs-target="#cadastrarEntrega">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-folder-plus" viewBox="0 0 16 16">
                                <path
                                    d="m.5 3 .04.87a1.99 1.99 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14H9v-1H2.826a1 1 0 0 1-.995-.91l-.637-7A1 1 0 0 1 2.19 4h11.62a1 1 0 0 1 .996 1.09L14.54 8h1.005l.256-2.819A2 2 0 0 0 13.81 3H9.828a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 6.172 1H2.5a2 2 0 0 0-2 2zm5.672-1a1 1 0 0 1 .707.293L7.586 3H2.19c-.24 0-.47.042-.683.12L1.5 2.98a1 1 0 0 1 1-.98h3.672z" />
                                <path
                                    d="M13.5 10a.5.5 0 0 1 .5.5V12h1.5a.5.5 0 1 1 0 1H14v1.5a.5.5 0 1 1-1 0V13h-1.5a.5.5 0 0 1 0-1H13v-1.5a.5.5 0 0 1 .5-.5z" />
                            </svg>
                            Cadastrar Entrega
                        </button>
                        <ul class="list-group">
                            <?= $resultadoEntregas ?>
                        </ul>
                        <hr>
                        <h6>DESCONTOS</h6>
                        <button type="button" class="btn btn-success btn-sm mt-2 mb-4" data-bs-toggle="modal"
                            data-bs-target="#cadastrarDesconto">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-folder-plus" viewBox="0 0 16 16">
                                <path
                                    d="m.5 3 .04.87a1.99 1.99 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14H9v-1H2.826a1 1 0 0 1-.995-.91l-.637-7A1 1 0 0 1 2.19 4h11.62a1 1 0 0 1 .996 1.09L14.54 8h1.005l.256-2.819A2 2 0 0 0 13.81 3H9.828a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 6.172 1H2.5a2 2 0 0 0-2 2zm5.672-1a1 1 0 0 1 .707.293L7.586 3H2.19c-.24 0-.47.042-.683.12L1.5 2.98a1 1 0 0 1 1-.98h3.672z" />
                                <path
                                    d="M13.5 10a.5.5 0 0 1 .5.5V12h1.5a.5.5 0 1 1 0 1H14v1.5a.5.5 0 1 1-1 0V13h-1.5a.5.5 0 0 1 0-1H13v-1.5a.5.5 0 0 1 .5-.5z" />
                            </svg>
                            Cadastrar Desconto
                        </button>
                        <ul class="list-group">
                            <?= $resultadoDescontos ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" autocomplete="off">
            <div class="modal fade" id="cadastrarFormaDePagamento" data-bs-backdrop="static" data-bs-keyboard="false"
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
                                Cadastrar Forma de Pagamento
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="tipoFormulario" value="cadastrarFormaDePagamento">
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col">
                                        <div class="form-floating mb-3">
                                            <input name="nomeFormaDePagamento" required="true" type="text"
                                                class="form-control" id="floatingInput" placeholder="Nome da categoria">
                                            <label for="floatingInput">Nome da forma de pagamento</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <div class="form-floating form-floating-sm">
                                            <select name="statusFormaDePagamento" required="true" class="form-select"
                                                id="floatingSelect" aria-label="Status">
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

        <form method="POST" autocomplete="off">
            <div class="modal fade" id="cadastrarEntrega" data-bs-backdrop="static" data-bs-keyboard="false"
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
                                Cadastrar Entrega
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="tipoFormulario" value="cadastrarEntrega">
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col">
                                        <div class="form-floating mb-3">
                                            <input name="nomeEntrega" required="true" type="text" class="form-control"
                                                id="floatingInput" placeholder="Nome da categoria">
                                            <label for="floatingInput">Nome da forma de entrega</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <div class="form-floating form-floating-sm">
                                            <select name="statusEntrega" required="true" class="form-select"
                                                id="floatingSelect" aria-label="Status">
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

        <form method="POST" autocomplete="off">
            <div class="modal fade" id="cadastrarDesconto" data-bs-backdrop="static" data-bs-keyboard="false"
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
                                Cadastrar Desconto
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="tipoFormulario" value="cadastrarDesconto">
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col">
                                        <div class="form-floating mb-3">
                                            <input name="porcentagemDesconto" required="true" type="number"
                                                class="form-control" id="floatingInput"
                                                placeholder="Porcentagem de desconto">
                                            <label for="floatingInput">Porcentagem de desconto</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <div class="form-floating mb-3">
                                            <input name="quantidadeProdutos" required="true" type="number"
                                                class="form-control" id="floatingInput"
                                                placeholder="Quantidade de produtos">
                                            <label for="floatingInput">Quantidade de produtos</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <div class="form-floating form-floating-sm mb-3">
                                            <select name="statusDesconto" required="true" class="form-select"
                                                id="floatingSelect" aria-label="Status">
                                                <option value="1" selected>Ativado</option>
                                                <option value="0">Desativado</option>
                                            </select>
                                            <label for="floatingSelect">Status</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <div class="form-floating form-floating-sm">
                                            <select name="quantidadeUnica" required="true" class="form-select"
                                                id="floatingSelect" aria-label="Quantidade única">
                                                <option value="1" selected>Sim</option>
                                                <option value="0">Não</option>
                                            </select>
                                            <label for="floatingSelect">Quantidade única</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <br>
                                        <small>
                                            <p><strong>*Quantidade de produtos:</strong> quantidade mínima de produtos
                                                para
                                                aplicar o desconto.</p>
                                            <p><strong>*Quantidade única:</strong> se marcado como Sim o desconto só
                                                será aplicado caso a quantidade de produtos for igual a definida, caso
                                                contrário o desconto será aplicado em qunatidades iguais e maiores.</p>
                                        </small>
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