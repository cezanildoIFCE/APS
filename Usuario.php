<?php
namespace controle_acesso;

abstract class Usuario {
    protected $id;
    protected $nome;
    protected $matricula;
    protected $senha;
    protected $administrador;

    public function __construct($id, $nome, $matricula, $senha, $administrador) {
        $this->id = $id;
        $this->nome = $nome;
        $this->matricula = $matricula;
        $this->senha = $senha;
        $this->administrador = $administrador;
    }

    abstract public function getTipo();

    public function getId() {
        return $this->id;
    }

    public function getNome() {
        return $this->nome;
    }

    public function getMatricula() {
        return $this->matricula;
    }

    public function getSenha() {
        return $this->senha;
    }

    public function isAdministrador() {
        return $this->administrador;
    }
}

class Aluno extends Usuario {
    public function getTipo() {
        return "aluno";
    }
}

class Servidor extends Usuario {
    public function getTipo() {
        return "servidor";
    }
}

class VisitanteCadastrado extends Usuario {
    public function getTipo() {
        return "visitante_cadastrado";
    }
}
?>

