# 🎮 Explicación del Sistema de Juegos

Esta guía explica **cómo funciona** el sistema de incorporación de juegos en la plataforma, paso a paso.

## 📋 Resumen Rápido

1. **Añades tu juego a la base de datos** (con un `slug` único)
2. **Creas una carpeta** con el mismo nombre que el `slug`
3. **Creas un archivo `game.html.twig`** con tu juego
4. **¡Listo!** El juego aparece automáticamente en la plataforma

---

## 🔍 ¿Cómo Funciona el Sistema?

### Paso 1: El Juego en la Base de Datos

Primero, tu juego debe estar registrado en la base de datos. Esto se hace con una consulta SQL:

```sql
INSERT INTO games (name, slug, description, icon, is_active, created_at) 
VALUES (
    'Bombas',                    -- name: nombre visible en la plataforma
    'bombas',                    -- slug: identificador único (MUY IMPORTANTE)
    'Juego de bombas',           -- description: descripción del juego
    'icons/bombas.png',          -- icon: icono del juego
    true,                        -- is_active: true para activarlo
    NOW()                        -- created_at: fecha actual
);
```

**¿Qué es el `slug`?**
- Es un identificador único para tu juego
- Debe ser en minúsculas, sin espacios, usando guiones
- Ejemplos: `bombas`, `snake-game`, `puzzle-adventure`
- **Este `slug` es la clave de todo el sistema**

### Paso 2: El Sistema Busca tu Juego

Cuando un usuario hace clic en un juego, esto es lo que pasa:

1. El usuario hace clic en la tarjeta del juego
2. Se abre la página `/game/play/{gameId}`
3. El controlador (`HomeController.php`) busca el juego en la BD
4. **Aquí viene lo importante**: El sistema busca si existe un template para ese juego

```php
// En HomeController.php, método play()

// Obtiene el slug del juego desde la BD
$gameSlug = $game->getSlug();  // Ejemplo: "bombas"

// Busca el template en: templates/games/{slug}/game.html.twig
$gameTemplate = 'games/' . $gameSlug . '/game.html.twig';
// Resultado: "games/bombas/game.html.twig"

// Verifica si el archivo existe
$gameTemplatePath = $projectDir . '/templates/' . $gameTemplate;
$gameExists = file_exists($gameTemplatePath);
```

### Paso 3: Renderizado del Juego

Si el template existe, se renderiza dentro de la página:

```twig
{# En templates/game/play.html.twig #}

{% if gameTemplate %}
    {# Si existe el template, lo incluye #}
    {% include gameTemplate with {'game': game, 'gameId': gameId, 'user': user} %}
{% else %}
    {# Si NO existe, muestra un mensaje #}
    <div>El juego aún no está disponible...</div>
{% endif %}
```

**¿Qué significa esto?**
- Si existe `templates/games/bombas/game.html.twig` → Se muestra tu juego
- Si NO existe → Se muestra un mensaje diciendo que falta crear el archivo

---

## 🗂️ Estructura de Archivos

```
plataforma_juegos/
├── src/
│   └── Controller/
│       └── HomeController.php          ← Busca el template del juego
│
├── templates/
│   ├── game/
│   │   └── play.html.twig              ← Página donde se muestra el juego
│   │
│   └── games/                          ← CARPETA DE TODOS LOS JUEGOS
│       ├── README.md                   ← Guía de cómo crear juegos
│       ├── EXPLICACION.md              ← Este archivo
│       │
│       ├── bombas/                     ← Tu juego (ejemplo)
│       │   └── game.html.twig         ← Tu código del juego aquí
│       │
│       └── snake-game/                 ← Otro juego (ejemplo)
│           └── game.html.twig         ← Código de otro juego
│
└── public/
    └── icons/
        └── bombas.png                  ← Icono del juego
```

---

## 🔄 Flujo Completo (Paso a Paso)

### Escenario: Quieres añadir el juego "Bombas"

#### 1️⃣ Añadir a la Base de Datos

```sql
INSERT INTO games (name, slug, description, icon, is_active, created_at) 
VALUES ('Bombas', 'bombas', 'Juego de bombas', 'icons/bombas.png', true, NOW());
```

**Resultado**: El juego aparece en la lista de juegos, pero al hacer clic muestra "El juego aún no está disponible"

#### 2️⃣ Crear la Carpeta

```
Crear: templates/games/bombas/
```

**IMPORTANTE**: El nombre de la carpeta (`bombas`) debe ser **exactamente igual** al `slug` de la BD

#### 3️⃣ Crear el Template

```
Crear: templates/games/bombas/game.html.twig
```

Dentro pones tu código:

```twig
<style>
    .bombas-container {
        width: 100%;
        height: 100%;
        background: #000;
        color: white;
    }
</style>

<div class="bombas-container">
    <h1>Mi Juego de Bombas</h1>
    <!-- Tu juego aquí -->
</div>

<script>
    // Tu código JavaScript aquí
</script>
```

#### 4️⃣ ¡Listo!

Ahora cuando alguien haga clic en "Bombas":
1. El sistema busca `templates/games/bombas/game.html.twig`
2. Lo encuentra ✅
3. Lo renderiza dentro de la página
4. Tu juego aparece y es jugable

---

## 🎯 Variables que Tienes Disponibles

Cuando creas tu `game.html.twig`, tienes acceso a estas variables:

```twig
{# Información del juego #}
{{ game.name }}        → "Bombas"
{{ game.slug }}        → "bombas"
{{ game.description }} → "Juego de bombas"
{{ game.id }}          → 1 (ID del juego)

{# ID del juego (útil para guardar puntuaciones) #}
{{ gameId }}           → 1

{# Información del usuario #}
{{ user.username }}    → "toni"
{{ user.email }}       → "toni@example.com"
{{ user.id }}          → 2
```

