# AD Manager
Proyecto elaborado para la materia de virtualización en octavo cuatrimestre. Llevado a cabo por: Edgar Andres Hernández Avila, Jorge Oved Flores Lopez, Nahym Emilio Rodriguez Serrato, Diego Alonso Gonzales Gandara. 

### Objetivos 
- Realizar una aplicación que permita gestionar usuarios, unidades organizativas, grupos y equipos de Active Directory mediante una interfaz gráfica. 
- Instalar y configurar Windows Server, así como configurar los roles y características: AD, DNS, DHCP, NPA, (Certificados si es necesario). 
- Investigar e implementar autenticación 802.1x para autenticar a usuarios del dominio en el switch cisco. 
- Preparar dispositivos para el entorno

### Topología
- Servidor con Windows Server 2019
- Maquina con Ubuntu 24.04 LTS
- Varias máquinas con Windows 10/11
- Switch Cisco Catalyst 3750

### Aplicación ad-manager
La base de la aplicación utiliza <b>Python</b> junto con la librería <b>pywinrm</b>, con el fin de realizar una conexión hacia Windows Server 2019 para ejecutar comandos en <b>PowerShell</b>, para lograr esto se crea un script en Python con las dirección IP del servidor Windows, las credenciales, y el comando que se ejecutará en la consola. Un ejemplo sencillo puede ser ejecutar el comando <i>ipconfig</i>:

```python
# Antes se debe configurar winrm en Windows Server 
import winrm
# Dirección IP y credenciales
s = winrm.Session('http://172.16.10.2:5985/wsman', auth=('Administrador',''))
# Comando a ejecutar
r = s.run_ps('ipconfig')
# Imprimiendo el resultado sin formato
print(r)
```
De manera similar es posible puede obtener, agregar, eliminar y editar objetos de Active Directory mediante Powershell. Entendiendo las bases de la aplicación web, lo siguiente es complementar con otras herramientas para obtener un sistema con una interfaz más atractiva.

### Primeros pasos 
- Preparar el entorno
- Habilitar WinRM en Windows Server <br>
```powershell
# PowerShell como Administrador

# Instalar módulo de Active Directory
Install-WindowsFeature RSAT-AD-PowerShell

# Habilitar WinRM
winrm quickconfig -y
winrm set winrm/config/service '@{AllowUnencrypted="true"}'
winrm set winrm/config/service/auth '@{Basic="true"; Negotiate="true"}'

# Hosts de confianza (para laboratorio: todos)
Set-Item WSMan:\localhost\Client\TrustedHosts -Value "*" -Force

# Abrir puerto 5985 en el Firewall 
netsh advfirewall firewall add rule name="WinRM-HTTP" dir=in action=allow protocol=TCP localport=5985

# Reiniciar WinRM
Restart-Service WinRM

# Verificar escucha
netstat -an | findstr 5985
```
### Carpeta <i>opt</i>
La carpeta "opt" contienen todas las funciones y archivos necesarios para el funcionamiento de la aplicación. Dentro se encontrarán dos carpetas más: ad-sistema y ad-manager. 
#### ad-sistema (backend)
Contiene las clases, funciones, librerías y más para realizar la conexión entre el PowerShell de Windows Server y Python (winrm), parsear los resultado a formato JSON, utilizar Flask para crear una API que resuelva las solicitudes de la interfaz web.

#### ad-manager (frontend)
Es utilizado para los estilos, funciones, páginas, las acciones de la interfaz gráfica, el manejo de peticiones hacia la API y autenticación de usuarios a la base de datos local. Principalmente se utiliza PHP con extensiones para el funcionamiento. En esta carpeta se encuentra un script de sql para crear una base de datos que permite la auditoría y autenticación de usuarios (estos usuarios son locales). 
Esta carpeta será copiada a la ruta de NGINX ``/var/www/html/ad-manager/`` ó en otro caso deberá de indicarle dónde encontrar los archivos. 




