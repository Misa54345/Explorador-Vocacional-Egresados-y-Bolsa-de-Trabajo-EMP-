<?php
/**
 * MÓDULO EMP · EGRESADOS Y BOLSA DE TRABAJO · Inserción Laboral
 * Explorador Vocacional UTBB — Equipo B-3
 *
 * Pantalla pública de seguimiento e inserción laboral de egresados por carrera.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../nucleo/comun.php';
require_once __DIR__ . '/includes/consultas.php';

$carreraId = entero(get('carrera'));
$egresados = emp_egresados_listar($carreraId);

cabecera('Inserción Laboral de Egresados · UTBB', '', 'css/modulo.css');
?>

<section class="portada">
  <div class="contenedor">
    <h1 style="color: #fff;">Inserción Laboral de Egresados</h1>
    <p class="entradilla">Conoce dónde trabajan nuestros egresados de la Universidad Tecnológica de Bahía de Banderas y el impacto profesional de cada carrera.</p>

    <div class="portada__acciones">
      <a class="boton boton--secundario" href="index.php">&larr; Volver a Bolsa de Trabajo</a>
    </div>
  </div>
</section>

<section class="contenedor seccion">

  <nav aria-label="Ruta">
    <ol class="migas">
      <li><a href="<?= RUTA_BASE ?>/index.php">Inicio</a></li>
      <li><a href="index.php">Bolsa de Trabajo</a></li>
      <li>Inserción Laboral</li>
    </ol>
  </nav>

  <?php pintar_mensajes(); ?>

  <div class="disposicion-filtros">

    <!-- Filtro por Carrera -->
    <form class="panel" method="GET" action="">
      <h2>Filtrar por carrera</h2>

      <div class="campo">
        <label class="campo__etiqueta" for="carrera">Programa educativo</label>
        <select class="campo__control" id="carrera" name="carrera">
          <option value="">Todas las carreras</option>
          <?php foreach (carreras() as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $carreraId === (int)$c['id'] ? 'selected' : '' ?>>
              <?= e($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <button class="boton boton--primario boton--bloque" type="submit">Filtrar</button>

      <?php if ($carreraId !== null): ?>
        <p class="centrado mt-4 sin-margen"><a href="egresados.php">Ver todas las carreras</a></p>
      <?php endif; ?>
    </form>

    <!-- Listado de Egresados -->
    <div>
      <p class="leyenda">
        <?= count($egresados) ?> egresado<?= count($egresados) === 1 ? '' : 's' ?> registrado<?= count($egresados) === 1 ? '' : 's' ?>
      </p>

      <?php if (!$egresados): ?>

        <div class="vacio">
          <h3>Sin registros en esta carrera</h3>
          <p class="ancho-lectura">Aún no hay casos de inserción laboral registrados para la carrera seleccionada.</p>
          <a class="boton boton--secundario" href="egresados.php">Ver todos los egresados</a>
        </div>

      <?php else: ?>

        <div class="rejilla">
          <?php foreach ($egresados as $eg): ?>
            <article class="tarjeta tarjeta--area <?= e(clase_area($eg['area'])) ?>">
              <div class="tarjeta__cuerpo">
                <span class="leyenda">Generación <?= e((string)$eg['anio_egreso']) ?></span>
                <h3 class="tarjeta__titulo"><?= e($eg['egresado_nombre']) ?></h3>
                <p class="leyenda"><strong>Carrera:</strong> <?= e($eg['carrera']) ?></p>

                <?php if (!empty($eg['puesto_actual'])): ?>
                  <p><strong>Puesto actual:</strong> <?= e($eg['puesto_actual']) ?></p>
                <?php endif; ?>

                <?php if (!empty($eg['empresa_actual'])): ?>
                  <p class="leyenda">🏢 <?= e($eg['empresa_actual']) ?></p>
                <?php endif; ?>
              </div>

              <div class="tarjeta__pie">
                <span class="insignia insignia--exito">Egresado UTBB</span>
                <span class="leyenda"><?= e($eg['area']) ?></span>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

      <?php endif; ?>
    </div>

  </div>
</section>

<?php
pie();