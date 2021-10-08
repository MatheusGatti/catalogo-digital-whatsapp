<?php

require 'vendor/autoload.php';

use App\Entity\Produto;
use App\Entity\Categoria;
use App\Db\Pagination;
use App\Session\Carrinho;

$quantidadeCarrinho = Carrinho::getQuantidadeProdutos();

//LISTAGEM DAS CATEGORIAS EM ORDEM CRESCENTE PELO ID
$categorias = Categoria::getCategorias(null, 'id ASC');
$resultadoCategorias = '';
$categoriasId = [];
$categoriasIndisponiveisId = [];
foreach ($categorias as $categoria) {
    //VALIDA SE A CATEGORIA ESTÁ DISPONÍVEL OU NÃO
    if ($categoria->disponivel == 1) {
        //CASO DISPONÍVEL MOSTRAR AO USUÁRIO
        $resultadoCategorias .= '<li><a class="dropdown-item" href="categoria.php?id=' . $categoria->id . '">' . $categoria->nome . '</a></li>';
    } else {
        //CASO NÃO DISPONÍVEL ATRIBUIR A UMA ARRAY DE CATEGORIAS INDISPONÍVEIS
        $categoriasIndisponiveisId[] = $categoria->id;
    }
}
$categoriasIndisponiveisId = array_filter($categoriasIndisponiveisId);

//FILTRO DE PESQUISA
$filtroNome = filter_input(INPUT_GET, 'filtroNome', FILTER_SANITIZE_STRING);

//CONDIÇÕES SQL PARA PESQUISA
$condicoes = [
    strlen($filtroNome) ? 'nome LIKE "%' . str_replace(' ', '%', $filtroNome) . '%"' : null,
    'disponivel = 1',
    count($categoriasIndisponiveisId) ? 'categoria NOT IN (' . implode(', ', $categoriasIndisponiveisId) . ')' : null
];
$condicoes = array_filter($condicoes);

//CLÁUSULA WHERE PARA PESQUISA
$where = implode(' AND ', $condicoes);

//QUANTIDADE TOTAL DE PRODUTOS
$quantidadeProdutos = Produto::getQuantidadeProdutos($where);

//VERIFICA SE RECEBEU STATUS E ATRIBUI A UMA VARIÁVEL
$status = $_GET['status'];

//PAGINAÇÃO DOS PRODUTOS
$currentPage = $_GET['pagina'] ?? 1;
$limit = 12;
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
unset($_GET['pagina']);
unset($_GET['status']);
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

//LISTAGEM DOS PRODUTOS EM ORDEM DECRESCENTE PELO ID
$produtos = Produto::getProdutos($where, 'id DESC', $objPaginacao->getLimit());
$resultadoProdutos;
foreach ($produtos as $produto) {
    $fotos = json_decode($produto->fotos);
    $resultadoProdutos .= '<div class="col-6 col-md-3 py-2"><div class="card h-100"><img src="/images/produtos/' . $fotos[0] . '" class="card-img-top"><div class="card-body"><h6 class="card-title text-center">' . $produto->nome . '</h6><h4 class="text-center"><span class="badge rounded-pill bg-light text-dark">R$' . number_format($produto->preco_de_venda, 2, ',', '.') . '</span></h4><div class="d-grid gap-2"><a href="produto.php?id=' . $produto->id . '" class="btn btn-success btn-sm">COMPRAR</a></div><p class="card-text text-center py-1"><small><span class="badge bg-warning text-dark">ou 3x de R$' . number_format(($produto->preco_de_venda / 3), 2, ',', '.') . '</span></small></p></div></div></div>';
}

?>
<!doctype html>
<html lang="pt-Br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Matheus Gatti">
    <title>Fonte das Joias | Catálogo</title>

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

        <section class="py-5 text-center justify-content-center container">
            <div class="row py-lg-5">
                <div class="col-lg-6 col-md-8 mx-auto">
                    <img src="/images/LOGO TRANSPARENTE PEQUENA.png" class="rounded">
                    <h1 class="fw-light">Fonte das Joias</h1>
                    <p class="lead text-muted">Catálogo digital exclusivo da loja Fonte das Joias!</p>
                    <p class="lead text-muted">Escolha seus produtos, vá para o carrinho e finalize o pedido, em seguida
                        iremos entrar em contato com você atráves do WhatsApp.</p>
                    <div class="btn-group" role="group">
                        <button id="btnGroupDrop1" type="button" class="btn btn-warning dropdown-toggle"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            Todos Produtos
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <?= $resultadoCategorias ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="row py-5">
                <div class="col text-center">
                    <h1>Produtos em Destaque</h1>
                    <div class="col-10 col-md-4 mx-auto">
                        <form method="get" autocomplete="off">
                            <div class="input-group input-group-sm mt-3">
                                <input name="filtroNome" type="text" class="form-control"
                                    placeholder="Pesquisar produto" value="<?= $filtroNome ?>">
                                <?= !strlen($filtroNome) ? '<button class="btn btn-secondary" type="submit"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z" /></svg></button>' : '<a class="btn btn-secondary" role="button" href="index.php">Voltar</a>'; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
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
        </section>

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

    <?php

    if (strlen($status)) {

        $tituloNotificacao = '';
        $descricaoNotificacao = '';

        if ($status == "obrigado") {
            echo "<script>Swal.fire({
                title: 'Obrigado!',
                html: 'O seu pedido foi feito com sucesso!<br><br>Em breve um atendente da equipe Fonte das Joias entrará em contato com você pelo WhatsApp.',
                icon: 'success',
                confirmButtonText: 'Obaa :)',
                confirmButtonColor: '#198754',
            });</script>";
            return;
        }

        switch ($status) {
            case 'erro':
                $tituloNotificacao = 'Erro!';
                $descricaoNotificacao = 'Ocorreu algum erro inesperado no site.';
            case 'erro_categoria':
                $tituloNotificacao = 'Erro!';
                $descricaoNotificacao = 'A categoria selecionada não foi encontrada.';
                break;
            case 'erro_produtos':
                $tituloNotificacao = 'Erro!';
                $descricaoNotificacao = 'Nenhum produto foi cadastrado na categoria selecionada.';
                break;
            case 'erro_produto':
                $tituloNotificacao = 'Erro!';
                $descricaoNotificacao = 'O produto selecionado não foi encontrado.';
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