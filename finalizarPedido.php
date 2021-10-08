<?php

require 'vendor/autoload.php';

use App\Entity\Produto;
use App\Entity\Categoria;
use App\Session\Carrinho;
use App\Entity\Desconto;
use App\Entity\FormaDePagamento;
use App\Entity\Entrega;
use App\Entity\Pedido;
use App\Telegram\Alert;

//VALIDA SE TEM ITENS NO CARRINHO
if (Carrinho::getQuantidadeProdutos() == 0) {
    header('Location: carrinho.php');
    echo '<script>location.href="carrinho.php"</script>';
    exit;
}

//VALIDA SE RECEBEU POST PARA CRIAR O PEDIDO
if (isset($_POST['nome'], $_POST['sobrenome'], $_POST['whatsapp'], $_POST['forma_de_pagamento'], $_POST['entrega']) and is_numeric($_POST['forma_de_pagamento']) and is_numeric($_POST['entrega'])) {

    //VALIDA WHATSAPP
    if (!preg_match("/\(?\d{2}\)?\s?\d{5}\-?\d{4}/", $_POST['whatsapp'])) {
        header('Location: ?status=erro_whatsapp');
        echo '<script>location.href="?status=erro_whatsapp"</script>';
        exit;
    }

    //VALIDA E DEFINE FORMA DE PAGAMENTO
    $formaDePagamento = FormaDePagamento::getFormaDePagamento($_POST['forma_de_pagamento']);
    if (!$formaDePagamento instanceof FormaDePagamento) {
        header('Location: ?status=erro');
        echo '<script>location.href="?status=erro"</script>';
        exit;
    }
    $formaDePagamento = $formaDePagamento->nome;

    //VALIDA E DEFINE ENTREGA
    $entrega = Entrega::getEntrega($_POST['entrega']);
    if (!$entrega instanceof Entrega) {
        header('Location: ?status=erro');
        echo '<script>location.href="?status=erro"</script>';
        exit;
    }
    $entrega = $entrega->nome;

    //VALOR TOTAL
    $valorTotal = 0;

    //LISTAGEM DOS PRODUTOS
    $carrinhoProdutos = Carrinho::getProdutos();
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

            $produtos[$produtoId] = [
                'id' => $produtoId,
                'nome' => $produto->nome,
                'categoria' => Categoria::getCategoria($produto->categoria)->nome,
                'quantidade' => Carrinho::getProduto($produtoId),
                'preco_de_venda' => $produto->preco_de_venda,
                'preco_total' => ($produto->preco_de_venda * Carrinho::getProduto($produtoId))
            ];

            //INCREMENTA O VALOR TOTAL
            $valorTotal += ($produto->preco_de_venda * Carrinho::getProduto($produtoId));
        }
    }

    //LISTA OS DESCONTOS ATIVOS
    $descontos = Desconto::getDescontos('ativo = 1');

    //VALIDA SE TEM DESCONTO ATIVO
    if (empty($descontos)) {
        $valorDesconto = 0;
    } else {
        //CHAVE PARA VERIFICAR SE UM DESCONTO FOI APLICADO
        $chaveDesconto = false;

        foreach ($descontos as $desconto) {
            //VALIDA SE O DESCONTO PARA QUANTIDADE ÚNICA SE APLICA
            if ($desconto->qnt_unica == 1 && Carrinho::getQuantidadeProdutos() == $desconto->qnt_produtos) {
                //APLICA O DESCONTO E DEFINE O VALOR
                $valorDesconto = $valorTotal * ($desconto->porcentagem / 100);

                //MARCA COMO DESCONTADO
                $chaveDesconto = true;
                break;
            }

            //VALIDA SE O DESCONTO PARA QUANTIDADE MAIOR OU IGUAL SE APLICA
            if ($desconto->qnt_unica == 0 && Carrinho::getQuantidadeProdutos() >= $desconto->qnt_produtos) {
                //APLICA O DESCONTO E DEFINE O VALOR
                $valorDesconto = $valorTotal * ($desconto->porcentagem / 100);

                //MARCA COMO DESCONTADO
                $chaveDesconto = true;
                break;
            }
        }

        //CASO NENHUM DESCONTO TENHA SE APLICADO
        if ($chaveDesconto === false) {
            $valorDesconto = 0;
        }
    }

    //FORMATA WHATSAPP
    $whatsapp = $_POST['whatsapp'];
    preg_match_all("/\d+/", $whatsapp, $whatsapp);
    $whatsapp = "+55" . implode('', $whatsapp[0]);

    //CADASTRA O PEDIDO NO BANCO
    $objPedido = new Pedido();
    $objPedido->nome = $_POST['nome'];
    $objPedido->sobrenome = $_POST['sobrenome'];
    $objPedido->whatsapp = $whatsapp;
    $objPedido->forma_de_pagamento = $formaDePagamento;
    $objPedido->entrega = $entrega;
    $objPedido->produtos = json_encode($produtos);
    $objPedido->valor_desconto = $valorDesconto;
    $objPedido->valor_total = $valorTotal;
    $objPedido->status = 0;
    $chaveCadastrado = $objPedido->cadastrar();

    //VALIDA SE FOI CADASTRADO PARA EXCLUIR OS PRODUTOS DO BANCO
    if ($chaveCadastrado) {
        foreach ($produtos as $produtoId => $produto) {
            $objProduto = Produto::getProduto($produtoId);
            //VALIDA SE O PRODUTO EXISTE
            if ($objProduto instanceof Produto) {
                $objProduto->estoque = ($objProduto->estoque - $produto['quantidade'] <= 0) ? 0 : $objProduto->estoque - $produto['quantidade'];
                if ($objProduto->estoque <= 0) {
                    $objProduto->disponivel = 0;
                    //AVISO TELEGRAM
                    Alert::sendMessage('<b>ESTOQUE ZERADO #' . $objProduto->id . ':</b> ' . $objProduto->nome . ' (<a href="http://' . $_SERVER["HTTP_HOST"] . '/fdj/editarProduto.php?id=' . $objPedido->id . '">editar produto</a>)', 'html');
                }
                $objProduto->atualizar();
            }
        }

        //LIMPA O CARRINHO
        Carrinho::clear();

        //LISTA DE PRODUTOS PARA O TELEGRAM
        $listaProdutos = '';
        $quantidadeProdutosPedido = 0;
        foreach (json_decode((string) $objPedido->produtos) as $produto) {
            $quantidadeProdutosPedido += $produto->quantidade;
            $listaProdutos .= $produto->quantidade . 'x ' . $produto->nome . ' (R$' . number_format(($produto->preco_total), 2, ',', '.') . ')
';
        }

        //AVISO TELEGRAM
        Alert::sendMessage('<b>--------- NOVO PEDIDO #' . $objPedido->id . ' ---------</b>

<pre>' . $listaProdutos . '</pre>
<b>Valor total:</b> ' . ($objPedido->valor_desconto > 0 ? '<del>R$' . number_format(($objPedido->valor_total), 2, ',', '.') . '</del> ' : '') . 'R$' . number_format(($objPedido->valor_total - $objPedido->valor_desconto), 2, ',', '.') . ' (' . ($quantidadeProdutosPedido > 1 ? $quantidadeProdutosPedido . ' produtos' : $quantidadeProdutosPedido . ' produto') . ')
<b>Desconto:</b> R$' . number_format(($objPedido->valor_desconto), 2, ',', '.') . '

<b>--------- INFORMAÇÕES ---------</b>

<b>Nome:</b> ' . $objPedido->nome . ' ' . $objPedido->sobrenome . '
<b>WhatsApp:</b> ' . $objPedido->whatsapp . '
<b>Data:</b> ' . (new DateTime($objPedido->data))->format('d/m/Y H:i:s') . '
<b>Forma de pagamento:</b> ' . $objPedido->forma_de_pagamento . '
<b>Entrega:</b> ' . $objPedido->entrega . '

<b>--------- OPÇÕES ---------</b>

<a href="http://' . $_SERVER["HTTP_HOST"] . '/fdj/pedidos.php?id=' . $objPedido->id . '&tipo=' . $objPedido->status . '">-> Fazer atendimento</a>', 'html');

        //SUCESSO
        header('Location: index.php?status=obrigado');
        echo '<script>location.href="index.php?status=obrigado"</script>';
        exit;
    } else {
        //ERRO
        header('Location: index.php?status=erro');
        echo '<script>location.href="index.php?status=erro"</script>';
    }
}

