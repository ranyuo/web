La versión unificada está en main. Para esta actualización lee también CORRECCION-RECLAMOS.md y ejecuta actualizar.php para completar códigos de reclamos anteriores.

# INSTITUTO PUBLICO DE ICA para Hostinger: PHP y MySQL, sin Laravel

Esta es la versión elegida para desplegar en hPanel. No utiliza Laravel, Composer, npm, un servidor Node ni acceso SSH. Necesita PHP 8.2 o posterior, PDO MySQL, sesiones, Fileinfo y Zlib. Esta edición se revisó con PHP 8.4 y MariaDB 11.8. Las consultas no usan funciones exclusivas de MySQL 8.

Para una **instalación nueva de demostración**, sigue «Instalar en `tu-dominio.example`» con una base vacía. Actualizar una instalación existente conserva sus datos y no carga la identidad neutra.

## Actualizar tu instalación existente

No vuelvas a instalar ni borres tu base de datos. Los cambios se aplican sobre tu instalación actual.

1. Crea una copia de archivos y base de datos desde hPanel. Conserva tu `portal-private/config.php` en privado.
2. Descarga el ZIP de la rama indicada en GitHub y extráelo en tu computadora.
3. Sube los archivos de **portal-private** a la carpeta **portal-private** que ya existe, junto a `public_html`. Incluye todos los archivos y subcarpetas del paquete, no solo los que ya existían. Conserva también la carpeta privada **backups**, si existe. No reemplaces ni elimines **config.php**.
4. Sube el contenido de **public_html** dentro del **public_html** de tu dominio. Incluye **assets/vendor**, `.htaccess` y `.user.ini`. No crees `public_html/public_html`.
5. Abre **https://tu-dominio.example/actualizar.php**. Introduce el valor `install_key` de tu configuración privada y pulsa **Actualizar conservando los datos**. No uses la contraseña de MySQL ni la del administrador en ese campo.
6. Abre la intranet con tu usuario y contraseña habituales. La actualización conserva el contenido y las cuentas existentes. Esas cuentas mantienen su rol de administrador; crea los demás perfiles desde Usuarios.
7. Limpia la caché del sitio en hPanel y recarga el navegador con Ctrl+F5. Comprueba guardado y lectura desde otra ventana privada. Elimina **actualizar.php** y **recuperar.php** del directorio público cuando termines. Puedes borrar también **install.php** si ya estaba instalado.

El actualizador añade columnas y tablas; no carga de nuevo los datos de ejemplo. Guarda una instantánea privada del contenido y cuentas en `pisco_upgrade_backups` antes del cambio. La copia independiente de hPanel sigue siendo necesaria para recuperar también archivos de código si una subida queda incompleta.

## Instalar en `tu-dominio.example`

1. En hPanel entra a **Databases → Management**. Crea una base MySQL y un usuario con acceso a esa base. Copia los nombres completos con el prefijo de Hostinger. Guarda la contraseña en privado.
2. Abre **Files → File manager**. Usa un sitio de prueba o guarda una copia de los archivos del sitio antes de reemplazar su página principal.
3. Ve a la carpeta del dominio, **un nivel por encima de `public_html`**. Sube y extrae `portal-hostinger-php-mysql.zip` allí. La estructura final debe ser:

   ```text
   carpeta-del-dominio/
   ├── portal-private/
   │   ├── config.example.php
   │   ├── Portal.php
   │   ├── bootstrap.php
   │   ├── schema.sql
   │   ├── data/
   │   └── views/
   └── public_html/
       ├── index.php
       ├── install.php
       ├── accounts.php
       ├── portal-server.js
       ├── forms.css
       ├── .htaccess
       └── .user.ini
   ```

   **No coloques `portal-private` dentro de `public_html`.** No subas el ZIP al directorio público: contiene el código privado y los datos iniciales.
4. Dentro de `portal-private`, copia `config.example.php` como **`config.php`**. Edita únicamente estos valores desde el administrador de archivos:
   - `db_host`: normalmente `localhost`; usa el servidor que indique hPanel.
   - `db_name`: nombre completo de la base MySQL.
   - `db_user`: nombre completo del usuario de la base.
   - `db_password`: la contraseña elegida al crearla.
   - `install_key`: una clave aleatoria privada de al menos 32 caracteres. Puedes generarla con un gestor de contraseñas. Se pedirá al instalar y para operaciones privadas de actualización o recuperación.

   Conserva las comillas de PHP. Si un valor contiene una comilla simple, escríbela como `\'`; una barra invertida como `\\`. Mantén `secure_cookies => true`, porque el sitio usa HTTPS. No compartas este archivo ni lo subas a GitHub.
