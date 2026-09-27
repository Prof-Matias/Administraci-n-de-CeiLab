<?php 
//Inserto la conexion a la base de datos.
include "../Config/Conexion.php";

//Inicio sesion a la base de datos
session_start();

//Pregunta si se recibio datos por metodo post(definido en el formulario).
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $Cedula =  $_POST["Cedula"];//Variable donde se guardaran los datos ingresados en el campo cedula del formulario

    try{
        $pdo = new PDO("mysql:host=localhost;dbname=ceilab", "root");//Activar y verificar la base de datos
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);//Deteccion de errores

        //Consulta a la base de datos usando LEFT JOIN(Lo que hace es pedir las cedulas de admin, usuario y cliente
        //Busca coinicidencias entre las 3 y asigna una subtabla temporal llamada rol, donde ingresa las cedulas iguales entre las tablas.
        //verifica entre usuario, cliente, ve si hay coincidencias, si no las hay, pasa a admin si encuentra, se crea la sub tabla
        // y agrega el usuario con la cedula ingresada tanto en usuario como en el rol que ocupa en el sistema
        $query ="SELECT 
                u.CI,
                CASE 
                    WHEN a.CI_Administrador IS NOT NULL THEN 'admin'
                    WHEN c.CI_Cliente IS NOT NULL THEN 'cliente'
                    ELSE 'sin_rol'
                END AS rol
            FROM usuario u
            LEFT JOIN administrador a ON u.CI = a.CI_Administrador
            LEFT JOIN cliente c ON u.CI = c.CI_Cliente
            WHERE u.CI = :cedula"
        $stmt = $pdo->prepare($query);//Prepara la consulta anterior
        $stmt->BindParam(":Cedula, $Cedula");//Especifica que compare el parametro pedido en la base de datos con el parametro ingresado en el formulario
        $stmt->execute()//Ejecuta la consulta

        if()

    }catch (PDOException $e) {
        // Manejo de excepciones en caso de error.
        echo "Error: " . $e->getMessage();
    }
}




?>