<?php

namespace App\Db;

use \PDO;
use PDOException;
use App\Telegram\Alert;

class Database
{

    /**
     * Host de conexão com o banco de dados
     * @var string
     */
    const HOST = "";

    /**
     * Nome do banco de dados
     * @var string
     */
    const NAME = '';

    /**
     * Usuário do banco de dados
     * @var string
     */
    const USER = '';

    /**
     * Senha de acesso do banco de dados
     * @var string
     */
    const PASS = '';

    /**
     * Nome da tabela a ser manipulada
     * @var string
     */
    private $table;

    /**
     * Instância de conexão com o banco de dados
     * @var PDO
     */
    private $connection;

    /**
     * Define a tabela e instância e conexão
     * @param string $table
     */
    public function __construct($table = null)
    {
        $this->table = $table;
        $this->setConnection();
    }

    /**
     * Método repsonsável por criar uma conexão com o banco de dados
     */
    private function setConnection()
    {
        try {
            $this->connection = new PDO('mysql:host=' . self::HOST . ';dbname=' . self::NAME, self::USER, self::PASS);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->exec('set names utf8mb4');
        } catch (PDOException $e) {
            Alert::sendMessage('<b>ERRO NO SITE:</b> <pre>' . $e->getMessage() . '</pre>');
            die();
        }
    }

    /**
     * Método responsável por executar queries dentro do banco de dados
     * @param string $query
     * @param array $params
     * @return PDOStatement
     */
    public function execute($query, $params = [])
    {
        try {
            $statement = $this->connection->prepare($query);
            $statement->execute($params);
            return $statement;
        } catch (PDOException $e) {
            Alert::sendMessage('<b>ERRO NO SITE:</b> <pre>' . $e->getMessage() . '</pre>');
            die();
        }
    }

    /**
     * Método responsável por inserir dados no banco
     * @param array $values [ field => value ]
     * @return integer ID inserido
     */
    public function insert($values)
    {
        //Dados da query
        $fields = array_keys($values);
        $binds = array_pad([], count($fields), '?');

        //Monta a query
        $query = 'INSERT INTO ' . $this->table . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $binds) . ')';

        //Executa o insert
        $this->execute($query, array_values($values));

        //Retorna o ID inserido
        return $this->connection->lastInsertId();
    }

    /**
     * Método responsável por executar uma consulta no banco
     * @param string $where
     * @param string $order
     * @param string $limit
     * @param string $fields
     * @return PDOStatement
     */
    public function select($where = null, $order = null, $limit = null, $fields = '*')
    {
        //Dados da query
        $where = strlen($where) ? 'WHERE ' . $where : '';
        $order = strlen($order) ? 'ORDER BY ' . $order : '';
        $limit = strlen($limit) ? 'LIMIT ' . $limit : '';

        //Monta a query
        $query = 'SELECT ' . $fields . ' FROM ' . $this->table . ' ' . $where . ' ' . $order . ' ' . $limit;

        //Executa a query
        return $this->execute($query);
    }

    /**
     * Método responsável por executar atualizações no banco de dado
     * @param string $where
     * @param array $values [ field => value ]
     * @return boolean
     */
    public function update($where, $values)
    {
        //Dados da query
        $fields = array_keys($values);

        //Monta a query
        $query = 'UPDATE ' . $this->table . ' SET ' . implode('=?,', $fields) . '=? WHERE ' . $where;

        //Executa a query
        $this->execute($query, array_values(($values)));

        //Retorna sucesso
        return true;
    }

    /**
     * Método responsável por excluir dados do banco
     * @param string $where
     * @return boolean
     */
    public function delete($where)
    {
        //Monta a query
        $query = 'DELETE FROM ' . $this->table . ' WHERE ' . $where;

        //Executa a query
        $this->execute($query);

        //Retorna sucesso
        return true;
    }
}