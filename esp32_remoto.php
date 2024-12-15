<?php

$host = 'localhost';
$user = 'esp32_remoto';
$pass = 'esp32_remoto';
$db = 'controle_acesso';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numero_cartao'])) {
    $numero_cartao = $_POST['numero_cartao'];

    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die("Falha na conexão: " . $conn->connect_error);
    }

    $sql = "SELECT numero_cartao FROM usuarios WHERE numero_cartao = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $numero_cartao);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "true";
    } else {
        echo "false";
    }
    $stmt->close();
    $conn->close();
} else {
    http_response_code(400);
    echo "Requisição inválida.";
}
?>
