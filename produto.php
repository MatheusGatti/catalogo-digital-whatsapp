<?php

require 'vendor/autoload.php';

use App\Entity\Produto;
use App\Entity\Categoria;
use App\Session\Carrinho;
use App\Entity\Desconto;

//ATRIBUI O STATUS A UMA VARIÁVEL
$status = $_GET['status'];

//VALIDAÇÃO DO ID DO PRODUTO
if (!isset($_GET['id']) or !is_numeric($_GET['id'])) {
    header("Location: index.php?status=erro_produto");
    echo '<script>location.href="index.php?status=erro_produto"</script>';
    exit;
}

//VALIDA SE O PRODUTO EXISTE E SE A CATEGORIA DO PRODUTO EXISTE E ESTÁ DISPONÍVEL
$produto = Produto::getProduto($_GET['id']);
if (!$produto instanceof Produto or $produto->disponivel != 1) {
    header("Location: index.php?status=erro_produto");
    echo '<script>location.href="index.php?status=erro_produto"</script>';
    exit;
} else {
    $categoria = Categoria::getCategoria($produto->categoria);
    if (!$categoria instanceof Categoria or $categoria->disponivel != 1) {
        header("Location: index.php?status=erro_produto");
        echo '<script>location.href="index.php?status=erro_produto"</script>';
        exit;
    }
}

//LISTAGEM DAS FOTOS
$fotos = json_decode((string) $produto->fotos);
$resultadoFotos = '';
$resultadoBotoesFotos = '';
$countFotos = 0;
foreach ($fotos as $foto) {
    $resultadoFotos .= '<div class="carousel-item ' . ($countFotos == 0 ? 'active' : '') . '"><img src="/images/produtos/' . $foto . '" class="d-block w-100"></div>';
    $resultadoBotoesFotos .= '<button type="button" data-bs-target="#carrosel" data-bs-slide-to="' . $countFotos . '" ' . ($countFotos == 0 ? 'class="active" aria-current="true"' : '') . '></button>';
    $countFotos++;
}

//VALIDA SE RECEBEU O POST PARA ADICIONAR AO CARRINHO
if (isset($_POST['id'], $_POST['quantidade']) && is_numeric($_POST['id']) && is_numeric($_POST['quantidade'])) {
    //VALIDA SE O PRODUTO EXISTE E SE A CATEGORIA DO PRODUTO EXISTE E ESTÁ DISPONÍVEL
    $produto = Produto::getProduto($_POST['id']);
    if (!$produto instanceof Produto or $produto->disponivel != 1) {
        header('Location: ?id=' . $_GET['id'] . '&status=erro');
        echo '<script>location.href="?id=' . $_GET['id'] . '&status=erro"</script>';
        exit;
    } else {
        $categoria = Categoria::getCategoria($produto->categoria);
        if (!$categoria instanceof Categoria or $categoria->disponivel != 1) {
            header('Location: ?id=' . $_GET['id'] . '&status=erro');
            echo '<script>location.href="?id=' . $_GET['id'] . '&status=erro"</script>';
            exit;
        }
    }

    //VALIDA SE O PRODUTO ESTÁ DISPONÍVEL
    if ($produto->disponivel == 0) {
        header('Location: ?id=' . $_GET['id'] . '&status=erro');
        echo '<script>location.href="?id=' . $_GET['id'] . '&status=erro"</script>';
        exit;
    }

    //VALIDA A QUANTIDADE
    $quantidadeProdutoCarrinho = Carrinho::getProduto($produto->id);
    if ($_POST['quantidade'] <= 0 or $_POST['quantidade'] > $produto->estoque or ($quantidadeProdutoCarrinho + $_POST['quantidade']) > $produto->estoque) {
        header('Location: ?id=' . $_GET['id'] . '&status=erro_quantidade');
        echo '<script>location.href="?id=' . $_GET['id'] . '&status=erro_quantidade"</script>';
        exit;
    }

    //ADICIONAR PRODUTO AO CARRINHO
    Carrinho::add($produto->id, $_POST['quantidade']);
    header('Location: ?id=' . $_GET['id'] . '&status=sucesso');
    echo '<script>location.href="?id=' . $_GET['id'] . '&status=sucesso"</script>';
}

