<?php
require_once __DIR__ . '/../../Config/Conexion.php';
require_once __DIR__ . '/../../Config/Estados.php'; 

class Modelo_Producto {
    private $conexion;

    public function __construct() {
        $this->conexion = Conexion::conectar();
    }

    // Método para verificar duplicados de número de serie (permite excluir un ID al editar)
    public function obtenerProductoPorSerie($id_serie, $excluirId = null) {
        if (empty(trim($id_serie))) return false;

        if ($excluirId) {
            $sql = "SELECT * FROM material WHERE LOWER(id_serie) = LOWER(?) AND id_material <> ?";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([trim($id_serie), $excluirId]);
        } else {
            $sql = "SELECT * FROM material WHERE LOWER(id_serie) = LOWER(?)";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([trim($id_serie)]);
        }
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Función para obtener productos, con la cantidad de productos disponibles y la cantidad de productos comprometidos
    public function obtenerProductos() {
        $sql = "SELECT m.*,
                    (SELECT pm.id_solicitud
                        FROM prestamo_material pm
                        JOIN solicitud s ON s.id_solicitud = pm.id_solicitud
                        WHERE pm.id_material = m.id_material AND pm.fecha_devolucion IS NULL AND s.estado = 'ACTIVO'
                        LIMIT 1) AS id_solicitud_activa
                FROM material m
                ORDER BY (m.estado = '" . EstadoMaterial::DADO_DE_BAJA . "'), m.nombre, m.id_serie, m.id_material";
        return $this->conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Función para obtener productos por su id
    public function obtenerProductoPorID($id) {
        $sql = "SELECT * FROM material WHERE id_material = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Función para obtener productos por su nombre
    public function obtenerProductoPorNombre($nombre) {
        $sql = "SELECT * FROM material WHERE LOWER(nombre) = LOWER(?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([trim($nombre)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Función para guardar productos
    public function guardarProducto($id_serie, $tipo_control, $nombre, $descripcion, $categoria, $cantTotal, $cantDisp, $estado, $foto) {
        $estadoInicial = $estado ?: EstadoMaterial::DISPONIBLE;
        
        $sql = "INSERT INTO material (id_serie, tipo_control, nombre, descripcion, categoria,
                                        cantidad_total, cantidad_disponible, estado, foto_material) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([$id_serie, $tipo_control, $nombre, $descripcion, $categoria, $cantTotal, $cantDisp, $estadoInicial, $foto]);
    }

    // Función para actualizar productos (Incluye id_serie)
    public function actualizarProducto($id, $nombre, $descripcion, $categoria, $cantTotal, $cantDisp, $estado, $foto, $id_serie = null) {
        $sql = "UPDATE material 
                SET nombre = ?, descripcion = ?, categoria = ?, cantidad_total = ?, cantidad_disponible = ?, estado = ?, foto_material = ?, id_serie = ? 
                WHERE id_material = ?";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([$nombre, $descripcion, $categoria, $cantTotal, $cantDisp, $estado, $foto, $id_serie, $id]);
    }

    // Función para cambiar el estado de un producto por un evento
    public function cambiarEstadoPorEvento($id_material, $evento) {
        $producto = $this->obtenerProductoPorID($id_material);
        if (!$producto) {
            throw new Exception("El material no existe.");
        }

        $nuevoEstado = EstadoMaterial::destino($producto['estado'], $evento, "El material '{$producto['nombre']}'");

        $sql = "UPDATE material SET estado = ? WHERE id_material = ?";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([$nuevoEstado, $id_material]);
    }

    // Función para eliminar productos
    public function eliminarProducto($id) {
        try {
            $sql = "DELETE FROM material WHERE id_material = ?";
            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            $sqlBaja = "UPDATE material SET estado = ?, cantidad_disponible = 0 WHERE id_material = ?";
            $stmtBaja = $this->conexion->prepare($sqlBaja);
            return $stmtBaja->execute([EstadoMaterial::DADO_DE_BAJA, $id]);
        }
    }

// Verifica de forma simple si el producto está prestado o reservado
public function esProductoComprometido($id_material) {
    $sql = "SELECT id_material 
            FROM material 
            WHERE id_material = ? 
              AND (
                  estado = 'PRESTADO'
                  OR id_material IN (
                      SELECT pm.id_material 
                      FROM prestamo_material pm
                      JOIN solicitud s ON s.id_solicitud = pm.id_solicitud
                      WHERE pm.fecha_devolucion IS NULL AND s.estado = 'ACTIVO'
                  )
                  OR id_material IN (
                      SELECT rm.id_material 
                      FROM reserva_material rm
                      JOIN solicitud s ON s.id_solicitud = rm.id_solicitud
                      WHERE s.estado IN ('PENDIENTE', 'APROBADO')
                  )
              )";

    $stmt = $this->conexion->prepare($sql);
    $stmt->execute([$id_material]);

    // Si fetch() obtiene una fila, retorna true; si no encuentra nada, devuelve false.
    return $stmt->fetch() !== false;
}

public function darDeBajaConMotivo($id, $motivo, $ciResponsable = null) {
    $producto = $this->obtenerProductoPorID($id);
    if (!$producto) return false;

    $estadoAnterior = $producto['estado'];
    $estadoNuevo    = EstadoMaterial::DADO_DE_BAJA;

    $sql = "UPDATE material 
            SET estado = ?, cantidad_disponible = 0, motivo_baja = ?, fecha_baja = NOW() 
            WHERE id_material = ?";
    $stmt = $this->conexion->prepare($sql);
    $exito = $stmt->execute([$estadoNuevo, $motivo, $id]);

    if ($exito) {
        $this->registrarHistorialEstado($id, $estadoAnterior, $estadoNuevo, 'DAR_DE_BAJA', $ciResponsable, null, $motivo);
    }

    return $exito;
}
    // Función para obtener el catálogo de productos
    public function catalogo() {
        $sql = "SELECT m.nombre,
                    MIN(m.categoria)     AS categoria,
                    MIN(m.descripcion)   AS descripcion,
                    MIN(m.foto_material) AS foto_material,
                    MIN(m.tipo_control)  AS tipo_control,
                    SUM(m.cantidad_total)AS total,
                    SUM(CASE WHEN m.estado = '" . EstadoMaterial::DISPONIBLE . "' THEN m.cantidad_disponible ELSE 0 END) AS disponibles,
                    SUM(m.estado = '" . EstadoMaterial::EN_MANTENIMIENTO . "') AS en_mantenimiento,
                    COUNT(*) AS filas,
                    COALESCE((SELECT SUM(pp.cantidad)
                                FROM prestamo_pedido pp
                                JOIN solicitud s ON s.id_solicitud = pp.id_solicitud
                                WHERE s.estado = 'APROBADO' AND pp.nombre_material = m.nombre), 0) AS comprometidas
                FROM material m
                WHERE m.estado <> '" . EstadoMaterial::DADO_DE_BAJA . "'
                GROUP BY m.nombre
                ORDER BY MIN(m.categoria), m.nombre";
        
        $filas = $this->conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($filas as &$f) {
            $f['libres'] = max(0, (int)$f['disponibles'] - (int)$f['comprometidas']);
            
            if ($f['libres'] > 0) {
                $f['disponibilidad'] = 'Disponible';
            } elseif ((int)$f['en_mantenimiento'] === (int)$f['filas']) {
                $f['disponibilidad'] = 'En mantenimiento';
            } else {
                $f['disponibilidad'] = 'Agotado';
            }
        }
        
        return $filas;
    }
    

    // Método para actualizar los datos compartidos de un grupo de productos por su nombre
    public function actualizarGrupoPorNombre($nombreOriginal, $nuevoNombre, $descripcion, $categoria, $foto) {
        $sql = "UPDATE material 
            SET nombre = ?, descripcion = ?, categoria = ?, foto_material = ? 
            WHERE LOWER(nombre) = LOWER(?)";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([$nuevoNombre, $descripcion, $categoria, $foto, trim($nombreOriginal)]);
}

// Método para reactivar un producto registrando en el historial
public function reactivarProducto($id, $ciResponsable = null) {
    $producto = $this->obtenerProductoPorID($id);
    if (!$producto) return false;

    $estadoAnterior = $producto['estado'];
    $estadoNuevo    = 'DISPONIBLE';

    $sql = "UPDATE material 
            SET estado = ?,
                cantidad_disponible = CASE 
                    WHEN tipo_control = 'SERIE' THEN 1 
                    ELSE cantidad_total 
                END,
                motivo_baja = NULL,
                fecha_baja = NULL 
            WHERE id_material = ?";
    $stmt = $this->conexion->prepare($sql);
    $exito = $stmt->execute([$estadoNuevo, $id]);

    if ($exito) {
        $this->registrarHistorialEstado($id, $estadoAnterior, $estadoNuevo, 'REACTIVAR', $ciResponsable, null, 'Reactivación manual de material');
    }

    return $exito;
}

// Función auxiliar para registrar cualquier cambio de estado en la tabla del historial
public function registrarHistorialEstado($idMaterial, $estadoAnterior, $estadoNuevo, $evento, $ciResponsable, $idSolicitud = null, $motivo = null) {
    // Si tu tabla en la base de datos se llama distinto (ej: historial_cambio_estado), ajusta el nombre aquí:
    $sql = "INSERT INTO historial_estado_material 
            (id_material, estado_anterior, estado_nuevo, evento, fecha, ci_responsable, id_solicitud, motivo) 
            VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)";
    $stmt = $this->conexion->prepare($sql);
    return $stmt->execute([
        $idMaterial,
        $estadoAnterior,
        $estadoNuevo,
        $evento,
        $ciResponsable,
        $idSolicitud,
        $motivo
    ]);
}

}


?>