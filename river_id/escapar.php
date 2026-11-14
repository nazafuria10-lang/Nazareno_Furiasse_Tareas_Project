<?php
// Función para escapar salidas HTML y prevenir inyección XSS
function escapar(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}
?>