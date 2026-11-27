<?php
/**
 * MÓDULO EMP · Egresados y Bolsa de Trabajo · Mis Postulaciones
 * Explorador Vocacional UTBB — Equipo B-3
 *
 * Pantalla privada exclusiva para alumnos/egresados con rol 'aspirante'.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../nucleo/comun.php';

// Solo alumnos con sesión iniciada y rol 'aspirante' pueden ver este apartado
requerir_rol('aspirante');

require_once __DIR__ . '/includes/consultas.php';

$postulaciones = emp_postulaciones_por_aspirante((int)usuario()['id']);

cabecera('Mis Postulaciones · Bolsa de Trabajo', '', 'css/modulo.css');
?>

<section class="contenedor seccion">

  <nav aria-label="Ruta">
    <ol class="migas">
      <li><a href="<?= RUTA_BASE ?>/index.php">Inicio</a></li>
      <li><a href="index.php">Bolsa de Trabajo</a></li>
      <li>Mis Postulaciones</li>
    </ol>
  </nav>

  <div class="fila fila--separada mb-6">
    <div>
      <h1 class="sin-margen">Mis Postulaciones</h1>
      <p class="leyenda sin-margen">Consulta el seguimiento y estatus de las vacantes a las que has enviado tu CV.</p>
    </div>
    <a class="boton boton--secundario" href="index.php">&larr; Volver a vacantes</a>
  </div>

  <?php pintar_mensajes(); ?>

  <?php if (!$postulaciones): ?>

    <div class="vacio">
      <h3>Aún no tienes postulaciones</h3>
      <p class="ancho-lectura">Explora las vacantes disponibles para tu carrera y postúlate adjuntando tu Currículum Vitae en PDF.</p>
      <a class="boton boton--primario" href="index.php">Ver vacantes disponibles</a>
    </div>

  <?php else: ?>

    <div class="tabla-envoltura">
      <table class="tabla">
        <caption><?= count($postulaciones) ?> postulación<?= count($postulaciones) === 1 ? '' : 'es' ?> registrada<?= count($postulaciones) === 1 ? '' : 's' ?></caption>
        <thead>
          <tr>
            <th scope="col">Vacante</th>
            <th scope="col">Empresa</th>
            <th scope="col">Ubicación / Modalidad</th>
            <th scope="col">Fecha de envío</th>
            <th scope="col">Estado</th>
            <th scope="col">Detalle</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($postulaciones as $post): ?>
            <tr>
              <td><strong><?= e($post['vacante_titulo']) ?></strong></td>
              <td><?= e($post['empresa']) ?></td>
              <td><?= e($post['ciudad']) ?> &middot; <small class="leyenda"><?= e($post['tipo_contratacion']) ?></small></td>
              <td><?= e(date('d/m/Y H:i', strtotime($post['creado_en']))) ?></td>
              <td>
                <span class="<?= e(emp_clase_estado_postulacion($post['estado'])) ?>">
                  <?= e(EMP_ESTADOS_POSTULACION[$post['estado']] ?? $post['estado']) ?>
                </span>
              </td>
              <td>
                <a class="boton boton--secundario boton--pequeno" href="ficha.php?id=<?= (int)$post['vacante_id'] ?>">Ver vacante</a>
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