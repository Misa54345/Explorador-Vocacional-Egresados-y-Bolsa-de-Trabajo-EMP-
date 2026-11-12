<?php
/**
 * MÓDULO EMP · EGRESADOS Y BOLSA DE TRABAJO · Consultas
 * Explorador Vocacional UTBB — Equipo B-3
 *
 * Consultas preparadas para vacantes, empresas, postulaciones y egresados.
 * Ningún dato variable se concatena a la cadena SQL.
 */

declare(strict_types=1);

const EMP_POR_PAGINA = 10;

/** Estados válidos para las vacantes */
const EMP_ESTADOS_VACANTE = [
    'borrador'  => 'Borrador',
    'publicada' => 'Publicada',
    'cerrada'   => 'Cerrada',
];

/** Estados válidos para las postulaciones */
const EMP_ESTADOS_POSTULACION = [
    'enviada'         => 'Enviada',
    'en proceso'      => 'En proceso',
    'seleccionado'    => 'Seleccionado',
    'no seleccionado' => 'No seleccionado',
];

/** Tipos de contratación válidos */
const EMP_TIPOS_CONTRATACION = [
    'Tiempo Completo' => 'Tiempo Completo',
    'Medio Tiempo'    => 'Medio Tiempo',
    'Prácticas'       => 'Prácticas',
    'Temporal'        => 'Temporal',
];

/** Insignias de diseño según el estado de la vacante */
function emp_clase_estado_vacante(string $estado): string
{
    return match ($estado) {
        'publicada' => 'insignia insignia--exito',
        'cerrada'   => 'insignia insignia--peligro',
        default     => 'insignia insignia--neutral',
    };
}

/** Insignias de diseño según el estado de la postulación */
function emp_clase_estado_postulacion(string $estado): string
{
    return match ($estado) {
        'seleccionado'    => 'insignia insignia--exito',
        'en proceso'      => 'insignia insignia--alerta',
        'no seleccionado' => 'insignia insignia--peligro',
        default           => 'insignia insignia--info',
    };
}

/* =========================================================================
 * 1. CONSULTAS DE VACANTES (Pública y Administración)
 * ========================================================================= */

/** Lista paginada de vacantes con filtros de búsqueda, carrera, ciudad y tipo */
function emp_vacantes_listar(
    string $busqueda = '',
    string $estado = '',
    ?int $carreraId = null,
    string $ciudad = '',
    string $tipoContratacion = '',
    int $pagina = 1
): array {
    $condiciones = [];
    $valores     = [];

    if ($busqueda !== '') {
        $condiciones[] = '(v.titulo LIKE ? OR v.descripcion LIKE ? OR emp.nombre LIKE ?)';
        $comodin = '%' . $busqueda . '%';
        $valores[] = $comodin;
        $valores[] = $comodin;
        $valores[] = $comodin;
    }

    if ($estado !== '' && array_key_exists($estado, EMP_ESTADOS_VACANTE)) {
        $condiciones[] = 'v.estado = ?';
        $valores[]     = $estado;
    }

    if ($carreraId !== null) {
        $condiciones[] = 'v.carrera_id = ?';
        $valores[]     = $carreraId;
    }

    if ($ciudad !== '') {
        $condiciones[] = 'v.ciudad = ?';
        $valores[]     = $ciudad;
    }

    if ($tipoContratacion !== '' && array_key_exists($tipoContratacion, EMP_TIPOS_CONTRATACION)) {
        $condiciones[] = 'v.tipo_contratacion = ?';
        $valores[]     = $tipoContratacion;
    }

    $donde = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

    $con = bd()->prepare("SELECT COUNT(*) 
                            FROM emp_vacantes v 
                            JOIN emp_empresas emp ON emp.id = v.empresa_id 
                         $donde");
    $con->execute($valores);
    $total = (int)$con->fetchColumn();

    $pagina = max(1, $pagina);
    $desde  = ($pagina - 1) * EMP_POR_PAGINA;
    $limite = EMP_POR_PAGINA;

    $sql = "SELECT v.id, v.titulo, v.descripcion, v.ciudad, v.tipo_contratacion, v.estado, v.actualizado_en,
                   c.nombre AS carrera, c.area,
                   emp.nombre AS empresa, emp.correo AS empresa_correo
              FROM emp_vacantes v
              JOIN emp_empresas emp ON emp.id = v.empresa_id
              JOIN carreras c ON c.id = v.carrera_id
              $donde
          ORDER BY v.actualizado_en DESC
             LIMIT $limite OFFSET $desde";

    $con = bd()->prepare($sql);
    $con->execute($valores);

    return [
        'filas'   => $con->fetchAll(),
        'total'   => $total,
        'pagina'  => $pagina,
        'paginas' => (int)ceil($total / EMP_POR_PAGINA),
    ];
}

/** Obtiene el detalle completo de una vacante */
function emp_vacante_obtener(int $id): ?array
{
    $con = bd()->prepare(
        'SELECT v.*, c.nombre AS carrera, c.area,
                emp.nombre AS empresa, emp.contacto_nombre, emp.correo AS empresa_correo, emp.telefono AS empresa_telefono
           FROM emp_vacantes v
           JOIN emp_empresas emp ON emp.id = v.empresa_id
           JOIN carreras c ON c.id = v.carrera_id
          WHERE v.id = ?'
    );
    $con->execute([$id]);

    return $con->fetch() ?: null;
}

/** Alta de vacante */
function emp_vacante_crear(array $datos): int
{
    $con = bd()->prepare(
        'INSERT INTO emp_vacantes (empresa_id, carrera_id, titulo, descripcion, ciudad, tipo_contratacion, estado)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    $con->execute([
        $datos['empresa_id'],
        $datos['carrera_id'],
        $datos['titulo'],
        $datos['descripcion'],
        $datos['ciudad'],
        $datos['tipo_contratacion'],
        $datos['estado'] ?? 'borrador',
    ]);

    return (int)bd()->lastInsertId();
}

