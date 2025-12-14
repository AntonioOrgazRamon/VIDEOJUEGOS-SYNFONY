# 📧 Configuración de Email para Recuperación de Contraseña

Para que el sistema de recuperación de contraseña funcione, necesitas configurar el envío de emails usando SMTP.

## 🚀 Configuración para Hostinger (Producción)

**Ver la guía completa**: [CONFIGURACION_SMTP_HOSTINGER.md](CONFIGURACION_SMTP_HOSTINGER.md)

### Configuración Rápida

Añade estas variables a tu archivo `.env` en el servidor:

```env
# Configuración SMTP Hostinger
MAILER_DSN=smtp://noreply@tudominio.com:TU_CONTRASEÑA@smtp.hostinger.com:587
MAILER_FROM=noreply@tudominio.com
```

**Ejemplo:**
```env
MAILER_DSN=smtp://noreply@plataformajuegos.com:MiPassword123@smtp.hostinger.com:587
MAILER_FROM=noreply@plataformajuegos.com
```

### Pasos en Hostinger

1. Crea cuenta de correo: `noreply@tudominio.com`
2. Anota: servidor (`smtp.hostinger.com`), puerto (`587`), usuario y contraseña
3. Activa SPF y DKIM en el panel de Hostinger
4. Configura las variables en `.env`

## 🔧 Otras Opciones SMTP

### Gmail
```env
MAILER_DSN=smtp://tu-email@gmail.com:tu-app-password@smtp.gmail.com:587
MAILER_FROM=tu-email@gmail.com
```

**Nota**: Necesitas crear una "Contraseña de aplicación" en tu cuenta de Google:
1. Ve a tu cuenta de Google → Seguridad
2. Activa la verificación en 2 pasos
3. Crea una "Contraseña de aplicación"
4. Usa esa contraseña en lugar de tu contraseña normal

### Outlook/Hotmail
```env
MAILER_DSN=smtp://tu-email@outlook.com:tu-contraseña@smtp-mail.outlook.com:587
MAILER_FROM=tu-email@outlook.com
```

### SendGrid
```env
MAILER_DSN=smtp://apikey:TU_API_KEY@smtp.sendgrid.net:587
MAILER_FROM=noreply@tudominio.com
```

### Mailtrap (Para desarrollo/testing)
```env
MAILER_DSN=smtp://usuario:contraseña@smtp.mailtrap.io:2525
MAILER_FROM=noreply@plataformajuegos.com
```

**Mailtrap es perfecto para desarrollo** - captura todos los emails sin enviarlos realmente.

## 🧪 Para Desarrollo Local (Sin Email Real)

Si solo quieres probar sin configurar email real:

```env
MAILER_DSN=null://null
```

Los emails se mostrarán en los logs de Symfony (`var/log/dev.log`).

## ✅ Verificar Configuración

Después de configurar, prueba el sistema:

1. Ve a la página de login
2. Falla el login (para que aparezca el botón)
3. Haz clic en "¿Olvidaste tu contraseña?"
4. Ingresa un email válido
5. Revisa tu bandeja de entrada (y la carpeta de spam)

## 🐛 Solución de Problemas

### Error: "Connection could not be established"
- Verifica que el servidor SMTP sea correcto
- Verifica que el puerto sea correcto (587 para TLS, 465 para SSL)
- Verifica que el firewall no bloquee la conexión

### Error: "Authentication failed"
- Verifica usuario y contraseña
- Para Gmail, usa una "Contraseña de aplicación"
- Verifica que la cuenta no tenga restricciones de seguridad

### Los emails no llegan
- Revisa la carpeta de spam
- Verifica que el email del destinatario sea correcto
- Revisa los logs: `var/log/dev.log` (desarrollo) o `var/log/prod.log` (producción)
- Verifica SPF y DKIM si usas Hostinger

## 📝 Nota Importante

En producción, asegúrate de:
- ✅ Usar un email profesional: `noreply@tudominio.com`
- ✅ Configurar SPF y DKIM en tu dominio
- ✅ No exponer credenciales en el código
- ✅ Usar variables de entorno para las credenciales
- ✅ Verificar los límites de envío de tu proveedor