**Ejemplo de uso:**

```twig
<div>
    <h1>{{ game.name }}</h1>
    <p>Jugando como: {{ user.username }}</p>
    <p>Game ID: {{ gameId }}</p>
</div>

<script>
    // Guardar puntuación usando el gameId
    fetch('/api/game/save-score', {
        method: 'POST',
        body: JSON.stringify({
            game_id: {{ gameId }},  // Usa la variable de Twig
            score: 1500
        })
    });
</script>
```

---

## 🔧 ¿Cómo Guardar Puntuaciones?

Tu juego puede guardar puntuaciones automáticamente usando la API:

```javascript
async function saveScore(score) {
    const response = await fetch('/api/game/save-score', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            game_id: {{ gameId }},  // ID del juego (variable de Twig)
            score: score,            // Puntuación obtenida
            duration: 120,          // Tiempo en segundos (opcional)
            level: 5                // Nivel alcanzado (opcional)
        })
    });
    
    const data = await response.json();
    if (data.success) {
        console.log('Puntuación guardada!');
    }
}

// Ejemplo: cuando el juego termina
saveScore(1500);
```

**¿Qué pasa con la puntuación?**
- Se guarda en la tabla `user_scores` de la BD
- Aparece automáticamente en el ranking (panel izquierdo)
- Se asocia al usuario que está jugando

---

## ❓ Preguntas Frecuentes

### ¿Qué pasa si el `slug` no coincide?

**Problema**: 
- En la BD: `slug = "bombas"`
- Carpeta creada: `templates/games/bombas-game/`

**Resultado**: ❌ El juego NO se encontrará y mostrará "El juego aún no está disponible"

**Solución**: El nombre de la carpeta debe ser **exactamente igual** al `slug`

### ¿Puedo usar frameworks como Phaser.js?

**Sí**, puedes usar cualquier framework. Solo inclúyelo en tu template:

```twig
<script src="https://cdn.jsdelivr.net/npm/phaser@3.70.0/dist/phaser.min.js"></script>

<script>
    // Tu código con Phaser aquí
    const config = {
        type: Phaser.AUTO,
        width: 800,
        height: 600,
        // ...
    };
    const game = new Phaser.Game(config);
</script>
```

### ¿Puedo crear subcarpetas para organizar mi código?

**Sí**, puedes organizar tu código así:

```
templates/games/bombas/
├── game.html.twig          ← Template principal
└── assets/                 ← Subcarpeta para assets
    ├── sprites/
    └── sounds/
```

Y referenciarlos en tu template:

```twig
<img src="/games/bombas/assets/sprites/bomba.png">
```

### ¿Cómo sé si mi juego está funcionando?

1. Añade el juego a la BD
2. Crea la carpeta y el `game.html.twig`
3. Haz clic en el juego en la plataforma
4. Si aparece tu juego → ✅ Funciona
5. Si aparece "El juego aún no está disponible" → ❌ Revisa:
   - ¿El `slug` coincide con el nombre de la carpeta?
   - ¿El archivo se llama exactamente `game.html.twig`?
   - ¿Hay errores en la consola del navegador (F12)?

---

## 📝 Resumen Visual

```
┌─────────────────────────────────────────────────────────┐
│  1. BASE DE DATOS                                       │
│     INSERT INTO games (slug = 'bombas')                 │
└─────────────────┬───────────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────────────┐
│  2. CARPETA                                             │
│     templates/games/bombas/                            │
└─────────────────┬───────────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────────────┐
│  3. ARCHIVO                                             │
│     templates/games/bombas/game.html.twig              │
└─────────────────┬───────────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────────────┐
│  4. USUARIO HACE CLIC EN EL JUEGO                      │
└─────────────────┬───────────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────────────┐
│  5. SISTEMA BUSCA:                                      │
│     templates/games/bombas/game.html.twig              │
└─────────────────┬───────────────────────────────────────┘
                  │
        ┌─────────┴─────────┐
        │                   │
        ▼                   ▼
   ✅ ENCONTRADO      ❌ NO ENCONTRADO
        │                   │
        │                   ▼
        │          "Juego no disponible"
        │
        ▼
┌─────────────────────────────────────────────────────────┐
│  6. RENDERIZA EL JUEGO                                  │
│     Tu código se muestra en la página                   │
└─────────────────────────────────────────────────────────┘
```

---

## ✅ Checklist para Añadir tu Juego

- [ ] Juego añadido a la BD con un `slug` único
- [ ] Carpeta creada: `templates/games/{slug}/`
- [ ] Archivo creado: `templates/games/{slug}/game.html.twig`
- [ ] El nombre de la carpeta coincide EXACTAMENTE con el `slug`
- [ ] El archivo se llama exactamente `game.html.twig`
- [ ] El juego se renderiza correctamente al hacer clic
- [ ] Sistema de puntuación implementado (si aplica)
- [ ] Guardado de puntuaciones funcionando (si aplica)

---

## 🎓 Conceptos Clave

1. **Slug**: Identificador único del juego (debe coincidir con el nombre de la carpeta)
2. **Template**: Archivo `game.html.twig` que contiene tu juego
3. **Renderizado**: El proceso de mostrar tu template dentro de la página
4. **Variables**: Datos que tienes disponibles (`game`, `gameId`, `user`)

---

¿Tienes dudas? Revisa el `README.md` o pregunta al equipo. ¡Buena suerte con tu juego! 🎮