5. Abre **`https://tu-dominio.example/install.php`**. Introduce la clave de instalación y el nombre, usuario, correo y contraseña de tu administrador. La contraseña debe tener entre 12 y 72 caracteres (máximo 72 bytes).
6. Pulsa **Instalar y crear mi cuenta**. El instalador crea las tablas y carga el contenido inicial. No hay que importar un SQL manualmente. Al existir una cuenta, el instalador se bloquea y no permite reinstalar encima de ella.
7. Abre **`https://tu-dominio.example/`**. Entra por Intranet con tu cuenta. Puedes borrar `public_html/install.php` después de completar la instalación.

No cambies el nombre de `portal-private`, salvo que adaptes las rutas de los tres PHP públicos. Esta distribución está preparada para la raíz del dominio o subdominio, no para una subcarpeta de la URL.

## Comprobar que funciona

1. Verifica la franja amarilla en Inicio y su ausencia en Programas.
2. Inicia sesión, abre Noticias y cambia el título de una noticia de prueba.
3. Abre una ventana privada y visita Noticias: debe mostrar el título guardado. Recarga para confirmar que viene de MySQL.
4. Envía un mensaje desde Contacto en esa ventana privada. En el panel del administrador debe aparecer en Mensajes.
5. Registra un reclamo ficticio, guarda el código y comprueba el seguimiento con el documento indicado. No uses datos personales reales para las pruebas.
6. Cierra sesión y confirma que no puedes volver al panel sin identificarte.

## Qué incluye esta versión

- Diseño e interfaz del portal, incluida la franja amarilla de admisión únicamente en el inicio.
- Sesiones PHP de servidor y los 11 perfiles del modelo original con `password_hash` / `password_verify`.
- Persistencia compartida en MySQL con PDO y consultas preparadas.
- Protección CSRF, caducidad de sesión y límites de intentos.
- Edición administrativa del contenido, con detección de conflictos si dos personas guardan a la vez.
- Formularios públicos de contacto, suscripción, reclamos y expedientes, con validación en servidor, revisión de archivos y seguimiento.
- Instalador y actualizador protegidos por clave privada; gestión de perfiles y permisos; cambio de contraseña e invalidación de otras sesiones.
- Alumnos, horarios con detección de cruces, notas, certificación modular, títulos y consulta de documentos por código o DNI.
- CV docente, perfil profesional de estudiantes y egresados, registro y validación de empresas, ofertas y postulaciones.
- Archivos reales en MySQL, descargables según los permisos de cada cuenta; copia SQL completa desde Respaldo.
- Contador de visitas del servidor y avisos internos de la intranet. Las cifras ficticias del prototipo dejan de aparecer.

El contenido se conserva en la tabla JSON `pisco_state` para mantener compatible el editor existente. Las cuentas, archivos binarios, visitas, auditoría, ajustes, versión de esquema y contadores de solicitudes tienen tablas propias. Los cambios se envían por registro: dos usuarios pueden editar registros distintos simultáneamente; los conflictos sobre el mismo registro se rechazan. Los datos iniciales vienen del prototipo, no de una base de producción ni de los navegadores que usaron la página antigua.

El envío de correo se omite por decisión del usuario: los mensajes, respuestas y constancias quedan guardados en el portal, y los avisos internos siguen activos. No se presenta una entrega de correo como realizada. PDF, Excel y QR se sirven desde **assets/vendor**, con sus licencias incluidas. Algunas imágenes, fuentes y mapas conservan proveedores externos; las fuentes tienen alternativas locales del navegador. Usa HTTPS.

Los perfiles deben vincularse a registros reales: docente con su ficha de persona y docente académico, estudiante/egresado con el maestro de alumnos, empresa con su registro y coordinador con su programa. No existen contraseñas de demostración. El contenido inicial heredado necesita la revisión y sustitución institucional de textos, imágenes y documentos de ejemplo.

## Si aparece un error

- **Primero configura...**: falta `portal-private/config.php` o la carpeta no está en el lugar indicado.
- **No se pudo completar...**: revisa en hPanel los nombres completos de base y usuario, su contraseña y el servidor MySQL. Si sigue fallando, consulta el registro de errores de PHP. La aplicación no muestra credenciales ni errores internos al visitante.
- **La clave de instalación no coincide**: usa exactamente el valor `install_key` de tu configuración.
- **Sesión caducada al instalar**: abre la URL con HTTPS y recarga. Las cookies seguras requieren HTTPS.
- **Ves la página anterior**: comprueba que extrajiste los archivos en el `public_html` de este dominio y que se incluyó `.htaccess`. Conserva una copia y retira un posible `index.html` anterior que tenga prioridad.
- **Adjuntos demasiado grandes**: en PHP Options confirma `post_max_size=64M`, `upload_max_filesize=48M` y `memory_limit=256M`. La distribución incluye `.user.ini` con estos valores; Hostinger puede tardar unos minutos en aplicarlos. El límite total de adjuntos por solicitud es 40 MB.

