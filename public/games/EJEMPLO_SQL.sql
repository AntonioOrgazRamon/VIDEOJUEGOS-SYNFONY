-- ============================================
-- EJEMPLO: Cómo añadir un juego a la base de datos
-- ============================================
-- 
-- Copia y pega este código en tu gestor de base de datos
-- (phpMyAdmin, MySQL Workbench, etc.)
-- 
-- IMPORTANTE: Cambia los valores según tu juego
-- ============================================

-- Ejemplo 1: Juego básico
INSERT INTO games (name, slug, description, icon, is_active, created_at) 
VALUES (
    'Mi Juego Increíble',                    -- name: nombre visible en la plataforma
    'mi-juego-increible',                    -- slug: debe coincidir con el nombre de tu carpeta en public/games/
    'Descripción de mi juego increíble',     -- description: descripción del juego
    'icons/mi-juego.png',                    -- icon: ruta al icono (debe estar en public/icons/)
    true,                                    -- is_active: true para activarlo, false para desactivarlo
    NOW()                                    -- created_at: fecha actual (automático)
);

-- Ejemplo 2: Otro juego
INSERT INTO games (name, slug, description, icon, is_active, created_at) 
VALUES (
    'Snake Game',
    'snake-game',
    'El clásico juego de la serpiente',
    'icons/snake.png',
    true,
    NOW()
);

-- Ejemplo 3: Juego desactivado (no aparecerá en la lista)
INSERT INTO games (name, slug, description, icon, is_active, created_at) 
VALUES (
    'Juego en Desarrollo',
    'juego-en-desarrollo',
    'Este juego aún está en desarrollo',
    'icons/placeholder.png',
    false,  -- Desactivado
    NOW()
);

-- ============================================
-- IMPORTANTE: Reglas para el SLUG
-- ============================================
-- 
-- El SLUG debe:
-- ✅ Ser en minúsculas
-- ✅ Usar guiones en lugar de espacios
-- ✅ Coincidir EXACTAMENTE con el nombre de tu carpeta
-- 
-- Ejemplos:
--   Nombre: "Mi Juego Increíble"
--   Slug:   "mi-juego-increible"
--   Carpeta: public/games/mi-juego-increible/
-- 
--   Nombre: "Snake Game"
--   Slug:   "snake-game"
--   Carpeta: public/games/snake-game/
-- 
-- ============================================
-- Ver todos los juegos
-- ============================================
SELECT * FROM games ORDER BY created_at DESC;

-- ============================================
-- Actualizar un juego existente
-- ============================================
-- UPDATE games 
-- SET name = 'Nuevo Nombre',
--     description = 'Nueva descripción',
--     icon = 'icons/nuevo-icono.png',
--     is_active = true
-- WHERE slug = 'mi-juego-increible';

-- ============================================
-- Eliminar un juego (¡CUIDADO!)
-- ============================================
-- DELETE FROM games WHERE slug = 'mi-juego-increible';

