<?php
/**
 * MÓDULO EMP · EGRESADOS Y BOLSA DE TRABAJO · Ficha de Vacante y Postulación
 * Explorador Vocacional UTBB — Equipo B-3
 *
 * Muestra el detalle completo de una vacante publicada (GET) y procesa
 * la postulación del aspirante con carga y validación real de CV en PDF (POST).
 */

declare(strict_types=1);

require_once __DIR__ . '/../../nucleo/comun.php';
require_once __DIR__ . '/includes/consultas.php';

const CARPETA_CV = __DIR__ . '/uploads';
const TAMANO_MAX_CV = 5 * 1024 * 1024; // 5 MB máximo permitido

// 1. Obtener y validar el ID de la vacante desde la URL (?id=...)
$id      = entero(get('id'));
$vacante = $id !== null ? emp_vacante_obtener($id) : null;

// Si no existe o no está publicada, no se debe mostrar al público
if ($vacante === null || $vacante['estado'] !== 'publicada') {
    no_encontrado('La vacante solicitada no existe o ya no está disponible.');
}

$usuarioActual = usuario();
$errores = [];

// 2. Procesar envío de postulación con archivo PDF (Método POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verificar que el aspirante tenga sesión iniciada
    if ($usuarioActual === null) {
        mensaje('error', 'Debes iniciar sesión para poder postularte a una vacante.');
        redirigir('ficha.php?id=' . (int)$vacante['id']);
    }

    verificar_token(); // Protección anti-CSRF

    /* --- Validación del archivo CV en PDF en el servidor ---------------- */
    if (empty($_FILES['cv']) || $_FILES['cv']['error'] === UPLOAD_ERR_NO_FILE) {
        $errores['cv'] = 'Debes adjuntar tu Currículum Vitae en formato PDF.';
    } elseif ($_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
        $errores['cv'] = 'Ocurrió un error al subir el archivo. Intenta de nuevo.';
    } elseif ($_FILES['cv']['size'] > TAMANO_MAX_CV) {
        $errores['cv'] = 'El archivo PDF no puede pesar más de 5 MB.';
    } else {
        // Inspeccionar el tipo MIME real del archivo usando finfo (no confiar en la extensión)
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($_FILES['cv']['tmp_name']);

        if ($mimeReal !== 'application/pdf') {
            $errores['cv'] = 'Archivo no válido. Únicamente se aceptan documentos PDF reales.';
        }
    }

    /* --- Guardar archivo con nombre único y registrar en BD ------------ */
    if (!$errores) {
        // Asegurar que exista la carpeta uploads
        if (!is_dir(CARPETA_CV)) {
            mkdir(CARPETA_CV, 0755, true);
        }

        // Generar nombre único seguro en el servidor (nunca usar el nombre del usuario)
        $nombreUnico = 'cv_' . bin2hex(random_bytes(16)) . '.pdf';
        $rutaDestino = CARPETA_CV . '/' . $nombreUnico;

        if (move_uploaded_file($_FILES['cv']['tmp_name'], $rutaDestino)) {
            try {
                emp_postulacion_crear(
                    (int)$vacante['id'],
                    (int)$usuarioActual['id'],
                    $nombreUnico
                );

                mensaje('exito', '¡Tu postulación y CV fueron enviados correctamente!');
                redirigir('ficha.php?id=' . (int)$vacante['id']); // POST-Redirect-GET
            } catch (PDOException $e) {
                // Si falla la BD (ej. ya se postuló antes), borramos el PDF subido
                @unlink($rutaDestino);
                $errores['general'] = 'Ya te has postulado anteriormente a esta vacante o ocurrió un error al registrar.';
            }
        } else {
            $errores['cv'] = 'No se pudo guardar el archivo PDF en el servidor.';
        }
    }
}

cabecera($vacante['titulo'] . ' · Bolsa de Trabajo', '', 'css/modulo.css');
?>

