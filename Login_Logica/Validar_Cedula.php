<?php
/**
 * Validar_Cedula.php
 */

function validarCedulaUruguaya($ci) {
    // Limpia el string eliminando cualquier carácter que no sea un dígito numérico
    $ci = preg_replace('/[^0-9]/', '', (string)$ci);

    // Si la cédula tiene 7 dígitos, se completa con cero a la izquierda
    if (strlen($ci) === 7) {
        $ci = '0' . $ci;
    }

    // Verifica que la cédula contenga exactamente 8 dígitos
    if (strlen($ci) !== 8) {
        return false;
    }

    // Factores ponderadores estándar utilizados para la CI uruguaya
    $factores = [2, 9, 8, 7, 6, 3, 4];
    $suma = 0;

    // Multiplica los primeros 7 dígitos por su respectivo factor
    for ($i = 0; $i < 7; $i++) {
        $suma += intval($ci[$i]) * $factores[$i];
    }

    // Corregido: Cálculo del dígito verificador esperado (se agregó el signo de resta -)
    $digitoEsperado = (10 - ($suma % 10)) % 10;
    
    // Obtiene el último dígito ingresado
    $digitoReal = intval($ci[7]);

    // Compara el dígito calculado con el real
    return $digitoEsperado === $digitoReal;
}

// =========================================================================
// LÓGICA DE RESPUESTA PARA EL FETCH / AJAX DE JAVASCRIPT
// =========================================================================
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula = $_POST['cedula'] ?? '';
    
    // Ejecuta la validación
    $esValida = validarCedulaUruguaya($cedula);

    // Devuelve el resultado en formato JSON para JavaScript
    echo json_encode([
        'valido' => $esValida
    ]);
    exit;
}