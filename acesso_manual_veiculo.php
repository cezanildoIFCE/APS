<?php
namespace controle_acesso;

session_start();
require_once 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $matricula = $_POST['matricula'];
    $placa = $_POST['placa'];
    $tipo_acesso = $_POST['tipo_acesso']; // 'entrada' ou 'saida'

    $conn = \controle_acesso\connect();

    // Se a placa for informada, ela terá prioridade
    if (!empty($placa)) {
        // Verifica se a placa está cadastrada
        $stmt = $conn->prepare("SELECT usuarios.id, usuarios.nome, usuarios.tipo_usuario 
                                FROM placas 
                                JOIN usuarios ON placas.usuario_id = usuarios.id 
                                WHERE placas.placa = ?");
        $stmt->bind_param("s", $placa);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $usuario_id = $row['id'];
            $nome = $row['nome'];
            $tipo_usuario = $row['tipo_usuario'];

            // Insere o registro de acesso
            $stmt = $conn->prepare("INSERT INTO registros_acesso (usuario_id, placa, tipo_acesso) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $usuario_id, $placa, $tipo_acesso);

            if ($stmt->execute()) {
                echo "<script>
                        alert('Registro de $tipo_acesso adicionado com sucesso para o veículo de $nome ($tipo_usuario)!');
                        setTimeout(function() {
                            window.location.href = 'acesso_manual.html';
                        }, 50);
                      </script>";
            } else {
                echo "<script>
                        alert('Erro ao adicionar registro de acesso: " . $stmt->error . "');
                        setTimeout(function() {
                            window.location.href = 'acesso_manual.html';
                        }, 50);
                      </script>";
            }
        } else {
            echo "<script>
                    alert('Erro: Placa não cadastrada.');
                    setTimeout(function() {
                        window.location.href = 'acesso_manual.html';
                    }, 50);
                  </script>";
        }
    } elseif (!empty($matricula)) {
        // Verifica se o usuário está cadastrado pela matrícula
        $stmt = $conn->prepare("SELECT id, nome, tipo_usuario FROM usuarios WHERE matricula = ?");
        $stmt->bind_param("s", $matricula);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $usuario_id = $row['id'];
            $nome = $row['nome'];
            $tipo_usuario = $row['tipo_usuario'];

            // Insere o registro de acesso sem placa
            $stmt = $conn->prepare("INSERT INTO registros_acesso (usuario_id, tipo_acesso) VALUES (?, ?)");
            $stmt->bind_param("is", $usuario_id, $tipo_acesso);

            if ($stmt->execute()) {
                echo "<script>
                        alert('Registro de $tipo_acesso adicionado com sucesso para $nome ($tipo_usuario)!');
                        setTimeout(function() {
                            window.location.href = 'acesso_manual.html';
                        }, 50);
                      </script>";
            } else {
                echo "<script>
                        alert('Erro ao adicionar registro de acesso: " . $stmt->error . "');
                        setTimeout(function() {
                            window.location.href = 'acesso_manual.html';
                        }, 50);
                      </script>";
            }
        } else {
            echo "<script>
                    alert('Erro: Usuário não encontrado.');
                    setTimeout(function() {
                        window.location.href = 'acesso_manual.html';
                    }, 50);
                  </script>";
        }
    } else {
        echo "<script>
                alert('Erro: Por favor, informe a matrícula ou a placa.');
                setTimeout(function() {
                    window.location.href = 'acesso_manual.html';
                }, 50);
              </script>";
    }

    $stmt->close();
    \controle_acesso\close($conn);
}
?>
