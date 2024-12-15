//db_conection
<?php

function connect() {
    $host = 'localhost';
    $user = 'root'; 
    $pass = ''; 
    $db = 'controle_acesso';

    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        die("Falha na conexão: " . $conn->connect_error);
    }
    return $conn;
}

function close($conn) {
    $conn->close();
}
?>