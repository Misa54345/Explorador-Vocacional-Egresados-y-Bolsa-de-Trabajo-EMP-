<?php
/**
 * MÓDULO EMP · Egresados y Bolsa de Trabajo · Catálogo y Alta de Empresas
 * Explorador Vocacional UTBB — Equipo B-3
 *
 * Exclusivo para rol 'coordinacion'.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../nucleo/comun.php';

requerir_rol('coordinacion');

require_once __DIR__ . '/../includes/consultas.php';

$errores = [];
$valores = [
    'nombre'          => '',
    'rfc'             => '',
    'contacto_nombre' => '',
    'correo'          => '',
    'telefono'        => '',
    'ciudad'          => 'Bahía de Banderas',
    'usuario_id'      => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_token();

    $valores = [
        'nombre'          => trim(post('nombre')),
        'rfc'             => strtoupper(trim(post('rfc'))),
        'contacto_nombre' => trim(post('contacto_nombre')),
        'correo'          => trim(post('correo')),
        'telefono'        => preg_replace('/\s+/', '', trim(post('telefono'))),
        'ciudad'          => trim(post('ciudad')),
        'usuario_id'      => entero(post('usuario_id')),
    ];

    if ($valores['nombre'] === '') {
        $errores['nombre'] = 'El nombre de la empresa es obligatorio.';
    }
    if ($valores['rfc'] !== '' && !preg_match('/^[A-ZÑ&]{3}[0-9]{6}[A-Z0-9]{3}$/u', $valores['rfc'])) {
        $errores['rfc'] = 'El RFC debe tener 3 letras, 6 dígitos y 3 caracteres de homoclave (ej. ABC120101XYZ).';
    }
    if ($valores['contacto_nombre'] === '') {
        $errores['contacto_nombre'] = 'El nombre del contacto es obligatorio.';
    }
    if ($valores['correo'] === '' || !filter_var($valores['correo'], FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'Ingresa un correo electrónico válido.';
    }
    if ($valores['telefono'] !== '' && !preg_match('/^[0-9]{10}$/', $valores['telefono'])) {
        $errores['telefono'] = 'El teléfono debe contener exactamente 10 dígitos numéricos.';
    }
    if ($valores['ciudad'] === '') {
        $errores['ciudad'] = 'La ciudad es obligatoria.';
    }

    if (!$errores) {
        emp_empresa_crear($valores);
        mensaje('exito', 'Empresa "' . $valores['nombre'] . '" registrada correctamente.');
        redirigir('empresas.php');
    }
}

$empresas = emp_empresas_listar();
$editores = emp_usuarios_editores_listar();

cabecera('Catálogo de Empresas · Módulo EMP', '', '../css/modulo.css');
?>

<section class="contenedor seccion">

  <nav aria-label="Ruta">
    <ol class="migas">
      <li><a href="<?= RUTA_BASE ?>/panel.php">Panel</a></li>
      <li><a href="index.php">Vacantes</a></li>
      <li>Empresas</li>
    </ol>
  </nav>

  <div class="fila fila--separada mb-6">
    <h1 class="sin-margen">Catálogo de Empresas Conveniadas</h1>
    <a class="boton boton--secundario" href="index.php">&larr; Volver a vacantes</a>
  </div>

  <?php pintar_mensajes(); ?>

  <!-- Formulario de Alta de Empresa -->
  <form class="panel mb-6" method="POST" action="">
    <h2>Registrar nueva empresa</h2>
    <?= campo_token() ?>

    <div class="fila">
      <div class="campo <?= isset($errores['nombre']) ? 'campo--invalido' : '' ?>" style="flex:2 1 260px">
        <label class="campo__etiqueta" for="nombre">Razón social o nombre comercial <span class="campo__requerido">*</span></label>
        <input class="campo__control" type="text" id="nombre" name="nombre" value="<?= e($valores['nombre']) ?>" required>
        <?php if (isset($errores['nombre'])): ?><small class="campo__error"><?= e($errores['nombre']) ?></small><?php endif; ?>
      </div>

      <div class="campo <?= isset($errores['rfc']) ? 'campo--invalido' : '' ?>" style="flex:1 1 180px">
        <label class="campo__etiqueta" for="rfc">RFC (Opcional)</label>
        <input class="campo__control" type="text" id="rfc" name="rfc" maxlength="12"
               pattern="[A-Za-zÑñ&]{3}[0-9]{6}[A-Za-z0-9]{3}"
               title="3 letras, 6 dígitos y 3 caracteres de homoclave (ej. ABC120101XYZ)"
               value="<?= e($valores['rfc']) ?>" placeholder="Ej. ABC120101XYZ">
        <?php if (isset($errores['rfc'])): ?><small class="campo__error"><?= e($errores['rfc']) ?></small><?php endif; ?>
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
      <div class="campo <?= isset($errores['telefono']) ? 'campo--invalido' : '' ?>" style="flex:1 1 180px">
        <label class="campo__etiqueta" for="telefono">Teléfono (10 dígitos)</label>
        <input class="campo__control" type="tel" id="telefono" name="telefono" maxlength="10"
               inputmode="numeric" pattern="[0-9]{10}"
               title="Ingresa 10 dígitos numéricos sin espacios ni letras"
               value="<?= e($valores['telefono']) ?>" placeholder="3220000000">
        <?php if (isset($errores['telefono'])): ?><small class="campo__error"><?= e($errores['telefono']) ?></small><?php endif; ?>
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

    <button class="boton boton--primario" type="submit">Registrar empresa</button>
  </form>

  <!-- Listado de Empresas -->
  <div class="tabla-envoltura">
    <table class="tabla">
      <caption><?= count($empresas) ?> empresa<?= count($empresas) === 1 ? '' : 's' ?> registrada<?= count($empresas) === 1 ? '' : 's' ?></caption>
      <thead>
        <tr>
          <th scope="col">Empresa</th>
          <th scope="col">RFC</th>
          <th scope="col">Contacto</th>
          <th scope="col">Correo / Teléfono</th>
          <th scope="col">Ciudad</th>
          <th scope="col">Usuario vinculado</th>
          <th scope="col">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($empresas as $emp): ?>
          <tr>
            <td><strong><?= e($emp['nombre']) ?></strong></td>
            <td><?= e($emp['rfc'] ?? '—') ?></td>
            <td><?= e($emp['contacto_nombre']) ?></td>
            <td><?= e($emp['correo']) ?><br><small class="leyenda"><?= e($emp['telefono'] ?? '') ?></small></td>
            <td><?= e($emp['ciudad']) ?></td>
            <td><?= e($emp['usuario_nombre'] ?? 'Sin vincular') ?></td>
            <td>
              <div class="tabla__acciones">
                <a class="boton boton--secundario boton--pequeno" href="empresa-editar.php?id=<?= (int)$emp['id'] ?>">Editar</a>

                <form method="POST" action="empresa-eliminar.php"
                      onsubmit="return confirm('¿Eliminar esta empresa y todas sus vacantes?')">
                  <?= campo_token() ?>
                  <input type="hidden" name="id" value="<?= (int)$emp['id'] ?>">
                  <button class="boton boton--peligro boton--pequeno" type="submit">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</section>

<script src="../js/empresas.js"></script>
<?php
pie();