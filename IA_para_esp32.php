<?php
namespace controle_acesso;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['placa'])) {
    $placa = $_POST['placa'];

    // Dados da placa enviados para o ESP32
    $esp32Server = "http://192.168.1.180/esp32_remoto.php";
    $postData = http_build_query(array('placa' => $placa));

    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postData
        )
    );

    $context = stream_context_create($opts);
    $response = file_get_contents($esp32Server, false, $context);
    
    if ($response === FALSE) {
        echo "Erro ao enviar dados para o ESP32.";
    } else {
        echo "Placa enviada para o ESP32: " . $placa;
    }
} else {
    echo "Nenhuma placa fornecida.";
}
?>
