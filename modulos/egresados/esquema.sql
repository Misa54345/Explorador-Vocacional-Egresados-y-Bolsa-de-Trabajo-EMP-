-- =============================================================================
-- MÓDULO EMP · EGRESADOS Y BOLSA DE TRABAJO · esquema.sql
-- Explorador Vocacional UTBB — Equipo B-3
-- =============================================================================

USE explorador_vocacional;

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 1. Tabla de Empresas
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS emp_empresas (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(120) NOT NULL,
    rfc            VARCHAR(13) NULL,
    contacto_nombre VARCHAR(100) NOT NULL,
    correo         VARCHAR(100) NOT NULL,
    telefono       VARCHAR(20) NULL,
    ciudad         VARCHAR(80) NOT NULL DEFAULT 'Bahía de Banderas',
    usuario_id     INT UNSIGNED NULL,
    creado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY ix_emp_empresas_usuario (usuario_id),
    CONSTRAINT fk_emp_empresas_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Tabla de Vacantes
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS emp_vacantes (
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

-- -----------------------------------------------------------------------------
-- 3. Tabla de Postulaciones (Incluye ruta para archivo CV en PDF)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS emp_postulaciones (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vacante_id    INT UNSIGNED NOT NULL,
    aspirante_id  INT UNSIGNED NOT NULL,
    archivo_cv    VARCHAR(100) NOT NULL, -- Nombre único generado por el servidor
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

-- -----------------------------------------------------------------------------
-- 4. Tabla de Registro de Egresados (Inserción laboral)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS emp_egresados (
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

-- -----------------------------------------------------------------------------
-- Datos de prueba para el módulo EMP
-- -----------------------------------------------------------------------------
INSERT INTO emp_empresas (nombre, rfc, contacto_nombre, correo, ciudad) VALUES
    ('Hotel St. Regis Punta Mita', 'HSR980101XXX', 'Recursos Humanos', 'rh@stregis.com', 'Punta de Mita'),
    ('Tech Solutions Riviera', 'TSR150520YYY', 'Carlos Mendoza', 'contacto@techriviera.mx', 'Bahía de Banderas');

INSERT INTO emp_vacantes (empresa_id, carrera_id, titulo, descripcion, ciudad, tipo_contratacion, estado) VALUES
    (1, 10, 'Fisioterapeuta Spa & Wellness', 'Atención a huéspedes en área de rehabilitación corporal.', 'Punta de Mita', 'Tiempo Completo', 'publicada'),
    (2, 1, 'Desarrollador Web Junior', 'Desarrollo de módulos PHP y soporte a BD.', 'Bahía de Banderas', 'Tiempo Completo', 'publicada'),
    (1, 10, 'Auxiliar de Terapia Física', 'Apoyo en ejercicios terapéuticos.', 'Punta de Mita', 'Prácticas', 'borrador');