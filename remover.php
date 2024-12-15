<?php
namespace controle_acesso;

session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['admin']) || !$_SESSION['admin']) {
    echo "<script>
            alert('Acesso negado.');
            setTimeout(function() {
                window.location.href = 'menu.html';
            }, 50);
          </script>";
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $matricula = $_POST['matricula'];
    $conn = \controle_acesso\connect();
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE matricula = ?");
        $stmt->bind_param("s", $matricula);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($usuario_id);
            $stmt->fetch();
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $usuario_id);

            if ($stmt->execute()) {
                $stmt = $conn->prepare("DELETE FROM placas WHERE usuario_id = ?");
                $stmt->bind_param("i", $usuario_id);

                if ($stmt->execute()) {
                    $conn->commit();
                    echo "<script>
                            alert('Usuário e suas placas removidos com sucesso!');
                            setTimeout(function() {
                                window.location.href = 'remover.html';
                            }, 50);
                          </script>";
                } else {
                    throw new \Exception("Erro ao remover as placas: " . $stmt->error);
                }
            } else {
                throw new \Exception("Erro ao remover o usuário: " . $stmt->error);
            }
        } else {
            throw new \Exception("Usuário não encontrado.");
        }
    } catch (\Exception $e) {
        $conn->rollback();
        echo "<script>
                alert('" . $e->getMessage() . "');
                setTimeout(function() {
                    window.location.href = 'remover.html';
                }, 50);
              </script>";
    }
    \controle_acesso\close($conn);
}
?>
