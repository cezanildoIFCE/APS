<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['admin']) || !$_SESSION['admin']) {
    echo "Acesso negado.";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $matricula = $_POST['matricula'];

    $conn = connect();

    $stmt = $conn->prepare("DELETE FROM usuarios WHERE matricula = ?");
    $stmt->bind_param("s", $matricula);

    if ($stmt->execute()) {
        echo "Usuário removido com sucesso!";
    } else {
        echo "Erro ao remover usuário: " . $stmt->error;
    }

    close($conn);
}
?>
