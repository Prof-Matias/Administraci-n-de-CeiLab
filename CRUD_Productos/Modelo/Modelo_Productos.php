<?php
require_once __DIR__ . '/../../Config/Conexion.php';

class Modelo_Producto {
    private $conexion;

    public function __construct() {
        $this->conexion = Conexion::conectar();
    }

    // Listar todos los productos
    public function obtenerProductos() {
        $sql = "SELECT m.*,
                    (SELECT pm.id_solicitud
                        FROM prestamo_material pm
                        JOIN solicitud s ON s.id_solicitud = pm.id_solicitud
                        WHERE pm.id_material = m.id_material AND pm.fecha_devolucion IS NULL AND s.estado = 'ACTIVO'
                        LIMIT 1) AS id_solicitud_activa
                FROM material m
                ORDER BY (m.estado = 'DADO_DE_BAJA'), m.nombre, m.id_serie, m.id_material";
        return $this->conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener un producto por ID
    public function obtenerProductoPorID($id) {
        $sql = "SELECT * FROM material WHERE id_material = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Comprobar si ya existe un producto con el mismo nombre
    public function obtenerProductoPorNombre($nombre) {
        $sql = "SELECT * FROM material WHERE LOWER(Nombre) = LOWER(?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([trim($nombre)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Insertar nuevo producto
    public function guardarProducto($id_serie, $tipo_control, $nombre, $descripcion, $categoria, $cantTotal, $cantDisp, $estado, $foto) {
        $sql = "INSERT INTO material (id_serie, tipo_control, nombre, descripcion, categoria,
                                    cantidad_total, cantidad_disponible, estado, foto_material) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'DISPONIBLE', ?)";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([$id_serie, $tipo_control, $nombre, $descripcion, $categoria, $cantTotal, $cantDisp, $estado, $foto]);
    }

    // Actualizar producto existente
    public function actualizarProducto($id, $nombre, $descripcion, $categoria, $cantTotal, $cantDisp, $estado, $foto) {
        $sql = "UPDATE material 
                SET nombre = ?, descripcion = ?, categoria = ?, cantidad_total = ?, cantidad_disponible = ?, estado = ?, foto_material = ? 
                WHERE id_material = ?";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([$nombre, $descripcion, $categoria, $cantTotal, $cantDisp, $estado, $foto, $id]);
    }

    // Eliminar producto
    public function eliminarProducto($id) {
        $sql = "DELETE FROM material WHERE id_material = ?";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([$id]);
    }

    // Obtener productos disponibles para el catálogo
    public function catalogo() {
        $sql = "SELECT m.nombre,
                    MIN(m.categoria)     AS categoria,
                    MIN(m.descripcion)   AS descripcion,
                    MIN(m.foto_material) AS foto_material,
                    MIN(m.tipo_control)  AS tipo_control,
                    SUM(m.cantidad_total)AS total,
                    SUM(CASE WHEN m.estado = 'DISPONIBLE' THEN m.cantidad_disponible ELSE 0 END) AS disponibles,
                    SUM(m.estado = 'EN_MANTENIMIENTO') AS en_mantenimiento,
                    COUNT(*) AS filas,
                    COALESCE((SELECT SUM(pp.cantidad)
                                FROM prestamo_pedido pp
                                JOIN solicitud s ON s.id_solicitud = pp.id_solicitud
                                WHERE s.estado = 'APROBADO' AND pp.nombre_material = m.nombre), 0) AS comprometidas
                FROM material m
                WHERE m.estado <> 'DADO_DE_BAJA'
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
