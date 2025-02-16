<?php
namespace controle_acesso;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["image"])) {
    $image = $_FILES["image"]["tmp_name"];
    $imagePath = 'uploads/' . basename($_FILES["image"]["name"]);

    // Verifica se o diretório uploads existe, se não, cria o diretório
    if (!is_dir('uploads')) {
        mkdir('uploads', 0777, true);
    }

    if (move_uploaded_file($image, $imagePath)) {
        // Executar o script Python para análise da placa
        $output = shell_exec("python analyze_plate.py " . escapeshellarg($imagePath));
        $plate = trim($output);

        // Enviar a placa para IA_para_esp32.php
        $ia_para_esp32 = "http://localhost/IA_para_esp32.php";
        $postData = http_build_query(array('placa' => $plate));

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postData
            )
        );

        $context = stream_context_create($opts);
        $response = file_get_contents($ia_para_esp32, false, $context);

        echo $response;
    } else {
        echo "Erro ao enviar a imagem.";
    }
}
?>
