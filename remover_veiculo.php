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
$conn = connect();
$matricula = $_POST['matricula'];
$placa = $_POST['placa'];
$sql = "SELECT id FROM usuarios WHERE matricula = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $matricula);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $usuario_id = $row['id'];
    $sql = "SELECT id FROM placas WHERE placa = ? AND usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $placa, $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $sql = "DELETE FROM placas WHERE placa = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $placa, $usuario_id);
        if ($stmt->execute()) {
            echo "<script>
                    alert('Placa removida com sucesso!');
                    setTimeout(function() {
                        window.location.href = 'alterar.html';
                    }, 50);
                  </script>";
        } else {
            echo "<script>
                    alert('Erro ao remover a placa: " . $stmt->error . "');
                    setTimeout(function() {
                        window.location.href = 'alterar.html';
                    }, 50);
                  </script>";
        }
    } else {
        echo "<script>
                alert('Erro: A placa não está associada ao usuário informado.');
                setTimeout(function() {
                    window.location.href = 'alterar.html';
                }, 50);
              </script>";
    }
} else {
    echo "<script>
            alert('Erro: Usuário não encontrado.');
            setTimeout(function() {
                window.location.href = 'alterar.html';
            }, 50);
          </script>";
}
$stmt->close();
close($conn);
?>
