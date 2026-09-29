<?php 
include "../Config/Conexion.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cedula = isset($_POST["Cedula"]) ? trim($_POST["Cedula"]) : '';

    if(!empty($cedula)){
         try{
            $query = "SELECT * FROM usuario WHERE CI = :Cedula";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":Cedula", $cedula);
            $stmt->execute();

            $verificar = $stmt->fetch(PDO::FETCH_ASSOC);

            if($verificar){
            $_SESSION['cedula_temp'] = $cedula;

            header("Location: ../Contraseña.php");
            }elseif(!$verificar){
            header("Location: ../Index.php?error=1");
            }      
    
         } catch (PDOException $e) {
        // Manejo de excepciones en caso de error.
        echo "Error: " . $e->getMessage();
        }   
    }else{
          header("Location: ../Index.php?error=empty");
    }
    

}
?>