<?php

namespace App\Entity;

use App\Db\Database;
use \PDO;

class Entrega
{

    /**
     * Identificador único da entrega
     * @var integer
     */
    public $id;

    /**
     * Nome da entrega
     * @var string
     */
    public $nome;

    /**
     * Define se a entrega está disponível
     * @var integer(0/1)
     */
    public $disponivel;

    /**
     * Método responsável por cadastrar uma nova entrega
     * @return boolean
     */
    public function cadastrar()
    {
        //Inserir a entrega no banco de dados
        $objDatabase = new Database('entregas');
        $this->id = $objDatabase->insert([
            'nome' => $this->nome,
            'disponivel' => $this->disponivel
        ]);

        //Retorna sucesso
        return true;
    }

    /**
     * Método responsável por atualizar uma entrega no banco
     * @return boolean
     */
    public function atualizar()
    {
        return (new Database('entregas'))->update('id = ' . $this->id, [
            'nome' => $this->nome,
            'disponivel' => $this->disponivel
        ]);
    }

    /**
     * Método responsável por excluir a entrega do banco
     * @return boolean
     */
    public function excluir()
    {
        return (new Database('entregas'))->delete('id = ' . $this->id);
    }

    /**
     * Método responsável por obter as entregas do banco de dados
     * @param string $here
     * @param string $order
     * @param string $limit
     * @return array
     */
    public static function getEntregas($where = null, $order = null, $limit = null)
    {
        return (new Database('entregas'))->select($where, $order, $limit)->fetchAll(PDO::FETCH_CLASS);
    }

    /**
     * Método responsável por buscar uma entrega com base em seu ID
     * @param integer $id
     * @return Entrega
     */
    public static function getEntrega($id)
    {
        return (new Database('entregas'))->select('id = ' . $id)->fetchObject(self::class);
    }
}