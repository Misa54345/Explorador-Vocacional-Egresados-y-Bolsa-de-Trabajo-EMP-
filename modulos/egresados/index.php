<?php
/**
 * MÓDULO EMP · EGRESADOS Y BOLSA DE TRABAJO · Pantalla pública
 * Explorador Vocacional UTBB — Equipo B-3
 *
 * Pantalla pública de vacantes: filtrado por carrera, ciudad y tipo de contratación.
 */

declare(strict_types=1); // tipado de datos estricto, para que PHP lance errores si se usan tipos incorrectos

require_once __DIR__ . '/../../nucleo/comun.php';
require_once __DIR__ . '/includes/consultas.php';

// --- Entrada, esto hace para poder recibir la informacion
$busqueda  = get('q');
$carreraId = entero(get('carrera'));
$ciudad    = get('ciudad');
$tipo      = get('tipo');
$pagina    = entero(get('pagina')) ?? 1; // el 1 hace que por defecto sea la primera página si no se pasa el parámetro

// Esto hace que el usuario solo vea las vacantes que esten publicadas, 
$resultado = emp_vacantes_listar($busqueda, 'publicada', $carreraId, $ciudad, $tipo, $pagina);

cabecera('Bolsa de Trabajo y Egresados', '', 'css/modulo.css'); // llama a la funcion de bolsa de trabajo y egresados 
?>

<section class="portada">
  <div class="contenedor">
    <h1>Bolsa de Trabajo y Egresados</h1>
    <p class="entradilla">Encuentra vacantes de empleo, prácticas profesionales y oportunidades laborales para egresados de la UTBB.</p>

    <div class="portada__acciones">
      <a class="boton boton--acento" href="egresados.php">Ver inserción laboral de egresados</a>

      <?php if (usuario() !== null && rol() === 'aspirante'): ?>
        <a class="boton boton--secundario" href="mis-postulaciones.php">Mis Postulaciones</a>
      <?php elseif (usuario() !== null && in_array(rol(), ['editor', 'coordinacion'], true)): ?>
        <a class="boton boton--secundario" href="admin/index.php">Panel de Administración (Empresas / Vinculación)</a>
      <?php endif; ?> <!-- evalua si el usuario inicio sesion: si es aspirante muestra Mis Postulaciones, y si es editor o coordinacion muestra el panel -->
    </div>
  </div>
</section>

<section class="contenedor seccion">

  <?php pintar_mensajes(); ?> <!-- imprime alertas de exito o de error -->

  <div class="disposicion-filtros"> <!-- sirve para que se divida la pantalla en dos columnas , izquierda filtro, y derecha las tarjetas -->

    <!-- Filtros -->
    <form class="panel" method="GET" action=""> <!-- permite compartir el enlace o recargar sin errores  -->
      <h2>Filtrar vacantes</h2>

      <div class="campo">
        <label class="campo__etiqueta" for="q">Buscar</label>
        <input class="campo__control" type="search" id="q" name="q"
               value="<?= e($busqueda) ?>" placeholder="Puesto o empresa..."> <!-- funcion de seguridad del nucleo, limpia lo que escribio el usuario -->
      </div>

      <div class="campo">
        <label class="campo__etiqueta" for="carrera">Carrera</label>
        <select class="campo__control" id="carrera" name="carrera">
          <option value="">Todas las carreras</option>
          <?php foreach (carreras() as $c): ?> <!--llama la funcion carreras, y las recorre para crear las opciones   -->
            <option value="<?= (int)$c['id'] ?>" <?= $carreraId === (int)$c['id'] ? 'selected' : '' ?>> <!--compara el id, con el filtro elegido, si coincide imprime selected   -->
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
          <?php foreach (EMP_TIPOS_CONTRATACION as $clave => $texto): ?> <!-- recorre los tipos de contratacion, tiempo completo, medio tiempo, practicas, etc -->
            <option value="<?= e($clave) ?>" <?= $tipo === $clave ? 'selected' : '' ?>>
              <?= e($texto) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <button class="boton boton--primario boton--bloque" type="submit">Aplicar filtros</button>
      <?php if ($busqueda !== '' || $carreraId !== null || $ciudad !== '' || $tipo !== ''): ?>
        <p class="centrado mt-4 sin-margen"><a href="?">Quitar filtros</a></p> <!-- si el usuario tiene un filtro activo muestra el enlace para limpiar el URL  -->
      <?php endif; ?>
    </form>

    <!-- Resultados -->
    <div>
      <p class="leyenda">
        <?= $resultado['total'] ?> vacante<?= $resultado['total'] === 1 ? '' : 's' ?> disponible<?= $resultado['total'] === 1 ? '' : 's' ?>
      <!--si resultado es 1 muestra 1 vacante, si es 2 o mas muestra 2 vacantes se agrega la "s"  -->
      </p>

      <?php if (!$resultado['filas']): ?> <!--si devuelve un arreglo vacio, muestra que no hay resultados disponibles  -->

        <div class="vacio">
          <h3>No se encontraron vacantes</h3>
          <p class="ancho-lectura">Prueba con otra búsqueda o quita los filtros aplicados para ver toda la oferta laboral.</p>
          <a class="boton boton--secundario" href="?">Quitar filtros</a>
        </div>

      <?php else: ?> <!--si si hay vacantes, entra a el bloque de dibuja las tarjetas  -->

        <div class="rejilla">
          <?php foreach ($resultado['filas'] as $f): ?> <!--recorre cada vacante encontrada  -->
            <a class="tarjeta tarjeta--area <?= e(clase_area($f['area'])) ?>"
               href="ficha.php?id=<?= (int)$f['id'] ?>"> <!-- funcion que asigna el borde de color superior dependiendo la carrera y que tambien sirve para que toda la tarjeta sea un enlace a ficha.php-->

              <div class="tarjeta__cuerpo">
                <span class="leyenda"><?= e($f['empresa']) ?></span>
                <h3 class="tarjeta__titulo"><?= e($f['titulo']) ?></h3>
                <p class="leyenda"><strong>Carrera:</strong> <?= e($f['carrera']) ?></p>
                <p class="leyenda">📍 <?= e($f['ciudad']) ?> &middot; ⏱️ <?= e($f['tipo_contratacion']) ?></p>
                <p><?= e(mb_strimwidth($f['descripcion'], 0, 140, '...')) ?></p>
                <!--recorta la descripcion, y agrega puntos suspensivos  -->
              </div>

              <div class="tarjeta__pie">
                <span class="<?= e(emp_clase_estado_vacante($f['estado'])) ?>"> <!--devuelve la clases CSS verde para el 'publicado'  -->
                  <?= e(EMP_ESTADOS_VACANTE[$f['estado']]) ?>
                </span>
                <span>Ver vacante &rarr;</span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>

        <?php if ($resultado['paginas'] > 1): ?> <!--Muestra los botones de paginas, 1,2,3 si hay mas de 10 vacantes en total  -->
          <nav aria-label="Paginación">
            <ul class="paginacion">
              <?php
              $base = http_build_query(array_filter([
                  'q'       => $busqueda,
                  'carrera' => $carreraId,
                  'ciudad'  => $ciudad,
                  'tipo'    => $tipo,
              ])); // esto sirve para que no se pierdan los filtros al momento de cambiar de pagina
              for ($p = 1; $p <= $resultado['paginas']; $p++): // genera un boton por cada pagina
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
pie(); //llama a la funcion del nucle que dibuja el pie de pagina y cierra las etiquetas body y html