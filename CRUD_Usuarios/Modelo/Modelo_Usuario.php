<?php
/**
 * Modelo_Usuario.php
 * 
 * Clase encargada de la gestión de persistencia y consulta de datos
 * de la tabla 'usuario' en la base de datos utilizando PDO.
 */

// Importa el archivo de configuración de la conexión usando una ruta relativa
require_once __DIR__ . '/../../Config/Conexion.php';

class Modelo_Usuario {
    /**
     * Instancia de conexión a la base de datos (PDO).
     * @var PDO
     */
    private $conexion;

    /**
     * Constructor de la clase.
     * Inicializa la conexión a la base de datos en el momento de la instanciación.
     */
    public function __construct() {
        $this->conexion = Conexion::conectar();
    }

    /**
     * Obtiene la lista completa de usuarios registrados en la base de datos.
     *
     * @return array Arreglo asociativo con los datos de todos los usuarios (sin incluir la contraseña).
     */
    public function obtenerUsuarios() {
        // Prepara la consulta para seleccionar los campos públicos de los usuarios
        $stmt = $this->conexion->prepare("SELECT CI, Nombre, Apellido, Email, Rol, Especialidad FROM usuario");
        
        // Ejecuta la consulta SQL
        $stmt->execute();
        
        // Retorna todos los registros en un arreglo asociativo
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca y retorna los datos de un usuario específico mediante su número de Cédula (CI).
     *
     * @param string|int $CI Número de documento de identidad del usuario.
     * @return array|false Arreglo asociativo con los datos del usuario o false si no se encuentra.
     */
    public function obtenerUsuarioPorCI($CI) {
        // Prepara la consulta parametrizada para evitar inyecciones SQL
        $stmt = $this->conexion->prepare("SELECT CI, Nombre, Apellido, Email, Rol, Especialidad, Contraseña FROM usuario WHERE CI = ?");
        
        // Ejecuta la consulta pasando la CI como parámetro
        $stmt->execute([$CI]);
        
        // Retorna la primera fila encontrada
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Registra un nuevo usuario en la base de datos.
     *
     * @param string $CI Documento de identidad.
     * @param string $Nombre Nombre del usuario.
     * @param string $Apellido Apellido del usuario.
     * @param string $Email Correo electrónico.
     * @param string $Contraseña Contraseña ya hasheada.
     * @param string $Rol Rol o perfil (ej: Admin, Docente, Alumno).
     * @param string $Especialidad Área de especialidad o carrera.
     * @return bool True si el registro se insertó correctamente, false en caso de error.
     */
    public function guardarUsuario($CI, $Nombre, $Apellido, $Email, $Contraseña, $Rol, $Especialidad) {
        // Prepara la sentencia SQL para insertar un nuevo usuario
        $stmt = $this->conexion->prepare(
            "INSERT INTO usuario (CI, Nombre, Apellido, Email, Contraseña, Rol, Especialidad) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        // Ejecuta la inserción con los valores enviados
        return $stmt->execute([$CI, $Nombre, $Apellido, $Email, $Contraseña, $Rol, $Especialidad]);
    }

    /**
     * Actualiza los datos de un usuario existente, permitiendo incluso modificar
     * su Cédula (clave primaria) manteniendo la referencia de la CI original.
     *
     * @param string $CIOriginal Cédula actual antes de la edición.
     * @param string $CINueva Nueva cédula a guardar.
     * @param string $Nombre Nombre actualizado.
     * @param string $Apellido Apellido actualizado.
     * @param string $Email Correo electrónico actualizado.
     * @param string $Contraseña Contraseña actualizada (hasheada).
     * @param string $Rol Rol/Perfil actualizado.
     * @param string $Especialidad Especialidad actualizada.
     * @return bool True si la actualización fue exitosa, false en caso contrario.
     */
    public function actualizarUsuario($CIOriginal, $CINueva, $Nombre, $Apellido, $Email, $Contraseña, $Rol, $Especialidad) {
        // Sentencia UPDATE con marcadores nombrados para mayor claridad y seguridad
        $sql = "UPDATE usuario 
                SET CI = :cedulaNueva, 
                    Nombre = :nombre, 
                    Apellido = :apellido, 
                    Email = :correo, 
                    Contraseña = :pass, 
                    Rol = :rol, 
                    Especialidad = :especialidad 
                WHERE CI = :cedulaOriginal";
                
        $stmt = $this->conexion->prepare($sql);
        
        // Asocia los parámetros nombrados a sus respectivos valores y ejecuta
        return $stmt->execute([
            ':cedulaNueva'    => $CINueva,
            ':nombre'         => $Nombre,
            ':apellido'       => $Apellido,
            ':correo'         => $Email,
            ':pass'           => $Contraseña,
            ':rol'            => $Rol,
            ':especialidad'   => $Especialidad,
            ':cedulaOriginal' => $CIOriginal
        ]);
    }

    /**
     * Elimina el registro de un usuario de la base de datos a partir de su CI.
     *
     * @param string|int $CI Número de documento de identidad a eliminar.
     * @return bool True si se eliminó correctamente, false en caso de error.
     */
    public function eliminarUsuario($CI) {
        // Prepara la sentencia DELETE de forma segura
        $stmt = $this->conexion->prepare("DELETE FROM usuario WHERE CI = ?");
        
        // Ejecuta la eliminación
        return $stmt->execute([$CI]);
    }
}