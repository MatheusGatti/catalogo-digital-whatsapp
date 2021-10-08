<?php

require '../vendor/autoload.php';

date_default_timezone_set('America/Sao_Paulo');

use App\Session\Login;
use App\Entity\Pedido;
use App\Entity\Usuario;
use App\Db\Pagination;
use App\Entity\Produto;
use App\Telegram\Alert;

//OBRIGA O USUÁRIO A ESTAR LOGADO
Login::requireLogin();

$usuarioLogado = Login::getUsuarioLogado();

//VERIFICA SE RECEBEU STATUS E ATRIBUI A UMA VARIÁVEL
$status = $_GET['status'];

//QUANTIDADE DE PEDIDOS NOVOS
$quantidadePedidosNovos = count(Pedido::getPedidos('status = 0'));

//LISTA ATENDENTES
$pesquisaAtendentes = Usuario::getUsuarios(null, 'id ASC');
$resultadoAtendentesID = [];
$resultadoAtendentes = '';
foreach ($pesquisaAtendentes as $atendente) {
    $resultadoAtendentes .= '<option value="' . $atendente->id . '">' . $atendente->nome . '</option>';
    array_push($resultadoAtendentesID, $atendente->id);
}
//FILTRO DE PESQUISA
$filtroNumeroPedido = filter_input(INPUT_GET, 'filtroNumeroPedido', FILTER_SANITIZE_NUMBER_INT);
$filtroNome = filter_input(INPUT_GET, 'filtroNome', FILTER_SANITIZE_STRING);
$filtroSobrenome = filter_input(INPUT_GET, 'filtroSobrenome', FILTER_SANITIZE_STRING);
$filtroStatus = filter_input(INPUT_GET, 'filtroStatus', FILTER_SANITIZE_NUMBER_INT);
$filtroStatus = in_array($filtroStatus, [0, 1, 2]) ? $filtroStatus : '';
$filtroAtendente = filter_input(INPUT_GET, 'filtroAtendente', FILTER_SANITIZE_NUMBER_INT);
$filtroAtendente = in_array($filtroAtendente, $resultadoAtendentesID) ? $filtroAtendente : '';
$filtroData = filter_input(INPUT_GET, 'filtroData', FILTER_SANITIZE_STRING);

//CONDIÇÕES SQL PARA O FILTRO DE PESQUISA
$condicoes = [
    strlen($filtroNumeroPedido) ? 'id = ' . $filtroNumeroPedido : '',
    strlen($filtroNome) ? 'nome LIKE "%' . str_replace(' ', '%', $filtroNome) . '%"' : null,
    strlen($filtroSobrenome) ? 'sobrenome LIKE "%' . str_replace(' ', '%', $filtroSobrenome) . '%"' : null,
    strlen($filtroStatus) ? 'status = ' . $filtroStatus . '' : null,
    strlen($filtroAtendente) ? 'atendente = ' . $filtroAtendente . '' : null,
    strlen($filtroData) ? 'data LIKE "%' . str_replace(' ', '%', $filtroData) . '%"' : null
];
$condicoes = array_filter($condicoes);

//CLÁUSULA WHERE PARA PESQUISA
$where = implode(' AND ', $condicoes);

//QUANTIDADE TOTAL DE PEDIDOS
$quantidadeTotalPedidos = Pedido::getQuantidadePedidos($where);

//PAGINAÇÃO
$currentPage = $_GET['pagina'] ?? 1;
$limit = 4;
$objPaginacao = new Pagination($quantidadeTotalPedidos, $currentPage, $limit);
$paginas = $objPaginacao->getPages();
$middle = ceil($limit / 2);
$start = $middle > $currentPage ? 0 : $currentPage - $middle;
$limit = $limit + $start;
if ($limit > count($paginas)) {
    $diff = $limit - count($paginas);
    $start -= $diff;
}
$paginacao = '';
unset($_GET['status']);
unset($_GET['pagina']);
$gets = http_build_query($_GET);
if ($start > 0) {
    $paginacao .= '<li class="page-item"><a class="page-link" href="?pagina=' . reset($paginas)['pagina'] . '" aria-label="Voltar"><span aria-hidden="true">&laquo;</span></a></li>';
}
foreach ($paginas as $key => $pagina) {
    if ($pagina['pagina'] <= $start) continue;
    if ($pagina['pagina'] > $limit) {
        $paginacao .= '<li class="page-item"><a class="page-link" href="?pagina=' . end($paginas)['pagina'] . '" aria-label="Avançar"><span aria-hidden="true">&raquo;</span></a></li>';
        break;
    }
    $class = $pagina['atual'] ? 'active' : '';
    $paginacao .= '<li class="page-item ' . $class . '"><a class="page-link" href="?pagina=' . $pagina['pagina'] . '&' . $gets . '">' . $pagina['pagina'] . '</a></li>';
}

