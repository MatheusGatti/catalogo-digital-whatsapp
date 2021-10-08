<?php

namespace App\Session;

class Carrinho
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

            //CRIA A SESSÃO DO CARRINHO
            if (!isset($_SESSION['carrinho'])) $_SESSION['carrinho'] = [];
        }
    }

    /**
     * Método responsável por adicionar um produto ao carrinho
     * @param integer $produto
     * @param integer $quantidade
     */
    public static function add($produto, $quantidade = 1)
    {
        //INICIA A SESSÃO
        self::init();

        //VALIDA SE A QUANTIDADE NÃO É 0
        if ($quantidade === 0) return;

        //VALIDA A SESSÃO DO PRODUTO NO CARRINHO
        if (!isset($_SESSION['carrinho'][$produto])) {
            //ADICIONA O PRODUTO COM A QUANTIDADE
            $_SESSION['carrinho'][$produto] = (int) $quantidade;
            return;
        }

        //INCREMENTA A QUANTIDADE DO PRODUTO
        $_SESSION['carrinho'][$produto] += $quantidade;
    }

    /**
     * Método responsável por remover um produto do carrinho
     * @param integer $produto
     */
    public static function remove($produto)
    {
        //INICIA A SESSÃO
        self::init();

        //VALIDA A SESSÃO DO PRODUTO NO CARRINHO
        if (isset($_SESSION['carrinho'][$produto])) unset($_SESSION['carrinho'][$produto]);
    }

    /**
     * Método responsável por alterar a quantidade de um produto do carrinho
     * @param integer $produto
     * @param integer $quantidade
     */
    public static function quantity($produto, $quantidade = 0)
    {
        //INICIA A SESSÃO
        self::init();

        //VALIDA A SESSÃO DO PRODUTO NO CARRINHO
        if (isset($_SESSION['carrinho'][$produto])) {
            //SE QUANTIDADE FOR 0 REMOVER PRODUTO
            if ($quantidade <= 0) {
                unset($_SESSION['carrinho'][$produto]);
                return;
            }

            $_SESSION['carrinho'][$produto] = $quantidade;
        }
    }

    /**
     * Método responsável por limpar o carrinho
     */
    public static function clear()
    {
        //INICIA A SESSÃO
        self::init();

        //REMOVE A SESSÃO
        unset($_SESSION['carrinho']);
    }

    /**
     * Método responsável por retornar a quantidade de produtos únicos que tem no carrinho
     * @return integer
     */
    public static function getQuantidadeProdutosUnicos()
    {

        //INICIA A SESSÃO
        self::init();

        return count($_SESSION['carrinho']);
    }

    /**
     * Método responsável por retornar a quantidade de produtos que tem no carrinho
     * @return integer
     */
    public static function getQuantidadeProdutos()
    {

        //INICIA A SESSÃO
        self::init();

        $contaProdutos = 0;
        foreach ($_SESSION['carrinho'] as $produto) {
            $contaProdutos += $produto;
        }

        return $contaProdutos;
    }

    /**
     * Método responsável por retornar um produto baseado em seu ID
     * @param integer $id
     * @return integer
     */
    public static function getProduto($id)
    {

        //INICIA A SESSÃO
        self::init();

        return isset($_SESSION['carrinho'][$id]) ? $_SESSION['carrinho'][$id] : 0;
    }

    /**
     * Método responsável por retornar os produtos do carrinho
     * @return array
     */
    public static function getProdutos()
    {

        //INICIA A SESSÃO
        self::init();

        return $_SESSION['carrinho'];
    }
}