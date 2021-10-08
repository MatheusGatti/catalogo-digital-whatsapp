<?php

require '../vendor/autoload.php';

use App\Entity\Produto;
use App\Entity\Categoria;
use App\Db\Pagination;
use App\Session\Login;

//OBRIGA O USUÁRIO A ESTAR LOGADO
Login::requireLogin();

//LISTA CATEGORIAS
$categorias = Categoria::getCategorias();
$resultadoCategorias = '';
$categoriasId = [];
foreach ($categorias as $categoria) {
    $categoriasId[] = $categoria->id;
    $resultadoCategorias .= '<option value="' . $categoria->id . '">' . $categoria->nome . '</option>';
}

//FILTRO DE PESQUISA
$filtroNome = filter_input(INPUT_GET, 'filtroNome', FILTER_SANITIZE_STRING);
$filtroEstoque = filter_input(INPUT_GET, 'filtroEstoque', FILTER_SANITIZE_NUMBER_INT);
$filtroEstoque = $filtroEstoque < 0 ? '' : $filtroEstoque;
$filtroStatus = filter_input(INPUT_GET, 'filtroStatus', FILTER_SANITIZE_NUMBER_INT);
$filtroStatus = in_array($filtroStatus, [1, 0]) ? $filtroStatus : '';
$filtroCategoria = filter_input(INPUT_GET, 'filtroCategoria', FILTER_SANITIZE_NUMBER_INT);
$filtroCategoria = in_array($filtroCategoria, $categoriasId) ? $filtroCategoria : '';

//CONDIÇÕES SQL PARA O FILTRO DE PESQUISA
$condicoes = [
    strlen($filtroNome) ? 'nome LIKE "%' . str_replace(' ', '%', $filtroNome) . '%"' : null,
    strlen($filtroEstoque) ? 'estoque = ' . $filtroEstoque . '' : null,
    strlen($filtroStatus) ? 'disponivel = ' . $filtroStatus . '' : null,
    strlen($filtroCategoria) ? 'categoria = ' . $filtroCategoria . '' : null,
];
$condicoes = array_filter($condicoes);

//CLÁUSULA WHERE PARA PESQUISA
$where = implode(' AND ', $condicoes);

//QUANTIDADE TOTAL DE PRODUTOS
$quantidadeProdutos = Produto::getQuantidadeProdutos($where);

//VERIFICA SE RECEBEU STATUS E ATRIBUI A UMA VARIÁVEL
$status = $_GET['status'];

//PAGINAÇÃO
$currentPage = $_GET['pagina'] ?? 1;
$limit = 8;
$objPaginacao = new Pagination($quantidadeProdutos, $currentPage, $limit);
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

//LISTAGEM DOS PRODUTOS
$produtos = Produto::getProdutos($where, 'nome ASC', $objPaginacao->getLimit());
$resultadoProdutos = '';
$quantidadeProdutosMostrando = count($produtos);
$contadorProdutos = 1;
foreach ($produtos as $produto) {
    $fotos = json_decode($produto->fotos);
    $resultadoProdutos .= '<div class="col-6 col-md-3 py-2"><div class="card ' . ($produto->disponivel == 1 ? 'border-success' : 'border-danger') . ' h-100"><img src="/images/produtos/' . $fotos[0] . '" class="card-img-top"><div class="card-body"><h6 class="card-title text-center">' . $produto->nome . '</h6><h4 class="text-center"><span class="badge rounded-pill bg-light text-dark">R$' . number_format($produto->preco_de_venda, 2, ',', '.') . '</span></h4><div class="d-grid gap-2"><a href="editarProduto.php?id=' . $produto->id . '" class="btn btn-warning btn-sm"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16"><path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z" /><path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z" /></svg>&nbsp;Editar</a></div><p class="card-text text-center py-1"><small><span class="badge ' . ($produto->estoque == 0 ? 'bg-danger' : 'bg-dark text-light') . '">' . $produto->estoque . ' unidade(s)</span></small></p></div></div></div>';
    if ($contadorProdutos % 4 == 0) {
        $resultadoProdutos .= '</div><div class="row">';
    }
    $contadorProdutos++;
}

