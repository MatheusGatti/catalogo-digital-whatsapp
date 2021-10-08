<?php

require '../vendor/autoload.php';

use App\Entity\Produto;
use App\Entity\Categoria;
use App\File\Upload;
use App\Session\Login;

//OBRIGA O USUÁRIO A ESTAR LOGADO
Login::requireLogin();

if (!isset($_GET['id']) or !is_numeric($_GET['id'])) {
    header("Location: produtos.php?status=erro");
    echo '<script>location.href="produtos.php?status=erro"</script>';
    exit;
}

$produto = Produto::getProduto($_GET['id']);

if (!$produto instanceof Produto) {
    header("Location: produtos.php?status=erro");
    echo '<script>location.href="produtos.php?status=erro"</script>';
    exit;
}

$categorias = Categoria::getCategorias();
$resultadoCategorias = '';
foreach ($categorias as $categoria) {
    $resultadoCategorias .= '<option value="' . $categoria->id . '">' . $categoria->nome . '</option>';
}

//Validação do get de exclusão do produto
if (isset($_GET['excluir'])) {
    $produto->excluir();
    header("Location: produtos.php?status=sucesso");
}

//Validação do post de edição do produto
if (isset($_FILES['fotos'], $_POST['nome'], $_POST['descricao'], $_POST['status'], $_POST['categoria'], $_POST['estoque'], $_POST['preco_de_venda'])) {
    //Valida status
    if (!is_numeric($_POST['status']) or ($_POST['status'] != 1 and $_POST['status'] != 0)) {
        header("Location: ?id=" . $_GET['id'] . "&status=erro_status");
        echo '<script>location.href="?id=' . $_GET['id'] . '&status=erro_status"</script>';
        exit;
    }
    //Valida categoria
    if (!is_numeric($_POST['categoria']) or (!Categoria::getCategoria($_POST['categoria']) instanceof Categoria)) {
        header("Location: ?id=" . $_GET['id'] . "&status=erro_categoria");
        echo '<script>location.href="?id=' . $_GET['id'] . '&status=erro_categoria"</script>';
        exit;
    }

    //Valida estoque
    if (!is_numeric($_POST['estoque']) or $_POST['estoque'] < 0 or ($_POST['status'] == 1 and $_POST['estoque'] <= 0)) {
        header("Location: ?id=" . $_GET['id'] . "&status=erro_estoque");
        echo '<script>location.href="?id=' . $_GET['id'] . '&status=erro_estoque"</script>';
        exit;
    }

    //Substitui a vírgula por ponto
    $preco_de_venda = str_replace('.', '', $_POST['preco_de_venda']);
    $preco_de_venda = str_replace(',', '.', $preco_de_venda);
    // $preco_de_venda = str_replace('.', ',', $_POST['preco_de_venda']);

    //Valida preço de venda
    if (!is_float((float) $preco_de_venda) or (float) $preco_de_venda < 0) {
        header("Location: ?id=" . $_GET['id'] . "&status=erro_preco_de_venda");
        echo '<script>location.href="?id=' . $_GET['id'] . '&status=erro_preco_de_venda"</script>';
        exit;
    }

    //Faz o upload das fotos
    $uploads = Upload::createMultipleUpload($_FILES['fotos']);
    $nomeFotos = [];
    foreach ($uploads as $objUpload) {
        $objUpload->generateNewName();
        $sucesso = $objUpload->upload(dirname(__DIR__, 1) . '/images/produtos', false);
        if ($sucesso) {
            $nomeFotos[] = $objUpload->getBasename();
            continue;
        }
    }
    $objProduto = Produto::getProduto($_GET['id']);
    $objProduto->nome = $_POST['nome'];
    $objProduto->descricao = $_POST['descricao'];
    $objProduto->disponivel = $_POST['status'];
    $objProduto->categoria = $_POST['categoria'];
    $objProduto->estoque = $_POST['estoque'];
    $objProduto->preco_de_venda = (float) $preco_de_venda;
    if (!empty($nomeFotos)) {
        $nomeFotos = json_encode($nomeFotos);
        $objProduto->fotos = $nomeFotos;
    }
    $objProduto->atualizar();
    header("Location: produtos.php?status=sucesso");
}

