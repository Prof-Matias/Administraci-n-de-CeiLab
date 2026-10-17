<?php
require_once __DIR__ . '/../../Config/Conexion.php';
require_once __DIR__ . '/../../Config/Estados.php'; 

class Modelo_Producto {
    private $conexion;

    public function __construct() {
        $this->conexion = Conexion::conectar();
    }
    // funcion para obtener productos, con la cantidad de productos disponibles y la cantidad de productos comprometidos en solicitudes aprobadas
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
    // funcion para obtener productos por su id
    public function obtenerProductoPorID($id) {
        $sql = "SELECT * FROM material WHERE id_material = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // funcion para obtener productos por su nombre
    public function obtenerProductoPorNombre($nombre) {
        $sql = "SELECT * FROM material WHERE LOWER(nombre) = LOWER(?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([trim($nombre)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // funcion para obtener productos por su nombre y id, para evitar duplicados al actualizar
    public function guardarProducto($id_serie, $tipo_control, $nombre, $descripcion, $categoria, $cantTotal, $cantDisp, $estado, $foto) {
        $estadoInicial = $estado ?: EstadoMaterial::DISPONIBLE;
        
        $sql = "INSERT INTO material (id_serie, tipo_control, nombre, descripcion, categoria,
                                        cantidad_total, cantidad_disponible, estado, foto_material) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([$id_serie, $tipo_control, $nombre, $descripcion, $categoria, $cantTotal, $cantDisp, $estadoInicial, $foto]);
    }

    // funcion para actualizar productos
    public function actualizarProducto($id, $nombre, $descripcion, $categoria, $cantTotal, $cantDisp, $estado, $foto) {
        $sql = "UPDATE material 
                SET nombre = ?, descripcion = ?, categoria = ?, cantidad_total = ?, cantidad_disponible = ?, estado = ?, foto_material = ? 
                WHERE id_material = ?";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([$nombre, $descripcion, $categoria, $cantTotal, $cantDisp, $estado, $foto, $id]);
    }
    // funcion para cambiar el estado de un producto por un evento, usando la clase EstadoMaterial para validar la transición
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

    // funcion para eliminar productos, si no se puede eliminar por restricciones de clave foranea, se cambia 
    // el estado a DADO_DE_BAJA y la cantidad disponible a 0
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
    // funcion para dar de baja un producto con un motivo, cambiando el estado a DADO_DE_BAJA y la cantidad disponible a 0
    public function darDeBajaConMotivo($id, $motivo) {
    $sql = "UPDATE material 
            SET estado = ?, cantidad_disponible = 0, motivo_baja = ? 
            WHERE id_material = ?";
    $stmt = $this->conexion->prepare($sql);
    return $stmt->execute([EstadoMaterial::DADO_DE_BAJA, $motivo, $id]);
}

    // funcion para obtener el catalogo de productos, agrupando por nombre y mostrando la cantidad total, disponibles, 
    // comprometidas y en mantenimiento
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
}