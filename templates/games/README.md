# 🎮 Guía para Crear tu Juego en la Plataforma

Esta carpeta contiene todos los juegos de la plataforma. Cada juego debe estar en su propia subcarpeta con su template y lógica.

> 📖 **¿Quieres una guía paso a paso?** Lee **[COMO_CREAR_UN_JUEGO.md](../COMO_CREAR_UN_JUEGO.md)** para una explicación detallada y clara.

> 📖 **¿No entiendes cómo funciona?** Lee primero `EXPLICACION.md` para entender el sistema completo paso a paso.

## 📁 Estructura de Carpetas

```
templates/games/
├── README.md (este archivo)
└── tu-juego/                (tu juego aquí)
    └── game.html.twig        (template de tu juego - OBLIGATORIO)
```

## 🚀 Pasos para Crear tu Juego

### Paso 1: Crear la Carpeta de tu Juego

1. Crea una nueva carpeta dentro de `templates/games/` con el nombre de tu juego
2. **IMPORTANTE**: El nombre de la carpeta debe coincidir EXACTAMENTE con el `slug` del juego en la base de datos
3. Ejemplo: Si el slug en la BD es `bombas`, crea `templates/games/bombas/`

### Paso 2: Crear el Template del Juego

1. Crea un archivo `game.html.twig` dentro de tu carpeta
2. Este archivo se renderizará dentro de la página del juego
3. Puedes usar HTML, CSS y JavaScript normalmente
4. Tienes acceso a las variables: `game`, `gameId`, `user`

### Paso 3: Estructura Mínima del Template

Tu `game.html.twig` debe tener esta estructura mínima:

```twig
{# 
    Juego: Nombre de tu Juego
    Template del juego que se renderiza dentro de play.html.twig
#}

<style>
    .tu-juego-container {
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
    
    /* Tus estilos aquí */
</style>

<div class="tu-juego-container">
    <!-- Tu juego aquí -->
    <h1>{{ game.name }}</h1>
    <p>¡Empieza a desarrollar!</p>
</div>

<script>
    // Tu código JavaScript aquí
    console.log('Juego: {{ game.name }}');
    console.log('Game ID: {{ gameId }}');
    console.log('Usuario: {{ user.username }}');
</script>
```

## 📝 Variables Disponibles

En tu template tienes acceso a:

- `{{ game }}` - Objeto del juego completo
  - `{{ game.name }}` - Nombre del juego
  - `{{ game.slug }}` - Slug del juego
  - `{{ game.description }}` - Descripción
  - `{{ game.id }}` - ID del juego
- `{{ gameId }}` - ID del juego (útil para guardar puntuaciones)
- `{{ user }}` - Usuario actual
  - `{{ user.username }}` - Nombre de usuario
  - `{{ user.email }}` - Email
  - `{{ user.id }}` - ID del usuario

## 🎯 Guardar Puntuaciones

Si tu juego tiene un sistema de puntuación, puedes guardarlo así:

```javascript
async function saveScore(score) {
    try {
        const response = await fetch('/api/game/save-score', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                game_id: {{ gameId }},
                score: score,
                duration: gameDuration,  // tiempo en segundos (opcional)
                level: currentLevel      // nivel alcanzado (opcional)
            })
        });
        
        const data = await response.json();
        if (data.success) {
            console.log('Puntuación guardada:', score);
            // Opcional: mostrar notificación de éxito
        }
    } catch (error) {
        console.error('Error al guardar puntuación:', error);
    }
}

// Ejemplo de uso cuando el juego termina
// saveScore(1500);
```

## 📝 Convenciones y Buenas Prácticas

### Nombres de Carpetas
- ✅ Usa nombres en minúsculas
- ✅ Usa guiones en lugar de espacios: `mi-juego-awesome`
- ✅ **DEBE coincidir EXACTAMENTE con el `slug` del juego en la base de datos**
- ❌ Evita: `Mi Juego`, `mi_juego`, `miJuego`
- ✅ Mejor: `mi-juego`, `snake-game`, `puzzle-adventure`

