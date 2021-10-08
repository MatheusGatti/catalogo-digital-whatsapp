<?php

namespace App\Entity;

use \App\Db\Database;
use \PDO;

class Usuario
{

    /**
     * Identificador único do usuário
     * @var integer
     */
    public $id;

    /**
     * Nome do usuário
     * @var string
     */
    public $nome;

    /**
     * Usuário
     * @var string
     */
    public $usuario;

    /**
     * Hash da senha do usuário
     * @var string
     */
    public $senha;

    /**
     * Privilégio do usuário (0 = administrador; 1 = colaborador)
     * @var integer
     */
    public $privilegio;

    /**
     * Método responsável por cadastrar um novo usuário
     * @return boolean
     */
    public function cadastrar()
    {
        //Inserir o usuário no banco de dados
        $objDatabase = new Database('usuarios');
        $this->id = $objDatabase->insert([
            'nome' => $this->nome,
            'usuario' => $this->usuario,
            'senha' => $this->senha,
            'privilegio' => $this->privilegio
        ]);

        //Retorna sucesso
        return true;
    }

    /**
     * Método responsável por atualizar um usuário no banco
     * @return boolean
     */
    public function atualizar()
    {
        return (new Database('usuarios'))->update('id = ' . $this->id, [
            'nome' => $this->nome,
            'usuario' => $this->usuario,
            'senha' => $this->senha,
            'privilegio' => $this->privilegio
        ]);
    }

    /**
     * Método responsável por excluir o usuário do banco
     * @return boolean
     */
    public function excluir()
    {
        return (new Database('usuarios'))->delete('id = ' . $this->id);
    }

    /**
     * Método responsável por obter os usuários do banco de dados
     * @param string $where
     * @param string $order
     * @param string $limit
     * @return array
     */
    public static function getUsuarios($where = null, $order = null, $limit = null)
    {
        return (new Database('usuarios'))->select($where, $order, $limit)->fetchAll(PDO::FETCH_CLASS);
    }

    /**
     * Método responsável por retornar uma instância de um usuário com base em seu usuário
     * @param string $usuario
     * @return Usuario
     */
    public static function getUsuarioPorUsuario($usuario)
    {
        return (new Database('usuarios'))->select('usuario = "' . $usuario . '"')->fetchObject(self::class);
    }

    /**
     * Método responsável por retornar uma instância de um usuário com base em seu ID
     * @param integer $id
     * @return Usuario
     */
    public static function getUsuario($id)
    {
        return (new Database('usuarios'))->select('id = ' . $id)->fetchObject(self::class);
    }
}