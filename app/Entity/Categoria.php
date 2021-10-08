<?php

namespace App\Entity;

use App\Db\Database;
use \PDO;

class Categoria
{

    /**
     * Identificador único da categoria
     * @var integer
     */
    public $id;

    /**
     * Nome da categoria
     * @var string
     */
    public $nome;

    /**
     * Define se a categoria está disponível
     * @var integer(0/1)
     */
    public $disponivel;

    /**
     * Método responsável por cadastrar uma nova categoria
     * @return boolean
     */
    public function cadastrar()
    {
        //Inserir a categoria no banco de dados
        $objDatabase = new Database('categorias');
        $this->id = $objDatabase->insert([
            'nome' => $this->nome,
            'disponivel' => $this->disponivel
        ]);

        //Retorna sucesso
        return true;
    }

    /**
     * Método responsável por atualizar uma categoria no banco
     * @return boolean
     */
    public function atualizar()
    {
        return (new Database('categorias'))->update('id = ' . $this->id, [
            'nome' => $this->nome,
            'disponivel' => $this->disponivel
        ]);
    }

    /**
     * Método responsável por excluir a categoria do banco
     * @return boolean
     */
    public function excluir()
    {
        return (new Database('categorias'))->delete('id = ' . $this->id);
    }

    /**
     * Método responsável por obter as categorias do banco de dados
     * @param string $here
     * @param string $order
     * @param string $limit
     * @return array
     */
    public static function getCategorias($where = null, $order = null, $limit = null)
    {
        return (new Database('categorias'))->select($where, $order, $limit)->fetchAll(PDO::FETCH_CLASS);
    }

    /**
     * Método responsável por buscar uma categoria com base em seu ID
     * @param integer $id
     * @return Categoria
     */
    public static function getCategoria($id)
    {
        return (new Database('categorias'))->select('id = ' . $id)->fetchObject(self::class);
    }
}