//ATRIBUI O STATUS A UMA VARIÁVEL
$status = $_GET['status'];

//LISTAGEM DAS FORMAS DE PAGAMENTO
$formasDePagamento = FormaDePagamento::getFormasDePagamento('disponivel = 1', 'id ASC');
$listaFormasDePagamento = '';
foreach ($formasDePagamento as $key => $formadePagamento) {
    $listaFormasDePagamento .= '<option value="' . $formadePagamento->id . '" ' . ($key === array_key_first($formasDePagamento) ? 'selected' : '') . '>' . $formadePagamento->nome . '</option>';
}

//LISTAGEM DAS ENTREGAS
$entregas = Entrega::getEntregas('disponivel = 1', 'id ASC');
$listaEntregas = '';
foreach ($entregas as $key => $entrega) {
    $listaEntregas .= '<option value="' . $entrega->id . '" ' . ($key === array_key_first($entrega) ? 'selected' : '') . '>' . $entrega->nome . '</option>';
}

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

            $produtos[$produtoId] = [
                'id' => $produtoId,
                'nome' => $produto->nome,
                'categoria' => Categoria::getCategoria($produto->categoria)->nome,
                'quantidade' => Carrinho::getProduto($produtoId),
                'preco_de_venda' => $produto->preco_de_venda,
                'preco_total' => ($produto->preco_de_venda * Carrinho::getProduto($produtoId))
            ];
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
        $valorTotal += ($produto['preco_de_venda'] * Carrinho::getProduto($produto['id']));

        //PRODUTO
        $listaProdutos .= '<li class="list-group-item d-flex justify-content-between lh-sm"><div><h6 class="my-0"><small>' . $produto['quantidade'] . 'x</small> ' . $produto['nome'] . '</h6><small class="text-muted">' . $produto['categoria'] . '</small></div><span class="text-muted">R$' . number_format($produto['preco_total'], 2, ',', '.') . '</span></li>';
    }

    //VARIÁVEIS PARA FINALIZAÇÃO DA COMPRA
    $listaDesconto = '';

    //LISTA OS DESCONTOS ATIVOS
    $descontos = Desconto::getDescontos('ativo = 1');

    //VALIDA SE TEM DESCONTO ATIVO
    if (empty($descontos)) {
        $valorTotal = number_format($valorTotal, 2, ',', '.');
        $valorTotal = '<strong>R$' . $valorTotal . '</strong>';
    } else {

        //CHAVE PARA VERIFICAR SE UM DESCONTO FOI APLICADO
        $chaveDesconto = false;

        foreach ($descontos as $desconto) {

            //VALIDA SE O DESCONTO PARA QUANTIDADE ÚNICA SE APLICA
            if ($desconto->qnt_unica == 1 && $quantidadeCarrinho == $desconto->qnt_produtos) {
                //APLICA O DESCONTO E DEFINE O VALOR
                $valorDesconto = $valorTotal * ($desconto->porcentagem / 100);
                $valorDescontado = $valorTotal - ($valorDesconto);
                $valorDescontado = number_format($valorDescontado, 2, ',', '.');
                $valorDesconto = number_format($valorDesconto, 2, ',', '.');
                $valorTotal = '<strong>R$' . $valorDescontado . '</strong>';

                //DEFINE A LISTA DO DESCONTO
                $listaDesconto = '<li class="list-group-item d-flex justify-content-between bg-light"><div class="text-success"><h6 class="my-0">Desconto</h6><small>' . $desconto->porcentagem . '%</small></div><span class="text-success">−R$' . $valorDesconto . '</span></li>';

                //MARCA COMO DESCONTADO
                $chaveDesconto = true;
                break;
            }

            //VALIDA SE O DESCONTO PARA QUANTIDADE MAIOR OU IGUAL SE APLICA
            if ($desconto->qnt_unica == 0 && $quantidadeCarrinho >= $desconto->qnt_produtos) {
                //APLICA O DESCONTO E DEFINE O VALOR
                $valorDesconto = $valorTotal * ($desconto->porcentagem / 100);
                $valorDescontado = $valorTotal - ($valorDesconto);
                $valorDescontado = number_format($valorDescontado, 2, ',', '.');
                $valorDesconto = number_format($valorDesconto, 2, ',', '.');
                $valorTotal = '<strong>R$' . $valorDescontado . '</strong>';

                //DEFINE A LISTA DO DESCONTO
                $listaDesconto = '<li class="list-group-item d-flex justify-content-between bg-light"><div class="text-success"><h6 class="my-0">Desconto</h6><small>' . $desconto->porcentagem . '%</small></div><span class="text-success">−R$' . $valorDesconto . '</span></li>';

                //MARCA COMO DESCONTADO
                $chaveDesconto = true;
                break;
            }
        }

        //CASO NENHUM DESCONTO TENHA SE APLICADO
        if ($chaveDesconto === false) {
            $valorTotal = number_format($valorTotal, 2, ',', '.');
            $valorTotal = '<strong>R$' . $valorTotal . '</strong>';
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
    <title>Fonte das Joias | Finalizar Pedido</title>

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

        <div class="container-fluid mb-5">
            <div class="row mt-5 mb-3">
                <div class="col text-center">
                    <h3 class="display-6">FINALIZAR PEDIDO</h3>
                </div>
            </div>
            <div class="row py-3 bg-light justify-content-center">
                <div class="col-12 col-md-4 py-2 text-center">
                    <p>Após você finalizar o seu pedido é só aguardar alguns minutos que entraremos em contato com você
                        no seu WhatsApp.</p>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="row g-5">
                <div class="col-md-5 col-lg-4 order-md-last">
                    <h4 class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-primary">Resumo do carrinho</span>
                        <span class="badge bg-primary rounded-pill"><?= $quantidadeCarrinho ?></span>
                    </h4>
                    <ul class="list-group mb-3">
                        <?= $listaProdutos ?>
                        <?= $listaDesconto ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Total</span>
                            <?= $valorTotal ?>
                        </li>
                    </ul>
                </div>
                <div class="col-md-7 col-lg-8">
                    <h4 class="mb-3">Suas Informações</h4>
                    <form method="post" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">Nome</label>
                                <input name="nome" type="text" class="form-control" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Sobrenome</label>
                                <input name="sobrenome" type="text" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label for="username" class="form-label">WhatsApp</label>
                                <div class="input-group">
                                    <span class="input-group-text"><svg xmlns="http://www.w3.org/2000/svg" width="16"
                                            height="16" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
                                            <path
                                                d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z" />
                                        </svg></span>
                                    <input name="whatsapp" id="whatsapp" type="text" class="form-control"
                                        placeholder="(00) 00000-0000" required>
                                </div>
                            </div>
                            <hr class="my-4">
                            <h4 class="mb-3">Pagamento e entrega</h4>
                            <div class="col-sm-3">
                                <label class="form-label">Forma de pagamento</label>
                                <select name="forma_de_pagamento" class="form-select">
                                    <?= $listaFormasDePagamento ?>
                                </select>
                            </div>

                            <div class="col-sm-9">
                                <label class="form-label">Entrega</label>
                                <select name="entrega" class="form-select">
                                    <?= $listaEntregas ?>
                                </select>
                            </div>

                            <hr class="my-4">

                            <button class="w-100 btn btn-success btn-lg" type="submit">Finalizar pedido</button>
                    </form>
                </div>
            </div>
        </div>
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
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery.maskedinput/1.4.1/jquery.maskedinput.min.js"></script>

    <script>
    $("#whatsapp")
        .mask("(99) 9999-9999?9")
        .focusout(function(event) {
            var target, phone, element;
            target = (event.currentTarget) ? event.currentTarget : event.srcElement;
            phone = target.value.replace(/\D/g, '');
            element = $(target);
            element.unmask();
            if (phone.length > 10) {
                element.mask("(99) 99999-999?9");
            } else {
                element.mask("(99) 9999-9999?9");
            }
        });
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
            case 'erro_whatsapp':
                $tituloNotificacao = 'Erro!';
                $descricaoNotificacao = 'O número do WhatsApp está incorreto.';
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