//LISTAGEM DOS PEDIDOS
$pesquisaPedidos = Pedido::getPedidos($where, 'id DESC', $objPaginacao->getLimit());
$resultadoPedidos = '';
if (empty($pesquisaPedidos)) {
    $resultadoPedidos .= '<h6 class="text-center">NENHUM PEDIDO FOI ENCONTRADO :(</h6>';
} else {
    foreach ($pesquisaPedidos as $pedido) {
        switch ($pedido->status) {
            case 0:
                $cor = 'success';
                break;
            case 1:
                $cor = 'warning';
                break;
            case 2:
                $cor = 'light';
                break;
        }
        $formatarWhatsApp = substr_replace($pedido->whatsapp, ' ', 3, 0);
        $formatarWhatsApp = substr_replace($formatarWhatsApp, '(', 4, 0);
        $formatarWhatsApp = substr_replace($formatarWhatsApp, ')', 7, 0);
        $formatarWhatsApp = substr_replace($formatarWhatsApp, ' ', 8, 0);
        $formatarWhatsApp = substr_replace($formatarWhatsApp, '-', 14, 0);
        $quantidadeProdutosPedido = 0;
        foreach (json_decode($pedido->produtos) as $produto) {
            $quantidadeProdutosPedido += $produto->quantidade;
        }
        $resultadoPedidos .= '<a href="pedidos.php?id=' . $pedido->id . '&tipo=' . $pedido->status . '" class="py-3 list-group-item list-group-item-action list-group-item-' . $cor . '" aria-current="true">
            <div class="d-flex w-100 justify-content-between">
                <h5 class="mb-1">Pedido #' . $pedido->id . '</h5>
                <small style="font-size: 0.8rem;">' . Pedido::getTempo($pedido->data) . '</small>
            </div>
            <small><strong>Nome completo: </strong>' . $pedido->nome . ' ' . $pedido->sobrenome . '</small>
            <br>
            <small><strong>WhatsApp: </strong>' . $formatarWhatsApp . '</small>
            <br>
            <small><strong>Total: </strong>R$' . number_format(($pedido->valor_total - $pedido->valor_desconto), 2, ',', '.') . ' (' . ($quantidadeProdutosPedido > 1 ? $quantidadeProdutosPedido . ' produtos' : $quantidadeProdutosPedido . ' produto') . ')</small>
            <br>
            <small><strong>Forma de pagamento: </strong>' . $pedido->forma_de_pagamento . '</small>
            <br>
            <small><strong>Entrega: </strong>' . $pedido->entrega . '</small>
        </a>';
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
    <title>Fonte das Joias | Pedidos</title>

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

    /* .list-group {
        max-height: 620px;
        margin-bottom: 10px;
        overflow: scroll;
        -webkit-overflow-scrolling: touch;
    } */
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
                    <h3 class="display-6">PEDIDOS</h3>
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
                    <h6 class="text-center"><?= $quantidadePedidosNovos ?> PEDIDO(S) NÃO ATENDIDO(S)</h6>
                    <div class="text-center">
                        <small>LEGENDA</small>
                        <br>
                        <div class="btn-group btn-group-sm my-2" role="group" aria-label="Basic mixed styles example">
                            <a role="button" class="btn btn-success">Novo</a>
                            <a role="button" class="btn btn-warning">Atendido</a>
                            <a role="button" class="btn btn-light">Finalizado</a>
                        </div>
                        <br>
                        <small>FILTROS</small>
                        <br>
                        <form method="get" autocomplete="off">
                            <input name="filtroNumeroPedido" class="form-control form-control-sm my-2" type="text"
                                placeholder="Número do pedido" value="<?= $filtroNumeroPedido ?>">
                            <input name="filtroNome" class="form-control form-control-sm my-2" type="text"
                                placeholder="Nome" value="<?= $filtroNome ?>">
                            <input name="filtroSobrenome" class="form-control form-control-sm my-2" type="text"
                                placeholder="Sobrenome" value="<?= $filtroSobrenome ?>">
                            <select id='filtroStatus' name="filtroStatus" class="form-select form-select-sm my-2">
                                <option value="" selected>Status</option>
                                <option value="0">Novo</option>
                                <option value="1">Atendido</option>
                                <option value="2">Finalizado</option>
                            </select>
                            <?php echo ($filtroStatus == '' ? '' : '<script>document.getElementById("filtroStatus").value = ' . $filtroStatus . '</script>'); ?>
                            <select id='filtroAtendente' name="filtroAtendente" class="form-select form-select-sm my-2">
                                <option value="" selected>Atendente</option>
                                <?= $resultadoAtendentes ?>
                            </select>
                            <?php echo ($filtroAtendente == '' ? '' : '<script>document.getElementById("filtroAtendente").value = ' . $filtroAtendente . '</script>'); ?>
                            <input name="filtroData" class="form-control form-control-sm my-2" type="date"
                                placeholder="Data" value="<?= $filtroData ?>">
                            <div class="btn-group btn-group-sm my-2" role="group">
                                <button type="submit" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                        class="bi bi-search" viewBox="0 0 16 16">
                                        <path
                                            d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z" />
                                    </svg>
                                    Pesquisar</a>
                                </button>
                                <a href="pedidos.php" role="button" class="btn btn-dark">Limpar</a>
                            </div>
                        </form>
                    </div>
                    <div class="list-group mt-3">
                        <?= $resultadoPedidos ?>
                    </div>
                </div>
                <div class="row my-3">
                    <nav>
                        <ul class="pagination pagination-sm justify-content-center">
                            <?= $paginacao ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

    </main>


    <script src="/js/bootstrap.bundle.min.js"></script>

    <?php

    //VALIDA SE RECEBEU GET PARA PEDIDO
    if (isset($_GET['id'], $_GET['tipo']) and is_numeric($_GET['id']) and is_numeric($_GET['tipo'])) {
        //VALIDA SE O PEDIDO EXISTE E SE É NOVO
        $objPedido = Pedido::getPedido($_GET['id']);
        if (!$objPedido instanceof Pedido) {
            header("Location: ?status=erro");
            echo '<script>location.href="?status=erro"</script>';
            exit;
        }

        //LISTA PRODUTOS
        $quantidadeProdutosPedido = 0;
        $listaProdutos = '';
        foreach (json_decode((string) $objPedido->produtos) as $produto) {
            $quantidadeProdutosPedido += $produto->quantidade;
            $listaProdutos .= '<li class="list-group-item d-flex justify-content-between lh-sm"><div><h6 class="my-0"><small>' . $produto->quantidade . 'x</small> ' . $produto->nome . '</h6><small class="text-muted">' . $produto->categoria . '</small></div><span class="text-muted">R$' . number_format($produto->preco_total, 2, ',', '.') . '</span></li>';
        }
        $listaDesconto = '';
        if ($objPedido->valor_desconto > 0) {
            $listaDesconto = '<li class="list-group-item d-flex justify-content-between bg-light"><div class="text-success"><h6 class="my-0">Desconto</h6></div><span class="text-success">−R$' . number_format($objPedido->valor_desconto, 2, ',', '.') . '</span></li>';
        }

        //VALIDA SE É PEDIDO NOVO E PRODUTO NOVO
        if ($_GET['tipo'] == 0 and $objPedido->status == 0) {
            //VALIDA ATENDENTE
            $objAtendente = Usuario::getUsuario(Login::getUsuarioLogado()['id']);
            if (!$objAtendente instanceof Usuario) {
                header("Location: ?status=erro");
                echo '<script>location.href="?status=erro"</script>';
                exit;
            }

            echo '<div class="modal fade" id="modalNovo" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
                aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="staticBackdropLabel">
                                Pedido #' . $objPedido->id . '
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="container-fluid">
                                <div class="row g-3 mb-2">
                                    <div class="col">
                                        <label class="form-label"><small>Nome</small></label>
                                        <input type="text" class="form-control form-control-sm" value="' . $objPedido->nome . '" readonly>
                                    </div>
                                    <div class="col">
                                        <label class="form-label"><small>Sobrenome</small></label>
                                        <input type="text" class="form-control form-control-sm" value="' . $objPedido->sobrenome . '" readonly>
                                    </div>
                                </div>
                                <div class="row g-3 mb-2">
                                    <div class="col">
                                        <label class="form-label"><small>Data</small></label>
                                        <input type="text" class="form-control form-control-sm" value="' . (new DateTime($objPedido->data))->format('d/m/Y H:i:s') . '"
                                            readonly>
                                    </div>
                                </div>
                                <div class="row g-3 mb-2">
                                    <div class="col">
                                        <label class="form-label"><small>WhatsApp</small></label>
                                        <input type="text" class="form-control form-control-sm" value="' . $objPedido->whatsapp . '"
                                            readonly>
                                    </div>
                                </div>
                                <div class="row g-3 mb-2">
                                    <div class="col d-grid gap-2">
                                        <a href="?id=' . $objPedido->id . '&chamar=" target="_blank" class="btn btn-sm btn-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
                                                <path
                                                    d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z" />
                                            </svg>
                                            Chamar
                                        </a>
                                    </div>
                                </div>
                                <div class="row g-3 mb-2">
                                    <div class="col-md-5">
                                        <label class="form-label"><small>Forma de pagamento</small></label>
                                        <input type="text" class="form-control form-control-sm" value="' . $objPedido->forma_de_pagamento . '" readonly>
                                    </div>
                                    <div class="col-md-7">
                                        <label class="form-label"><small>Entrega</small></label>
                                        <input type="text" class="form-control form-control-sm"
                                            value="' . $objPedido->entrega . '" readonly>
                                    </div>
                                </div>
                                <hr>
                                <div class="row g-5">
                                    <div class="col order-md-last">
                                        <h5 class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="text-primary">Resumo do carrinho</span>
                                            <span class="badge bg-primary rounded-pill">' . $quantidadeProdutosPedido . '</span>
                                        </h5>
                                        <ul class="list-group mb-3">
                                            ' . $listaProdutos . '
                                            ' . $listaDesconto . '
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span>Total</span>
                                                <strong>R$' . number_format(($objPedido->valor_total - $objPedido->valor_desconto), 2, ',', '.') . '</strong>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-dark btn-sm"
                                data-bs-dismiss="modal">Fechar</button>
                            <a href="?id=' . $objPedido->id . '&excluir=" role="button" class="btn btn-outline-danger btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                    class="bi bi-trash" viewBox="0 0 16 16">
                                    <path
                                        d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z" />
                                    <path fill-rule="evenodd"
                                        d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z" />
                                </svg>
                                Excluir
                            </a>
                            <a href="?id=' . $objPedido->id . '&chamar=" role="button" class="btn btn-primary btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                    class="bi bi-whatsapp" viewBox="0 0 16 16">
                                    <path
                                        d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z" />
                                </svg>
                                Chamar
                            </a>
                        </div>
                    </div>
                </div>
            </div>';
            echo "<script>var modalNovo = new bootstrap.Modal(document.getElementById('modalNovo'))</script>";
            echo "<script>modalNovo.show()</script>";
        } else if ($_GET['tipo'] == 1 and $objPedido->status == 1) {
            //VALIDA SE É PEDIDO ATENDIDO

            //VALIDA ATENDENTE
            $objAtendente = Usuario::getUsuario($objPedido->atendente);
            if (!$objAtendente instanceof Usuario) {
                header("Location: ?status=erro");
                echo '<script>location.href="?status=erro"</script>';
                exit;
            }
            echo '<div class="modal fade" id="modalAtendido" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
                aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="staticBackdropLabel">
                                Pedido #' . $objPedido->id . '
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="container-fluid">
                                <div class="row g-3 mb-2">
                                    <div class="col">
                                        <label class="form-label"><small>Nome</small></label>
                                        <input type="text" class="form-control form-control-sm" value="' . $objPedido->nome . '" readonly>
                                    </div>
                                    <div class="col">
                                        <label class="form-label"><small>Sobrenome</small></label>
                                        <input type="text" class="form-control form-control-sm" value="' . $objPedido->sobrenome . '" readonly>
                                    </div>
                                </div>
                                <div class="row g-3 mb-2">
                                    <div class="col">
                                        <label class="form-label"><small>Data</small></label>
                                        <input type="text" class="form-control form-control-sm" value="' . (new DateTime($objPedido->data))->format('d/m/Y H:i:s') . '"
                                            readonly>
                                    </div>
                                </div>
                                <div class="row g-3 mb-2">
                                    <div class="col">
                                        <label class="form-label"><small>WhatsApp</small></label>
                                        <input type="text" class="form-control form-control-sm" value="' . $objPedido->whatsapp . '"
                                            readonly>
                                    </div>
                                </div>
                                <div class="row g-3 mb-2">
                                    <div class="col d-grid gap-2">
                                        <a href="?id=' . $objPedido->id . '&chamar=" target="_blank" class="btn btn-sm btn-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
                                                <path
                                                    d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z" />
                                            </svg>
                                            Chamar
                                        </a>
                                    </div>
                                </div>
                                <div class="row g-3 mb-2">
                                    <div class="col-md-5">
                                        <label class="form-label"><small>Forma de pagamento</small></label>
                                        <input type="text" class="form-control form-control-sm" value="' . $objPedido->forma_de_pagamento . '" readonly>
                                    </div>
                                    <div class="col-md-7">
                                        <label class="form-label"><small>Entrega</small></label>
                                        <input type="text" class="form-control form-control-sm"
                                            value="' . $objPedido->entrega . '" readonly>
                                    </div>
                                </div>
                                <div class="row g-3 mb-2">
                                    <div class="col">
                                        <label class="form-label"><small>Atendido por</small></label>
                                        <input type="text" class="form-control form-control-sm" value="' . $objAtendente->nome . '" readonly>
                                    </div>
                                </div>
                                <hr>
                                <div class="row g-5">
                                    <div class="col order-md-last">
                                        <h5 class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="text-primary">Resumo do carrinho</span>
                                            <span class="badge bg-primary rounded-pill">' . $quantidadeProdutosPedido . '</span>
                                        </h5>
                                        <ul class="list-group mb-3">
                                            ' . $listaProdutos . '
                                            ' . $listaDesconto . '
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span>Total</span>
                                                <strong>R$' . number_format(($objPedido->valor_total - $objPedido->valor_desconto), 2, ',', '.') . '</strong>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-dark btn-sm"
                                data-bs-dismiss="modal">Fechar</button>
                            <a href="?id=' . $objPedido->id . '&excluir=" role="button" class="btn btn-outline-danger btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                    class="bi bi-trash" viewBox="0 0 16 16">
                                    <path
                                        d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z" />
                                    <path fill-rule="evenodd"
                                        d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z" />
                                </svg>
                                Excluir
                            </a>
                            <a href="?id=' . $objPedido->id . '&finalizar=" type="role" class="btn btn-success btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                    class="bi bi-save" viewBox="0 0 16 16">
                                    <path
                                        d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H9.5a1 1 0 0 0-1 1v7.293l2.646-2.647a.5.5 0 0 1 .708.708l-3.5 3.5a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L7.5 9.293V2a2 2 0 0 1 2-2H14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 0 1H2z" />
                                </svg>
                                Finalizar
                            </a>
                        </div>
                    </div>
                </div>
            </div>';
            echo "<script>var modalAtendido = new bootstrap.Modal(document.getElementById('modalAtendido'))</script>";
            echo "<script>modalAtendido.show()</script>";
        } else if ($_GET['tipo'] == 2 and $objPedido->status == 2) {
            //VALIDA SE É PEDIDO FINALIZADO
            //VALIDA ATENDENTE
            $objAtendente = Usuario::getUsuario($objPedido->atendente);
            if (!$objAtendente instanceof Usuario) {
                header("Location: ?status=erro");
                echo '<script>location.href="?status=erro"</script>';
                exit;
            }
            $urlWhatsapp = 'https://wa.me/' . $objPedido->whatsapp . '/?text=' . urlencode('Oi ' . $objPedido->nome . ', tudo bem?
estou passando aqui apenas para agradecer você por confiar no nosso trabalho e na nossa empresa. Saiba que daqui para frente meu compromisso é sempre te servir melhor, tenha um ótimo dia!');
            echo '<div class="modal fade" id="modalFinalizado" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
                aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="staticBackdropLabel">
                                Pedido #' . $objPedido->id . '
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="container-fluid">
                                <div class="row g-3 mb-2">
                                    <div class="col">
                                        <label class="form-label"><small>Nome</small></label>
                                        <input type="text" class="form-control form-control-sm" value="' . $objPedido->nome . '" readonly>
                                    </div>
                                    <div class="col">
                                        <label class="form-label"><small>Sobrenome</small></label>
                                        <input type="text" class="form-control form-control-sm" value="' . $objPedido->sobrenome . '" readonly>
                                    </div>
                                </div>
                                <div class="row g-3 mb-2">
                                    <div class="col">
                                        <label class="form-label"><small>Data</small></label>
                                        <input type="text" class="form-control form-control-sm" value="' . (new DateTime($objPedido->data))->format('d/m/Y H:i:s') . '"
                                            readonly>
                                    </div>
                                </div>
                                <div class="row g-3 mb-2">
                                    <div class="col">
                                        <label class="form-label"><small>WhatsApp</small></label>
                                        <input type="text" class="form-control form-control-sm" value="' . $objPedido->whatsapp . '"
                                            readonly>
                                    </div>
                                </div>
                                <div class="row g-3 mb-2">
                                    <div class="col d-grid gap-2">
                                        <a href="' . $urlWhatsapp . '&chamar=" target="_blank" class="btn btn-sm btn-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
                                                <path
                                                    d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z" />
                                            </svg>
                                            Agradecer Compra
                                        </a>
                                    </div>
                                </div>
                                <div class="row g-3 mb-2">
                                    <div class="col-md-5">
                                        <label class="form-label"><small>Forma de pagamento</small></label>
                                        <input type="text" class="form-control form-control-sm" value="' . $objPedido->forma_de_pagamento . '" readonly>
                                    </div>
                                    <div class="col-md-7">
                                        <label class="form-label"><small>Entrega</small></label>
                                        <input type="text" class="form-control form-control-sm"
                                            value="' . $objPedido->entrega . '" readonly>
                                    </div>
                                </div>
                                <div class="row g-3 mb-2">
                                    <div class="col">
                                        <label class="form-label"><small>Atendido por</small></label>
                                        <input type="text" class="form-control form-control-sm" value="' . $objAtendente->nome . '" readonly>
                                    </div>
                                </div>
                                <hr>
                                <div class="row g-5">
                                    <div class="col order-md-last">
                                        <h5 class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="text-primary">Resumo do carrinho</span>
                                            <span class="badge bg-primary rounded-pill">' . $quantidadeProdutosPedido . '</span>
                                        </h5>
                                        <ul class="list-group mb-3">
                                            ' . $listaProdutos . '
                                            ' . $listaDesconto . '
                                            <li class="list-group-item d-flex justify-content-between">
                                                <span>Total</span>
                                                <strong>R$' . number_format(($objPedido->valor_total - $objPedido->valor_desconto), 2, ',', '.') . '</strong>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="?id=' . $objPedido->id . '&excluir=" role="button" class="btn btn-outline-danger btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                    class="bi bi-trash" viewBox="0 0 16 16">
                                    <path
                                        d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z" />
                                    <path fill-rule="evenodd"
                                        d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z" />
                                </svg>
                                Excluir
                            </a>
                            <button type="button" class="btn btn-outline-dark btn-sm" data-bs-dismiss="modal">Fechar</button>
                        </div>
                    </div>
                </div>
            </div>';
            echo "<script>var modalFinalizado = new bootstrap.Modal(document.getElementById('modalFinalizado'))</script>";
            echo "<script>modalFinalizado.show()</script>";
        } else {
            header("Location: ?status=erro");
            echo '<script>location.href="?status=erro"</script>';
            exit;
        }
    }

    //VALIDA O GET DE CHAMAR NO WHATSAPP
    if (isset($_GET['id'], $_GET['chamar']) and is_numeric($_GET['id'])) {
        //VALIDA SE O PEDIDO EXISTE
        $objPedido = Pedido::getPedido($_GET['id']);
        if (!$objPedido instanceof Pedido) {
            header("Location: ?status=erro");
            echo '<script>location.href="?status=erro"</script>';
            exit;
        }

        //VALIDA ATENDENTE
        $objAtendente = Usuario::getUsuario(Login::getUsuarioLogado()['id']);
        if (!$objAtendente instanceof Usuario) {
            header("Location: ?status=erro");
            echo '<script>location.href="?status=erro"</script>';
            exit;
        }

        $listaProdutos = '';
        $quantidadeProdutosPedido = 0;
        foreach (json_decode((string) $objPedido->produtos) as $produto) {
            $quantidadeProdutosPedido += $produto->quantidade;
            $listaProdutos .= $produto->quantidade . 'x ' . $produto->nome . ' (R$' . number_format(($produto->preco_total), 2, ',', '.') . ')
';
        }

        $urlMensagem = 'https://wa.me/' . $objPedido->whatsapp . '/?text=' . urlencode('Olá, eu me chamo ' . $objAtendente->nome . ', sou um dos representantes da loja Fonte das Joias e irei dar continuidade ao seu atendimento.

*--------- PEDIDO #' . $objPedido->id . ' ---------*

```' . $listaProdutos . '```
*Valor total:* ' . ($objPedido->valor_desconto > 0 ? '~R$' . number_format(($objPedido->valor_total), 2, ',', '.') . '~ ' : '') . 'R$' . number_format(($objPedido->valor_total - $objPedido->valor_desconto), 2, ',', '.') . ' (' . ($quantidadeProdutosPedido > 1 ? $quantidadeProdutosPedido . ' produtos' : $quantidadeProdutosPedido . ' produto') . ')

*--------- INFORMAÇÕES ---------*

*Nome:* ' . $objPedido->nome . ' ' . $objPedido->sobrenome . '
*Forma de pagamento:* ' . $objPedido->forma_de_pagamento . '
*Entrega:* ' . $objPedido->entrega . '


Está correto?');

        //ALTERA O STATUS DO PEDIDO PARA ATENDIDO E SETA O ATENDENTE
        $objPedido->status = 1;
        $objPedido->atendente = $objAtendente->id;
        $objPedido->atualizar();

        //AVISO TELEGRAM
        Alert::sendMessage('<b>PEDIDO ATENDIDO #' . $objPedido->id . '</b> por ' . $objAtendente->nome . ' (<a href="http://' . $_SERVER["HTTP_HOST"] . '/fdj/pedidos.php?id=' . $objPedido->id . '&tipo=' . $objPedido->status . '">ver pedido</a>)', 'html');

        //REDIRECIONA PARA O WHATSAPP NUMA NOVA ABA
        // echo '<script>window.open("' . $urlMensagem . '", "_blank");</script>';

        //REDIRECIONA PARA O WHATSAPP
        header("Location: " . $urlMensagem);
        echo '<script>location.href="' . $urlMensagem . '"</script>';

        //SUCESSO
        header("Location: ?status=chamado");
        echo '<script>location.href="?status=chamado"</script>';
        exit;
    }

    //VALIDA SE O GET FOI PARA EXCLUIR
    if (isset($_GET['id'], $_GET['excluir']) and is_numeric($_GET['id'])) {
        //VALIDA SE O PEDIDO EXISTE
        $objPedido = Pedido::getPedido($_GET['id']);
        if (!$objPedido instanceof Pedido) {
            header("Location: ?status=erro");
            echo '<script>location.href="?status=erro"</script>';
            exit;
        }

        //VALIDA SE É PARA RETORNAR PRODUTOS AO ESTOQUE OU NÃO
        if ($_GET['excluir'] == "retornar") {
            //RETORNAR PRODUTO AO ESTOQUE
            foreach (json_decode((string) $objPedido->produtos) as $produto) {
                $objProduto = Produto::getProduto($produto->id);
                //VALIDA SE O PRODUTO EXISTE
                if ($objProduto instanceof Produto) {
                    $objProduto->estoque += $produto->quantidade;
                    //AVISO TELEGRAM
                    Alert::sendMessage('<b>ESTOQUE ATUALIZADO POR EXCLUSÃO DE PEDIDO #' . $objProduto->id . ':</b> ' . $objProduto->estoque . 'x ' . $objProduto->nome . ' (<a href="http://' . $_SERVER["HTTP_HOST"] . '/fdj/editarProduto.php?id=' . $objPedido->id . '">editar produto</a>)', 'html');
                    if ($objProduto->estoque > 0) {
                        $objProduto->disponivel = 1;
                    }
                    $objProduto->atualizar();
                }
            }

            //LISTA DE PRODUTOS PARA O TELEGRAM
            $listaProdutos = '';
            $quantidadeProdutosPedido = 0;
            foreach (json_decode((string) $objPedido->produtos) as $produto) {
                $quantidadeProdutosPedido += $produto->quantidade;
                $listaProdutos .= $produto->quantidade . 'x ' . $produto->nome . ' (R$' . number_format(($produto->preco_total), 2, ',', '.') . ')
';
            }

            //AVISO TELEGRAM
            Alert::sendMessage('<b>--------- PEDIDO EXCLUÍDO #' . $objPedido->id . ' ---------</b>

<pre>' . $listaProdutos . '</pre>
<b>Valor total:</b> ' . ($objPedido->valor_desconto > 0 ? '<del>R$' . number_format(($objPedido->valor_total), 2, ',', '.') . '</del> ' : '') . 'R$' . number_format(($objPedido->valor_total - $objPedido->valor_desconto), 2, ',', '.') . ' (' . ($quantidadeProdutosPedido > 1 ? $quantidadeProdutosPedido . ' produtos' : $quantidadeProdutosPedido . ' produto') . ')
<b>Desconto:</b> R$' . number_format(($objPedido->valor_desconto), 2, ',', '.') . '

<b>--------- INFORMAÇÕES ---------</b>

<b>Nome:</b> ' . $objPedido->nome . ' ' . $objPedido->sobrenome . '
<b>WhatsApp:</b> ' . $objPedido->whatsapp . '
<b>Data:</b> ' . (new DateTime($objPedido->data))->format('d/m/Y H:i:s') . '
<b>Forma de pagamento:</b> ' . $objPedido->forma_de_pagamento . '
<b>Entrega:</b> ' . $objPedido->entrega . '', 'html');

            //EXCLUIR PEDIDO
            $objPedido->excluir();

            //SUCESSO
            header("Location: ?status=sucesso");
            echo '<script>location.href="?status=sucesso"</script>';
            exit;
        } else if ($_GET['excluir'] == "nao_retornar") {
            //AVISO TELEGRAM
            Alert::sendMessage('<b>--------- PEDIDO EXCLUÍDO #' . $objPedido->id . ' ---------</b>

<pre>' . $listaProdutos . '</pre>
<b>Valor total:</b> ' . ($objPedido->valor_desconto > 0 ? '<del>R$' . number_format(($objPedido->valor_total), 2, ',', '.') . '</del> ' : '') . 'R$' . number_format(($objPedido->valor_total - $objPedido->valor_desconto), 2, ',', '.') . ' (' . ($quantidadeProdutosPedido > 1 ? $quantidadeProdutosPedido . ' produtos' : $quantidadeProdutosPedido . ' produto') . ')
<b>Desconto:</b> R$' . number_format(($objPedido->valor_desconto), 2, ',', '.') . '

<b>--------- INFORMAÇÕES ---------</b>

<b>Nome:</b> ' . $objPedido->nome . ' ' . $objPedido->sobrenome . '
<b>WhatsApp:</b> ' . $objPedido->whatsapp . '
<b>Data:</b> ' . (new DateTime($objPedido->data))->format('d/m/Y H:i:s') . '
<b>Forma de pagamento:</b> ' . $objPedido->forma_de_pagamento . '
<b>Entrega:</b> ' . $objPedido->entrega . '', 'html');

            //EXCLUIR PEDIDO
            $objPedido->excluir();
            //SUCESSO
            header("Location: ?status=sucesso");
            echo '<script>location.href="?status=sucesso"</script>';
            exit;
        } else {
            //PERGUNTA SE QUER RETORNAR OU NÃO
            echo '<div class="modal modal-alert fade" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
                aria-labelledby="staticBackdropLabel" aria-hidden="true" role="dialog" id="modalPerguntaExcluir">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content rounded-4 shadow">
                        <div class="modal-body p-4 text-center">
                            <h5 class="mb-0">Excluir?</h5>
                            <p class="mb-0">Escolha se gostaria de colocar os produtos de volta ao estoque.</p>
                        </div>
                        <div class="modal-footer flex-nowrap p-0">
                            <a href="?id=' . $objPedido->id . '&excluir=retornar" role="button" class="btn btn-lg btn-link fs-6 text-decoration-none col-4 m-0 rounded-0 border-right"><strong>Sim, retornar produtos para o estoque</strong></a>
                            <a href="?id=' . $objPedido->id . '&excluir=nao_retornar" role="button" class="btn btn-lg btn-link fs-6 text-decoration-none col-4 m-0 rounded-0 border-right">Não, excluir tudo</a>
                            <a href="?id=' . $objPedido->id . '&tipo=' . $objPedido->status . '" role="button" class="btn btn-lg btn-link fs-6 text-decoration-none col-4 m-0 rounded-0">Cancelar</a>
                        </div>
                    </div>
                </div>
            </div>';


            echo "<script>var modalPerguntaExcluir = new bootstrap.Modal(document.getElementById('modalPerguntaExcluir'))</script>";
            echo "<script>modalPerguntaExcluir.show()</script>";
        }
    }

    //VALIDA SE O GET FOI PARA FINALIZAR
    if (isset($_GET['id'], $_GET['finalizar']) and is_numeric($_GET['id'])) {
        //VALIDA SE O PEDIDO EXISTE
        $objPedido = Pedido::getPedido($_GET['id']);
        if (!$objPedido instanceof Pedido) {
            header("Location: ?status=erro");
            echo '<script>location.href="?status=erro"</script>';
            exit;
        }

        //VALIDA ATENDENTE
        $objAtendente = Usuario::getUsuario(Login::getUsuarioLogado()['id']);
        if (!$objAtendente instanceof Usuario) {
            header("Location: ?status=erro");
            echo '<script>location.href="?status=erro"</script>';
            exit;
        }

        //FINALIZA PEDIDO
        $objPedido->status = 2;
        $objPedido->atendente = $objAtendente->id;
        $objPedido->atualizar();

        //AVISO TELEGRAM
        Alert::sendMessage('<b>PEDIDO FINALIZADO #' . $objPedido->id . '</b> por ' . $objAtendente->nome . ' (<a href="http://' . $_SERVER["HTTP_HOST"] . '/fdj/pedidos.php?id=' . $objPedido->id . '&tipo=' . $objPedido->status . '">ver pedido</a>)', 'html');

        //SUCESSO
        header("Location: ?status=sucesso");
        echo '<script>location.href="?status=sucesso"</script>';
    }

    //STATUS
    if (strlen($status)) {

        $tituloNotificacao = '';
        $descricaoNotificacao = '';

        switch ($status) {
            case 'sucesso':
                $tituloNotificacao = 'Sucesso!';
                $descricaoNotificacao = 'A operação foi realizada com êxito.';
                break;
            case 'chamado':
                $tituloNotificacao = 'Sucesso!';
                $descricaoNotificacao = 'Você já foi direcionado para o WhatsApp do cliente e o pedido já foi marcado como atendido.';
                break;
            case 'erro':
                $tituloNotificacao = 'Erro!';
                $descricaoNotificacao = 'Ocorreu algum erro ao realizar a operação.';
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