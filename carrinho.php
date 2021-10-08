<?php

require 'vendor/autoload.php';

use App\Entity\Produto;
use App\Entity\Categoria;
use App\Session\Carrinho;
use App\Entity\Desconto;

//VALIDA O POST DE ALTERAR A QUANTIDADE DO PRODUTO
if (isset($_POST['id'], $_POST['quantidade'], $_POST['alterar']) and is_numeric($_POST['id']) and is_numeric($_POST['quantidade'])) {
    //VALIDA SE O PRODUTO EXISTE E SE A CATEGORIA DO PRODUTO EXISTE E ESTÁ DISPONÍVEL
    $produto = Produto::getProduto($_POST['id']);
    if (!$produto instanceof Produto or $produto->disponivel != 1) {
        Carrinho::remove($_POST['id']);
        header('Location: ?status=erro_produto');
        echo '<script>location.href="?status=erro_produto"</script>';
        exit;
    } else {
        $categoria = Categoria::getCategoria($produto->categoria);
        if (!$categoria instanceof Categoria or $categoria->disponivel != 1) {
            Carrinho::remove($_POST['id']);
            header('Location: ?status=erro_produto');
            echo '<script>location.href="?status=erro_produto"</script>';
            exit;
        }
    }

    //VALIDA SE O PRODUTO ESTÁ DISPONÍVEL
    if ($produto->disponivel == 0) {
        Carrinho::remove($_POST['id']);
        header('Location: ?status=erro_produto');
        echo '<script>location.href="?status=erro_produto"</script>';
        exit;
    }

    //VALIDA A QUANTIDADE
    if ($_POST['quantidade'] > $produto->estoque) {
        header('Location: ?status=erro_quantidade');
        echo '<script>location.href="?status=erro_quantidade"</script>';
        exit;
    }

    //ALTERA A QUANTIDADE DO PRODUTO NO CARRINHO
    Carrinho::quantity($produto->id, $_POST['quantidade']);
}

//VALIDA O POST DE EXCLUIR UM PRODUTO
if (isset($_POST['id'], $_POST['excluir']) and is_numeric($_POST['id'])) {
    //EXCLUIR PRODUTO
    Carrinho::remove($_POST['id']);
}

//ATRIBUI O STATUS A UMA VARIÁVEL
$status = $_GET['status'];

//LISTAGEM DOS PRODUTOS
$carrinhoProdutos = Carrinho::getProdutos();
if (!empty($carrinhoProdutos)) {
    $produtos = [];
    foreach ($carrinhoProdutos as $produtoId => $quantidade) {
        //VALIDA SE PRODUTO É UM INTEGER
        if (is_numeric($produtoId)) {
            $produto = Produto::getProduto($produtoId);

            //VALIDA SE O PRODUTO EXISTE; SE O PRODUTO ESTÁ DISPONÍVEL; SE A QUANTIDADE É MENOR OU IGUAL A 0; SE A QUANTIDADE É MAIOR DO ESTOQUE
            if (!$produto instanceof Produto or $produto->disponivel == 0 or $produto->estoque == 0 or $quantidade <= 0 or $quantidade > $produto->estoque) {
                //REMOVE O PRODUTO DO CARRINHO
                Carrinho::remove($produtoId);

                continue;
            }

            $produtos[] = $produto;
        }
    }
}

//CARREGAR CARRINHO
$quantidadeCarrinho = Carrinho::getQuantidadeProdutos();

