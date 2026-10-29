<?php

require_once __DIR__ . '/../../Config/Conexion.php';

class Modelo_Usuario {
    /** @var PDO */
    private $conexion;

    public function __construct() {
        $this->conexion = Conexion::conectar();
    }

    /**
     * Obtiene todos los usuarios (incluye estado_cuenta) con sus teléfonos.
     */
    public function obtenerUsuarios() {
        $stmt = $this->conexion->prepare(
            "SELECT ci, nombre, apellido, email, rol, especialidad, estado_cuenta FROM usuario"
        );
        $stmt->execute();
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($usuarios as &$u) {
            $u['telefonos'] = $this->obtenerTelefonosPorCI($u['ci']);
        }
        return $usuarios;
    }

    public function obtenerTodos() {
        return $this->obtenerUsuarios();
    }

    public function obtenerTelefonosPorCI($ci) {
        $stmt = $this->conexion->prepare("SELECT telefono FROM telefono WHERE ci_usuario = ?");
        $stmt->execute([$ci]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function obtenerUsuarioPorCI($ci) {
        $stmt = $this->conexion->prepare(
            "SELECT ci, nombre, apellido, email, rol, especialidad, contrasenia, estado_cuenta FROM usuario WHERE ci = ?"
        );
        $stmt->execute([$ci]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $usuario['telefonos'] = $this->obtenerTelefonosPorCI($ci);
        }
        return $usuario;
    }

    public function obtenerPorCedula($ci) {
        return $this->obtenerUsuarioPorCI($ci);
    }

    public function guardarUsuario($ci, $nombre, $apellido, $email, $contrasenia, $rol, $especialidad, $telefonos = []) {
        try {
            $this->conexion->beginTransaction();
            $especialidad = !empty($especialidad) ? $especialidad : null;

            if (password_get_info($contrasenia)['algo'] === 0) {
                $contrasenia = password_hash($contrasenia, PASSWORD_DEFAULT);
            }

            // estado_cuenta toma su valor por defecto 'ACTIVA'
            $stmt = $this->conexion->prepare(
                "INSERT INTO usuario (ci, nombre, apellido, email, contrasenia, rol, especialidad) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$ci, $nombre, $apellido, $email, $contrasenia, $rol, $especialidad]);

            if (empty($telefonos)) {
                throw new Exception("Debe ingresar al menos un teléfono.");
            }
            $this->insertarTelefonosBD($ci, $telefonos);

            $this->conexion->commit();
            return true;
        } catch (Exception $e) {
            $this->conexion->rollBack();
            return false;
        }
    }

    public function crearUsuario($ci, $nombre, $apellido, $email, $contrasenia, $rol, $especialidad, $telefonos = []) {
        return $this->guardarUsuario($ci, $nombre, $apellido, $email, $contrasenia, $rol, $especialidad, $telefonos);
    }

    public function actualizarUsuario($ciOriginal, $ciNueva, $nombre, $apellido, $email, $contrasenia, $rol, $especialidad, $telefonos = [], $cambiarPass = true) {
        try {
            $this->conexion->beginTransaction();
            $especialidad = !empty($especialidad) ? $especialidad : null;

            if ($cambiarPass && !empty($contrasenia) && password_get_info($contrasenia)['algo'] === 0) {
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
            $stmt->execute([
                ':cedulaNueva'    => $ciNueva,
                ':nombre'         => $nombre,
                ':apellido'       => $apellido,
                ':correo'         => $email,
                ':pass'           => $contrasenia,
                ':rol'            => $rol,
                ':especialidad'   => $especialidad,
                ':cedulaOriginal' => $ciOriginal
            ]);

            if ($ciOriginal !== $ciNueva) {
                $stmtFK = $this->conexion->prepare("UPDATE telefono SET ci_usuario = ? WHERE ci_usuario = ?");
                $stmtFK->execute([$ciNueva, $ciOriginal]);
            }

            if (!empty($telefonos)) {
                $stmtDel = $this->conexion->prepare("DELETE FROM telefono WHERE ci_usuario = ?");
                $stmtDel->execute([$ciNueva]);
                $this->insertarTelefonosBD($ciNueva, $telefonos);
            }

            $this->conexion->commit();
            return true;
        } catch (Exception $e) {
            $this->conexion->rollBack();
            return false;
        }
    }

    private function insertarTelefonosBD($ci, $telefonos) {
        $stmtTel = $this->conexion->prepare("INSERT INTO telefono (ci_usuario, telefono) VALUES (?, ?)");
        foreach ($telefonos as $tel) {
            $telLimpio = trim($tel);
            if (!empty($telLimpio)) {
                $stmtTel->execute([$ci, $telLimpio]);
            }
        }
    }

    /**
     * Baja lógica: cambia estado_cuenta a 'ACTIVA' o 'INACTIVA'.
     */
    public function cambiarEstadoCuenta($ci, $estado) {
        try {
            $stmt = $this->conexion->prepare("UPDATE usuario SET estado_cuenta = ? WHERE ci = ?");
            return $stmt->execute([$estado, $ci]);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function eliminarUsuario($ci) {
        try {
            $stmt = $this->conexion->prepare("DELETE FROM usuario WHERE ci = ?");
            return $stmt->execute([$ci]);
        } catch (PDOException $e) {
            return false;
        }
    }
}

// Alias de la clase
class ModeloUsuarios extends Modelo_Usuario {}