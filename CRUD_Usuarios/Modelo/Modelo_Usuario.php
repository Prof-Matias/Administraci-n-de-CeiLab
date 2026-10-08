<?php
/**
 * Modelo_Usuario.php
 * 
 * Clase encargada de la gestión de persistencia y consulta de datos
 * de la tabla 'usuario' en la base de datos utilizando PDO.
 */

require_once __DIR__ . '/../../Config/Conexion.php';

class Modelo_Usuario {
    /**
     * Instancia de conexión a la base de datos (PDO).
     * @var PDO
     */
    private $conexion;

    public function __construct() {
        $this->conexion = Conexion::conectar();
    }

    /**
     * Obtiene la lista completa de usuarios.
     */
    public function obtenerUsuarios() {
        $stmt = $this->conexion->prepare("SELECT ci, nombre, apellido, email, rol, especialidad FROM usuario");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Alias para compatibilidad
    public function obtenerTodos() {
        return $this->obtenerUsuarios();
    }

    /**
     * Busca y retorna los datos de un usuario específico mediante su Cédula (CI).
     */
    public function obtenerUsuarioPorCI($ci) {
        $stmt = $this->conexion->prepare("SELECT ci, nombre, apellido, email, rol, especialidad, contrasenia FROM usuario WHERE ci = ?");
        $stmt->execute([$ci]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Alias para compatibilidad
    public function obtenerPorCedula($ci) {
        return $this->obtenerUsuarioPorCI($ci);
    }

    /**
     * Registra un nuevo usuario en la base de datos.
     */
    public function guardarUsuario($ci, $nombre, $apellido, $email, $contrasenia, $rol, $especialidad) {
        try {
            $especialidad = !empty($especialidad) ? $especialidad : null;
            
            // Si la contraseña no está encriptada aún, se le aplica el hash
            if (password_get_info($contrasenia)['algo'] === 0) {
                $contrasenia = password_hash($contrasenia, PASSWORD_DEFAULT);
            }

            $stmt = $this->conexion->prepare(
                "INSERT INTO usuario (ci, nombre, apellido, email, contrasenia, rol, especialidad) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            
            return $stmt->execute([$ci, $nombre, $apellido, $email, $contrasenia, $rol, $especialidad]);
        } catch (PDOException $e) {
            return false;
        }
    }

    // Alias para compatibilidad
    public function crearUsuario($ci, $nombre, $apellido, $email, $contrasenia, $rol, $especialidad) {
        return $this->guardarUsuario($ci, $nombre, $apellido, $email, $contrasenia, $rol, $especialidad);
    }

    /**
     * Actualiza los datos de un usuario existente.
     */
    public function actualizarUsuario($ciOriginal, $ciNueva, $nombre, $apellido, $email, $contrasenia, $rol, $especialidad, $cambiarPass = true) {
        try {
            $especialidad = !empty($especialidad) ? $especialidad : null;

            // Si se debe cambiar contraseña y no viene hasheada
            if ($cambiarPass && password_get_info($contrasenia)['algo'] === 0) {
                $contrasenia = password_hash($contrasenia, PASSWORD_DEFAULT);
            }

            $sql = "UPDATE usuario 
                    SET ci = :cedulaNueva, 
                        nombre = :nombre, 
                        apellido = :apellido, 
                        email = :correo, 
                        contrasenia = :pass, 
                        rol = :rol, 
                        especialidad = :especialidad 
                    WHERE ci = :cedulaOriginal";
                    
            $stmt = $this->conexion->prepare($sql);
            
            return $stmt->execute([
                ':cedulaNueva'    => $ciNueva,
                ':nombre'         => $nombre,
                ':apellido'       => $apellido,
                ':correo'         => $email,
                ':pass'           => $contrasenia,
                ':rol'            => $rol,
                ':especialidad'   => $especialidad,
                ':cedulaOriginal' => $ciOriginal
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Elimina el registro de un usuario.
     */
    public function eliminarUsuario($ci) {
        try {
            $stmt = $this->conexion->prepare("DELETE FROM usuario WHERE ci = ?");
            return $stmt->execute([$ci]);
        } catch (PDOException $e) {
            return false;
        }
    }
}

// Alias de la clase para evitar errores de instanciación
class ModeloUsuarios extends Modelo_Usuario {}