Las tablas usan el prefijo `pisco_`; el instalador no borra tablas de otras aplicaciones. Evita instalar sobre otra copia de este mismo portal. Haz copias de seguridad de la base desde hPanel y conserva también `portal-private/config.php` en un lugar privado. No confíes solo en los archivos del sitio: el contenido compartido está en MySQL.

## Desarrollo y pruebas

Con PHP y las extensiones indicadas:

```sh
php -S 127.0.0.1:8083 -t public_html
```

Para HTTP local usa una configuración privada con `secure_cookies => false`; en el hosting usa siempre `true` y HTTPS.

Las pruebas de servidor no requieren PHPUnit ni Composer. Necesitan una base exclusiva cuyo nombre termine en `_test`, una configuración `portal-private/config.test.php` y permisos para crear tablas en ella:

```sh
PORTAL_CONFIG_FILE=/ruta/completa/portal-private/config.test.php php tests/run.php
PORTAL_CONFIG_FILE=/ruta/completa/portal-private/config.test.php php tests/parity.php
```

Las pruebas recrean únicamente las tablas `pisco_*` de esa base de pruebas. El script rechaza configuraciones cuyo archivo o base no indiquen que son de pruebas. Las pruebas de navegador de `tests/http-smoke.cjs` utilizan Playwright como herramienta externa de desarrollo, no como dependencia del hosting.


Las pruebas `tests/upgrade.php` simulan el esquema anterior en esa misma base desechable y comprueban que actualizarlo dos veces conserva exactamente el contenido y los hashes de contraseña. Cambian los perfiles de esa base al simular una instalación antigua: vuelve a ejecutar `parity.php` antes de probar los perfiles en el navegador.

`tests/browser-parity.cjs` recorre los módulos y editores, las rutas públicas y los accesos de cada perfil. `browser-workflows.cjs` prueba los formularios con PDF, la descarga por el administrador y el respaldo SQL; utiliza las bibliotecas incluidas en el paquete. `browser-profiles.cjs` guarda CV docente y CV de alumno desde la interfaz. Son herramientas de desarrollo; no se suben a Hostinger.

Para restaurar la copia SQL descargada desde Respaldo, usa phpMyAdmin en hPanel sobre la base del portal después de guardar una copia actual. El SQL reemplaza únicamente las tablas del portal incluidas en la copia; no incluye `config.php` ni los archivos PHP del sitio. Guarda esos archivos por separado. La copia contiene información privada y hashes de cuentas: no la publiques ni la dejes en `public_html`.

## Respaldos, restauración y programación

Entra como administrador a **Respaldo** o `/respaldos.php`. Puedes crear y descargar una copia portable `.piscobak`, subirla y restaurarla con tu contraseña y la confirmación RESTAURAR. Incluye contenido, cuentas, visitas y archivos; valida la integridad y crea automáticamente una copia anterior a la restauración. Las sesiones anteriores se invalidan. La auditoría y la configuración privada del hosting se conservan.

Las copias se guardan fuera del directorio público en `portal-private/backups`. No las publiques. Para archivos que superen el límite de subida de PHP, usa el administrador de archivos de hPanel para colocarlos allí, conservando su nombre original, y selecciónalos desde el panel de respaldos.

La misma pantalla permite activar la copia diaria y elegir hora y retención. Para que se ejecute sin visitas, crea en **Advanced → Cron Jobs** de hPanel una tarea PHP cada hora que ejecute la ruta absoluta a `portal-private/cron.php`; el panel muestra la ruta de tu instalación. No uses una URL pública ni incluyas contraseñas. La aplicación evita duplicar la copia diaria y elimina las copias programadas que exceden la retención elegida. La programación necesita esta configuración de hPanel; no depende del correo.

La auditoría completa, con filtros y CSV, está en `/auditoria.php` para administradores. Registra cambios de registros, cuentas, accesos y operaciones de respaldo; no sustituye una copia de seguridad independiente.

El único servicio deliberadamente pendiente es el correo saliente y la recuperación por correo. La recuperación del propietario mediante la clave privada continúa disponible en `recuperar.php` cuando necesites volver a subir ese archivo temporalmente.

Antes de actualizar, revisa `REVISION-PROFUNDA.md` y utiliza el paquete de la rama `main`, que incorpora las correcciones de la revisión funcional posterior.

La carpeta tests es exclusivamente para desarrollo: no la subas a Hostinger.