//CARREGAR CARRINHO
$quantidadeCarrinho = Carrinho::getQuantidadeProdutos();
$quantidadeProdutoCarrinho = Carrinho::getProduto($produto->id);
?>
<!doctype html>
<html lang="pt-Br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Matheus Gatti">
    <title>Fonte das Joias | <?= $produto->nome ?></title>

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

        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-4 py-2">
                    <div class="col text-center mt-5 mb-4">
                        <h3 class="display-6"><?= $produto->nome ?></h3>
                        <a href="categoria.php?id=<?= $produto->categoria ?>" class="btn btn-sm btn-warning"
                            role="button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-arrow-left-short" viewBox="0 0 16 16">
                                <path fill-rule="evenodd"
                                    d="M12 8a.5.5 0 0 1-.5.5H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5a.5.5 0 0 1 .5.5z" />
                            </svg>
                            Voltar
                        </a>
                    </div>
                    <form method="post" autocomplete="off">
                        <div class="card">
                            <div id="carrosel" class="carousel slide card-img-top" data-bs-ride="carousel">
                                <div class="carousel-indicators">
                                    <?= $resultadoBotoesFotos ?>
                                </div>
                                <div class="carousel-inner">
                                    <?= $resultadoFotos ?>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#carrosel"
                                    data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Anterior</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#carrosel"
                                    data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Próximo</span>
                                </button>
                            </div>
                            <div class="card-body">
                                <h4 class="text-center mb-3"><span
                                        class="badge rounded-pill bg-light text-dark">R$<?= number_format($produto->preco_de_venda, 2, ',', '.') ?></span>
                                </h4>
                                <div class="row justify-content-center">
                                    <div class="col-4">
                                        <input type="hidden" name="id" required value="<?= $produto->id ?>">
                                        <div class="input-group input-group-sm mb-3">
                                            <button disabled class="btn btn-outline-secondary" type="button"
                                                id="diminuir" data-field="quantidade">-</button>
                                            <input name="quantidade" id="quantidade" type="number" required
                                                class="form-control text-center"
                                                value="<?= ($quantidadeProdutoCarrinho == $produto->estoque) ? 0 : 1 ?>"
                                                min="1" max="<?= ($produto->estoque - $quantidadeProdutoCarrinho) ?>">
                                            <button
                                                <?= ($produto->estoque - $quantidadeProdutoCarrinho <= 1) ? 'disabled' : '' ?>
                                                class="btn btn-outline-secondary" type="button" id="aumentar"
                                                data-field="quantidade">+</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-grid gap-2">
                                    <button
                                        <?= ($produto->estoque - $quantidadeProdutoCarrinho == 0) ? 'disabled' : '' ?>
                                        type="submit" class="btn btn-success btn-sm">COMPRAR</button>
                                </div>
                                <p class="card-text text-center py-1"><small><span class="badge bg-warning text-dark">ou
                                            3x
                                            de
                                            R$<?= number_format(($produto->preco_de_venda / 3), 2, ',', '.') ?></span></small>
                                </p>
                            </div>
                            <?= strlen($produto->descricao) ? '<div class="card-footer text-muted">' . nl2br($produto->descricao) . '</div>' : '' ?>
                        </div>
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

    <script>
    function aumentar(e) {
        e.preventDefault();
        var fieldName = $(e.target).data('field');
        var parent = $(e.target).closest('div');
        var currentVal = parseInt(parent.find('input[name=' + fieldName + ']').val(), 10);

        if (!isNaN(currentVal)) {
            parent.find('input[name=' + fieldName + ']').val(currentVal + 1);
        } else {
            parent.find('input[name=' + fieldName + ']').val(1);
        }

        //VALIDAR SE ULTRAPASSOU O ESTOQUE
        if (parent.find('input[name=' + fieldName + ']').val() ==
            <?= $produto->estoque - $quantidadeProdutoCarrinho ?>) {
            $(e.target).prop('disabled', true);
        }

        //VALIDA SE CHEGOU A 1 UNIDADE
        if (parent.find('input[name=' + fieldName + ']').val() == 1) {
            $("#diminuir").prop('disabled', true);
        } else {
            $("#diminuir").prop('disabled', false);
        }
    }

    function diminuir(e) {
        e.preventDefault();
        var fieldName = $(e.target).data('field');
        var parent = $(e.target).closest('div');
        var currentVal = parseInt(parent.find('input[name=' + fieldName + ']').val(), 10);

        if (!isNaN(currentVal) && currentVal > 1) {
            parent.find('input[name=' + fieldName + ']').val(currentVal - 1);
        } else {
            parent.find('input[name=' + fieldName + ']').val(1);
        }

        //VALIDAR SE ULTRAPASSOU O ESTOQUE
        if (parent.find('input[name=' + fieldName + ']').val() <
            <?= $produto->estoque - $quantidadeProdutoCarrinho ?>) {
            $("#aumentar").prop('disabled', false);
        }

        //VALIDA SE CHEGOU A 1 UNIDADE
        if (parent.find('input[name=' + fieldName + ']').val() == 1) {
            $("#diminuir").prop('disabled', true);
        } else {
            $("#diminuir").prop('disabled', false);
        }
    }

    $('#aumentar').on('click', function(e) {
        aumentar(e);
    });

    $('#diminuir').on('click', function(e) {
        diminuir(e);
    });

    $('#quantidade').on('change', function(e) {
        if ($(this).val() >= <?= $produto->estoque - $quantidadeProdutoCarrinho ?>) {
            $(this).val(<?= $produto->estoque - $quantidadeProdutoCarrinho ?>);
            if ($('#quantidade').val() == <?= $produto->estoque - $quantidadeProdutoCarrinho ?>) {
                $('#aumentar').prop('disabled', true);
            }
            if ($('#quantidade').val() == 1) {
                $("#diminuir").prop('disabled', true);
            } else {
                $("#diminuir").prop('disabled', false);
            }
        } else if ($(this).val() <= 0) {
            $(this).val(1);
            if ($('#quantidade').val() < <?= $produto->estoque - $quantidadeProdutoCarrinho ?>) {
                $("#aumentar").prop('disabled', false);
            }
            if ($('#quantidade').val() == 1) {
                $("#diminuir").prop('disabled', true);
            } else {
                $("#diminuir").prop('disabled', false);
            }
        } else {
            $("#aumentar").prop('disabled', false);
            $("#diminuir").prop('disabled', false);
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
            case 'erro_quantidade':
                $tituloNotificacao = 'Erro!';
                $descricaoNotificacao = 'A quantidade do produto não pode ser menor/igual a zero ou maior do que temos no estoque.';
                break;
            case 'sucesso':
                $sucesso = true;
                break;
            default:
                $status = false;
        }

        if ($sucesso) {

            //LISTA OS DESCONTOS ATIVOS
            $descontos = Desconto::getDescontos('ativo = 1');

            //VALIDA SE TEM DESCONTOS
            if (empty($descontos)) {
                echo "<script>Swal.fire({
                    title: 'Sucesso!',
                    text: 'Produto adicionado ao seu carrinho de compras.',
                    icon: 'success',
                    confirmButtonText: 'Continuar comprando',
                    showDenyButton: true,
                    denyButtonText: 'Ir para o carrinho',
                    denyButtonColor: '#333333',
                    confirmButtonColor: '#198754',
                }).then((result) => {
                    if (result.isDenied) {
                        location.href='carrinho.php';
                    }
                })</script>";
                return;
            }

            foreach ($descontos as $desconto) {
                //QUANTIDADE TOTAL DE PRODUTOS NO CARRINHO
                $quantidadeTotalProdutos = Carrinho::getQuantidadeProdutos();

                //VALIDA SE O DESCONTO PARA QUANTIDADE ÚNICA SE APLICA
                if ($desconto->qnt_unica == 1 && $quantidadeTotalProdutos == $desconto->qnt_produtos) {
                    echo "<script>Swal.fire({
                        title: 'Sucesso!',
                        html: 'O seu produto foi adicionado com sucesso ao carrinho de compras. ' +
                        '<div class=\'alert alert-success mt-3\' role=\'alert\'>' +
                        '<b>Parabéns! Você acabou de ganhar um desconto de " . $desconto->porcentagem . "%.</b>' +
                        '</div>',
                        icon: 'success',
                        confirmButtonText: 'Garantir meu desconto!',
                        showDenyButton: true,
                        denyButtonText: 'Continuar comprando',
                        denyButtonColor: '#333333',
                        confirmButtonColor: '#198754',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.href='carrinho.php';
                        }
                    })</script>";
                    return;
                    break;
                }

                //VALIDA SE O DESCONTO PARA QUANTIDADE MAIOR OU IGUAL SE APLICA
                if ($desconto->qnt_unica == 0 && $quantidadeTotalProdutos >= $desconto->qnt_produtos) {
                    echo "<script>Swal.fire({
                        title: 'Parabéns!',
                        html: 'O seu produto foi adicionado com sucesso ao carrinho de compras. ' +
                        '<div class=\'alert alert-success mt-3\' role=\'alert\'>' +
                        '<b>Você acabou de ganhar um desconto de " . $desconto->porcentagem . "%.</b>' +
                        '</div>',
                        icon: 'success',
                        confirmButtonText: 'Garantir meu desconto!',
                        showDenyButton: true,
                        denyButtonText: 'Continuar comprando',
                        denyButtonColor: '#333333',
                        confirmButtonColor: '#198754',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.href='carrinho.php';
                        }
                    })</script>";
                    return;
                    break;
                }

                //VERIFICA SE NÃO TEM ALGUM DESCONTO PRÓXIMO A SER APLICADO
                $diferenca = $desconto->qnt_produtos - $quantidadeTotalProdutos;
                if ($diferenca === 1) {
                    echo "<script>Swal.fire({
                        title: 'Sucesso!',
                        html: 'O seu produto foi adicionado com sucesso ao carrinho de compras. ' +
                        '<div class=\'alert alert-warning mt-3\' role=\'alert\'>' +
                        '<b>Na compra de mais um produto você ganhará um desconto único e imperdível.</b>' +
                        '</div>',
                        icon: 'success',
                        confirmButtonText: 'Continuar comprando',
                        showDenyButton: true,
                        denyButtonText: 'Ir para o carrinho',
                        denyButtonColor: '#333333',
                        confirmButtonColor: '#198754',
                    }).then((result) => {
                        if (result.isDenied) {
                            location.href='carrinho.php';
                        }
                    })</script>";
                    return;
                    break;
                }
            }

            //CASO NENHUM DESCONTO TENHA SE APLICADO
            echo "<script>Swal.fire({
                    title: 'Sucesso!',
                    text: 'Produto adicionado ao seu carrinho de compras.',
                    icon: 'success',
                    confirmButtonText: 'Continuar comprando',
                    showDenyButton: true,
                    denyButtonText: 'Ir para o carrinho',
                    denyButtonColor: '#333333',
                    confirmButtonColor: '#198754',
                }).then((result) => {
                    if (result.isDenied) {
                        location.href='carrinho.php';
                    }
                })</script>";
        } elseif ($status) {
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