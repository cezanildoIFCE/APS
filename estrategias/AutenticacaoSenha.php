<?php
namespace controle_acesso\estrategias;

require_once __DIR__ . '/../db_connection.php';
use function controle_acesso\connect;

// criar classe
class AutenticacaoSenha implements AutenticacaoStrategy {
    public function autenticar($matricula, $senha) {
        $conn = connect();
        $stmt = $conn->prepare("SELECT id, nome, senha, administrador, tipo_usuario FROM usuarios WHERE matricula = ?");
        $stmt->bind_param("s", $matricula);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($senha, $user['senha'])) {
                return $user;
            } else {
                throw new \Exception("Senha incorreta.");
            }
        } else {
            throw new \Exception("Matrícula não encontrada.");
        }
    }
}
?>
