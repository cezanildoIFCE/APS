<?php
namespace controle_acesso;

session_start();
require_once 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $matricula = $_POST['matricula'];
    $senha = $_POST['senha'];

    $conn = connect();

    $stmt = $conn->prepare("SELECT id, nome, senha, administrador FROM usuarios WHERE matricula = ?");
    $stmt->bind_param("s", $matricula);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($senha, $user['senha'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['admin'] = $user['administrador'];
            
            if ($user['administrador']) {
                header("Location: menu.html");
            } else {
                header("Location: menu_usuario.html");
            }
            exit();
        } else {
            $message = "Senha incorreta.";
        }
    } else {
        $message = "Matrícula não encontrada.";
    }
    
    close($conn);

    if (isset($message)) {
        echo "<script>
                alert('$message');
                setTimeout(function() {
                    window.location.href = 'tela_login.html';
                }, 50);
              </script>";
    }
}
?>
