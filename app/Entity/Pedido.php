<?php

namespace App\Entity;

date_default_timezone_set('America/Sao_Paulo');

use \App\Db\Database;
use DateTime;
use \PDO;

class Pedido
{

    /**
     * Identificador único do pedido
     * @var integer
     */
    public $id;

    /**
     * Nome do cliente
     * @var string
     */
    public $nome;

    /**
     * Sobrenome do cliente
     * @var string
     */
    public $sobrenome;

    /**
     * WhatsApp do cliente
     * @var string
     */
    public $whatsapp;

    /**
     * Data de cadastro do pedido
     * @var string
     */
    public $data;

    /**
     * Forma de pagamento do pedido
     * @var string
     */
    public $forma_de_pagamento;

    /**
     * Forma de entrega do pedido
     * @var string
     */
    public $entrega;

    /**
     * Produtos do pedido
     * @var array
     */
    public $produtos;

    /**
     * Valor do desconto do pedido
     * @var float
     */
    public $valor_desconto;

    /**
     * Valor total do pedido sem desconto
     * @var float
     */
    public $valor_total;

    /**
     * Status do pedido
     * 0 = novo;
     * 1 = atendido;
     * 2 = finalizado;
     * @var integer (0, 1, 2)
     */
    public $status;

    /**
     * Atendente do pedido
     * @var string
     */
    public $atendente;

    /**
     * Método responsável por cadastrar um novo pedido
     * @return boolean
     */
    public function cadastrar()
    {
        //DEFINE A DATA
        $this->data = date('Y-m-d H:i:s');

        //Inserir o pedido no banco de dados
        $objDatabase = new Database('pedidos');
        $this->id = $objDatabase->insert([
            'nome' => $this->nome,
            'sobrenome' => $this->sobrenome,
            'whatsapp' => $this->whatsapp,
            'data' => $this->data,
            'forma_de_pagamento' => $this->forma_de_pagamento,
            'entrega' => $this->entrega,
            'produtos' => $this->produtos,
            'valor_desconto' => $this->valor_desconto,
            'valor_total' => $this->valor_total,
            'status' => $this->status,
            'atendente' => $this->atendente
        ]);

        //Retorna sucesso
        return true;
    }

    /**
     * Método responsável por atualizar um pedido no banco
     * @return boolean
     */
    public function atualizar()
    {
        return (new Database('pedidos'))->update('id = ' . $this->id, [
            'nome' => $this->nome,
            'sobrenome' => $this->sobrenome,
            'whatsapp' => $this->whatsapp,
            'data' => $this->data,
            'forma_de_pagamento' => $this->forma_de_pagamento,
            'entrega' => $this->entrega,
            'produtos' => $this->produtos,
            'valor_desconto' => $this->valor_desconto,
            'valor_total' => $this->valor_total,
            'status' => $this->status,
            'atendente' => $this->atendente
        ]);
    }

    /**
     * Método responsável por excluir o pedido do banco
     * @return boolean
     */
    public function excluir()
    {
        return (new Database('pedidos'))->delete('id = ' . $this->id);
    }

    /**
     * Método responsável por obter os pedidos do banco de dados
     * @param string $where
     * @param string $order
     * @param string $limit
     * @return array
     */
    public static function getPedidos($where = null, $order = null, $limit = null)
    {
        return (new Database('pedidos'))->select($where, $order, $limit)->fetchAll(PDO::FETCH_CLASS);
    }

    /**
     * Método responsável por retornar uma instância de um pedido com base em seu ID
     * @param integer $id
     * @return Pedido
     */
    public static function getPedido($id)
    {
        return (new Database('pedidos'))->select('id = ' . $id)->fetchObject(self::class);
    }

    /**
     * Método responsável por obter a quantidade de pedidos do banco de dados
     * @param string $where
     * @return integer
     */
    public static function getQuantidadePedidos($where = null)
    {
        return (new Database('pedidos'))->select($where, null, null, 'COUNT(*) AS qtd')->fetchObject()->qtd;
    }

    /**
     * Método responsável por retornar quanto tempo já se passou desde que o pedido foi feito
     * @param string $data
     * @return string
     */
    public static function getTempo($data)
    {
        $dataPedido = new DateTime($data);
        $dataAgora = new DateTime();
        $diferenca = $dataAgora->diff($dataPedido);
        $tempo = [];

        //VALIDA SE JÁ PASSOU ANOS, MESES, DIAS, HORAS OU MINUTOS
        if ((int)$diferenca->format('%Y') > 0) {
            $tempo[] = $diferenca->format('%Y anos');
        }
        if ((int)$diferenca->format('%m') > 0) {
            $tempo[] = $diferenca->format('%m meses');
        }
        if ((int)$diferenca->format('%d') > 0) {
            $tempo[] = $diferenca->format('%d dias');
        }
        if ((int)$diferenca->format('%H') > 0) {
            $tempo[] = $diferenca->format('%H horas');
        }
        if ((int)$diferenca->format('%i') > 0) {
            $tempo[] = $diferenca->format('%i minutos');
        }

        //FILTRA A ARRAY TEMPO E TRANSFORMA EM STRING
        if (!empty($tempo)) {
            $tempo = 'há ' . join(' e ', array_filter(array_merge(array(join(', ', array_slice($tempo, 0, -1))), array_slice($tempo, -1)), 'strlen'));
        } else {
            $tempo = 'Agora';
        }

        return $tempo;
    }
}