### Estructura del Template
- El archivo **debe** llamarse exactamente `game.html.twig`
- Puedes incluir CSS dentro de `<style>` tags
- Puedes incluir JavaScript dentro de `<script>` tags
- El contenedor principal debe ocupar el 100% del espacio disponible

### Dimensiones
- El contenedor del juego tiene un tamaño máximo de 1920x1080px
- El juego se ajusta automáticamente, pero es mejor diseñar para estas dimensiones
- Usa unidades relativas (%, vh, vw) para mejor responsividad

### Estilos
- El fondo de la página del juego es oscuro (rgba(0, 0, 0, 0.95))
- Diseña tu juego con colores que contrasten bien
- Puedes usar cualquier paleta de colores que quieras

## 💡 Ideas para tu Juego

- **Canvas Games**: Usa `<canvas>` para juegos 2D
- **DOM Games**: Usa HTML/CSS para juegos de lógica o puzzles
- **Frameworks**: Puedes usar Phaser.js, Three.js, etc. (incluye los scripts en tu template)
- **Multiplayer**: Puedes hacer peticiones AJAX para juegos multijugador

## 🔧 Organización del Código

Puedes organizar tu código de varias formas:

1. **Todo en el template** (recomendado para juegos simples)
   - Todo el código en `game.html.twig`
   - CSS en `<style>` tags
   - JavaScript en `<script>` tags

2. **JavaScript externo** (para juegos complejos)
   - Crea `public/js/games/tu-juego.js`
   - Inclúyelo en tu template: `<script src="/js/games/tu-juego.js"></script>`

3. **CSS externo** (opcional)
   - Crea `public/css/games/tu-juego.css`
   - Inclúyelo en tu template: `<link rel="stylesheet" href="/css/games/tu-juego.css">`

## 📚 Recursos Útiles

- [MDN Web Docs](https://developer.mozilla.org/) - Documentación de HTML, CSS y JavaScript
- [Phaser.js](https://phaser.io/) - Framework para juegos 2D
- [Three.js](https://threejs.org/) - Framework para juegos 3D
- [Canvas API](https://developer.mozilla.org/en-US/docs/Web/API/Canvas_API) - Para juegos con canvas
- [PixiJS](https://pixijs.com/) - Motor de renderizado 2D

## 🆘 ¿Necesitas Ayuda?

Si tienes problemas:
1. Revisa que tu carpeta tenga el mismo nombre que el `slug` del juego en la BD
2. Verifica que el archivo se llame exactamente `game.html.twig`
3. Comprueba que el juego esté en la base de datos con el `slug` correcto
4. Revisa la consola del navegador (F12) para ver errores

## ✅ Checklist para tu Juego

- [ ] Carpeta creada en `templates/games/{slug}/` (slug = nombre en BD)
- [ ] Archivo `game.html.twig` creado
- [ ] Juego añadido a la base de datos con el `slug` correcto
- [ ] El juego se renderiza correctamente al hacer clic
- [ ] Sistema de puntuación implementado (si aplica)
- [ ] Guardado de puntuaciones funcionando (si aplica)
- [ ] Controles funcionando correctamente
- [ ] Game Over y reinicio implementados (si aplica)

## 📌 Ejemplo de Flujo Completo

1. **Añadir juego a la BD:**
   ```sql
   INSERT INTO games (name, slug, description, icon, is_active, created_at) 
   VALUES ('Mi Juego', 'mi-juego', 'Descripción', 'icons/mi-juego.png', true, NOW());
   ```

2. **Crear carpeta:**
   - Crear: `templates/games/mi-juego/`

3. **Crear template:**
   - Crear: `templates/games/mi-juego/game.html.twig`
   - Añadir tu código HTML/CSS/JS

4. **Probar:**
   - Hacer clic en el juego en la plataforma
   - Tu juego debería aparecer automáticamente

¡Buena suerte con tu juego! 🎮🚀
