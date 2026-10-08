<?php
/**
 * Validar_Cedula.php
 * 
 * Valida si una Cédula de Identidad uruguaya es válida utilizando 
 * el algoritmo oficial del dígito verificador (Módulo 10).
 *
 * @param string|int $ci Número de cédula de identidad a validar (con o sin puntos/guiones).
 * @return bool Devuelve true si la cédula es válida o false si no cumple con el formato/dígito verificador.
 */
function validarCedulaUruguaya($ci) {
    // Limpia el string eliminando cualquier carácter que no sea un dígito numérico (puntos, guiones, espacios)
    $ci = preg_replace('/[^0-9]/', '', (string)$ci);

    // Si la cédula tiene 7 dígitos (ejemplo cédulas antiguas sin el cero inicial), se completa con cero a la izquierda
    if (strlen($ci) === 7) {
        $ci = '0' . $ci;
    }

    // Verifica que la cédula contenga exactamente 8 dígitos (7 números + 1 dígito verificador)
    if (strlen($ci) !== 8) {
        return false;
    }

    // Factores ponderadores estándar utilizados para la CI uruguaya
    $factores = [2, 9, 8, 7, 6, 3, 4];
    $suma = 0;

    // Multiplica los primeros 7 dígitos por su respectivo factor y acumula el total
    for ($i = 0; $i < 7; $i++) {
        $suma += intval($ci[$i]) * $factores[$i];
    }

    // Calcula el dígito verificador esperado mediante módulo 10
    // Si la resta (10 - residuo) es 10, la operación % 10 lo convierte en 0
    $digitoEsperado = (10 - ($suma % 10)) % 10;
    
    // Obtiene el último dígito ingresado (posición 8, índice 7)
    $digitoReal = intval($ci[7]);

    // Compara el dígito calculado con el real
    return $digitoEsperado === $digitoReal;
}