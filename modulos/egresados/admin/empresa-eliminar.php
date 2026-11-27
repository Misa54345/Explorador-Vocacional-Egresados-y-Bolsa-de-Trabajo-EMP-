<?php
/**
 * MÓDULO EMP · Egresados y Bolsa de Trabajo · Eliminación de Empresa
 * Explorador Vocacional UTBB — Equipo B-3
 *
 * Baja segura vía POST con validación de rol 'coordinacion' y token CSRF.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../nucleo/comun.php';

requerir_rol('coordinacion');

require_once __DIR__ . '/../includes/consultas.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('empresas.php');
}

verificar_token();

$id      = entero(post('id'));
$empresa = $id !== null ? emp_empresa_obtener($id) : null; 
//consulta para saber si esa empresa existe antes de intentar borrarla

if ($empresa === null) {
    mensaje('error', 'La empresa que intentas eliminar no existe.');
    redirigir('empresas.php');
} // si el id venia vacion o la empresa ya habia sido eliminada, guarda una notificacion
// y redirige a la lista de empresas

emp_empresa_eliminar((int)$empresa['id']); // ejecuta la eliminacion de la empresa 
mensaje('exito', 'Empresa "' . $empresa['nombre'] . '" eliminada correctamente.');
redirigir('empresas.php');
// si se borra la empresa, se borra tambien las vacantes y postulaciones asociadas