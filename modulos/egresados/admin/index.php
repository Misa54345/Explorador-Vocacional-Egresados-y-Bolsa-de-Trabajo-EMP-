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

// Un editor solo administra la empresa que le corresponde
$empresaId = null;
$carreraId = entero(get('carrera'));

if (rol() === 'editor') {
    $miEmpresa = emp_empresa_por_usuario((int)usuario()['id']);
    $empresaId = $miEmpresa ? (int)$miEmpresa['id'] : 0;
}

// Procesa cambio rápido de estado de la vacante
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    verificar_token();
    $vacId       = entero(post('vacante_id'));
    $nuevoEstado = post('nuevo_estado');
    $vacObj      = $vacId ? emp_vacante_obtener($vacId) : null;

    if ($vacObj !== null && array_key_exists($nuevoEstado, EMP_ESTADOS_VACANTE)) {
        emp_verificar_propiedad_vacante($vacObj);
        emp_vacante_cambiar_estado($vacId, $nuevoEstado);
        mensaje('exito', 'Estado de la vacante actualizado a ' . EMP_ESTADOS_VACANTE[$nuevoEstado] . '.');
    }
    redirigir('index.php');
}

$pagina      = entero(get('pagina')) ?? 1;
$resultado   = emp_vacantes_listar($busqueda, $estado, $carreraId, $ciudad, $tipoContratacion, $pagina, $empresaId);
$hayFiltros  = ($busqueda !== '' || $estado !== '');

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
    <div class="fila">
      <?php if (rol() === 'coordinacion'): ?> <!-- si el rol es coordinacion, muestra el boton de administrar empresas -->
        <a class="boton boton--secundario" href="empresas.php">Administrar Empresas</a>
      <?php endif; ?>
      <a class="boton boton--primario" href="editar.php">Nueva Vacante</a>
    </div>
  </div>

  <?php pintar_mensajes(); ?>

  <form class="panel mb-6" method="GET" action="">
    <div class="fila" style="align-items: flex-end;">
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

      <?php if ($hayFiltros): ?>
        <a class="boton boton--secundario" href="index.php">Limpiar</a>
      <?php endif; ?>
    </div>
  </form>

  <?php if (!$resultado['filas']): ?>

    <div class="vacio">
      <?php if ($hayFiltros): ?>
        <h3>Sin coincidencias</h3>
        <p class="ancho-lectura">No hay vacantes que coincidan con los filtros aplicados.</p>
        <a class="boton boton--secundario" href="index.php">Limpiar filtros</a>
      <?php else: ?>
        <h3>No hay vacantes registradas</h3>
        <p class="ancho-lectura">Cuando publiques la primera oferta aparecerá aquí.</p>
        <a class="boton boton--primario" href="editar.php">Crear la primera vacante</a>
      <?php endif; ?>
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
                  <a class="boton boton--primario boton--pequeno" href="postulantes.php?vacante_id=<?= (int)$f['id'] ?>">Postulantes</a>
                  <a class="boton boton--secundario boton--pequeno" href="editar.php?id=<?= (int)$f['id'] ?>">Editar</a>

                  <?php if ($f['estado'] === 'borrador'): ?>
                    <form method="POST" action="">
                      <?= campo_token() ?>
                      <input type="hidden" name="cambiar_estado" value="1">
                      <input type="hidden" name="vacante_id" value="<?= (int)$f['id'] ?>">
                      <input type="hidden" name="nuevo_estado" value="publicada">
                      <button class="boton boton--secundario boton--pequeno" type="submit">Publicar</button>
                    </form>
                  <?php elseif ($f['estado'] === 'publicada'): ?>
                    <form method="POST" action="">
                      <?= campo_token() ?>
                      <input type="hidden" name="cambiar_estado" value="1">
                      <input type="hidden" name="vacante_id" value="<?= (int)$f['id'] ?>">
                      <input type="hidden" name="nuevo_estado" value="cerrada">
                      <button class="boton boton--secundario boton--pequeno" type="submit">Cerrar</button>
                    </form>
                  <?php endif; ?>

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