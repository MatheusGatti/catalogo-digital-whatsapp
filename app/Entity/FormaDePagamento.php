<?php

namespace App\Entity;

use App\Db\Database;
use \PDO;

class FormaDePagamento
{

    /**
     * Identificador único da forma de pagamento
     * @var integer
     */
    public $id;

    /**
     * Nome da forma de pagamento
     * @var string
     */
    public $nome;

    /**
     * Define se a forma de pagamento está disponível
     * @var integer(0/1)
     */
    public $disponivel;

    /**
     * Método responsável por cadastrar uma nova forma de pagamento
     * @return boolean
     */
    public function cadastrar()
    {
        //Inserir a forma de pagamento no banco de dados
        $objDatabase = new Database('formas_de_pagamento');
        $this->id = $objDatabase->insert([
            'nome' => $this->nome,
            'disponivel' => $this->disponivel
        ]);

        //Retorna sucesso
        return true;
    }

    /**
     * Método responsável por atualizar uma fomra de pagamento no banco
     * @return boolean
     */
    public function atualizar()
    {
        return (new Database('formas_de_pagamento'))->update('id = ' . $this->id, [
            'nome' => $this->nome,
            'disponivel' => $this->disponivel
        ]);
    }

    /**
     * Método responsável por excluir a forma de pagamento do banco
     * @return boolean
     */
    public function excluir()
    {
        return (new Database('formas_de_pagamento'))->delete('id = ' . $this->id);
    }

    /**
     * Método responsável por obter as formas de pagamento do banco de dados
     * @param string $here
     * @param string $order
     * @param string $limit
     * @return array
     */
    public static function getFormasDePagamento($where = null, $order = null, $limit = null)
    {
        return (new Database('formas_de_pagamento'))->select($where, $order, $limit)->fetchAll(PDO::FETCH_CLASS);
    }

    /**
     * Método responsável por buscar uma forma de pagamento com base em seu ID
     * @param integer $id
     * @return FormaDePagamento
     */
    public static function getFormaDePagamento($id)
    {
        return (new Database('formas_de_pagamento'))->select('id = ' . $id)->fetchObject(self::class);
    }
}