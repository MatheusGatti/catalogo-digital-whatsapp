<?php

namespace App\Entity;

use App\Db\Database;
use \PDO;

class Desconto
{

    /**
     * Identificador único do desconto
     * @var integer
     */
    public $id;

    /**
     * Porcentagem do desconto
     * @var float
     */
    public $porcentagem;

    /**
     * Quantidade de produtos para aplicar o desconto
     * @var integer
     */
    public $qnt_produtos;

    /**
     * Define se é para aplicar o desconto em uma quantidade única ou maior igual
     * @var integer (0/1)
     */
    public $qnt_unica;

    /**
     * Define se o desconto está ativo
     * @var integer (0/1)
     */
    public $ativo;

    /**
     * Método responsável por cadastrar um novo desconto
     * @return boolean
     */
    public function cadastrar()
    {
        //Inserir o desconto no banco de dados
        $objDatabase = new Database('descontos');
        $this->id = $objDatabase->insert([
            'porcentagem' => $this->porcentagem,
            'qnt_produtos' => $this->qnt_produtos,
            'qnt_unica' => $this->qnt_unica,
            'ativo' => $this->ativo
        ]);

        //Retorna sucesso
        return true;
    }

    /**
     * Método responsável por atualizar um desconto
     * @return boolean
     */
    public function atualizar()
    {
        return (new Database('descontos'))->update('id = ' . $this->id, [
            'porcentagem' => $this->porcentagem,
            'qnt_produtos' => $this->qnt_produtos,
            'qnt_unica' => $this->qnt_unica,
            'ativo' => $this->ativo
        ]);
    }

    /**
     * Método responsável por excluir o desconto
     * @return boolean
     */
    public function excluir()
    {
        return (new Database('descontos'))->delete('id = ' . $this->id);
    }

    /**
     * Método responsável por obter os descontos
     * @param string $here
     * @param string $order
     * @param string $limit
     * @return array
     */
    public static function getDescontos($where = null, $order = null, $limit = null)
    {
        return (new Database('descontos'))->select($where, $order, $limit)->fetchAll(PDO::FETCH_CLASS);
    }

    /**
     * Método responsável por buscar um desconto com base em seu ID
     * @param integer $id
     * @return Desconto
     */
    public static function getDesconto($id)
    {
        return (new Database('descontos'))->select('id = ' . $id)->fetchObject(self::class);
    }
}