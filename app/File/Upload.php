<?php

namespace App\File;

class Upload
{

    /**
     * Nome do arquivo (sem extensão)
     * @var string
     */
    private $name;

    /**
     * Extensão do arquivo (sem ponto)
     * @var string
     */
    private $extension;

    /**
     * Type do arquivo
     * @var string
     */
    private $type;

    /**
     * Nome/caminho temporário do arquivo
     * @var string
     */
    private $tmpName;

    /**
     * Código de erro do upload
     * @var integer
     */
    private $error;

    /**
     * Tamanho do arquivo
     * @var integer
     */
    private $size;

    /**
     * Contador de duplicação de arquivo
     * @var integer
     */
    private $duplicates = 0;

    /**
     * Construtor da classe
     * @param array $file  $_FILES[campo]
     */
    public function __construct($file)
    {
        $this->type = $file['type'];
        $this->tmpName = $file['tmp_name'];
        $this->error = $file['error'];
        $this->size = $file['size'];
        $info = pathinfo($file['name']);
        $this->name = $info['filename'];
        $this->extension = $info['extension'];
    }

    /**
     * Método responsável por alterar nome do arquivo
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Método responsável por gerar um novo nome aleatório
     */
    public function generateNewName()
    {
        $this->name = md5(rand(0000000000, 9999999999));
    }

    /**
     * Método responsável por retornar o nome do arquivo com sua extensão
     * @return string
     */
    public function getBasename()
    {
        //Valida extensão
        $extension = strlen($this->extension) ? '.' . $this->extension : '';

        //Valida duplicação
        $duplicates = $this->duplicates > 0 ? '-' . $this->duplicates : '';

        //Retorna o nome completo
        return $this->name . $duplicates . $extension;
    }

    /**
     * Método responsável por obeter um nome possível para o arquivo
     * @param string $dir
     * @param boolean $overwrite
     * @return string
     */
    private function getPossibleBasename($dir, $overwrite)
    {
        //Sobrescrever arquivo
        if ($overwrite) return $this->getBasename();

        //Não pode sobrescrever arquivo
        $basename = $this->getBasename();

        //Verificar duplicação
        if (!file_exists($dir . '/' . $basename)) {
            return $basename;
        }

        //Incrementar duplicações
        $this->duplicates++;

        //Retorno o próprio método
        return $this->getPossibleBasename($dir, $overwrite);
    }

    /**
     * Método responsável por mover o arquivo de upload
     * @param string $dir
     * @param boolean $overwrite
     * @param boolean $onlyImages
     * @return boolean
     */
    public function upload($dir, $overwrite = true)
    {
        //Verificar erro
        if ($this->error != 0) return false;

        //Verifica se é uma imagem
        $tiposPermitidos = array("image/jpeg", "image/png");
        if (!in_array($this->type, $tiposPermitidos)) return false;

        //Caminho completo de destino
        $path = $dir . '/' . $this->getPossibleBasename($dir, $overwrite);

        //Move o arquivo para pasta de destino
        return move_uploaded_file($this->tmpName, $path);
    }

    /**
     * Método responsável por criar instâncias de upload para múltiplos arquivos
     * @param array $files $_FILES['campo']
     * @return array
     */
    public static function createMultipleUpload($files)
    {
        $uploads = [];

        foreach ($files['name'] as $key => $value) {
            //Array de arquivo
            $file = [
                'name' => $files['name'][$key],
                'type' => $files['type'][$key],
                'tmp_name' => $files['tmp_name'][$key],
                'error' => $files['error'][$key],
                'size' => $files['size'][$key]
            ];

            //Nova instância
            $uploads[] = new Upload($file);
        }

        return $uploads;
    }
}