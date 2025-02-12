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
    $sql = "SELECT id FROM placas WHERE placa = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $placa);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>
                alert('Erro: A placa já está cadastrada.');
                setTimeout(function() {
                    window.location.href = 'alterar.html';
                }, 50);
              </script>";
    } else {
        $sql = "INSERT INTO placas (placa, usuario_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $placa, $usuario_id);
        if ($stmt->execute()) {
            echo "<script>
                    alert('Placa adicionada com sucesso!');
                    setTimeout(function() {
                        window.location.href = 'alterar.html';
                    }, 50);
                  </script>";
        } else {
            echo "<script>
                    alert('Erro ao adicionar a placa: " . $stmt->error . "');
                    setTimeout(function() {
                        window.location.href = 'alterar.html';
                    }, 50);
                  </script>";
        }
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
