<?php
namespace controle_acesso;

session_start();
require_once 'db_connection.php';
require_once 'Usuario.php';
require_once 'UsuarioFactory.php';

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
    $senha = $_POST['senha'];
    $placa = $_POST['placa'];
    $numero_cartao = $_POST['numero_cartao'];
    $tipo_usuario = $_POST['tipo_usuario'];
    $administrador = $_POST['administrador'];

    $conn = connect();
    $conn->begin_transaction();

    try {
        // Criar usuário usando a fábrica
        $usuario = UsuarioFactory::criarUsuario($tipo_usuario, null, $nome, $matricula, $senha, $administrador);

        // Inserir usuário no banco de dados
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, matricula, senha, tipo_usuario, numero_cartao, administrador) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $usuario->getNome(), $usuario->getMatricula(), password_hash($usuario->getSenha(), PASSWORD_DEFAULT), $tipo_usuario, $numero_cartao, $administrador);

        if ($stmt->execute()) {
            $usuario_id = $stmt->insert_id;
            $stmt = $conn->prepare("INSERT INTO placas (placa, usuario_id) VALUES (?, ?)");
            $stmt->bind_param("si", $placa, $usuario_id);
            if ($stmt->execute()) {
                $conn->commit();
                echo "<script>
                        alert('Usuário cadastrado com sucesso!');
                        setTimeout(function() {
                            window.location.href = 'cadastrar.html';
                        }, 50);
                      </script>";
                exit();
            } else {
                throw new \Exception("Erro ao cadastrar a placa: " . $stmt->error);
            }
        } else {
            throw new \Exception("Erro ao cadastrar usuário: " . $stmt->error);
        }
    } catch (\mysqli_sql_exception $e) {
        $conn->rollback();
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
            } elseif (strpos($e->getMessage(), 'placa') !== false) {
                echo "<script>
                        alert('Erro: A placa já está cadastrada.');
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
    } catch (\Exception $e) {
        $conn->rollback();
        echo "<script>
                alert('" . $e->getMessage() . "');
                setTimeout(function() {
                    window.location.href = 'cadastrar.html';
                }, 50);
              </script>";
    }

    close($conn);
}
?>