//VALIDA SE HÁ PRODUTOS
if (!empty($produtos)) {
    //VALOR TOTAL
    $valorTotal = 0;

    //LISTAGEM DOS PRODUTOS
    $listaProdutos = '';

    foreach ($produtos as $produto) {
        //INCREMENTA VALOR TOTAL
        $valorTotal += ($produto->preco_de_venda * Carrinho::getProduto($produto->id));

        //FOTOS PRODUTO
        $fotos = json_decode($produto->fotos);

        //CARD PRODUTO
        $listaProdutos .= '<div class="col-6 col-md-3 py-2">
        <div class="card h-100">
            <img src="/images/produtos/' . $fotos[0] . '" class="card-img-top">
            <div class="card-body">
            <form method="post" autocomplete="off">
                <h6 class="card-title text-center">' . $produto->nome . '</h6>
                <h4 class="text-center"><span class="badge rounded-pill bg-light text-dark">R$' . number_format($produto->preco_de_venda, 2, ',', '.') . '</span>
                </h4>
                <div class="row justify-content-center">
                    <input type="hidden" type="number" name="id" required value="' . $produto->id . '">
                    <div class="col-10 col-md-6">
                        <div class="input-group input-group-sm mb-3">
                            <button id="diminuir-' . $produto->id . '" onclick="diminuir(this, ' . ($produto->estoque) . ', ' . $produto->id . ')" ' . (Carrinho::getProduto($produto->id) < 0 ? 'disabled' : '') . ' class="btn btn-outline-secondary" type="button">-</button>
                            <input onchange="verificar(this, ' . $produto->estoque . ', ' . $produto->id . ')" id="quantidade-' . $produto->id . '" min="0" max="' . ($produto->estoque) . '" name="quantidade" type="number" class="form-control text-center" value="' . Carrinho::getProduto($produto->id) . '">
                            <button id="aumentar-' . $produto->id . '" onclick="aumentar(this, ' . ($produto->estoque) . ', ' . $produto->id . ')" ' . (Carrinho::getProduto($produto->id) == $produto->estoque ? 'disabled' : '') . ' class="btn btn-outline-secondary" type="button">+</button>
                        </div>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <div class="btn-group-vertical" role="group">
                        <button type="submit" name="alterar" class="btn btn-sm btn-warning">ALTERAR</button>
                        <button type="submit" name="excluir" class="btn btn-sm btn-danger">EXCLUIR</button>
                    </div>
                </div>
            </form>
            </div>
        </div>
    </div>';
    }

    //VARIÁVEIS PARA FINALIZAÇÃO DA COMPRA
    $botaoFinalizarCompra = '';
    $avisoDesconto = '';

    //LISTA OS DESCONTOS ATIVOS
    $descontos = Desconto::getDescontos('ativo = 1');

    //VALIDA SE TEM DESCONTO ATIVO
    if (empty($descontos)) {
        $valorTotal = number_format($valorTotal, 2, ',', '.');
        $valorTotal = '<span class="badge bg-primary rounded-pill">R$' . $valorTotal . '</span>';
        $botaoFinalizarCompra = '<a href="/index.php" role="button" class="btn btn-outline-secondary">Continuar comprando</a><a href="/finalizarPedido.php" role="button" class="btn btn-success">Finalizar meu pedido</a>';
    } else {

        //CHAVE PARA VERIFICAR SE UM DESCONTO FOI APLICADO
        $chaveDesconto = false;

        foreach ($descontos as $desconto) {

            //VALIDA SE O DESCONTO PARA QUANTIDADE ÚNICA SE APLICA
            if ($desconto->qnt_unica == 1 && $quantidadeCarrinho == $desconto->qnt_produtos) {
                //APLICA O DESCONTO E DEFINE O VALOR
                $valorDescontado = $valorTotal - ($valorTotal * ($desconto->porcentagem / 100));
                $valorTotal = number_format($valorTotal, 2, ',', '.');
                $valorDescontado = number_format($valorDescontado, 2, ',', '.');
                $valorTotal = '<span class="badge bg-primary rounded-pill"><del>R$' . $valorTotal . '</del> R$' . $valorDescontado . '</span>';

                //DEFINE O BOTÃO DE FINALIZAR COMPRA
                $botaoFinalizarCompra = '<a href="/index.php" role="button" class="btn btn-outline-secondary">Continuar comprando</a><a href="/finalizarPedido.php" role="button" class="btn btn-success">Finalizar meu pedido com ' . $desconto->porcentagem . '% de desconto</a>';

                //DEFINE UM AVISO DO DESCONTO
                $avisoDesconto = '<div class="mt-3 alert alert-warning d-flex align-items-center" role="alert"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2" viewBox="0 0 16 16" role="img"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" /></svg><div>Seu desconto é <b>valido por apenas 10 minutos.</b></div></div>';

                //MARCA COMO DESCONTADO
                $chaveDesconto = true;
                break;
            }

            //VALIDA SE O DESCONTO PARA QUANTIDADE MAIOR OU IGUAL SE APLICA
            if ($desconto->qnt_unica == 0 && $quantidadeCarrinho >= $desconto->qnt_produtos) {
                //APLICA O DESCONTO E DEFINE O VALOR
                $valorDescontado = $valorTotal - ($valorTotal * ($desconto->porcentagem / 100));
                $valorTotal = number_format($valorTotal, 2, ',', '.');
                $valorDescontado = number_format($valorDescontado, 2, ',', '.');
                $valorTotal = '<span class="badge bg-primary rounded-pill"><del>R$' . $valorTotal . '</del> R$' . $valorDescontado . '</span>';

                //DEFINE O BOTÃO DE FINALIZAR COMPRA
                $botaoFinalizarCompra = '<a href="/index.php" role="button" class="btn btn-outline-secondary">Continuar comprando</a><a href="/finalizarPedido.php" role="button" class="btn btn-success">Finalizar meu pedido com ' . $desconto->porcentagem . '% de desconto</a>';

                //DEFINE UM AVISO DO DESCONTO
                $avisoDesconto = '<div class="mt-3 alert alert-warning d-flex align-items-center" role="alert"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2" viewBox="0 0 16 16" role="img"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" /></svg><div>Seu desconto é <b>valido por apenas 10 minutos.</b></div></div>';

                //MARCA COMO DESCONTADO
                $chaveDesconto = true;
                break;
            }

            //VERIFICA SE NÃO TEM ALGUM DESCONTO PRÓXIMO A SER APLICADO
            $diferenca = $desconto->qnt_produtos - $quantidadeCarrinho;
            if ($diferenca === 1) {
                $avisoDesconto = "<script>Swal.fire({
            title: 'Você ganhou um presente!',
            text: 'O seu presente é um desconto surpresa válido pelos próximos 10 minutos na compra de " . $desconto->qnt_produtos . ($desconto->qnt_unica == 1 ? ' produtos.' : ' ou mais produtos.') . "',
            icon: 'success',
            confirmButtonText: 'Continuar comprando',
            showCancelButton: true,
            cancelButtonText: 'Deixa pra próxima :(',
            cancelButtonColor: '#333333',
            confirmButtonColor: '#198754',
        }).then((result) => {
            if (result.isConfirmed) {
                location.href='index.php';
            }
        })</script>";
            }
        }

        //CASO NENHUM DESCONTO TENHA SE APLICADO
        if ($chaveDesconto === false) {
            //VALOR
            $valorTotal = number_format($valorTotal, 2, ',', '.');
            $valorTotal = '<span class="badge bg-primary rounded-pill">R$' . $valorTotal . '</span>';

            //BOTÃO FINALIZAR COMPRA
            $botaoFinalizarCompra = '<a href="/index.php" role="button" class="btn btn-outline-secondary">Continuar comprando</a><a href="/finalizarPedido.php" role="button" class="btn btn-success">Finalizar meu pedido</a>';
        }
    }
}
?>
<!doctype html>
<html lang="pt-Br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Matheus Gatti">
    <title>Fonte das Joias | Carrinho</title>

    <!-- Bootstrap core CSS -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CSS-->
    <link rel="stylesheet" href="/icons/bootstrap-icons.css">
    <!-- SweetAlert2 -->
    <script src="/js/sweetalert2.all.min.js"></script>

    <!-- Favicons -->
    <link rel="apple-touch-icon" href="/images/LOGO.png" sizes="180x180">
    <link rel="icon" href="/images/LOGO.png" sizes="32x32" type="image/png">
    <link rel="icon" href="/images/LOGO.png" sizes="16x16" type="image/png">
    <link rel="manifest" href="/images/LOGO.png">
    <link rel="mask-icon" href="/images/LOGO.png">
    <link rel="icon" href="/images/LOGO.png">

    <!-- CSS Personalizado -->
    <style>
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type=number] {
        -moz-appearance: textfield;
    }
    </style>

