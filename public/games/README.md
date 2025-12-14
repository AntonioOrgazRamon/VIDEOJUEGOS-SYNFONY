# 🎮 Guía para Añadir un Juego a la Plataforma

Esta carpeta contiene todos los juegos de la plataforma. Cada juego debe estar en su propia subcarpeta.

## 📁 Estructura de Carpetas

```
public/games/
├── README.md (este archivo)
├── ejemplo-juego/          (ejemplo de estructura)
│   ├── index.html         (archivo principal del juego)
│   ├── game.js            (lógica del juego - opcional)
│   ├── game.css           (estilos del juego - opcional)
│   └── assets/            (imágenes, sonidos, etc. - opcional)
│       ├── sprites/
│       └── sounds/
└── tu-juego/              (tu juego aquí)
    ├── index.html
    └── ...
```

## 🚀 Pasos para Añadir tu Juego

### Paso 1: Crear tu Carpeta de Juego

1. Crea una nueva carpeta dentro de `public/games/` con el nombre de tu juego (sin espacios, usa guiones: `mi-juego-awesome`)

### Paso 2: Crear el Archivo Principal

1. Crea un archivo `index.html` dentro de tu carpeta
2. Este archivo será el que se cargue cuando el usuario haga clic en el juego
3. Puedes usar HTML, CSS y JavaScript normalmente

### Paso 3: Estructura Mínima del `index.html`

Tu `index.html` debe tener esta estructura mínima:

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Juego</title>
    <style>
        /* Tus estilos aquí */
        body {
            margin: 0;
            padding: 0;
            background: #000;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: Arial, sans-serif;
        }
        
        #game-container {
            /* Contenedor principal de tu juego */
            width: 800px;
            height: 600px;
            background: #fff;
            position: relative;
        }
    </style>
</head>
<body>
    <div id="game-container">
        <!-- Tu juego aquí -->
        <h1>Mi Juego</h1>
        <p>¡Empieza a desarrollar!</p>
    </div>
    
    <script>
        // Tu código JavaScript aquí
        console.log('¡Juego cargado!');
    </script>
</body>
</html>
```

### Paso 4: Integrar tu Juego en la Base de Datos

Para que tu juego aparezca en la plataforma, necesitas añadirlo a la base de datos:

1. **Opción A: Desde el Admin Panel** (si está disponible)
   - Ve a la sección de administración
   - Añade un nuevo juego con el nombre y la ruta correcta

2. **Opción B: Directamente en la Base de Datos**
   - Abre tu gestor de base de datos (phpMyAdmin, MySQL Workbench, etc.)
   - Ve a la tabla `games`
   - Inserta un nuevo registro con estos campos:
     ```sql
     INSERT INTO games (name, slug, description, icon, is_active, created_at) 
     VALUES (
         'Nombre de tu Juego',                    -- name: nombre visible
         'slug-de-tu-juego',                     -- slug: URL-friendly (sin espacios, minúsculas)
         'Descripción de tu juego',              -- description: descripción del juego
         'icons/tu-icono.png',                    -- icon: ruta al icono (en public/icons/)
         true,                                    -- is_active: true para activarlo
         NOW()                                    -- created_at: fecha actual
     );
     ```

3. **Opción C: Usando el Controlador** (más avanzado)
   - Puedes crear un script PHP temporal para añadir el juego

### Paso 5: Añadir el Icono del Juego

1. Crea o busca un icono para tu juego (recomendado: 200x200px, formato PNG)
2. Guárdalo en `public/icons/` con un nombre descriptivo (ej: `mi-juego.png`)
3. Usa ese nombre en el campo `icon` de la base de datos

### Paso 6: Configurar la Ruta del Juego

Una vez que tu juego esté en la base de datos, necesitas modificar el controlador para que cargue tu juego:

1. Ve a `src/Controller/HomeController.php`
2. En el método `play()`, busca donde se carga el juego
3. Añade la lógica para cargar tu juego desde `public/games/tu-juego/index.html`

**Ejemplo de código en `play()`:**

```php
// En HomeController.php, método play()
$gamePath = 'games/' . $game->getSlug() . '/index.html';
$gameExists = file_exists($this->getParameter('kernel.project_dir') . '/public/' . $gamePath);