<section class="contenedor seccion">

  <!-- Migas de pan -->
  <nav aria-label="Ruta">
    <ol class="migas">
      <li><a href="<?= RUTA_BASE ?>/index.php">Inicio</a></li>
      <li><a href="index.php">Bolsa de Trabajo</a></li>
      <li><?= e($vacante['titulo']) ?></li>
    </ol>
  </nav>

  <?php pintar_mensajes(); ?>

  <?php if (!empty($errores['general'])): ?>
    <div class="alerta alerta--error" role="alert">
      <p><span class="alerta__titulo">Aviso:</span> <?= e($errores['general']) ?></p>
    </div>
  <?php endif; ?>

  <!-- Detalle de la Vacante -->
  <article class="panel mb-6">
    <div class="fila fila--separada mb-4">
      <div>
        <span class="leyenda"><?= e($vacante['empresa']) ?></span>
        <h1 class="sin-margen"><?= e($vacante['titulo']) ?></h1>
      </div>
      <span class="<?= e(emp_clase_estado_vacante($vacante['estado'])) ?>">
        <?= e(EMP_ESTADOS_VACANTE[$vacante['estado']]) ?>
      </span>
    </div>

    <p class="leyenda">
      <strong>Carrera:</strong> <?= e($vacante['carrera']) ?> &nbsp;|&nbsp;
      <strong>Ubicación:</strong> <?= e($vacante['ciudad']) ?> &nbsp;|&nbsp;
      <strong>Contrato:</strong> <?= e($vacante['tipo_contratacion']) ?>
    </p>

    <hr>

    <h2>Descripción del puesto</h2>
    <p style="white-space: pre-line;"><?= e($vacante['descripcion']) ?></p>

    <hr>

    <h3>Datos de contacto de la empresa</h3>
    <ul style="list-style: none; padding: 0;">
      <li><strong>Empresa:</strong> <?= e($vacante['empresa']) ?></li>
      <?php if (!empty($vacante['contacto_nombre'])): ?>
        <li><strong>Contacto:</strong> <?= e($vacante['contacto_nombre']) ?></li>
      <?php endif; ?>
      <li><strong>Correo:</strong> <?= e($vacante['empresa_correo']) ?></li>
      <?php if (!empty($vacante['empresa_telefono'])): ?>
        <li><strong>Teléfono:</strong> <?= e($vacante['empresa_telefono']) ?></li>
      <?php endif; ?>
    </ul>
  </article>

  <!-- Sección de Postulación -->
  <div class="panel">
    <h2>Postularse a esta vacante</h2>

    <?php if ($usuarioActual === null): ?>
      <p>Para enviar tu Currículum Vitae y postularte a esta oferta laboral necesitas iniciar sesión con tu cuenta institucional.</p>
      <a class="boton boton--primario" href="<?= RUTA_BASE ?>/entrar.php">Iniciar sesión para postularme</a>
    <?php else: ?>
      <p class="leyenda">Postulándote como: <strong><?= e($usuarioActual['nombre']) ?></strong> (<?= e($usuarioActual['correo']) ?>)</p>

      <form class="formulario" method="POST" action="" enctype="multipart/form-data">
        <?= campo_token() ?>

        <div class="campo <?= isset($errores['cv']) ? 'campo--invalido' : '' ?>">
          <label class="campo__etiqueta" for="cv">
            Currículum Vitae (Únicamente formato PDF) <span class="campo__requerido" aria-hidden="true">*</span>
          </label>
          <input class="campo__control" type="file" id="cv" name="cv" accept="application/pdf,.pdf" required>
          <small class="campo__ayuda">Tamaño máximo: 5 MB. El servidor verificará que sea un archivo PDF auténtico.</small>
          <?php if (isset($errores['cv'])): ?>
            <small class="campo__error"><?= e($errores['cv']) ?></small>
          <?php endif; ?>
        </div>

        <div class="fila">
          <button class="boton boton--primario" type="submit">Enviar postulación</button>
          <a class="boton boton--secundario" href="index.php">Volver al listado</a>
        </div>
      </form>
    <?php endif; ?>
  </div>

</section>

<?php
pie();