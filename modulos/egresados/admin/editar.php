<?php
/**
 * MÓDULO  EMP · EGRESADOS Y BOLSA DE TRABAJO · Alta y modificación de vacantes 
 * Explorador Vocacional UTBB
 *
 * Una sola página resuelve alta y modificación: sin ?id= es alta, con ?id=
 * es modificación. Repetir el formulario en dos archivos duplica el trabajo
 * y garantiza que tarde o temprano se desincronicen.
 *
 * Ciclo completo: GET pinta el formulario → POST valida en el servidor →
 * si hay errores se vuelve a pintar con los datos escritos → si todo está
 * bien se guarda, se deja un mensaje y se REDIRIGE.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../nucleo/comun.php';

// Verificación obligatoria de rol en la primera línea ejecutable
requerir_rol('editor', 'coordinacion');

require_once __DIR__ . '/../includes/consultas.php';

// 1. Determinar si se edita o se crea una vacante
$id       = entero(get('id'));
$registro = $id !== null ? emp_vacante_obtener($id) : null;

if ($id !== null && $registro === null) {
    no_encontrado('La vacante no existe.');
}

//una empresa solo edita sus propioas vacantes
if ($registro !== null){
  emp_verificar_propiedad_vacante($registro);
}

// identificar la empresa si quien entro tiene el rol de editor
$miEmpresa =(rol() === 'editor') ? emp_empresa_por_usuario((int)usuario()['id']) : null;
if (rol() === 'editor' && $miEmpresa === null){
  http_response_code(403);
  exit ('Tu cuenta no tiene una empresa vinculada. Contacta a Coordinacion.');
}

// 2. Valores iniciales que se pintan en el formulario
$datos = [
    'empresa_id'        => $registro['empresa_id']        ?? ($miEmpresa['id'] ?? ''),
    'carrera_id'        => $registro['carrera_id']        ?? (carrera_del_usuario() ?? ''),
    'titulo'            => $registro['titulo']            ?? '',
    'descripcion'       => $registro['descripcion']       ?? '',
    'ciudad'            => $registro['ciudad']            ?? ($miEmpresa['ciudad'] ?? 'Bahía de Banderas'),
    'tipo_contratacion' => $registro['tipo_contratacion'] ?? 'Tiempo Completo',
];

$errores = [];

// 3. Procesar el formulario enviado por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verificar_token(); // Protección anti-CSRF

    // Si es empresa, el servidor fija su propio empresa_id; si es coordinación, lo toma del select
    if (rol() === 'editor') {
        $datos['empresa_id'] = (int)$miEmpresa['id'];
    } else {
        $datos['empresa_id'] = entero(post('empresa_id'));
    }

    $datos['carrera_id']        = entero(post('carrera_id'));
    $datos['titulo']            = trim(post('titulo'));
    $datos['descripcion']       = trim(post('descripcion'));
    $datos['ciudad']            = trim(post('ciudad'));
    $datos['tipo_contratacion'] = post('tipo_contratacion');

    /* --- Validaciones estrictas en el servidor ------------------------ */

    if ($datos['empresa_id'] === null || emp_empresa_obtener($datos['empresa_id']) === null) {
        $errores['empresa_id'] = 'Selecciona una empresa válida.';
    }

    if ($datos['carrera_id'] === null || carrera($datos['carrera_id']) === null) {
        $errores['carrera_id'] = 'Selecciona una carrera válida.';
    }

    if ($datos['titulo'] === '') {
        $errores['titulo'] = 'El título de la vacante es obligatorio.';
    } elseif (mb_strlen($datos['titulo']) > 160) {
        $errores['titulo'] = 'El título no puede superar los 160 caracteres.';
    }

    if ($datos['descripcion'] === '') {
        $errores['descripcion'] = 'La descripción de la vacante es obligatoria.';
    }

    if ($datos['ciudad'] === '') {
        $errores['ciudad'] = 'La ciudad o ubicación es obligatoria.';
    }

    if (!array_key_exists($datos['tipo_contratacion'], EMP_TIPOS_CONTRATACION)) {
        $errores['tipo_contratacion'] = 'Selecciona un tipo de contratación válido.';
    }

    /* --- Guardar en la Base de Datos si no hay errores ----------------- */

    if (!$errores) {
        if ($registro === null) {
            $datos['estado'] = 'borrador'; // Estado inicial automático fijado por el servidor
            emp_vacante_crear($datos);
            mensaje('exito', 'Vacante creada como borrador.');
        } else {
            emp_vacante_actualizar((int)$registro['id'], $datos);
            mensaje('exito', 'Cambios guardados correctamente.');
        }

        redirigir('index.php'); // Patrón POST-Redirect-GET
    }
}

cabecera($registro ? 'Editar vacante' : 'Nueva vacante', '', '../css/modulo.css');
?>