?>
<!doctype html>
<html lang="pt-Br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Matheus Gatti">
    <title>Fonte das Joias | Editar Produto</title>

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
                    <h3 class="display-6">EDITAR PRODUTO</h3>
                    <a href="produtos.php" role="button" class="btn btn-warning btn-sm">
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
                        <form method="post" autocomplete="off" enctype="multipart/form-data">
                            <div class="input-group input-group-sm my-2">
                                <label class="input-group-text" for="inputGroupFile01">Fotos do produto</label>
                                <input name="fotos[]" class="form-control form-control-sm" type="file"
                                    id="formFileMultiple" multiple accept="image/png, image/jpeg">
                            </div>
                            <input name="nome" required class="form-control form-control-sm my-2" type="text"
                                placeholder="Nome do produto" value="<?= $produto->nome ?>">
                            <textarea style="resize:none;" rows="5" name="descricao"
                                class="form-control form-control-sm my-2" type="text"
                                placeholder="Descrição do produto (não é obrigatório)"><?= $produto->descricao ?></textarea>
                            <select name="status" required class="form-select form-select-sm my-2">
                                <option>Status</option>
                                <option value="1" <?= $produto->disponivel == 1 ? 'selected' : '' ?>>Disponível</option>
                                <option value="0" <?= $produto->disponivel == 1 ? '' : 'selected' ?>>Indisponível
                                </option>
                            </select>
                            <select name="categoria" id="categoria" required class="form-select form-select-sm my-2">
                                <option selected>Categoria</option>
                                <?= $resultadoCategorias ?>
                                <script>
                                document.getElementById("categoria").value = <?= $produto->categoria ?>
                                </script>
                            </select>
                            <input name="estoque" required class="form-control form-control-sm my-2" type="number"
                                placeholder="Unidades do produto em estoque" value="<?= $produto->estoque ?>">
                            <div class="input-group input-group-sm my-2">
                                <span class="input-group-text" id="basic-addon1">R$</span>
                                <input name="preco_de_venda" required type="text" class="form-control"
                                    placeholder="Preço de venda do produto" aria-label="Preço de venda do produto"
                                    aria-describedby="basic-addon1"
                                    value="<?= str_replace('.', ',', $produto->preco_de_venda); ?>">
                            </div>
                            <div class="btn-group btn-group-sm my-2" role="group"
                                aria-label="Basic mixed styles example">
                                <button type="submit" class="btn btn-success">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                        class="bi bi-folder-plus" viewBox="0 0 16 16">
                                        <path
                                            d="m.5 3 .04.87a1.99 1.99 0 0 0-.342 1.311l.637 7A2 2 0 0 0 2.826 14H9v-1H2.826a1 1 0 0 1-.995-.91l-.637-7A1 1 0 0 1 2.19 4h11.62a1 1 0 0 1 .996 1.09L14.54 8h1.005l.256-2.819A2 2 0 0 0 13.81 3H9.828a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 6.172 1H2.5a2 2 0 0 0-2 2zm5.672-1a1 1 0 0 1 .707.293L7.586 3H2.19c-.24 0-.47.042-.683.12L1.5 2.98a1 1 0 0 1 1-.98h3.672z" />
                                        <path
                                            d="M13.5 10a.5.5 0 0 1 .5.5V12h1.5a.5.5 0 1 1 0 1H14v1.5a.5.5 0 1 1-1 0V13h-1.5a.5.5 0 0 1 0-1H13v-1.5a.5.5 0 0 1 .5-.5z" />
                                    </svg>
                                    Salvar</button>
                                <button data-bs-toggle="modal" data-bs-target="#modalExcluir" type="button"
                                    class="btn btn-danger">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                        class="bi bi-trash" viewBox="0 0 16 16">
                                        <path
                                            d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z" />
                                        <path fill-rule="evenodd"
                                            d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z" />
                                    </svg>
                                    Excluir</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal modal-alert fade" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="staticBackdropLabel" aria-hidden="true" role="dialog" id="modalExcluir">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content rounded-4 shadow">
                    <div class="modal-body p-4 text-center">
                        <input type="hidden" name="tipoFormulario" value="excluirCategoria">
                        <h5 class="mb-0">Excluir produto?</h5>
                        <p class="mb-0">Essa ação não poderá ser desfeita.</p>
                    </div>
                    <div class="modal-footer flex-nowrap p-0">
                        <a href="?id=<?= $produto->id ?>&excluir" role="button"
                            class="btn btn-lg btn-link fs-6 text-decoration-none col-6 m-0 rounded-0 border-right"><strong>Sim,
                                excluir</strong></a>
                        <button type="button" class="btn btn-lg btn-link fs-6 text-decoration-none col-6 m-0 rounded-0"
                            data-bs-dismiss="modal">Não, voltar</button>
                    </div>
                </div>
            </div>
        </div>

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
            case 'erro_status':
                $tituloNotificacao = 'Erro!';
                $descricaoNotificacao = 'O produto precisa ser selecionado como disponível ou indisponível.';
                break;
            case 'erro_categoria':
                $tituloNotificacao = 'Erro!';
                $descricaoNotificacao = 'A categoria selecionada não existe.';
                break;
            case 'erro_estoque':
                $tituloNotificacao = 'Erro!';
                $descricaoNotificacao = 'O estoque não pode ser menor que zero ou o produto não pode ficar disponível com estoque zerado.';
                break;
            case 'erro_preco_de_venda':
                $tituloNotificacao = 'Erro!';
                $descricaoNotificacao = 'O preço de venda não pode ser menor que zero.';
                break;
            case 'erro_fotos':
                $tituloNotificacao = 'Erro!';
                $descricaoNotificacao = 'Não foi possível enviar as fotos selecionadas.';
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