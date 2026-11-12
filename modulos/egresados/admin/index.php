<?php
/**
 * MÓDULO EMP · Egresados y Bolsa de Trabajo · Panel de administración
 * Explorador Vocacional UTBB — Equipo B-3
 *
 * Verificación de rol en la PRIMERA instrucción ejecutable.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../nucleo/comun.php';

requerir_rol('editor', 'coordinacion');

require_once __DIR__ . '/../includes/consultas.php';

$busqueda         = get('q');
$estado           = get('estado');
$ciudad           = get('ciudad');
$tipoContratacion = get('tipo');

// Un editor solo administra la carrera que le corresponde
$carreraId = (rol() === 'editor') ? carrera_del_usuario() : entero(get('carrera'));

$pagina    = entero(get('pagina')) ?? 1;
$resultado = emp_vacantes_listar($busqueda, $estado, $carreraId, $ciudad, $tipoContratacion, $pagina);

cabecera('Panel · Bolsa de Trabajo (EMP)', '', '../css/modulo.css');
?>

<section class="contenedor seccion">

  <nav aria-label="Ruta">
    <ol class="migas">
      <li><a href="<?= RUTA_BASE ?>/panel.php">Panel</a></li>
      <li>Módulo EMP: Egresados y Bolsa de Trabajo</li>
    </ol>
  </nav>

  <div class="fila fila--separada mb-6">
    <h1 class="sin-margen">Administración de Vacantes</h1>
    <a class="boton boton--primario" href="editar.php">Nueva Vacante</a>
  </div>

  <?php pintar_mensajes(); ?>

  <form class="panel mb-6" method="GET" action="">
    <div class="fila">
      <div class="campo sin-margen" style="flex:1 1 200px">
        <label class="campo__etiqueta" for="q">Buscar</label>
        <input class="campo__control" type="search" id="q" name="q" value="<?= e($busqueda) ?>" placeholder="Título o empresa...">
      </div>

      <div class="campo sin-margen" style="flex:0 1 160px">
        <label class="campo__etiqueta" for="estado">Estado</label>
        <select class="campo__control" id="estado" name="estado">
          <option value="">Todos</option>
          <?php foreach (EMP_ESTADOS_VACANTE as $clave => $texto): ?>
            <option value="<?= e($clave) ?>" <?= $estado === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <button class="boton boton--secundario" type="submit">Filtrar</button>
    </div>
  </form>

  <?php if (!$resultado['filas']): ?>

    <div class="vacio">
      <h3>No hay vacantes registradas</h3>
      <p class="ancho-lectura">Cuando publiques la primera oferta aparecerá aquí.</p>
      <a class="boton boton--primario" href="editar.php">Crear la primera vacante</a>
    </div>

  <?php else: ?>

    <div class="tabla-envoltura">
      <table class="tabla">
        <caption><?= $resultado['total'] ?> vacante<?= $resultado['total'] === 1 ? '' : 's' ?> encontrada<?= $resultado['total'] === 1 ? '' : 's' ?></caption>
        <thead>
          <tr>
            <th scope="col">Título</th>
            <th scope="col">Empresa</th>
            <th scope="col">Carrera</th>
            <th scope="col">Ciudad</th>
            <th scope="col">Estado</th>
            <th scope="col">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($resultado['filas'] as $f): ?>
            <tr>
              <td><strong><?= e($f['titulo']) ?></strong></td>
              <td><?= e($f['empresa']) ?></td>
              <td><?= e($f['carrera']) ?></td>
              <td><?= e($f['ciudad']) ?></td>
              <td><span class="<?= e(emp_clase_estado_vacante($f['estado'])) ?>"><?= e(EMP_ESTADOS_VACANTE[$f['estado']]) ?></span></td>
              <td>
                <div class="tabla__acciones">
                  <a class="boton boton--secundario boton--pequeno" href="editar.php?id=<?= (int)$f['id'] ?>">Editar</a>

                  <form method="POST" action="eliminar.php"
                        onsubmit="return confirm('¿Eliminar esta vacante? La acción no se puede deshacer.')">
                    <?= campo_token() ?>
                    <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                    <button class="boton boton--peligro boton--pequeno" type="submit">Eliminar</button>
                  </form>
                </div>
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