<section class="contenedor seccion">

  <nav aria-label="Ruta">
    <ol class="migas">
      <li><a href="<?= RUTA_BASE ?>/panel.php">Panel</a></li>
      <li><a href="index.php">Módulo EMP: Egresados y Bolsa de Trabajo</a></li>
      <li><?= $registro ? 'Editar vacante' : 'Nueva vacante' ?></li>
    </ol>
  </nav>

  <h1><?= $registro ? 'Editar vacante' : 'Nueva vacante' ?></h1>

  <?php if ($errores): ?>
    <div class="alerta alerta--error" role="alert">
      <p><span class="alerta__titulo">Revisa los datos.</span>
         Hay <?= count($errores) ?> campo<?= count($errores) === 1 ? '' : 's' ?> con problemas.</p>
    </div>
  <?php endif; ?>

  <form class="formulario" method="POST" action="">
    <?= campo_token() ?>

    <fieldset class="grupo-campos">
      <legend class="grupo-campos__titulo">Información de la Oferta Laboral</legend>

      <?php if (rol() === 'coordinacion'): ?>
        <!-- Empresa ofertante (visible solo para Coordinación) -->
        <div class="campo <?= isset($errores['empresa_id']) ? 'campo--invalido' : '' ?>">
          <label class="campo__etiqueta" for="empresa_id">
            Empresa Ofertante <span class="campo__requerido" aria-hidden="true">*</span>
          </label>
          <select class="campo__control" id="empresa_id" name="empresa_id" required>
            <option value="">Selecciona una empresa</option>
            <?php foreach (emp_empresas_listar() as $emp): ?>
              <option value="<?= (int)$emp['id'] ?>" <?= (int)$datos['empresa_id'] === (int)$emp['id'] ? 'selected' : '' ?>>
                <?= e($emp['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errores['empresa_id'])): ?>
            <small class="campo__error"><?= e($errores['empresa_id']) ?></small>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Carrera a la que va dirigida la oferta -->
      <div class="campo <?= isset($errores['carrera_id']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="carrera_id">
          Carrera Destino <span class="campo__requerido" aria-hidden="true">*</span>
        </label>
        <select class="campo__control" id="carrera_id" name="carrera_id" required>
          <option value="">Selecciona una opción</option>
          <?php foreach (carreras() as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (int)$datos['carrera_id'] === (int)$c['id'] ? 'selected' : '' ?>>
              <?= e($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errores['carrera_id'])): ?>
          <small class="campo__error"><?= e($errores['carrera_id']) ?></small>
        <?php endif; ?>
      </div>

      <!-- Título de la Vacante -->
      <div class="campo <?= isset($errores['titulo']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="titulo">
          Título del Puesto <span class="campo__requerido" aria-hidden="true">*</span>
        </label>
        <input class="campo__control" type="text" id="titulo" name="titulo"
               value="<?= e($datos['titulo']) ?>" maxlength="160" required
               placeholder="Ej. Fisioterapeuta Spa o Desarrollador Web Jr.">
        <?php if (isset($errores['titulo'])): ?>
          <small class="campo__error"><?= e($errores['titulo']) ?></small>
        <?php endif; ?>
      </div>

      <!-- Ciudad / Ubicación -->
      <div class="campo <?= isset($errores['ciudad']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="ciudad">
          Ciudad / Ubicación <span class="campo__requerido" aria-hidden="true">*</span>
        </label>
        <input class="campo__control" type="text" id="ciudad" name="ciudad"
               value="<?= e($datos['ciudad']) ?>" required
               placeholder="Ej. Bahía de Banderas, Puerto Vallarta...">
        <?php if (isset($errores['ciudad'])): ?>
          <small class="campo__error"><?= e($errores['ciudad']) ?></small>
        <?php endif; ?>
      </div>

      <!-- Tipo de Contratación -->
      <div class="campo <?= isset($errores['tipo_contratacion']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="tipo_contratacion">
          Tipo de Contratación <span class="campo__requerido" aria-hidden="true">*</span>
        </label>
        <select class="campo__control" id="tipo_contratacion" name="tipo_contratacion" required>
          <?php foreach (EMP_TIPOS_CONTRATACION as $clave => $texto): ?>
            <option value="<?= e($clave) ?>" <?= $datos['tipo_contratacion'] === $clave ? 'selected' : '' ?>>
              <?= e($texto) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errores['tipo_contratacion'])): ?>
          <small class="campo__error"><?= e($errores['tipo_contratacion']) ?></small>
        <?php endif; ?>
      </div>

      <!-- Descripción y Requisitos -->
      <div class="campo <?= isset($errores['descripcion']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="descripcion">
          Descripción y Requisitos <span class="campo__requerido" aria-hidden="true">*</span>
        </label>
        <textarea class="campo__control" id="descripcion" name="descripcion" rows="6" required><?= e($datos['descripcion']) ?></textarea>
        <?php if (isset($errores['descripcion'])): ?>
          <small class="campo__error"><?= e($errores['descripcion']) ?></small>
        <?php endif; ?>
      </div>

    </fieldset>

    <div class="fila">
      <button class="boton boton--primario" type="submit">
        <?= $registro ? 'Guardar vacante' : 'Crear vacante' ?>
      </button>
      <a class="boton boton--secundario" href="index.php">Cancelar</a>
    </div>
  </form>

</section>

<?php
pie();