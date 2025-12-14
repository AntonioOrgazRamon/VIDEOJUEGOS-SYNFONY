# 🎮 Cómo Crear un Juego - Guía Paso a Paso

Esta es la guía **definitiva** para crear un juego en la plataforma. Sigue estos pasos en orden.

---

## 📋 Índice

1. [Paso 1: Añadir el Juego a la Base de Datos](#paso-1-añadir-el-juego-a-la-base-de-datos)
2. [Paso 2: Crear la Carpeta del Juego](#paso-2-crear-la-carpeta-del-juego)
3. [Paso 3: Crear el Archivo del Juego](#paso-3-crear-el-archivo-del-juego)
4. [Paso 4: Probar el Juego](#paso-4-probar-el-juego)
5. [Paso 5: Guardar Puntuaciones (Opcional)](#paso-5-guardar-puntuaciones-opcional)

---

## Paso 1: Añadir el Juego a la Base de Datos

### ¿Qué hacer?

Añadir tu juego a la tabla `games` de la base de datos usando SQL.

### ¿Dónde hacerlo?

- **Base de datos MySQL** (phpMyAdmin, MySQL Workbench, o línea de comandos)
- **Tabla**: `games`

### Código SQL:

```sql
INSERT INTO games (name, slug, description, icon, is_active, created_at) 
VALUES (
    'Mi Juego',                    -- name: Nombre visible en la plataforma
    'mi-juego',                    -- slug: IDENTIFICADOR ÚNICO (MUY IMPORTANTE)
    'Descripción de mi juego',    -- description: Descripción del juego
    'icons/mi-juego.png',         -- icon: Ruta del icono (opcional)
    true,                          -- is_active: true para activarlo
    NOW()                          -- created_at: Fecha actual
);
```

### ⚠️ IMPORTANTE: El `slug`

- **DEBE** ser único (no puede haber dos juegos con el mismo slug)
- **DEBE** estar en minúsculas
- **DEBE** usar guiones en lugar de espacios
- **DEBE** coincidir EXACTAMENTE con el nombre de la carpeta que crearás después

**Ejemplos de slugs válidos:**
- ✅ `mi-juego`
- ✅ `snake-game`
- ✅ `puzzle-adventure`
- ✅ `bombas`
- ❌ `Mi Juego` (mayúsculas)
- ❌ `mi_juego` (guiones bajos)
- ❌ `miJuego` (camelCase)

### Ejemplo Real:

```sql
INSERT INTO games (name, slug, description, icon, is_active, created_at) 
VALUES (
    'Snake',
    'snake',
    'El clásico juego de la serpiente',
    'icons/snake.png',
    true,
    NOW()
);
```

**Resultado**: El juego aparece en la lista de juegos, pero al hacer clic mostrará "El juego aún no está disponible" hasta que crees el template.

---

## Paso 2: Crear la Carpeta del Juego

### ¿Qué hacer?

Crear una carpeta con el mismo nombre que el `slug` del juego.

### ¿Dónde hacerlo?

**Ruta exacta**: `templates/games/{slug}/`

### Pasos:

1. Ve a la carpeta: `plataforma_juegos/VIDEOJUEGOS-SYNFONY/templates/games/`
2. Crea una nueva carpeta con el nombre del `slug`
3. **IMPORTANTE**: El nombre de la carpeta debe ser EXACTAMENTE igual al `slug`

### Ejemplo:

Si tu `slug` es `snake`, crea:
```
templates/games/snake/
```

**Estructura resultante:**
```
templates/games/
├── README.md
├── EXPLICACION.md
└── snake/              ← Tu carpeta nueva
```

### ⚠️ ERROR COMÚN:

- ❌ **Slug en BD**: `snake`
- ❌ **Carpeta creada**: `snake-game`
- ❌ **Resultado**: El juego NO se encontrará

- ✅ **Slug en BD**: `snake`
- ✅ **Carpeta creada**: `snake`
- ✅ **Resultado**: El juego se encontrará correctamente

---

## Paso 3: Crear el Archivo del Juego

### ¿Qué hacer?

Crear el archivo `game.html.twig` dentro de la carpeta que acabas de crear.

### ¿Dónde hacerlo?

**Ruta exacta**: `templates/games/{slug}/game.html.twig`

### Pasos:

1. Dentro de la carpeta que creaste (`templates/games/{slug}/`)
2. Crea un archivo llamado exactamente: `game.html.twig`
3. Añade tu código HTML, CSS y JavaScript

### Estructura Mínima:

```twig
{# 
    Juego: Nombre de tu Juego
    Template del juego que se renderiza dentro de play.html.twig
#}

<style>
    .mi-juego-container {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #1a1a2e;
        color: white;
        font-family: 'Arial', sans-serif;
    }
    
    /* Tus estilos CSS aquí */
</style>

<div class="mi-juego-container">
    <h1>{{ game.name }}</h1>
    <p>¡Empieza a desarrollar tu juego aquí!</p>
    
    <!-- Tu HTML del juego aquí -->
</div>

<script>
    // Tu código JavaScript aquí
    console.log('Juego: {{ game.name }}');
    console.log('Game ID: {{ gameId }}');
    console.log('Usuario: {{ user.username }}');
    
    // Tu lógica del juego aquí
</script>
```

### Variables Disponibles:

En tu template tienes acceso a estas variables:

```twig
{# Información del juego #}
{{ game.name }}          → "Snake"
{{ game.slug }}          → "snake"
{{ game.description }}   → "El clásico juego de la serpiente"
{{ game.id }}            → 1
{{ game.isActive }}      → true

{# ID del juego (útil para guardar puntuaciones) #}
{{ gameId }}             → 1

{# Información del usuario #}
{{ user.username }}      → "toni"
{{ user.email }}         → "toni@example.com"
{{ user.id }}            → 2
```

### Ejemplo Completo (Juego Simple):

```twig
<style>
    .snake-container {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: #0a0a0a;
        color: #00ff00;
        font-family: 'Courier New', monospace;
    }
    
    #gameCanvas {
        border: 2px solid #00ff00;
        background: #000;
    }
    
    .score {
        font-size: 24px;
        margin-bottom: 20px;
    }
</style>

<div class="snake-container">
    <div class="score">Puntuación: <span id="score">0</span></div>
    <canvas id="gameCanvas" width="800" height="600"></canvas>
    <button onclick="startGame()">Iniciar Juego</button>
</div>

<script>
    let score = 0;
    let gameRunning = false;
    
    // Usar variables de Twig
    const gameId = {{ gameId }};
    const username = '{{ user.username }}';
    
    function startGame() {
        gameRunning = true;
        // Tu lógica del juego aquí
    }
    
    function gameOver() {
        gameRunning = false;
        // Guardar puntuación cuando termine el juego
        saveScore(score);
    }
    
    // Función para guardar puntuación (ver Paso 5)
    async function saveScore(finalScore) {
        // ... código de guardado (ver Paso 5)
    }
</script>
```

---

## Paso 4: Probar el Juego

### ¿Qué hacer?

Verificar que el juego se muestra correctamente en la plataforma.

### Pasos:

1. **Asegúrate de que el servidor esté corriendo**
2. **Inicia sesión** en la plataforma
3. **Busca tu juego** en la lista de juegos
4. **Haz clic** en el juego
5. **Verifica** que aparece tu juego

### ✅ Si Funciona:

- Verás tu juego renderizado en el centro de la pantalla
- El ranking aparecerá a la izquierda
- El panel de usuario aparecerá arriba a la derecha

### ❌ Si NO Funciona:

**Síntomas:**
- Aparece el mensaje: "El juego aún no está disponible..."

**Soluciones:**

1. **Verifica el slug:**
   - ¿El `slug` en la BD coincide EXACTAMENTE con el nombre de la carpeta?
   - Ejemplo: BD = `snake`, Carpeta = `snake` ✅

2. **Verifica el archivo:**
   - ¿El archivo se llama exactamente `game.html.twig`?
   - ¿Está en la ruta correcta: `templates/games/{slug}/game.html.twig`?

3. **Verifica la consola:**
   - Abre la consola del navegador (F12)
   - Busca errores de JavaScript
   - Busca errores 404 (archivos no encontrados)

4. **Verifica la base de datos:**
   - ¿El juego está en la tabla `games`?
   - ¿El campo `is_active` es `true`?

---

## Paso 5: Guardar Puntuaciones (Opcional)

### ¿Qué hacer?

Si tu juego tiene un sistema de puntuación, puedes guardarlo automáticamente.

### ¿Dónde hacerlo?

En tu archivo `game.html.twig`, dentro de la sección `<script>`.

### Código para Guardar Puntuación:

```javascript
async function saveScore(score, duration = null, level = null) {
    try {
        const response = await fetch('/api/game/save-score', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                game_id: {{ gameId }},  // ID del juego (variable de Twig)
                score: score,            // Puntuación obtenida
                duration: duration,      // Tiempo en segundos (opcional)
                level: level            // Nivel alcanzado (opcional)
            })
        });
        
        const data = await response.json();
        if (data.success) {
            console.log('✅ Puntuación guardada:', score);
            // Opcional: mostrar notificación de éxito
            if (typeof window.showSuccess === 'function') {
                window.showSuccess('Puntuación guardada: ' + score);
            }
        } else {
            console.error('❌ Error al guardar:', data.message);
        }
    } catch (error) {
        console.error('❌ Error al guardar puntuación:', error);
    }
}
```

### Ejemplo de Uso:

```javascript
// Cuando el juego termina
function gameOver() {
    const finalScore = 1500;
    const gameDuration = 120; // segundos
    const levelReached = 5;
    
    // Guardar la puntuación
    saveScore(finalScore, gameDuration, levelReached);
}
```

### ¿Qué pasa con la puntuación?

- ✅ Se guarda en la tabla `user_scores` de la BD
- ✅ Aparece automáticamente en el ranking (panel izquierdo)
- ✅ Se asocia al usuario que está jugando
- ✅ Si es tu mejor puntuación, aparece en el ranking

---

## 📁 Estructura Final de Archivos

Cuando termines, tu estructura debería verse así:

```
plataforma_juegos/VIDEOJUEGOS-SYNFONY/
├── templates/
│   └── games/
│       ├── README.md
│       ├── EXPLICACION.md
│       └── mi-juego/              ← Tu carpeta (nombre = slug)
│           └── game.html.twig    ← Tu archivo del juego
│
└── public/
    └── icons/
        └── mi-juego.png          ← Icono del juego (opcional)
```

---

## ✅ Checklist Final

Antes de considerar tu juego terminado, verifica:

- [ ] Juego añadido a la BD con un `slug` único
- [ ] Carpeta creada: `templates/games/{slug}/`
- [ ] Archivo creado: `templates/games/{slug}/game.html.twig`
- [ ] El nombre de la carpeta coincide EXACTAMENTE con el `slug`
- [ ] El archivo se llama exactamente `game.html.twig`
- [ ] El juego se renderiza correctamente al hacer clic
- [ ] No hay errores en la consola del navegador (F12)
- [ ] Sistema de puntuación implementado (si aplica)
- [ ] Guardado de puntuaciones funcionando (si aplica)
- [ ] Controles funcionando correctamente
- [ ] Game Over y reinicio implementados (si aplica)

---

## 🎯 Resumen Rápido

1. **SQL**: Añade el juego a la BD con un `slug` único
2. **Carpeta**: Crea `templates/games/{slug}/`
3. **Archivo**: Crea `templates/games/{slug}/game.html.twig`
4. **Código**: Añade tu HTML, CSS y JavaScript
5. **Probar**: Haz clic en el juego y verifica que funciona
6. **Puntuación**: Implementa `saveScore()` si tu juego tiene puntuación

---

## 🆘 ¿Problemas?

### El juego no aparece
- Verifica que el `slug` coincida exactamente con el nombre de la carpeta
- Verifica que el archivo se llame `game.html.twig`

### El juego aparece pero no funciona
- Abre la consola del navegador (F12)
- Busca errores de JavaScript
- Verifica que todas las variables de Twig estén correctas

### La puntuación no se guarda
- Verifica que estés usando `{{ gameId }}` (no `{{ game.id }}`)
- Verifica que el usuario esté autenticado
- Revisa la consola del navegador para errores

---

## 📚 Recursos Adicionales

- **README.md**: Guía general de creación de juegos
- **EXPLICACION.md**: Explicación técnica del sistema
- **MDN Web Docs**: Documentación de HTML, CSS y JavaScript
- **Phaser.js**: Framework para juegos 2D
- **Three.js**: Framework para juegos 3D

---

¡Buena suerte con tu juego! 🎮🚀

