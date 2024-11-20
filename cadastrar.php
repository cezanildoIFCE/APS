//cadastrar.php
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
    $nome = $_POST['nome'];
    $matricula = $_POST['matricula'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $placa = $_POST['placa'];
    $numero_cartao = $_POST['numero_cartao'];
    $tipo_usuario = $_POST['tipo_usuario'];
    $administrador = $_POST['administrador'];

    $conn = connect();

   
    $stmt = $conn->prepare("INSERT INTO usuarios (nome, matricula, senha, placa, tipo_usuario, numero_cartao, administrador) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $nome, $matricula, $senha, $placa, $tipo_usuario, $numero_cartao, $administrador);

    try {
        
        if ($stmt->execute()) {
            echo "<script>
                    alert('Usuário cadastrado com sucesso!');
                    setTimeout(function() {
                        window.location.href = 'cadastrar.html';
                    }, 50); 
                  </script>";
            exit();
        }
    } catch (mysqli_sql_exception $e) {
        
        if ($e->getCode() == 1062) {
            if (strpos($e->getMessage(), 'matricula') !== false) {
                echo "<script>
                        alert('Erro: A matrícula já está cadastrada.');
                        setTimeout(function() {
                            window.location.href = 'cadastrar.html';
                        }, 50);
                      </script>";
            } elseif (strpos($e->getMessage(), 'numero_cartao') !== false) {
                echo "<script>
                        alert('Erro: O número do cartão já está cadastrado.');
                        setTimeout(function() {
                            window.location.href = 'cadastrar.html';
                        }, 50);
                      </script>";
            }
        } else {
            echo "<script>
                    alert('Erro ao cadastrar usuário: " . $e->getMessage() . "');
                    setTimeout(function() {
                        window.location.href = 'cadastrar.html';
                    }, 50);
                  </script>";
        }
    }

    
    close($conn);
}
?>