/** Modificación de vacante */
function emp_vacante_actualizar(int $id, array $datos): void
{
    $con = bd()->prepare(
        'UPDATE emp_vacantes
            SET carrera_id = ?, titulo = ?, descripcion = ?, ciudad = ?, tipo_contratacion = ?
          WHERE id = ?'
    );

    $con->execute([
        $datos['carrera_id'],
        $datos['titulo'],
        $datos['descripcion'],
        $datos['ciudad'],
        $datos['tipo_contratacion'],
        $id,
    ]);
}

/** Cambio de estado de la vacante (borrador -> publicada -> cerrada) */
function emp_vacante_cambiar_estado(int $id, string $nuevoEstado): void
{
    if (!array_key_exists($nuevoEstado, EMP_ESTADOS_VACANTE)) {
        throw new InvalidArgumentException('Estado de vacante no válido.');
    }

    $con = bd()->prepare('UPDATE emp_vacantes SET estado = ? WHERE id = ?');
    $con->execute([$nuevoEstado, $id]);
}

/** Elimina una vacante */
function emp_vacante_eliminar(int $id): void
{
    $con = bd()->prepare('DELETE FROM emp_vacantes WHERE id = ?');
    $con->execute([$id]);
}

/* =========================================================================
 * 2. POSTULACIONES Y MANEJO DE CV (PDF)
 * ========================================================================= */

/** Registra la postulación de un estudiante a una vacante con su archivo CV en PDF */
function emp_postulacion_crear(int $vacanteId, int $aspiranteId, string $archivoCv): int
{
    $con = bd()->prepare(
        'INSERT INTO emp_postulaciones (vacante_id, aspirante_id, archivo_cv, estado)
         VALUES (?, ?, ?, "enviada")'
    );

    $con->execute([$vacanteId, $aspiranteId, $archivoCv]);

    return (int)bd()->lastInsertId();
}

/** Lista postulaciones recibidas para una vacante específica (para el panel de la Empresa) */
function emp_postulaciones_por_vacante(int $vacanteId): array
{
    $con = bd()->prepare(
        'SELECT p.*, u.nombre AS aspirante_nombre, u.correo AS aspirante_correo
           FROM emp_postulaciones p
           JOIN usuarios u ON u.id = p.aspirante_id
          WHERE p.vacante_id = ?
       ORDER BY p.creado_en DESC'
    );
    $con->execute([$vacanteId]);

    return $con->fetchAll();
}

/** Cambia el estado del postulante (enviada -> en proceso -> seleccionado/no seleccionado) */
function emp_postulacion_cambiar_estado(int $postulacionId, string $nuevoEstado): void
{
    if (!array_key_exists($nuevoEstado, EMP_ESTADOS_POSTULACION)) {
        throw new InvalidArgumentException('Estado de postulación no válido.');
    }

    $con = bd()->prepare('UPDATE emp_postulaciones SET estado = ? WHERE id = ?');
    $con->execute([$postulacionId, $id]);
}

/** Obtiene una postulación por id (utilizado para verificar permisos de descarga de PDF) */
function emp_postulacion_obtener(int $id): ?array
{
    $con = bd()->prepare(
        'SELECT p.*, v.empresa_id
           FROM emp_postulaciones p
           JOIN emp_vacantes v ON v.id = p.vacante_id
          WHERE p.id = ?'
    );
    $con->execute([$id]);

    return $con->fetch() ?: null;
}

/* =========================================================================
 * 3. SECCIÓN DE INSERCIÓN LABORAL / EGRESADOS
 * ========================================================================= */

/** Lista los egresados de una carrera con su información de empleo */
function emp_egresados_por_carrera(int $carreraId): array
{
    $con = bd()->prepare(
        'SELECT e.*, u.nombre AS egresado_nombre, c.nombre AS carrera
           FROM emp_egresados e
           JOIN usuarios u ON u.id = e.usuario_id
           JOIN carreras c ON c.id = e.carrera_id
          WHERE e.carrera_id = ?
       ORDER BY e.anio_egreso DESC'
    );
    $con->execute([$carreraId]);

    return $con->fetchAll();
}