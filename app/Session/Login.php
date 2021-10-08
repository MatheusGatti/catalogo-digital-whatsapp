<?php

namespace App\Session;

class Login
{

    /**
     * Método responsável por iniciar a sessão
     */
    private static function init()
    {
        //VERIFICA O STATUS DA SESSÃO
        if (session_status() !== PHP_SESSION_ACTIVE) {
            //INICIA A SESSÃO
            session_start();
        }
    }

    /**
     * Método responsável por retornar os dados do usuário logado
     * @return array
     */
    public static function getUsuarioLogado()
    {
        //INICIA A SESSÃO
        self::init();

        //RETORNA DADOS DO USUÁRIO
        return self::isLogged() ? $_SESSION['usuario'] : null;
    }

    /**
     * Método responsável por logar o usuário
     * @param Usuario $objUsuario
     */
    public static function login($objUsuario)
    {
        //INICIA A SESSÃO
        self::init();

        //SESSÃO DE USUÁRIO
        $_SESSION['usuario'] = [
            'id' => $objUsuario->id,
            'nome' => $objUsuario->nome,
            'privilegio' => $objUsuario->privilegio
        ];

        //VALIDA SE VEIO POR OUTRA PÁGINA
        if (isset($_SESSION['pagina_de_origem'])) {
            $paginaDeOrigem = $_SESSION['pagina_de_origem'];
            unset($_SESSION['pagina_de_origem']);
            header('Location: ' . $paginaDeOrigem);
        } else {
            //REDIRECIONA O USUÁRIO PARA O PAINEL
            header('Location: painel.php');
        }
    }

    /**
     * Método responsável por deslogar o usuário
     */
    public static function logout()
    {
        //INICIA A SESSÃO
        self::init();

        //REMOVE A SESSÃO DE USUÁRIO
        unset($_SESSION['usuario']);

        //REDIRECIONA USUÁRIO PARA O LOGIN
        header('Location: index.php');
    }

    /**
     * Método responsável por verificar se o usuário está logado
     * @return boolean
     */
    public static function isLogged()
    {
        //INICIA A SESSÃO
        self::init();

        //VALIDAÇÃO DA SESSÃO
        return isset($_SESSION['usuario']['id']);
    }

    /**
     * Método responsável por obrigar o usuário a estar logado para acessar
     */
    public static function requireLogin()
    {
        if (!self::isLogged()) {
            //DEFINE A SESSÃO DE REDIRECIONAMENTO
            $_SESSION['pagina_de_origem'] = $_SERVER["REQUEST_URI"];

            header('Location: index.php');
            exit;
        }
    }

    /**
     * Método responsável por obrigar o usuário a estar deslogado para acessar
     */
    public static function requireLogout()
    {
        if (self::isLogged()) {
            //VALIDA SE VEIO POR OUTRA PÁGINA
            if (isset($_SESSION['pagina_de_origem'])) {
                $paginaDeOrigem = $_SESSION['pagina_de_origem'];
                unset($_SESSION['pagina_de_origem']);
                header('Location: ' . $paginaDeOrigem);
            } else {
                //REDIRECIONA O USUÁRIO PARA O PAINEL
                header('Location: painel.php');
            }
            exit;
        }
    }
}