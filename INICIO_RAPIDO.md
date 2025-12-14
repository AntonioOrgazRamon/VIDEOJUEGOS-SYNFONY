# 🚀 Inicio Rápido - Plataforma de Juegos

## Opción 1: Laragon (MÁS FÁCIL) ⭐

1. Descarga e instala Laragon: https://laragon.org/
2. Copia este proyecto a: `C:\laragon\www\plataforma_juegos`
3. Abre Laragon y haz clic en "Start All"
4. Abre tu navegador en: `http://plataforma_juegos.test`

## Opción 2: PHP 8.2 Standalone

1. Descarga PHP 8.2: https://windows.php.net/download/
   - Elige: **PHP 8.2.x Thread Safe (ZIP)**
2. Extrae a: `C:\php82`
3. Agrega `C:\php82` al PATH del sistema
4. Reinicia PowerShell
5. Ejecuta estos comandos:

```powershell
# Instalar dependencias
php composer.phar install

# Crear base de datos (asegúrate de que MySQL esté corriendo en XAMPP)
# Ejecuta el script SQL proporcionado en phpMyAdmin

# Iniciar servidor
php -S localhost:8000 -t public
```

6. Abre: `http://localhost:8000`

## Opción 3: Usar XAMPP con PHP 8.2

1. Descarga PHP 8.2: https://windows.php.net/download/
2. Extrae a: `C:\php82`
3. Copia `php.ini-development` a `php.ini`
4. Edita `php.ini` y descomenta:
   - `extension=pdo_mysql`
   - `extension=mbstring`
   - `extension=curl`
   - `extension=openssl`
5. En XAMPP, edita `C:\xampp\apache\conf\httpd.conf`:
   - Cambia `LoadModule php8_module` a apuntar a `C:\php82\php8apache2_4.dll`
6. Reinicia Apache

## Opción 4: Solo Base de Datos con XAMPP

Si solo quieres usar MySQL de XAMPP:

1. Asegúrate de que MySQL esté corriendo en XAMPP
2. Crea la base de datos `game_platform` ejecutando el script SQL
3. Usa PHP 8.2 standalone solo para la aplicación:

```powershell
# Descarga PHP 8.2 y agrégalo al PATH
php composer.phar install
php -S localhost:8000 -t public
```

## Configuración de Base de Datos

El archivo `.env` ya está configurado para:
- Base de datos: `game_platform`
- Usuario: `root`
- Sin contraseña
- Puerto: `3306`

## Comandos Útiles

```powershell
# Instalar dependencias
php composer.phar install

# Crear base de datos
php bin/console doctrine:database:create

# Sincronizar esquema
php bin/console doctrine:schema:update --force

# Cargar datos de prueba
php bin/console doctrine:fixtures:load

# Limpiar caché
php bin/console cache:clear
```



