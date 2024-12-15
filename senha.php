<?php
// Recebe a senha do usuário (normalmente vinda de um formulário)
$senha = '00000000000000';

// Cria o hash da senha usando o algoritmo bcrypt (padrão)
$hash = password_hash($senha, PASSWORD_DEFAULT);

// Exibe o hash gerado
echo "Hash da senha: " . $hash;
?>

