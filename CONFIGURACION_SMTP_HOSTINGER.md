# 📧 Configuración SMTP con Hostinger

Esta guía te ayudará a configurar el envío de emails usando el SMTP de Hostinger.

## 🚀 Pasos para Configurar SMTP en Hostinger

### 1. Obtener Credenciales SMTP de Hostinger

1. **Accede a tu panel de Hostinger** (hPanel)
2. Ve a **Correo Electrónico** → **Cuentas de Correo**
3. Si no tienes una cuenta de correo, créala:
   - Email: `noreply@tudominio.com` (o el que prefieras)
   - Contraseña: (guárdala bien, la necesitarás)
4. **Anota estos datos**:
   - **Servidor SMTP**: `smtp.hostinger.com`
   - **Puerto**: `587` (TLS) o `465` (SSL)
   - **Usuario**: Tu email completo (ej: `noreply@tudominio.com`)
   - **Contraseña**: La contraseña de la cuenta de correo

### 2. Configurar en tu Proyecto Symfony

Añade estas variables a tu archivo `.env` en el servidor:

```env
# Configuración SMTP Hostinger
MAILER_DSN=smtp://noreply@tudominio.com:TU_CONTRASEÑA@smtp.hostinger.com:587
MAILER_FROM=noreply@tudominio.com
```

**Ejemplo real:**
```env
MAILER_DSN=smtp://noreply@plataformajuegos.com:MiPassword123@smtp.hostinger.com:587
MAILER_FROM=noreply@plataformajuegos.com
```

### 3. Configuración por Puerto

#### Puerto 587 (TLS - Recomendado)
```env
MAILER_DSN=smtp://usuario@tudominio.com:contraseña@smtp.hostinger.com:587
```

#### Puerto 465 (SSL)
```env
MAILER_DSN=smtp://usuario@tudominio.com:contraseña@smtp.hostinger.com:465
```

### 4. Verificar Configuración

Después de configurar, prueba el sistema:

1. Ve a la página de login
2. Falla el login (para que aparezca el botón)
3. Haz clic en "¿Olvidaste tu contraseña?"
4. Ingresa un email válido
5. Revisa tu bandeja de entrada (y la carpeta de spam)

## ⚠️ Consideraciones Importantes

### Límites de Hostinger

- **Límite diario**: Depende de tu plan (consulta con Hostinger)
- **Límite por hora**: Generalmente 500-1000 emails/hora
- Si te pasas → bloqueo temporal

### Problemas Comunes

#### 1. Correos que van a spam
- **Solución**: Configura SPF y DKIM en tu dominio
- En Hostinger: **Correo Electrónico** → **Autenticación de correo** → Activa SPF y DKIM

#### 2. Error de autenticación
- Verifica que el usuario sea el email completo: `noreply@tudominio.com`
- Verifica que la contraseña sea correcta
- Asegúrate de usar el puerto correcto (587 o 465)

#### 3. Conexión rechazada
- Verifica que el firewall no bloquee el puerto
- Prueba con el puerto 465 (SSL) si 587 no funciona

### Configuración SPF y DKIM (Recomendado)

Para mejorar la entrega de emails:

1. En Hostinger: **Correo Electrónico** → **Autenticación de correo**
2. Activa **SPF** y **DKIM**
3. Esto ayuda a que los emails no vayan a spam

## 🔧 Configuración Avanzada

### Para Desarrollo Local

Si quieres probar sin enviar emails reales:

```env
MAILER_DSN=null://null
```

Los emails se mostrarán en los logs de Symfony.

### Para Producción

Asegúrate de:

1. ✅ Usar un email profesional: `noreply@tudominio.com`
2. ✅ Configurar SPF y DKIM
3. ✅ No exponer credenciales en el código
4. ✅ Usar variables de entorno
5. ✅ Monitorear los límites de envío

## 📝 Resumen Rápido

1. Crea cuenta de correo en Hostinger: `noreply@tudominio.com`
2. Anota: servidor (`smtp.hostinger.com`), puerto (`587`), usuario y contraseña
3. Añade a `.env`:
   ```env
   MAILER_DSN=smtp://noreply@tudominio.com:CONTRASEÑA@smtp.hostinger.com:587
   MAILER_FROM=noreply@tudominio.com
   ```
4. Activa SPF y DKIM en Hostinger
5. ¡Listo! Los emails funcionarán

## 🐛 Solución de Problemas

### Error: "Connection could not be established"
- Verifica que `smtp.hostinger.com` sea correcto
- Verifica el puerto (587 o 465)
- Verifica que el firewall no bloquee

### Error: "Authentication failed"
- Verifica que el usuario sea el email completo
- Verifica la contraseña
- Asegúrate de que la cuenta de correo esté activa

### Los emails no llegan
- Revisa la carpeta de spam
- Verifica SPF y DKIM
- Revisa los logs: `var/log/prod.log`
- Verifica los límites de envío de Hostinger

## ✅ Checklist de Despliegue

Antes de subir a producción:

- [ ] Cuenta de correo creada en Hostinger
- [ ] `MAILER_DSN` configurado en `.env`
- [ ] `MAILER_FROM` configurado
- [ ] SPF activado en Hostinger
- [ ] DKIM activado en Hostinger
- [ ] Probado envío de email de prueba
- [ ] Verificado que no va a spam

¡Listo para producción! 🚀

