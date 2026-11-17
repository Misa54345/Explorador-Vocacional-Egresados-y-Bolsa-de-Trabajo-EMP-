
-- MÓDULO EMP · EGRESADOS Y BOLSA DE TRABAJO · esquema.sql
-- Explorador Vocacional UTBB — Equipo B-3


USE explorador_vocacional;

SET NAMES utf8mb4;

-- Reinicia únicamente las tablas del módulo EMP sin tocar usuarios ni carreras del núcleo
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS emp_postulaciones;
DROP TABLE IF EXISTS emp_egresados;
DROP TABLE IF EXISTS emp_vacantes;
DROP TABLE IF EXISTS emp_empresas;
SET FOREIGN_KEY_CHECKS = 1;


-- 1. Tabla de Empresas

CREATE TABLE emp_empresas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(120) NOT NULL,
    rfc             VARCHAR(13) NULL,
    contacto_nombre VARCHAR(100) NOT NULL,
    correo          VARCHAR(100) NOT NULL,
    telefono        VARCHAR(20) NULL,
    ciudad          VARCHAR(80) NOT NULL DEFAULT 'Bahía de Banderas',
    usuario_id      INT UNSIGNED NULL,
    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY ix_emp_empresas_usuario (usuario_id),
    CONSTRAINT fk_emp_empresas_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2. Tabla de Vacantes

CREATE TABLE emp_vacantes (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    empresa_id        INT UNSIGNED NOT NULL,
    carrera_id        INT UNSIGNED NOT NULL,
    titulo            VARCHAR(160) NOT NULL,
    descripcion       TEXT NOT NULL,
    ciudad            VARCHAR(80) NOT NULL,
    tipo_contratacion ENUM('Tiempo Completo', 'Medio Tiempo', 'Prácticas', 'Temporal') NOT NULL DEFAULT 'Tiempo Completo',
    estado            ENUM('borrador', 'publicada', 'cerrada') NOT NULL DEFAULT 'borrador',
    creado_en         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY ix_emp_vacantes_estado (estado),
    KEY ix_emp_vacantes_carrera (carrera_id),
    KEY ix_emp_vacantes_empresa (empresa_id),

    CONSTRAINT fk_emp_vacantes_empresa
        FOREIGN KEY (empresa_id) REFERENCES emp_empresas(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_emp_vacantes_carrera
        FOREIGN KEY (carrera_id) REFERENCES carreras(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla de Postulaciones (Incluye ruta para archivo CV en PDF)

CREATE TABLE emp_postulaciones (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vacante_id    INT UNSIGNED NOT NULL,
    aspirante_id  INT UNSIGNED NOT NULL,
    archivo_cv    VARCHAR(100) NOT NULL,
    estado        ENUM('enviada', 'en proceso', 'seleccionado', 'no seleccionado') NOT NULL DEFAULT 'enviada',
    creado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY ix_emp_postulaciones_vacante (vacante_id),
    KEY ix_emp_postulaciones_aspirante (aspirante_id),

    CONSTRAINT fk_emp_postulaciones_vacante
        FOREIGN KEY (vacante_id) REFERENCES emp_vacantes(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_emp_postulaciones_aspirante
        FOREIGN KEY (aspirante_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 4. Tabla de Registro de Egresados (Inserción laboral)

CREATE TABLE emp_egresados (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id     INT UNSIGNED NOT NULL,
    carrera_id     INT UNSIGNED NOT NULL,
    empresa_actual VARCHAR(120) NULL,
    puesto_actual  VARCHAR(100) NULL,
    anio_egreso    YEAR NOT NULL,
    creado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY ix_emp_egresados_carrera (carrera_id),

    CONSTRAINT fk_emp_egresados_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_emp_egresados_carrera
        FOREIGN KEY (carrera_id) REFERENCES carreras(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




-- Usuarios de prueba para el módulo EMP (Contraseña para todos: Demo1234)
SET @hash_demo = (SELECT contrasena FROM usuarios WHERE id = 1 LIMIT 1);

INSERT INTO usuarios (id, nombre, correo, contrasena, rol, carrera_id, activo) VALUES
    (901, 'RH St. Regis Punta Mita', 'rh@stregis.com', @hash_demo, 'editor', 4, 1),
    (902, 'Carlos Mendoza (Tech Solutions)', 'contacto@techriviera.mx', @hash_demo, 'editor', 1, 1),
    (903, 'Coordinación Vinculación EMP', 'vinculacion.emp@utbb.edu.mx', @hash_demo, 'coordinacion', NULL, 1),
    (904, 'Ana Sofía Egresada', 'ana.egresada@utbb.edu.mx', @hash_demo, 'aspirante', 4, 1)
ON DUPLICATE KEY UPDATE 
    contrasena = VALUES(contrasena),
    rol = VALUES(rol),
    carrera_id = VALUES(carrera_id);


-- Datos de prueba para el módulo EMP

INSERT INTO emp_empresas (id, nombre, rfc, contacto_nombre, correo, ciudad, usuario_id) VALUES
    (1, 'Hotel St. Regis Punta Mita', 'HSR980101XXX', 'Recursos Humanos', 'rh@stregis.com', 'Punta de Mita', 901),
    (2, 'Tech Solutions Riviera', 'TSR150520YYY', 'Carlos Mendoza', 'contacto@techriviera.mx', 'Bahía de Banderas', 902);

INSERT INTO emp_vacantes (id, empresa_id, carrera_id, titulo, descripcion, ciudad, tipo_contratacion, estado) VALUES
    (1, 1, 4, 'Fisioterapeuta Spa & Wellness', 'Atención a huéspedes en área de rehabilitación corporal.', 'Punta de Mita', 'Tiempo Completo', 'publicada'),
    (2, 2, 1, 'Desarrollador Web Junior', 'Desarrollo de módulos PHP y soporte a BD.', 'Bahía de Banderas', 'Tiempo Completo', 'publicada'),
    (3, 1, 4, 'Auxiliar de Terapia Física', 'Apoyo en ejercicios terapéuticos.', 'Punta de Mita', 'Prácticas', 'borrador');

INSERT INTO emp_egresados (usuario_id, carrera_id, empresa_actual, puesto_actual, anio_egreso) VALUES
    (904, 4, 'Clínica de Rehabilitación Bahía', 'Fisioterapeuta Titular', 2025);