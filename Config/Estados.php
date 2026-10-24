<?php

/** Este archivo define los estados de varias partes del código
 *  y las transiciones válidas entre ellos, para que no estén
 *  dispersos en el código y se pueda cambiar fácilmente en un solo lugar.
 * 
 * Esto sirve para mantener consistencia y evitar errores de lógica.
 * Ayuda a que los modelos y controladores puedan validar transiciones de estado sin repetir la lógica,
 * ni hacer cambios de estados sin pasar por aquí antes.
 */

// ESTADOS DE MATERIAL

/** Error de regla de negocio: se muestra tal cual al usuario. */
class ErrorNegocio extends Exception {
    /** @var string[] */
    public $errores;

    public function __construct($mensaje, array $errores = []) {
        parent::__construct($mensaje);
        $this->errores = $errores;
    }
}

final class EstadoMaterial {
    const DISPONIBLE       = 'DISPONIBLE';
    const PRESTADO         = 'PRESTADO';
    const EN_MANTENIMIENTO = 'EN_MANTENIMIENTO';
    const DADO_DE_BAJA     = 'DADO_DE_BAJA';

    const TODOS = [self::DISPONIBLE, self::PRESTADO, self::EN_MANTENIMIENTO, self::DADO_DE_BAJA];

    // Eventos                                            Quien se encarga de realizarlos:
    const ENTREGAR             = 'ENTREGAR';              // sistema, al entregar un préstamo
    const DEVOLVER             = 'DEVOLVER';              // sistema, devolución sin daños
    const DEVOLVER_CON_DANO    = 'DEVOLVER_CON_DANO';     // sistema, devolución con daños
    const ENVIAR_MANTENIMIENTO = 'ENVIAR_MANTENIMIENTO';  // administrador
    const REINTEGRAR           = 'REINTEGRAR';            // administrador
    const DAR_DE_BAJA          = 'DAR_DE_BAJA';           // administrador

    /** Eventos que el administrador puede disparar a mano desde «Gestionar Productos». */
    const EVENTOS_MANUALES = [self::ENVIAR_MANTENIMIENTO, self::REINTEGRAR, self::DAR_DE_BAJA];

    /** [estado origen][evento] => estado destino o tambien llamado estado nuevo */
    const TRANSICIONES = [
        self::DISPONIBLE => [
            self::ENTREGAR             => self::PRESTADO,
            self::ENVIAR_MANTENIMIENTO => self::EN_MANTENIMIENTO,
            self::DAR_DE_BAJA          => self::DADO_DE_BAJA,
        ],
        self::PRESTADO => [
            self::DEVOLVER             => self::DISPONIBLE,
            self::DEVOLVER_CON_DANO    => self::EN_MANTENIMIENTO,
        ],
        self::EN_MANTENIMIENTO => [
            self::REINTEGRAR           => self::DISPONIBLE,
            self::DAR_DE_BAJA          => self::DADO_DE_BAJA,
        ],
        self::DADO_DE_BAJA => [],  // estado final
    ];
    /** Etiquetas legibles para mostrar al usuario */
    const ETIQUETAS = [
        self::DISPONIBLE       => 'Disponible',
        self::PRESTADO         => 'Prestado',
        self::EN_MANTENIMIENTO => 'En mantenimiento',
        self::DADO_DE_BAJA     => 'Dado de baja',
    ];

    /** Devuelve el estado al que lleva el evento, o lanza ErrorNegocio si no se permite. */
    public static function destino($estadoActual, $evento, $descripcion = 'El material') {
        if (!isset(self::TRANSICIONES[$estadoActual])) {
            throw new ErrorNegocio("Estado de material desconocido: $estadoActual");
        }
        if (!isset(self::TRANSICIONES[$estadoActual][$evento])) {
            throw new ErrorNegocio(sprintf(
                '%s está en estado «%s»: no se puede %s.',
                $descripcion,
                self::ETIQUETAS[$estadoActual],
                self::verbo($evento)
            ));
        }
        return self::TRANSICIONES[$estadoActual][$evento];
    }

    public static function verbo($evento) {
        switch ($evento) {
            case self::ENTREGAR:             return 'prestar';
            case self::DEVOLVER:
            case self::DEVOLVER_CON_DANO:    return 'devolver';
            case self::ENVIAR_MANTENIMIENTO: return 'enviar a mantenimiento';
            case self::REINTEGRAR:           return 'reintegrar al servicio';
            case self::DAR_DE_BAJA:          return 'dar de baja';
            default:                         return strtolower($evento);
        }
    }
}

final class EstadoSolicitud {
    const PENDIENTE  = 'PENDIENTE';
    const APROBADO   = 'APROBADO';
    const RECHAZADO  = 'RECHAZADO';
    const ACTIVO     = 'ACTIVO';
    const FINALIZADO = 'FINALIZADO';
    const CANCELADO  = 'CANCELADO';

    const TODOS = [self::PENDIENTE, self::APROBADO, self::RECHAZADO, self::ACTIVO, self::FINALIZADO, self::CANCELADO];

    const APROBAR  = 'APROBAR';
    const RECHAZAR = 'RECHAZAR';
    const ENTREGAR = 'ENTREGAR';
    const FINALIZAR = 'FINALIZAR';
    const CANCELAR = 'CANCELAR';

    const TRANSICIONES = [
        self::PENDIENTE  => [self::APROBAR => self::APROBADO, self::RECHAZAR => self::RECHAZADO, self::CANCELAR => self::CANCELADO],
        self::APROBADO   => [self::ENTREGAR => self::ACTIVO, self::CANCELAR => self::CANCELADO],
        self::ACTIVO     => [self::FINALIZAR => self::FINALIZADO],
        self::RECHAZADO  => [],
        self::FINALIZADO => [],
        self::CANCELADO  => [],
    ];

    const ETIQUETAS = [
        self::PENDIENTE  => 'pendiente',
        self::APROBADO   => 'aprobada',
        self::RECHAZADO  => 'rechazada',
        self::ACTIVO     => 'activa (ya entregada)',
        self::FINALIZADO => 'finalizada',
        self::CANCELADO  => 'cancelada',
    ];

    public static function destino($estadoActual, $evento) {
        if (!isset(self::TRANSICIONES[$estadoActual][$evento])) {
            throw new ErrorNegocio(sprintf(
                'La solicitud está %s: no se puede %s.',
                self::ETIQUETAS[$estadoActual] ?? strtolower($estadoActual),
                strtolower($evento)
            ));
        }
        return self::TRANSICIONES[$estadoActual][$evento];
    }
}

final class HistorialEstado {
    /**
     * Registra un cambio de estado de un material en historial_estado_material.
     * Es estática y recibe la conexión PDO: así la pueden usar tanto Modelo_Prestamo
     * como Modelo_Producto con la misma conexión (y la misma transacción, si hay una abierta).
     */
    public static function registrarHistorialEstado(PDO $conexion, $idMaterial, $estadoAnterior, $estadoNuevo,
                                                    $evento, $ciResponsable, $idSolicitud = null, $motivo = null) {
        $sql = "INSERT INTO historial_estado_material
                    (id_material, estado_anterior, estado_nuevo, evento, fecha, ci_responsable, id_solicitud, motivo)
                VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
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