<?php
/**
 * MÓDULO EMP · Egresados y Bolsa de Trabajo · Gestión de Postulantes
 * Explorador Vocacional UTBB — Equipo B-3
 *
 * Lista los aspirantes postulados a una vacante, permite descargar su CV en PDF
 * de forma restringida y gestionar el estado de su postulación.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../nucleo/comun.php';

// Criterio #11: Verificación de rol en la primera instrucción ejecutable
requerir_rol('editor', 'coordinacion');

require_once __DIR__ . '/../includes/consultas.php';

// 1. Obtener y validar la vacante
$vacanteId = entero(get('vacante_id'));
$vacante   = $vacanteId !== null ? emp_vacante_obtener($vacanteId) : null;

if ($vacante === null) {
    no_encontrado('La vacante solicitada no existe.');
}

// 2. Evitar IDOR: Verificar que la vacante pertenezca a la empresa logueada (o sea coordinación)
emp_verificar_propiedad_vacante($vacante);

// 3. Procesar cambio de estado del postulante (POST-Redirect-GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_token();

    $postulacionId = entero(post('postulacion_id'));
    $nuevoEstado   = post('nuevo_estado');
    $postulacion   = $postulacionId !== null ? emp_postulacion_obtener($postulacionId) : null;

    // Verificar que la postulación exista, pertenezca a esta vacante y el estado sea válido
    if (
        $postulacion !== null
        && (int)$postulacion['vacante_id'] === (int)$vacante['id']
        && array_key_exists($nuevoEstado, EMP_ESTADOS_POSTULACION)
    ) {
        emp_postulacion_cambiar_estado($postulacionId, $nuevoEstado);
        mensaje('exito', 'Estado del postulante actualizado a "' . EMP_ESTADOS_POSTULACION[$nuevoEstado] . '".');
    } else {
        mensaje('error', 'No se pudo actualizar el estado de la postulación.');
    }

    redirigir('postulantes.php?vacante_id=' . (int)$vacante['id']);
}

$postulaciones = emp_postulaciones_por_vacante((int)$vacante['id']);

cabecera('Postulantes · ' . $vacante['titulo'], '', '../css/modulo.css');
?>

<section class="contenedor seccion">

  <nav aria-label="Ruta">
    <ol class="migas">
      <li><a href="<?= RUTA_BASE ?>/panel.php">Panel</a></li>
      <li><a href="index.php">Vacantes</a></li>
      <li>Postulantes</li>
    </ol>
  </nav>

  <div class="fila fila--separada mb-6">
    <div>
      <span class="leyenda"><?= e($vacante['empresa']) ?> &middot; <?= e($vacante['carrera']) ?></span>
      <h1 class="sin-margen">Postulantes: <?= e($vacante['titulo']) ?></h1>
    </div>
    <a class="boton boton--secundario" href="index.php">&larr; Volver a vacantes</a>
  </div>

  <?php pintar_mensajes(); ?>

  <?php if (!$postulaciones): ?>

    <div class="vacio">
      <h3>Aún no hay postulantes</h3>
      <p class="ancho-lectura">Cuando los egresados o aspirantes envíen su currículum para esta vacante aparecerán aquí.</p>
      <a class="boton boton--secundario" href="index.php">Volver al listado</a>
    </div>

  <?php else: ?>

    <div class="tabla-envoltura">
      <table class="tabla">
        <caption><?= count($postulaciones) ?> postulación<?= count($postulaciones) === 1 ? '' : 'es' ?> recibida<?= count($postulaciones) === 1 ? '' : 's' ?></caption>
        <thead>
          <tr>
            <th scope="col">Aspirante / Egresado</th>
            <th scope="col">Correo</th>
            <th scope="col">Fecha de postulación</th>
            <th scope="col">Currículum (PDF)</th>
            <th scope="col">Estado actual</th>
            <th scope="col">Actualizar estado</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($postulaciones as $p): ?>
            <tr>
              <td><strong><?= e($p['aspirante_nombre']) ?></strong></td>
              <td><?= e($p['aspirante_correo']) ?></td>
              <td><?= e(date('d/m/Y H:i', strtotime($p['creado_en']))) ?></td>
              <td>
                <a class="boton boton--secundario boton--pequeno"
                   href="../descargar-cv.php?id=<?= (int)$p['id'] ?>"
                   target="_blank"
                   rel="noopener">
                  Ver / Descargar CV
                </a>
              </td>
              <td>
                <span class="<?= e(emp_clase_estado_postulacion($p['estado'])) ?>">
                  <?= e(EMP_ESTADOS_POSTULACION[$p['estado']]) ?>
                </span>
              </td>
              <td>
                <form class="tabla__acciones" method="POST" action="">
                  <?= campo_token() ?>
                  <input type="hidden" name="postulacion_id" value="<?= (int)$p['id'] ?>">

                  <select class="campo__control" name="nuevo_estado" aria-label="Cambiar estado de <?= e($p['aspirante_nombre']) ?>" style="min-width: 150px; padding: 0.35rem 0.5rem;">
                    <?php foreach (EMP_ESTADOS_POSTULACION as $clave => $etiqueta): ?>
                      <option value="<?= e($clave) ?>" <?= $p['estado'] === $clave ? 'selected' : '' ?>>
                        <?= e($etiqueta) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>

                  <button class="boton boton--primario boton--pequeno" type="submit">Guardar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  <?php endif; ?>

</section>

<?php
pie();