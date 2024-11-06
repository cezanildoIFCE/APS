<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['admin']) || !$_SESSION['admin']) {
    echo "Acesso negado.";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $matricula = $_POST['matricula'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $placa = $_POST['placa'];
    $tipo_usuario = $_POST['tipo_usuario'];
    $administrador = $_POST['administrador'];

    $conn = connect();

    $stmt = $conn->prepare("INSERT INTO usuarios (nome, matricula, senha, placa, tipo_usuario, administrador) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $nome, $matricula, $senha, $placa, $tipo_usuario, $administrador);

    if ($stmt->execute()) {
        echo "Usuário cadastrado com sucesso!";
    } else {
        echo "Erro ao cadastrar usuário: " . $stmt->error;
    }

    close($conn);
}
?>
