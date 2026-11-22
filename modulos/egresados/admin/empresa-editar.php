<?php
/**
 * MÓDULO EMP · Egresados y Bolsa de Trabajo · Edición de Empresa
 * Explorador Vocacional UTBB — Equipo B-3
 *
 * Exclusivo para rol 'coordinacion'.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../nucleo/comun.php';

requerir_rol('coordinacion');

require_once __DIR__ . '/../includes/consultas.php';

$id      = entero(get('id'));
$empresa = $id !== null ? emp_empresa_obtener($id) : null;

if ($empresa === null) {
    no_encontrado('La empresa solicitada no existe.');
}

$errores = [];
$valores = [
    'nombre'          => $empresa['nombre'],
    'rfc'             => $empresa['rfc'] ?? '',
    'contacto_nombre' => $empresa['contacto_nombre'],
    'correo'          => $empresa['correo'],
    'telefono'        => $empresa['telefono'] ?? '',
    'ciudad'          => $empresa['ciudad'],
    'usuario_id'      => $empresa['usuario_id'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_token();

    $valores = [
        'nombre'          => trim(post('nombre')),
        'rfc'             => strtoupper(trim(post('rfc'))),
        'contacto_nombre' => trim(post('contacto_nombre')),
        'correo'          => trim(post('correo')),
        'telefono'        => trim(post('telefono')),
        'ciudad'          => trim(post('ciudad')),
        'usuario_id'      => entero(post('usuario_id')),
    ];

    if ($valores['nombre'] === '') {
        $errores['nombre'] = 'El nombre de la empresa es obligatorio.';
    }
    if ($valores['contacto_nombre'] === '') {
        $errores['contacto_nombre'] = 'El nombre del contacto es obligatorio.';
    }
    if ($valores['correo'] === '' || !filter_var($valores['correo'], FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'Ingresa un correo electrónico válido.';
    }
    if ($valores['ciudad'] === '') {
        $errores['ciudad'] = 'La ciudad es obligatoria.';
    }

    if (!$errores) {
        emp_empresa_actualizar((int)$empresa['id'], $valores);
        mensaje('exito', 'Datos de la empresa actualizados correctamente.');
        redirigir('empresas.php');
    }
}

$editores = emp_usuarios_editores_listar();

cabecera('Editar Empresa · Módulo EMP', '', '../css/modulo.css');
?>

<section class="contenedor seccion">

  <nav aria-label="Ruta">
    <ol class="migas">
      <li><a href="<?= RUTA_BASE ?>/panel.php">Panel</a></li>
      <li><a href="empresas.php">Empresas</a></li>
      <li>Editar</li>
    </ol>
  </nav>

  <div class="fila fila--separada mb-6">
    <h1 class="sin-margen">Editar Empresa: <?= e($empresa['nombre']) ?></h1>
    <a class="boton boton--secundario" href="empresas.php">&larr; Volver al catálogo</a>
  </div>

  <form class="panel" method="POST" action="">
    <?= campo_token() ?>

    <div class="fila">
      <div class="campo <?= isset($errores['nombre']) ? 'campo--invalido' : '' ?>" style="flex:2 1 260px">
        <label class="campo__etiqueta" for="nombre">Razón social o nombre comercial <span class="campo__requerido">*</span></label>
        <input class="campo__control" type="text" id="nombre" name="nombre" value="<?= e($valores['nombre']) ?>" required>
        <?php if (isset($errores['nombre'])): ?><small class="campo__error"><?= e($errores['nombre']) ?></small><?php endif; ?>
      </div>

      <div class="campo" style="flex:1 1 160px">
        <label class="campo__etiqueta" for="rfc">RFC (Opcional)</label>
        <input class="campo__control" type="text" id="rfc" name="rfc" maxlength="13" value="<?= e($valores['rfc']) ?>">
      </div>
    </div>

    <div class="fila">
      <div class="campo <?= isset($errores['contacto_nombre']) ? 'campo--invalido' : '' ?>" style="flex:1 1 220px">
        <label class="campo__etiqueta" for="contacto_nombre">Persona de contacto <span class="campo__requerido">*</span></label>
        <input class="campo__control" type="text" id="contacto_nombre" name="contacto_nombre" value="<?= e($valores['contacto_nombre']) ?>" required>
        <?php if (isset($errores['contacto_nombre'])): ?><small class="campo__error"><?= e($errores['contacto_nombre']) ?></small><?php endif; ?>
      </div>

      <div class="campo <?= isset($errores['correo']) ? 'campo--invalido' : '' ?>" style="flex:1 1 220px">
        <label class="campo__etiqueta" for="correo">Correo electrónico <span class="campo__requerido">*</span></label>
        <input class="campo__control" type="email" id="correo" name="correo" value="<?= e($valores['correo']) ?>" required>
        <?php if (isset($errores['correo'])): ?><small class="campo__error"><?= e($errores['correo']) ?></small><?php endif; ?>
      </div>
    </div>

    <div class="fila">
      <div class="campo" style="flex:1 1 180px">
        <label class="campo__etiqueta" for="telefono">Teléfono</label>
        <input class="campo__control" type="text" id="telefono" name="telefono" value="<?= e($valores['telefono']) ?>">
      </div>

      <div class="campo <?= isset($errores['ciudad']) ? 'campo--invalido' : '' ?>" style="flex:1 1 200px">
        <label class="campo__etiqueta" for="ciudad">Ciudad <span class="campo__requerido">*</span></label>
        <input class="campo__control" type="text" id="ciudad" name="ciudad" value="<?= e($valores['ciudad']) ?>" required>
        <?php if (isset($errores['ciudad'])): ?><small class="campo__error"><?= e($errores['ciudad']) ?></small><?php endif; ?>
      </div>

      <div class="campo" style="flex:1 1 240px">
        <label class="campo__etiqueta" for="usuario_id">Cuenta de acceso (Rol Editor)</label>
        <select class="campo__control" id="usuario_id" name="usuario_id">
          <option value="">Sin cuenta vinculada</option>
          <?php foreach ($editores as $ed): ?>
            <option value="<?= (int)$ed['id'] ?>" <?= (int)$valores['usuario_id'] === (int)$ed['id'] ? 'selected' : '' ?>>
              <?= e($ed['nombre']) ?> (<?= e($ed['correo']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="fila">
      <button class="boton boton--primario" type="submit">Guardar cambios</button>
      <a class="boton boton--secundario" href="empresas.php">Cancelar</a>
    </div>
  </form>

</section>

<?php
pie();