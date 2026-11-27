<?php
/**
 * MÓDULO EMP · EGRESADOS Y BOLSA DE TRABAJO · Baja de Vacantes
 * Explorador Vocacional UTBB — Equipo B-3
 *
 * Esta página NO imprime HTML: recibe la petición POST, borra la vacante y redirige.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../nucleo/comun.php';

// Control de acceso por rol verificado en el servidor
requerir_rol('editor', 'coordinacion');

require_once __DIR__ . '/../includes/consultas.php';

// 1. Solo se permite el método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

// 2. Verificar token anti-CSRF
verificar_token();

$id = entero(post('id'));

if ($id === null) {
    mensaje('error', 'No se especificó la vacante a eliminar.');
    redirigir('index.php');
}

// 3. Obtener la vacante y validar que existe
$registro = emp_vacante_obtener($id);

if ($registro === null) {
    mensaje('error', 'La vacante ya no existe.');
    redirigir('index.php');
}

// 4. Verificar pertenencia de carrera una empresa solo borra sus propias vacantes, coordinacion pasa siempr
emp_verificar_propiedad_vacante($registro);  

try {
    // 5. Ejecutar borrado seguro en la BD
    emp_vacante_eliminar($id);
    mensaje('exito', 'Vacante eliminada correctamente.');
} catch (PDOException $e) {
    error_log('Error al eliminar vacante: ' . $e->getMessage());
    mensaje('error', 'No se pudo eliminar la vacante debido a postulaciones asociadas.');
}

// 6. Redirección siguiendo el patrón POST-Redirect-GET
redirigir('index.php');