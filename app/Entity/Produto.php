<?php

namespace App\Entity;

use App\Db\Database;
use \PDO;

class Produto
{

    /**
     * Identificador único do produto
     * @var integer
     */
    public $id;

    /**
     * Nome do produto
     * @var string
     */
    public $nome;

    /**
     * Descrição do produto
     * @var string
     */
    public $descricao;

    /**
     * Define se o produto está disponível
     * @var integer(0/1)
     */
    public $disponivel;

    /**
     * ID da categoria que o produto está inserido
     * @var integer
     */
    public $categoria;

    /**
     * Quantiade do produto em estoque
     * @var integer
     */
    public $estoque;

    /**
     * Preço de venda do produto
     * @var float
     */
    public $preco_de_venda;

    /**
     * Nome do arquivo das fotos do produto
     * @var array
     */
    public $fotos;

    /**
     * Método responsável por cadastrar um novo produto
     * @return boolean
     */
    public function cadastrar()
    {
        //Inserir o produto no banco de dados
        $objDatabase = new Database('produtos');
        $this->id = $objDatabase->insert([
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'disponivel' => $this->disponivel,
            'categoria' => $this->categoria,
            'estoque' => $this->estoque,
            'preco_de_venda' => $this->preco_de_venda,
            'fotos' => $this->fotos
        ]);

        //Retorna sucesso
        return true;
    }

    /**
     * Método responsável por atualizar um produto no banco
     * @return boolean
     */
    public function atualizar()
    {
        return (new Database('produtos'))->update('id = ' . $this->id, [
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'disponivel' => $this->disponivel,
            'categoria' => $this->categoria,
            'estoque' => $this->estoque,
            'preco_de_venda' => $this->preco_de_venda,
            'fotos' => $this->fotos
        ]);
    }

    /**
     * Método responsável por excluir o produto do banco
     * @return boolean
     */
    public function excluir()
    {
        return (new Database('produtos'))->delete('id = ' . $this->id);
    }

    /**
     * Método responsável por obter os produtos do banco de dados
     * @param string $where
     * @param string $order
     * @param string $limit
     * @return array
     */
    public static function getProdutos($where = null, $order = null, $limit = null)
    {
        return (new Database('produtos'))->select($where, $order, $limit)->fetchAll(PDO::FETCH_CLASS);
    }

    /**
     * Método responsável por obter a quantidade de produtos do banco de dados
     * @param string $where
     * @return integer
     */
    public static function getQuantidadeProdutos($where = null)
    {
        return (new Database('produtos'))->select($where, null, null, 'COUNT(*) AS qtd')->fetchObject()->qtd;
    }

    /**
     * Método responsável por buscar um produto com base em seu ID
     * @param integer $id
     * @return Produto
     */
    public static function getProduto($id)
    {
        return (new Database('produtos'))->select('id = ' . $id)->fetchObject(self::class);
    }
}