return $this->render('game/play.html.twig', [
    'user' => $user,
    'game' => $game,
    'topScores' => $topScores,
    'themeMode' => $themeMode,
    'language' => $language,
    'gamePath' => $gameExists ? $gamePath : null, // Ruta al juego si existe
]);
```

Luego en `templates/game/play.html.twig`, carga el juego:

```twig
<div class="game-frame" id="gameFrame">
    {% if gamePath %}
        <iframe src="/{{ gamePath }}" frameborder="0" style="width: 100%; height: 100%; border: none;"></iframe>
    {% else %}
        <div class="game-placeholder">
            <p>🎮</p>
            <p>El juego se cargará aquí</p>
            <p style="font-size: 14px; margin-top: 10px; opacity: 0.7;">Contenedor del juego listo</p>
        </div>
    {% endif %}
</div>
```

## 📝 Convenciones y Buenas Prácticas

### Nombres de Archivos y Carpetas
- ✅ Usa nombres en minúsculas
- ✅ Usa guiones en lugar de espacios: `mi-juego-awesome`
- ✅ No uses caracteres especiales: `á`, `ñ`, `@`, `#`, etc.
- ❌ Evita: `Mi Juego`, `mi_juego`, `miJuego`
- ✅ Mejor: `mi-juego`, `snake-game`, `puzzle-adventure`

### Estructura del Juego
- El archivo principal **debe** llamarse `index.html`
- Puedes crear subcarpetas para organizar assets (imágenes, sonidos, etc.)
- Mantén los archivos organizados y con nombres descriptivos

### Dimensiones Recomendadas
- **Ancho máximo**: 1920px
- **Alto máximo**: 1080px
- **Aspecto recomendado**: 16:9 (ej: 1280x720, 1600x900)
- El contenedor se ajustará automáticamente, pero es mejor diseñar para estas dimensiones

### Guardar Puntuaciones

Si tu juego tiene un sistema de puntuación, puedes guardarlo usando esta función:

```javascript
// Función para guardar la puntuación
async function saveScore(score, gameId) {
    try {
        const response = await fetch('/api/game/save-score', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                game_id: gameId,
                score: score,
                duration: gameDuration, // tiempo en segundos (opcional)
                level: currentLevel    // nivel alcanzado (opcional)
            })
        });
        
        const data = await response.json();
        if (data.success) {
            console.log('Puntuación guardada:', score);
        }
    } catch (error) {
        console.error('Error al guardar puntuación:', error);
    }
}

// Ejemplo de uso cuando el juego termina
// saveScore(1500, 1); // puntuación 1500, juego ID 1
```

## 🎯 Ejemplo Completo

Mira la carpeta `ejemplo-juego/` para ver un ejemplo básico de cómo estructurar tu juego.

## ❓ Preguntas Frecuentes

**P: ¿Puedo usar frameworks como Phaser, Three.js, etc.?**
R: ¡Sí! Puedes usar cualquier framework o librería que quieras. Solo asegúrate de incluir los archivos necesarios en tu carpeta o usar CDN.

**P: ¿Cómo accedo a datos del usuario desde mi juego?**
R: Por ahora, el juego se carga en un iframe, así que no tienes acceso directo. Si necesitas datos del usuario, puedes hacer peticiones AJAX a la API.

**P: ¿Puedo usar sonidos e imágenes?**
R: ¡Por supuesto! Guárdalos en una subcarpeta `assets/` dentro de tu juego y referencia los paths relativos.

**P: ¿Qué pasa si mi juego necesita más espacio?**
R: El contenedor se ajusta automáticamente. Solo asegúrate de que tu juego sea responsive o tenga dimensiones fijas razonables.

## 🆘 ¿Necesitas Ayuda?

Si tienes problemas:
1. Revisa que tu carpeta y archivos tengan los nombres correctos
2. Verifica que el juego esté en la base de datos
3. Comprueba que la ruta en el controlador sea correcta
4. Revisa la consola del navegador (F12) para ver errores

¡Buena suerte con tu juego! 🎮🚀

