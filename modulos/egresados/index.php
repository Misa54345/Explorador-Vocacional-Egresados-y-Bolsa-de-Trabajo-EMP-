<?php
/**
 * MÓDULO EMP · EGRESADOS Y BOLSA DE TRABAJO · Pantalla pública
 * Explorador Vocacional UTBB — Equipo B-3
 *
 * Pantalla pública de vacantes: filtrado por carrera, ciudad y tipo de contratación.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../nucleo/comun.php';
require_once __DIR__ . '/includes/consultas.php';

// --- Entrada ---------------------------------------------------------------
$busqueda  = get('q');
$carreraId = entero(get('carrera'));
$ciudad    = get('ciudad');
$tipo      = get('tipo');
$pagina    = entero(get('pagina')) ?? 1;

// Al público solo se le muestran vacantes en estado 'publicada'
$resultado = emp_vacantes_listar($busqueda, 'publicada', $carreraId, $ciudad, $tipo, $pagina);

cabecera('Bolsa de Trabajo y Egresados', '', 'css/modulo.css');
?>

<section class="portada">
  <div class="contenedor">
    <h1>Bolsa de Trabajo y Egresados</h1>
    <p class="entradilla">Encuentra vacantes de empleo, prácticas profesionales y oportunidades laborales para egresados de la UTBB.</p>

    <div class="portada__acciones">
      <a class="boton boton--acento" href="egresados.php">Ver inserción laboral de egresados</a>

      <?php if (usuario() !== null && in_array(rol(), ['editor', 'coordinacion'], true)): ?>
        <a class="boton boton--secundario" href="admin/index.php">Panel de Administración (Empresas / Vinculación)</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="contenedor seccion">

  <?php pintar_mensajes(); ?>

  <div class="disposicion-filtros">

    <!-- Filtros -->
    <form class="panel" method="GET" action="">
      <h2>Filtrar vacantes</h2>

      <div class="campo">
        <label class="campo__etiqueta" for="q">Buscar</label>
        <input class="campo__control" type="search" id="q" name="q"
               value="<?= e($busqueda) ?>" placeholder="Puesto o empresa...">
      </div>

      <div class="campo">
        <label class="campo__etiqueta" for="carrera">Carrera</label>
        <select class="campo__control" id="carrera" name="carrera">
          <option value="">Todas las carreras</option>
          <?php foreach (carreras() as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $carreraId === (int)$c['id'] ? 'selected' : '' ?>>
              <?= e($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="campo">
        <label class="campo__etiqueta" for="ciudad">Ciudad</label>
        <input class="campo__control" type="text" id="ciudad" name="ciudad"
               value="<?= e($ciudad) ?>" placeholder="Ej. Bahía de Banderas">
      </div>

      <div class="campo">
        <label class="campo__etiqueta" for="tipo">Contratación</label>
        <select class="campo__control" id="tipo" name="tipo">
          <option value="">Todos los tipos</option>
          <?php foreach (EMP_TIPOS_CONTRATACION as $clave => $texto): ?>
            <option value="<?= e($clave) ?>" <?= $tipo === $clave ? 'selected' : '' ?>>
              <?= e($texto) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <button class="boton boton--primario boton--bloque" type="submit">Aplicar filtros</button>
      <?php if ($busqueda !== '' || $carreraId !== null || $ciudad !== '' || $tipo !== ''): ?>
        <p class="centrado mt-4 sin-margen"><a href="?">Quitar filtros</a></p>
      <?php endif; ?>
    </form>

    <!-- Resultados -->
    <div>
      <p class="leyenda">
        <?= $resultado['total'] ?> vacante<?= $resultado['total'] === 1 ? '' : 's' ?> disponible<?= $resultado['total'] === 1 ? '' : 's' ?>
      </p>

      <?php if (!$resultado['filas']): ?>

        <div class="vacio">
          <h3>No se encontraron vacantes</h3>
          <p class="ancho-lectura">Prueba con otra búsqueda o quita los filtros aplicados para ver toda la oferta laboral.</p>
          <a class="boton boton--secundario" href="?">Quitar filtros</a>
        </div>

      <?php else: ?>

        <div class="rejilla">
          <?php foreach ($resultado['filas'] as $f): ?>
            <a class="tarjeta tarjeta--area <?= e(clase_area($f['area'])) ?>"
               href="ficha.php?id=<?= (int)$f['id'] ?>">

              <div class="tarjeta__cuerpo">
                <span class="leyenda"><?= e($f['empresa']) ?></span>
                <h3 class="tarjeta__titulo"><?= e($f['titulo']) ?></h3>
                <p class="leyenda"><strong>Carrera:</strong> <?= e($f['carrera']) ?></p>
                <p class="leyenda">📍 <?= e($f['ciudad']) ?> &middot; ⏱️ <?= e($f['tipo_contratacion']) ?></p>
                <p><?= e(mb_strimwidth($f['descripcion'], 0, 140, '...')) ?></p>
              </div>

              <div class="tarjeta__pie">
                <span class="<?= e(emp_clase_estado_vacante($f['estado'])) ?>">
                  <?= e(EMP_ESTADOS_VACANTE[$f['estado']]) ?>
                </span>
                <span>Ver vacante &rarr;</span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>

        <?php if ($resultado['paginas'] > 1): ?>
          <nav aria-label="Paginación">
            <ul class="paginacion">
              <?php
              $base = http_build_query(array_filter([
                  'q'       => $busqueda,
                  'carrera' => $carreraId,
                  'ciudad'  => $ciudad,
                  'tipo'    => $tipo,
              ]));
              for ($p = 1; $p <= $resultado['paginas']; $p++):
              ?>
                <li>
                  <?php if ($p === $resultado['pagina']): ?>
                    <span aria-current="page"><?= $p ?></span>
                  <?php else: ?>
                    <a href="?<?= $base ? $base . '&amp;' : '' ?>pagina=<?= $p ?>"><?= $p ?></a>
                  <?php endif; ?>
                </li>
              <?php endfor; ?>
            </ul>
          </nav>
        <?php endif; ?>

      <?php endif; ?>
    </div>

  </div>
</section>

<?php
pie();