<?php
namespace controle_acesso;

class Autenticador {
    private $strategy;

    public function __construct(estrategias\AutenticacaoStrategy $strategy) {
        $this->strategy = $strategy;
    }

    public function autenticar($matricula, $credencial) {
        return $this->strategy->autenticar($matricula, $credencial);
    }
}
?>
