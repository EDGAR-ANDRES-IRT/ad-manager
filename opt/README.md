# AD Manager OPT — Guía de Instalación

Arquitectura de dos componentes:
- **ad-sistema/** → API backend en Python/Flask + pywinrm (se comunica con Windows Server)
- **ad-manager/** → Frontend web en PHP (se comunica SOLO con la API Flask, nunca con WinRM)

```
Navegador → NGINX → PHP (ad-manager)  →  Flask API (ad-sistema) → WinRM → Windows Server AD
                    PHP (ad-manager)  ←  Flask API              ←        ←
```

---

## 1. Requisitos

### Servidor web (Linux)
- Python 3.8+, pip, venv
- PHP 8.3+ con extensiones: `pdo`, `pdo_mysql`, `curl`, `mbstring`
- NGINX + PHP-FPM  *(recomendado)*
- MySQL 9.x

### Windows Server 2019
- WinRM habilitado en HTTP (puerto 5985)
- Usuario Administrador con acceso remoto
- Módulo RSAT-AD-PowerShell instalado

---

## 2. Preparar Windows Server

Ejecuta en PowerShell como Administrador:

```powershell
# Instalar módulo AD si no está
Install-WindowsFeature RSAT-AD-PowerShell

# Habilitar WinRM
winrm quickconfig -y
winrm set winrm/config/service '@{AllowUnencrypted="true"}'
winrm set winrm/config/service/auth '@{Basic="true"; Negotiate="true"}'

# Hosts de confianza (para laboratorio: todos)
Set-Item WSMan:\localhost\Client\TrustedHosts -Value "*" -Force

# Abrir firewall
netsh advfirewall firewall add rule name="WinRM-HTTP" dir=in action=allow protocol=TCP localport=5985

# Reiniciar WinRM
Restart-Service WinRM

# Verificar escucha
netstat -an | findstr 5985
```

---

## 3. Instalar Backend Flask (ad-sistema)

```bash
# Copiar archivos
sudo cp -r ad-sistema/ /opt/ad-sistema/
cd /opt/ad-sistema

# Crear entorno virtual e instalar dependencias
python3 -m venv venv
source venv/bin/activate
pip install flask flask-cors pywinrm requests-ntlm requests-kerberos gunicorn

# Probar conexión manualmente (¡hazlo antes de continuar!)
python3 -c "
import winrm
s = winrm.Session('http://172.16.10.2:5985/wsman',
                  auth=('Administrador','Eaha823'),
                  transport='ntlm')
r = s.run_ps('Get-Date')
print(r.std_out.decode())
"

# Probar la API Flask directamente
python3 app.py &
curl http://127.0.0.1:5000/api/ping
curl http://127.0.0.1:5000/api/dominio/stats
# Ctrl+C para detener

# Instalar como servicio systemd
sudo mkdir -p /var/log/ad-sistema
sudo cp ad-sistema.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now ad-sistema
sudo systemctl status ad-sistema
```

---

## 4. Instalar Frontend PHP (ad-manager)

```bash
# Instalar PHP-FPM y dependencias
sudo apt install nginx php8.3-fpm php8.3-mysql php8.3-curl php8.3-mbstring -y

# Copiar archivos
sudo cp -r ad-manager/ /var/www/html/ad-manager/
sudo chown -R www-data:www-data /var/www/html/ad-manager/

# Configurar base de datos
sudo mysql -u root -p < /var/www/html/ad-manager/database.sql

# Editar configuración si es necesario
sudo nano /var/www/html/ad-manager/config/config.php
```

---

## 5. Configurar NGINX

```bash
sudo cp nginx-ad-manager.conf /etc/nginx/sites-available/ad-manager
sudo ln -s /etc/nginx/sites-available/ad-manager /etc/nginx/sites-enabled/ad-manager
sudo rm -f /etc/nginx/sites-enabled/default   # quitar sitio por defecto si interfiere
sudo nginx -t
sudo systemctl reload nginx
```

### ¿Por qué NGINX en lugar de Apache?

- Flask/Gunicorn trabaja nativamente como servidor WSGI detrás de NGINX
- NGINX actúa como proxy inverso: `/api/*` va a Flask, el resto va a PHP-FPM
- Mejor rendimiento para conexiones concurrentes
- Sin necesidad de `mod_wsgi` ni configuraciones complejas de Apache

---

## 6. Verificar que todo funciona

```bash
# 1. Flask responde
curl http://127.0.0.1:5000/api/ping
# → {"status":"success","data":{"message":"API operativa"}}

# 2. Flask llega al AD
curl http://127.0.0.1:5000/api/dominio/stats
# → {"status":"success","data":{"Users":X,"Groups":X,...}}

# 3. NGINX hace proxy correctamente (desde otra máquina)
curl http://IP_SERVIDOR/api/ping
# → mismo resultado que arriba

# 4. Abrir en navegador
http://IP_SERVIDOR/ad-manager/
# Usuario: admin | Contraseña: admin123
```

---

## 7. Estructura de archivos final

```
/opt/ad-sistema/
    ├── ad_helper.py          # Clase ADManager — todas las ops de AD
    ├── app.py                # API Flask REST completa
    ├── ad-sistema.service    # Servicio systemd
    ├── nginx-ad-manager.conf # Config NGINX
    └── venv/                 # Entorno virtual Python

/var/www/html/ad-manager/
    ├── index.php             # Login
    ├── logout.php
    ├── database.sql          # Schema MySQL
    ├── config/
    │   └── config.php        # URL API + config MySQL
    ├── includes/
    │   ├── api_client.php    # Cliente HTTP → Flask (reemplaza winrm.php)
    │   ├── auth.php          # Sesiones y autenticación local
    │   ├── database.php      # Conexión MySQL
    │   ├── header.php        # Layout
    │   └── footer.php
    └── pages/
        ├── dashboard.php     # Estadísticas del dominio
        ├── users.php         # CRUD usuarios AD
        ├── groups.php        # CRUD grupos AD
        ├── ous.php           # CRUD unidades organizativas
        ├── computers.php     # Ver/mover/eliminar equipos
        ├── search.php        # Búsqueda global
        ├── app_users.php     # Usuarios de esta app web
        └── logs.php          # Registro de actividad
```

---

## 8. Rutas disponibles en la API Flask

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/ping` | Health check |
| GET | `/api/dominio` | Info del dominio |
| GET | `/api/dominio/stats` | Estadísticas |
| GET | `/api/ous` | Listar OUs |
| POST | `/api/ous` | Crear OU |
| DELETE | `/api/ous` | Eliminar OU |
| GET | `/api/usuarios` | Listar usuarios |
| GET | `/api/usuarios?ou=DN` | Usuarios por OU |
| POST | `/api/usuarios` | Crear usuario |
| PUT | `/api/usuarios/<sam>` | Actualizar usuario |
| DELETE | `/api/usuarios/<sam>` | Eliminar usuario |
| POST | `/api/usuarios/<sam>/toggle` | Activar/Desactivar |
| POST | `/api/usuarios/<sam>/reset-password` | Restablecer contraseña |
| POST | `/api/usuarios/<sam>/unlock` | Desbloquear cuenta |
| POST | `/api/usuarios/<sam>/mover` | Mover a otra OU |
| GET | `/api/grupos` | Listar grupos |
| POST | `/api/grupos` | Crear grupo |
| DELETE | `/api/grupos/<sam>` | Eliminar grupo |
| GET | `/api/grupos/<sam>/miembros` | Miembros del grupo |
| POST | `/api/grupos/<sam>/miembros` | Agregar miembro |
| DELETE | `/api/grupos/<sam>/miembros/<user>` | Quitar miembro |
| GET | `/api/equipos` | Listar equipos |
| DELETE | `/api/equipos/<sam>` | Eliminar equipo |
| POST | `/api/equipos/<sam>/mover` | Mover equipo |
| GET | `/api/buscar?q=texto` | Búsqueda global |

---

## Solución de problemas

### `systemctl status ad-sistema` muestra errores
```bash
# Ver logs completos
journalctl -u ad-sistema -n 50
# Ver logs de gunicorn
cat /var/log/ad-sistema/error.log
```

### Flask responde pero el PHP no recibe datos
```bash
# Verificar que NGINX hace el proxy correctamente
curl -v http://localhost/api/ping
# Verificar logs de NGINX
tail -f /var/log/nginx/error.log
```

### Error de autenticación en WinRM
```bash
# Probar con ntlm explícito
python3 -c "
import winrm
s = winrm.Session('http://172.16.10.2:5985/wsman',
    auth=('Administrador','Eaha823'), transport='ntlm')
print(s.run_ps('whoami').std_out.decode())
"
```

### PHP no puede conectar a MySQL
```bash
# Verificar extensión PDO
php -m | grep pdo
# Verificar credenciales en config/config.php
```
