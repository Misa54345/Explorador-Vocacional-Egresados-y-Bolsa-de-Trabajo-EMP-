<?php
/**
 * MÓDULO EMP · Descarga restringida de Currículum en PDF
 * Explorador Vocacional UTBB — Equipo B-3
 */

declare(strict_types=1);

require_once __DIR__ . '/../../nucleo/comun.php';

// Verifica rol en la primera instrucción ejecutable (Criterio #11)
requerir_rol('editor', 'coordinacion');
// si alguien intenta acceder a este archivo sin haber iniciado sesion
// el nucleo detiene la ejecucion y le manda un error

require_once __DIR__ . '/includes/consultas.php';

$postulacionId = entero(get('id')); // lee el parametro de la url y lo convierte a entero
if ($postulacionId === null) {
    no_encontrado('Solicitud inválida.');
}

$postulacion = emp_postulacion_obtener($postulacionId);
if ($postulacion === null) {
    no_encontrado('La postulación no existe.');
}

// Verifica que el usuario sea Coordinación o la Empresa dueña de la vacante (Criterio #15)
if (rol() !== 'coordinacion' && (int)$postulacion['empresa_usuario_id'] !== (int)usuario()['id']) {
    http_response_code(403);
    exit('Acceso denegado: no tienes permisos para descargar este currículum.');
}

$nombreArchivo = basename($postulacion['archivo_cv']);
$rutaArchivo   = __DIR__ . '/uploads/' . $nombreArchivo;

if (!is_file($rutaArchivo)) {
    no_encontrado('El archivo PDF no se encuentra en el servidor.');
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="CV_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $postulacion['vacante_titulo']) . '.pdf"');
header('Content-Length: ' . filesize($rutaArchivo));
header('Cache-Control: private, max-age=0, must-revalidate');

readfile($rutaArchivo);
exit;