</head>

<body>

    <header>
        <div class="navbar navbar-dark bg-dark shadow-sm">
            <div class="container">
                <a href="/index.php" class="navbar-brand d-flex align-items-center">
                    <strong>Fonte das Joias</strong>
                </a>
                <a href="carrinho.php" role="button" class="btn btn-light btn-sm position-relative">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bag"
                        viewBox="0 0 16 16">
                        <path
                            d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1zm3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4h-3.5zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V5z" />
                    </svg>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">
                        <?= $quantidadeCarrinho ?>
                        <span class="visually-hidden">produtos no carrinho</span>
                    </span>
                </a>
            </div>
        </div>
    </header>

    <main>

        <div class="container-fluid">
            <div class="row mt-5 mb-3">
                <div class="col text-center">
                    <h3 class="display-6">SEU CARRINHO</h3>
                </div>
            </div>
            <div class="row py-3 bg-light justify-content-center">
                <div class="col-12 col-md-4 py-2 text-center">
                    <?php
                    //VALIDA SE TEM PRODUTOS NO CARRINHO
                    if ($quantidadeCarrinho <= 0) {
                        echo '<h6>O SEU CARRINHO AINDA ESTÁ VAZIO :(</h6>';
                        echo '<a href="index.php" role"button" class="btn btn-success btn-sm">Fazer compras</a>';
                    } else {
                    ?>
                    <h6>RESUMO DO PEDIDO</h6>
                    <ul class="list-group mt-3">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Quantidade de produtos
                            <span class="badge bg-primary rounded-pill"><?= $quantidadeCarrinho ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Valor total
                            <?= $valorTotal ?>
                        </li>
                    </ul>
                    <div class="btn-group mt-3" role="group">
                        <?= $botaoFinalizarCompra ?>
                    </div>
                    <?= $avisoDesconto ?>
                </div>
                <?php } ?>
            </div>
        </div>
        <?php
        //VALIDA SE TEM PRODUTOS NO CARRINHO
        if ($quantidadeCarrinho > 0) {
        ?>
        <div class="container">
            <div class="row py-5">
                <?= $listaProdutos ?>
            </div>
        </div>
        <?php } ?>

    </main>

    <footer class="text-muted py-5">
        <div class="container">
            <p class="float-end mb-1">
            <div class="btn-group">
                <a href="https://wa.me/message/KZP7FWBQ7E33D1" class="btn btn-success btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-whatsapp" viewBox="0 0 16 16">
                        <path
                            d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z" />
                    </svg>&nbsp;WhatsApp
                </a>
                <a href="https://instagram.com/fontedasjoias.es" class="btn btn-danger btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-instagram" viewBox="0 0 16 16">
                        <path
                            d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.917 3.917 0 0 0-1.417.923A3.927 3.927 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.916 3.916 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.926 3.926 0 0 0-.923-1.417A3.911 3.911 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0h.003zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599.28.28.453.546.598.92.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.47 2.47 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.478 2.478 0 0 1-.92-.598 2.48 2.48 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233 0-2.136.008-2.388.046-3.231.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92.28-.28.546-.453.92-.598.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045v.002zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92zm-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217zm0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334z" />
                    </svg>&nbsp;Instagram
                </a>
            </div>
            </p>
            <p class="mb-1">Fonte das Joias &copy; 2021.</p>
        </div>
    </footer>


    <script src="/js/bootstrap.bundle.min.js"></script>
    <script src="/js/jquery-3.6.0.min.js"></script>

    <script>
    function aumentar(e, maximo, id) {
        var fieldName = $("#quantidade-" + id);
        var parent = $("#quantidade-" + id);
        var currentVal = parseInt(parent.val(), 10);

        if (!isNaN(currentVal)) {
            parent.val(currentVal + 1);
        } else {
            parent.val(1);
        }

        //VALIDAR SE ULTRAPASSOU O ESTOQUE
        if (parent.val() == maximo) {
            $("#aumentar-" + id).prop('disabled', true);
        }

        //VALIDA SE CHEGOU A 0 OU NEGATIVO
        if (parent.val() <= 0) {
            $("#diminuir-" + id).prop('disabled', true);
        } else {
            $("#diminuir-" + id).prop('disabled', false);
        }
    }

    function diminuir(e, maximo, id) {
        var fieldName = $("#quantidade-" + id);
        var parent = $("#quantidade-" + id);
        var currentVal = parseInt(parent.val(), 10);

        if (!isNaN(currentVal) && currentVal > 0) {
            parent.val(currentVal - 1);
        } else {
            parent.val(0);
        }

        //VALIDAR SE ULTRAPASSOU O ESTOQUE
        if (parent.val() < maximo) {
            $("#aumentar-" + id).prop('disabled', false);
        }

        //VALIDA SE CHEGOU A 0 OU NEGATIVO
        if (parent.val() <= 0) {
            $("#diminuir-" + id).prop('disabled', true);
        } else {
            $("#diminuir-" + id).prop('disabled', false);
        }
    }

    function verificar(e, maximo, id) {
        if ($(e).val() >= maximo) {
            $(e).val(maximo);
            if ($('#quantidade-' + id).val() == maximo) {
                $('#aumentar-' + id).prop('disabled', true);
            }
            if ($('#quantidade-' + id).val() == 0) {
                $('#diminuir-' + id).prop('disabled', true);
            } else {
                $('#diminuir-' + id).prop('disabled', false);
            }
        } else if ($(e).val() <= 0) {
            $(e).val(0);
            if ($('#quantidade-' + id).val() < maximo) {
                $('#aumentar-' + id).prop('disabled', false);
            }
            if ($('#quantidade-' + id).val() == 0) {
                $('#diminuir-' + id).prop('disabled', true);
            } else {
                $('#diminuir-' + id).prop('disabled', false);
            }
        } else {
            $('#aumentar-' + id).prop('disabled', false);
            $('#diminuir-' + id).prop('disabled', false);
        }
    }
    </script>

    <?php

    if (strlen($status)) {

        $tituloNotificacao = '';
        $descricaoNotificacao = '';
        $sucesso = $false;

        switch ($status) {
            case 'erro':
                $tituloNotificacao = 'Erro!';
                $descricaoNotificacao = 'Ocorreu algum erro ao realizar a operação.';
                break;
            case 'erro_produto':
                $tituloNotificacao = 'Erro!';
                $descricaoNotificacao = 'O produto selecionado não foi encontrado.';
                break;
            case 'erro_quantidade':
                $tituloNotificacao = 'Erro!';
                $descricaoNotificacao = 'Infelizmente não temos a quantidade desejada do produto disponível em nosso estoque.';
                break;
            default:
                $status = false;
        }

        if ($status) {
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
    }

    ?>

</body>

</html>