<?php
require_once __DIR__ . '/../../Config/Conexion.php';

class Modelo_Producto {
    private $conexion;

    public function __construct() {
        $this->conexion = Conexion::conectar();
    }

    // Listar todos los productos
    public function obtenerProductos() {
        $sql = "SELECT * FROM material ORDER BY ID_Material DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener un producto por ID
    public function obtenerProductoPorID($id) {
        $sql = "SELECT * FROM material WHERE ID_Material = ?";
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
    public function guardarProducto($nombre, $descripcion, $categoria, $cantTotal, $cantDisp, $estado, $foto) {
        $sql = "INSERT INTO material (Nombre, Descripcion, Categoria, Cantidad_Total, Cantidad_Disponible, Estado, Foto_Material) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([$nombre, $descripcion, $categoria, $cantTotal, $cantDisp, $estado, $foto]);
    }

    // Actualizar producto existente
    public function actualizarProducto($id, $nombre, $descripcion, $categoria, $cantTotal, $cantDisp, $estado, $foto) {
        $sql = "UPDATE material 
                SET Nombre = ?, Descripcion = ?, Categoria = ?, Cantidad_Total = ?, Cantidad_Disponible = ?, Estado = ?, Foto_Material = ? 
                WHERE ID_Material = ?";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([$nombre, $descripcion, $categoria, $cantTotal, $cantDisp, $estado, $foto, $id]);
    }

    // Eliminar producto
    public function eliminarProducto($id) {
        $sql = "DELETE FROM material WHERE ID_Material = ?";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([$id]);
    }
}