?>
<!doctype html>
<html lang="pt-Br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Matheus Gatti">
    <title>Fonte das Joias | Produtos</title>

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
                    <h3 class="display-6">PRODUTOS</h3>
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
                    <h6 class="text-center"><?= $quantidadeProdutosMostrando ?> DE
                        <?= $quantidadeProdutos ?>
                        PRODUTO(S)
                        CADASTRADO(S)</h6>
                    <div class="text-center">
                        <a href="cadastrarProduto.php" role="button" class="btn btn-sm my-2 btn-success">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                class="bi bi-folder-plus" viewBox="0 0 16 16">
                                <path
                                    d="m.5 3 .04.87a1.99 1.99 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14H9v-1H2.826a1 1 0 0 1-.995-.91l-.637-7A1 1 0 0 1 2.19 4h11.62a1 1 0 0 1 .996 1.09L14.54 8h1.005l.256-2.819A2 2 0 0 0 13.81 3H9.828a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 6.172 1H2.5a2 2 0 0 0-2 2zm5.672-1a1 1 0 0 1 .707.293L7.586 3H2.19c-.24 0-.47.042-.683.12L1.5 2.98a1 1 0 0 1 1-.98h3.672z" />
                                <path
                                    d="M13.5 10a.5.5 0 0 1 .5.5V12h1.5a.5.5 0 1 1 0 1H14v1.5a.5.5 0 1 1-1 0V13h-1.5a.5.5 0 0 1 0-1H13v-1.5a.5.5 0 0 1 .5-.5z" />
                            </svg>
                            Cadastrar produto
                        </a>
                        <br>
                        <small>FILTROS</small>
                        <br>
                        <form method="get" autocomplete="off" id="formFiltro">
                            <input name="filtroNome" class="form-control form-control-sm my-2" type="text"
                                placeholder="Nome" value="<?= $filtroNome ?>">
                            <input name="filtroEstoque" class="form-control form-control-sm my-2" type="number"
                                placeholder="Estoque" value="<?= $filtroEstoque ?>">
                            <select name="filtroStatus" id="status" class="form-select form-select-sm my-2">
                                <option value="" selected>Status</option>
                                <option value="1">Disponível</option>
                                <option value="0">Indisponível</option>
                            </select>
                            <?php echo ($filtroStatus == '' ? '' : '<script>document.getElementById("status").value = ' . $filtroStatus . '</script>'); ?>
                            <select name="filtroCategoria" id="categoria" class="form-select form-select-sm my-2">
                                <option value="" selected>Categoria</option>
                                <?= $resultadoCategorias ?>
                            </select>
                            <?php echo ($filtroCategoria == '' ? '' : '<script>document.getElementById("categoria").value = ' . $filtroCategoria . '</script>'); ?>
                            <div class="btn-group btn-group-sm my-2" role="group"
                                aria-label="Basic mixed styles example">
                                <button type="submit" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                        class="bi bi-search" viewBox="0 0 16 16">
                                        <path
                                            d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z" />
                                    </svg>
                                    Pesquisar</button>
                                </a>
                                <a role="button" href="produtos.php" class="btn btn-dark">Limpar</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <div class="container my-3">
            <div class="row">
                <?= $resultadoProdutos ?>
            </div>
            <div class="row my-3">
                <nav>
                    <ul class="pagination pagination-sm justify-content-center">
                        <?= $paginacao ?>
                    </ul>
                </nav>
            </div>
        </div>

    </main>


    <script src="/js/bootstrap.bundle.min.js"></script>

    <?php

    if (strlen($status)) {

        $tituloNotificacao = '';
        $descricaoNotificacao = '';

        switch ($status) {
            case 'sucesso':
                $tituloNotificacao = 'Sucesso!';
                $descricaoNotificacao = 'A operação foi realizada com êxito.';
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