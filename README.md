# INSTITUTO PUBLICO DE ICA — Portal de demostración

[Control de calidad ampliado, correcciones y pasos para actualizar](CONTROL-CALIDAD-ESTANDAR.md).

Esta edición incluye identidad, personas, empresas, contactos e imágenes de demostración. Los correos `.example` son referenciales y las redes/WhatsApp quedan sin destinos activos. Conserva los módulos, rutas, relaciones e identificadores internos del sistema original.

Los datos neutros se cargan en **instalaciones nuevas** desde `portal-private/data/portal.json`. Actualizar archivos sobre una instalación existente no sustituye el contenido guardado en MySQL. Para presentar esta demo, utiliza una base nueva y la instalación habitual; no reinstales sobre una base de producción. Las evidencias históricas de `evidencias/` corresponden al proyecto original y no forman parte del contenido de esta demo.

La rama `main` contiene la versión unificada con las correcciones de la revisión profunda, el cargo de Mesa de Partes y el Libro de Reclamaciones.

[Descargar la versión completa de main](https://github.com/ranyuo/web/archive/refs/heads/main.zip)

## Tecnologías y carpetas

PHP sin Laravel, MySQL, HTML, CSS y JavaScript. No requiere Composer, Node ni compilar archivos para desplegar en Hostinger. Las bibliotecas de PDF, Excel y QR están incluidas.

- `public_html/`: archivos públicos del portal.
- `portal-private/`: lógica PHP, plantillas, configuración y acceso a MySQL. Debe quedar al mismo nivel que public_html.
- `tests/`: pruebas de desarrollo; no subir a Hostinger.
- `evidencias/`: PDF e imágenes de pruebas con datos sintéticos; no subir a Hostinger ni presentarlos como documentos oficiales.

## Actualizar tu instalación existente

1. Guarda una copia de archivos y base de datos en hPanel.
2. Descarga el ZIP y sube el contenido de portal-private a la carpeta privada existente. **Conserva config.php y backups**; tu configuración privada no está incluida en este repositorio.
3. Sube el contenido de public_html al public_html del dominio, incluidos assets/vendor y los archivos ocultos. No crees public_html/public_html.
4. Abre https://tu-dominio.example/actualizar.php y ejecuta la actualización con tu install_key. Este paso completa códigos de verificación de reclamos anteriores sin cambiar sus números de seguimiento, usuarios o contraseñas.
5. Limpia la caché y comprueba el portal desde una ventana privada. Vuelve a descargar los cargos y constancias para ver sus cambios.
6. Al finalizar elimina actualizar.php, recuperar.php e install.php del directorio público. No reinstales ni importes schema.sql sobre tu base existente.

[Instrucciones completas](INSTRUCCIONES.md) · [Corrección del Libro de Reclamaciones](CORRECCION-RECLAMOS.md) · [Corrección del cargo de Mesa de Partes](CORRECCION-CARGO.md)

## Correcciones incluidas

- Cargos nuevos de Mesa de Partes con código aleatorio de 16 caracteres; los anteriores conservan su código completo y ajustan el texto antes del sello.
- Reclamos y quejas con verificación persistente de 16 caracteres y constancia/hoja PDF con QR de seguimiento.
- Consulta de reclamos por documento completo o verificación; validaciones claras y compatibilidad con referencias anteriores.
- Actualización de reclamos anteriores sin cambiar sus números RCL ni su contenido.
- Correcciones de guardado, sesiones, permisos, versiones, documentos, publicación y formularios de la revisión profunda.

## Pruebas y evidencias

[Revisión profunda por módulo](REVISION-PROFUNDA.md) · [Verificación general](VERIFICACION.md) · [Control de calidad](CONTROL-CALIDAD.md)

[PDF y consulta del Libro de Reclamaciones](evidencias/reclamos/README.md) · [PDF del cargo con ambas longitudes de código](evidencias/README.md)

Las pruebas se realizaron en PHP/MySQL y Chromium locales. Los QR del Libro fueron decodificados con un lector independiente y su enlace abrió el seguimiento en otra sesión. La aplicación de estos cambios en Hostinger requiere la subida y la actualización indicadas arriba; publicar en GitHub no modifica el hosting.

El correo saliente y la recuperación por correo continúan pendientes por indicación del propietario. Los mensajes y respuestas se guardan en el portal.

La mejora de **Usuarios y roles** incluye edición de permisos por perfil, filtros y control de sesiones. Consulte [MEJORA-USUARIOS-ROLES.md](MEJORA-USUARIOS-ROLES.md) para actualizar Hostinger y conocer el alcance de los permisos editables.
