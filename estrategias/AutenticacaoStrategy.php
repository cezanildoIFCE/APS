<?php
namespace controle_acesso\estrategias;

interface AutenticacaoStrategy {
    public function autenticar($matricula, $credencial);
}
?>
