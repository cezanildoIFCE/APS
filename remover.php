//remover.php
<?php
session_start();
require 'db_connection.php';

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

    $conn = connect();


    $stmt = $conn->prepare("SELECT 1 FROM usuarios WHERE matricula = ?");
    $stmt->bind_param("s", $matricula);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {

        $stmt = $conn->prepare("DELETE FROM usuarios WHERE matricula = ?");
        $stmt->bind_param("s", $matricula);

        if ($stmt->execute()) {
            echo "<script>
                    alert('Usuário removido com sucesso!');
                    setTimeout(function() {
                        window.location.href = 'remover.html'; 
                    }, 50);
                  </script>";
        } else {
            echo "<script>
                    alert('Erro ao remover usuário: " . $stmt->error . "');
                    setTimeout(function() {
                        window.location.href = 'remover.html'; 
                    }, 50);
                  </script>";
        }
    } else {

        echo "<script>
                alert('Erro: Usuário não encontrado.');
                setTimeout(function() {
                    window.location.href = 'remover.html'; 
                }, 50);
              </script>";
    }

    close($conn);
}
?>
