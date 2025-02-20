<?php
namespace controle_acesso;

class UsuarioFactory {
    public static function criarUsuario($tipo, $id, $nome, $matricula, $senha, $administrador) {
        switch ($tipo) {
            case "aluno":
                return new Aluno($id, $nome, $matricula, $senha, $administrador);
            case "servidor":
                return new Servidor($id, $nome, $matricula, $senha, $administrador);
            case "visitante_cadastrado":
                return new VisitanteCadastrado($id, $nome, $matricula, $senha, $administrador);
            default:
                throw new \Exception("Tipo de usuário inválido.");
        }
    }
}
?>
