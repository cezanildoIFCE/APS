<?php
namespace controle_acesso;

session_start();
require_once 'db_connection.php';
require_once 'Usuario.php';
require_once 'UsuarioFactory.php';
require_once 'estrategias/AutenticacaoStrategy.php';
require_once 'estrategias/AutenticacaoSenha.php';
require_once 'Autenticador.php';

use controle_acesso\estrategias\AutenticacaoSenha;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $matricula = $_POST['matricula'];
    $senha = $_POST['senha'];

    try {
        $autenticacao = new Autenticador(new AutenticacaoSenha());
        $user = $autenticacao->autenticar($matricula, $senha);

        $tipo = $user['tipo_usuario'];
        $usuario = UsuarioFactory::criarUsuario($tipo, $user['id'], $user['nome'], $user['matricula'], $user['senha'], $user['administrador']);

        $_SESSION['user_id'] = $usuario->getId();
        $_SESSION['admin'] = $usuario->isAdministrador();
        
        if ($usuario->isAdministrador()) {
            header("Location: menu.html");
        } else {
            header("Location: menu_usuario.html");
        }
        exit();
    } catch (\Exception $e) {
        echo "<script>
                alert('" . $e->getMessage() . "');
                setTimeout(function() {
                    window.location.href = 'tela_login.html';
                }, 50);
              </script>";
    }
}
?>
