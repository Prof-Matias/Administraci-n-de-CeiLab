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

    /**
     * Permite cambiar dinámicamente el estado de un producto (DISPONIBLE, EN_MANTENIMIENTO, DADO_DE_BAJA)
     * registrando el cambio en el historial y gestionando la cantidad disponible.
     */
    public function cambiarEstadoMaterial($id, $nuevoEstado, $motivo, $ciResponsable = null) {
        $producto = $this->obtenerProductoPorID($id);
        if (!$producto) return false;

        $estadoAnterior = $producto['estado'];
        
        // Si el estado no cambió y no hay motivo relevante, evitamos procesar innecesariamente
        if ($estadoAnterior === $nuevoEstado) return true;

        // Definimos la cantidad disponible según el nuevo estado elegido
        if ($nuevoEstado === EstadoMaterial::DADO_DE_BAJA || $nuevoEstado === EstadoMaterial::EN_MANTENIMIENTO) {
            $cantidadDisponible = 0;
        } else if ($nuevoEstado === EstadoMaterial::DISPONIBLE) {
            $cantidadDisponible = ($producto['tipo_control'] === 'SERIE') ? 1 : $producto['cantidad_total'];
        } else {
            $cantidadDisponible = $producto['cantidad_disponible'];
        }

        // Si se da de baja se registran los campos de fecha_baja y motivo_baja
        if ($nuevoEstado === EstadoMaterial::DADO_DE_BAJA) {
            $sql = "UPDATE material 
                    SET estado = ?, cantidad_disponible = ?, motivo_baja = ?, fecha_baja = NOW() 
                    WHERE id_material = ?";
            $stmt = $this->conexion->prepare($sql);
            $exito = $stmt->execute([$nuevoEstado, $cantidadDisponible, $motivo, $id]);
        } else {
            $sql = "UPDATE material 
                    SET estado = ?, cantidad_disponible = ?, motivo_baja = NULL, fecha_baja = NULL 
                    WHERE id_material = ?";
            $stmt = $this->conexion->prepare($sql);
            $exito = $stmt->execute([$nuevoEstado, $cantidadDisponible, $id]);
        }

        // Si la actualización fue exitosa, registramos el movimiento en la tabla de historial
        if ($exito) {
            $evento = ($nuevoEstado === EstadoMaterial::DADO_DE_BAJA) ? 'DAR_DE_BAJA' : 'CAMBIO_ESTADO';
            
            if (class_exists('HistorialEstado') && method_exists('HistorialEstado', 'registrarHistorialEstado')) {
                HistorialEstado::registrarHistorialEstado(
                    $this->conexion, 
                    $id, 
                    $estadoAnterior, 
                    $nuevoEstado, 
                    $evento, 
                    $ciResponsable, 
                    null, 
                    $motivo
                );
            }
        }

        return $exito;
    }

    // Mantenemos la función darDeBajaConMotivo reutilizando la nueva lógica general
    public function darDeBajaConMotivo($id, $motivo, $ciResponsable = null) {
        return $this->cambiarEstadoMaterial($id, EstadoMaterial::DADO_DE_BAJA, $motivo, $ciResponsable);
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

    // Unidades de este material que están hoy fuera del CeiLab: entregadas en préstamos ACTIVOS y sin devolver
    public function cantidadEntregada($idMaterial) {
        $sql = "SELECT COALESCE(SUM(pm.cantidad), 0) AS cantidad,
                       GROUP_CONCAT(DISTINCT pm.id_solicitud ORDER BY pm.id_solicitud SEPARATOR ', #') AS solicitudes
                  FROM prestamo_material pm
                  JOIN solicitud s ON s.id_solicitud = pm.id_solicitud
                 WHERE pm.id_material = ? AND pm.fecha_devolucion IS NULL AND s.estado = 'ACTIVO'";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$idMaterial]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return ['cantidad' => (int)$fila['cantidad'], 'solicitudes' => $fila['solicitudes']];
    }

    // Unidades de un material (por nombre) comprometidas en préstamos APROBADOS que todavía no se entregaron
    public function cantidadComprometida($nombre) {
        $sql = "SELECT COALESCE(SUM(pp.cantidad), 0) AS cantidad,
                       GROUP_CONCAT(DISTINCT pp.id_solicitud ORDER BY pp.id_solicitud SEPARATOR ', #') AS solicitudes
                  FROM prestamo_pedido pp
                  JOIN solicitud s ON s.id_solicitud = pp.id_solicitud
                 WHERE s.estado = 'APROBADO' AND pp.nombre_material = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$nombre]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return ['cantidad' => (int)$fila['cantidad'], 'solicitudes' => $fila['solicitudes']];
    }

    // Números de los préstamos APROBADOS o ACTIVOS que piden este material (los pedidos se guardan por nombre)
    public function solicitudesAbiertasPorNombre($nombre) {
        $sql = "SELECT DISTINCT pp.id_solicitud
                  FROM prestamo_pedido pp
                  JOIN solicitud s ON s.id_solicitud = pp.id_solicitud
                 WHERE s.estado IN ('APROBADO', 'ACTIVO') AND pp.nombre_material = ?
                 ORDER BY pp.id_solicitud";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$nombre]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
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

        return $stmt->fetch() !== false;
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
        return $this->cambiarEstadoMaterial($id, EstadoMaterial::DISPONIBLE, 'Reactivación manual de material', $ciResponsable);
    }

    // Obtiene el historial de cambios de estado ordenado del más reciente al más antiguo
    public function obtenerHistorialProducto($idMaterial) {
        $sql = "SELECT id_cambio, id_material, estado_anterior, estado_nuevo, evento, fecha, ci_responsable, id_solicitud, motivo 
            FROM historial_estado_material
            WHERE id_material = ? 
            ORDER BY fecha DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$idMaterial]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el reporte de stock actual y calcula los materiales comprometidos.
     */
    public function obtenerReporteStock() {
        $st = $this->conexion->query(
            "SELECT nombre, 
                    SUM(cantidad_total) AS cantidad_total, 
                    SUM(cantidad_disponible) AS cantidad_disponible,
                    MIN(tipo_control) AS tipo_control
            FROM material 
            GROUP BY nombre 
            ORDER BY nombre ASC"
        );
        $materiales = $st->fetchAll(PDO::FETCH_ASSOC);

        foreach ($materiales as &$mat) {
            $nombreMat = $mat['nombre'];
            $tipoControl = $mat['tipo_control'];

            $mat['series_aprobadas'] = [];
            $mat['series_prestadas'] = [];

            if ($tipoControl === 'SERIE') {
                // --- APROBADOS ---
                $stAprobados = $this->conexion->prepare(
                    "SELECT m.id_serie 
                    FROM material m
                    JOIN prestamo_material pm ON pm.id_material = m.id_material
                    JOIN solicitud s ON s.id_solicitud = pm.id_solicitud
                    WHERE m.nombre = ? AND s.estado = 'APROBADO' 
                    AND m.id_serie IS NOT NULL AND m.id_serie != ''"
                );
                $stAprobados->execute([$nombreMat]);
                $mat['series_aprobadas'] = $stAprobados->fetchAll(PDO::FETCH_COLUMN);

                // --- PRESTADOS (ACTIVOS) ---
                $stPrestados = $this->conexion->prepare(
                    "SELECT m.id_serie 
                    FROM material m
                    JOIN prestamo_material pm ON pm.id_material = m.id_material
                    JOIN solicitud s ON s.id_solicitud = pm.id_solicitud
                    WHERE m.nombre = ? AND s.estado = 'ACTIVO' 
                    AND m.id_serie IS NOT NULL AND m.id_serie != ''"
                );
                $stPrestados->execute([$nombreMat]);
                $mat['series_prestadas'] = $stPrestados->fetchAll(PDO::FETCH_COLUMN);

                $mat['count_aprobados'] = count($mat['series_aprobadas']);
                $mat['count_prestados'] = count($mat['series_prestadas']);
            } else {
                // Consumibles por cantidad
                $stCantAp = $this->conexion->prepare(
                    "SELECT SUM(pm.cantidad) 
                    FROM prestamo_material pm
                    JOIN material m ON m.id_material = pm.id_material
                    JOIN solicitud s ON s.id_solicitud = pm.id_solicitud
                    WHERE m.nombre = ? AND s.estado = 'APROBADO'"
                );
                $stCantAp->execute([$nombreMat]);
                $mat['count_aprobados'] = (int)($stCantAp->fetchColumn() ?: 0);

                $stCantPr = $this->conexion->prepare(
                    "SELECT SUM(pm.cantidad) 
                    FROM prestamo_material pm
                    JOIN material m ON m.id_material = pm.id_material
                    JOIN solicitud s ON s.id_solicitud = pm.id_solicitud
                    WHERE m.nombre = ? AND s.estado = 'ACTIVO'"
                );
                $stCantPr->execute([$nombreMat]);
                $mat['count_prestados'] = (int)($stCantPr->fetchColumn() ?: 0);
            }

            // Total comprometidos general
            $mat['comprometidos'] = $mat['count_aprobados'] + $mat['count_prestados'];
        }
        unset($mat);

        return $materiales;
    }
}
?>