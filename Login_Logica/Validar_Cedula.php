<?php
/**
 * Valida si una Cédula de Identidad uruguaya es válida utilizando el algoritmo oficial del dígito verificador.
 *
 * @param string|int $CI Número de cédula de identidad a validar (con o sin puntos/guiones).
 * @return bool Devuelve true si la cédula es válida o false si no cumple con el formato/dígito verificador.
 */
function validarCedulaUruguaya($CI) {
    // Limpia el string eliminando cualquier carácter que no sea un dígito numérico (puntos, guiones, espacios)
    $CI = preg_replace('/[^0-9]/', '', $CI);

    // Verifica que la cédula contenga exactamente 8 dígitos (7 dígitos + 1 dígito verificador)
    if (strlen($CI) !== 8) {
        return false;
    }

    // Factores ponderadores estándar utilizados por el Ministerio del Interior de Uruguay
    $factores = [2, 9, 8, 7, 6, 3, 4];
    $suma = 0;

    // Multiplica los primeros 7 dígitos por su respectivo factor y acumula el total
    for ($i = 0; $i < 7; $i++) {
        $suma += intval($CI[$i]) * $factores[$i];
    }

    // Calcula el dígito verificador esperado mediante módulo 10
    // Si el residuo da 10, la operación % 10 lo convierte en 0
    $digitoEsperado = (10 - ($suma % 10)) % 10;
    
    // Obtiene el último dígito (posición 8, índice 7) que es el dígito verificador real ingresado
    $digitoReal = intval($CI[7]);

    // Compara el dígito calculado con el dígito real y retorna true o false
    return $digitoEsperado === $digitoReal;
}
?>