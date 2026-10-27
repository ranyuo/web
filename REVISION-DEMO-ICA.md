# Revisión de INSTITUTO PUBLICO DE ICA

Fecha: 27 de octubre de 2026.

La revisión ampliada y los resultados más recientes están en [CONTROL-CALIDAD-ESTANDAR.md](CONTROL-CALIDAD-ESTANDAR.md). Este documento conserva el alcance y las observaciones de la revisión inicial.

## Corrección posterior: cierre de sesión

La prueba en Hostinger reveló una ruta no cubierta por la revisión inicial: el botón de salida envía `DELETE /api/session` sin cuerpo y el servidor intentaba leer JSON antes de cerrar la sesión. Esto producía HTTP 422 y mantenía la sesión abierta. `public_html/index.php` ahora procesa esa ruta después de verificar CSRF y antes de leer el cuerpo.

Se añadió y ejecutó `tests/browser-logout.cjs`: reproduce la solicitud sin cuerpo, comprueba el rechazo de CSRF incorrecto y verifica los botones de salida desde el escritorio y el módulo de alumnos. En ambos casos, tras cerrar y recargar, el usuario queda sin sesión y las cuentas privadas devuelven 403. Esta corrección requiere reemplazar únicamente `public_html/index.php` en una instalación existente; conserva la base y la configuración privada.

Resultado: las pruebas locales de esta edición terminaron aprobadas después de corregir los datos y ajustar los preparativos de las pruebas. Se ejecutaron los 15 scripts PHP y los 31 scripts JavaScript de `tests/`. Las pruebas que modifican contenido utilizaron exclusivamente bases desechables terminadas en `_test`; no se accedió a Hostinger ni a una base de producción.

## Entorno y alcance

PHP 8.4.27, MariaDB 11.8.6, Chromium y Playwright. Se comprobó una instalación nueva con los datos neutros exactos y otra base con los escenarios de pruebas del proyecto. Las bibliotecas PDF, Excel y QR se cargaron desde los archivos incluidos. Las solicitudes a recursos externos se excluyeron de las pruebas de navegador.

| Área | Comprobaciones y resultado |
| --- | --- |
| Instalación nueva | Tablas, administrador, bloqueo de reinstalación, contenido ICA y persistencia. Aprobado. |
| Datos iniciales | 175 registros pasan las validaciones de edición del servidor; identidad, contactos, referencias académicas y logo coherentes. Aprobado. |
| Alumnos de la demo | Creación de los cinco accesos, ingreso como estudiante/egresado según su estado y aislamiento de sus datos. Aprobado. |
| Documentos de la demo | Consulta pública de los dos certificados modulares y el título de ejemplo. Aprobado. |
| Portal público | 71 rutas y 5.630 referencias de enlaces revisadas; sin enlaces internos rotos, errores JavaScript ni desbordamientos en 320, 375, 768 y 1440 píxeles. Aprobado. |
| Administración y perfiles | 694 rutas y 32.984 referencias de enlaces, con los 11 perfiles; accesos, pantallas auxiliares y diseño adaptable. Aprobado. |
| Edición de contenido | 50 operaciones de creación/edición y 101 operaciones de ciclo de vida, incluidas papelera, restauración y eliminación cuando corresponden. Aprobado. |
| Portada y configuración | Visibilidad y orden de los 11 bloques, comunicado, identidad, UIT, estadísticas, licenciamiento, publicación y conservación de cambios. Aprobado. |
| Docentes y egresados | Filtros, perfiles, CV, autorización, adjuntos y persistencia desde la interfaz. Aprobado. |
| Gestión académica | Alumnos, unidades, módulos, docentes, ambientes, horarios, detección de cruces, publicación, notas, certificados y títulos. Aprobado. |
| Admisión, matrícula y becas | Edición, vigencia, publicación, importación y rechazo de filas inválidas; consulta con identificación enmascarada. Aprobado. |
| Contacto | Validación, captcha, recepción, respuesta y consulta administrativa. Aprobado. |
| Mesa de Partes | Registro, PDF adjunto, cargo, seguimiento, derivación, atención e historial. Aprobado. |
| Cargos anteriores y nuevos | Códigos de 16 y 32 caracteres, PDF sin superposición con el sello y consulta válida. Se preparó un expediente antiguo sintético en la base de pruebas para la comprobación de compatibilidad. Aprobado. |
| Libro de Reclamaciones | Reclamo/queja, evidencia, constancia, QR, consulta, respuestas y actualización de códigos anteriores. Aprobado. |
| Bolsa laboral | Registro y validación de empresas, ofertas, postulaciones, CV y acceso autorizado a documentos. Aprobado. |
| Cuentas y permisos | Roles, edición/revocación de permisos, contraseñas, cierre de sesiones, recuperación privada y rechazo de accesos ajenos. Aprobado. |
| Integridad y errores | CSRF, límites de acceso, MIME de archivos, inmutabilidad de registros, validaciones y errores de guardado sin anunciar éxito. Aprobado. |
| Documentos y exportaciones | Generación/lectura XLSX, PDF multipágina, QR, emisión, titularidad, revocación y descarga autorizada. Aprobado. |
| Concurrencia | 20 ediciones simultáneas desde dos perfiles y una revisión inicial común, con cuatro procesos de servicio, sin pérdida de cambios. Aprobado. |
| Respaldos y auditoría | SQL con archivos binarios, copia portable, restauración de contenido y cuentas, historial y exportación CSV. Aprobado. |
| Programación de respaldos | Configuración desde la interfaz y ejecución real del cron; una segunda ejecución no duplicó el respaldo diario. Aprobado. |
| Actualización | Clave incorrecta rechazada; actualización repetible conserva datos y hashes de contraseñas. Aprobado. |
| Sintaxis | 41 archivos PHP y 32 archivos JavaScript, además de los bloques JavaScript de la plantilla. Aprobado. |

Los recuentos de rutas y enlaces incluyen recorridos por perfil y referencias repetidas; no representan páginas o destinos únicos en todos los casos.

## Correcciones de esta revisión

- Los tres certificados/títulos iniciales ahora incluyen `estado: Válido`, necesario para su edición y consulta coherentes.
- Los RUC de empresas de ejemplo tienen dígito verificador coherente.
- Se eliminaron títulos de Pisco de instalación, actualización, recuperación, cuentas, roles, auditoría y respaldos. También se neutralizaron el nombre del SQL descargado y el prefijo de contraseñas temporales.
- La documentación de instalación usa un dominio de ejemplo y distingue una instalación nueva de una actualización que conserva la base existente.
- Se adaptaron los datos de prueba a los alumnos precargados, los enlaces desactivados y la espera de navegación al cambiar filtros. Las aserciones y las validaciones del producto se conservaron.
- Se añadieron `tests/demo-installation.php` y `tests/demo-validation.php` para comprobar específicamente la edición neutra, además de `tests/demo-content.cjs`.

Los identificadores internos, tablas `pisco_*`, cookie de sesión y formato `.piscobak` se conservan para mantener la compatibilidad de la estructura y los respaldos.

## Comprobaciones necesarias en Hostinger

Esta revisión valida el funcionamiento local; no demuestra una instalación ya realizada en Hostinger. Después de subir los archivos, comprueba HTTPS, conexión MySQL, permisos de la carpeta privada y la configuración PHP del plan contratado siguiendo `INSTRUCCIONES.md`. La configuración de Apache/LiteSpeed y `.user.ini` debe verificarse allí, porque las pruebas locales utilizaron el servidor de desarrollo de PHP.

El correo saliente y la recuperación por correo siguen sin estar implementados/configurados en esta edición. Los mensajes y respuestas se guardan en el portal; la recuperación privada por clave de propietario sí se probó. Las direcciones `.example`, redes y WhatsApp de la demo requieren personalización para un cliente real.

Si se desean respaldos automáticos, hay que crear la tarea cron en hPanel; guardar el horario en el portal no crea esa tarea en Hostinger. La disponibilidad de servicios externos y el comportamiento bajo carga de producción no forman parte de esta revisión.

Los datos, ilustraciones y documentos de demostración son referenciales y no tienen validez oficial. Para cargar esta identidad usa una base nueva: actualizar una base existente conserva su contenido.

## Repetir las pruebas

Las instrucciones generales están en `INSTRUCCIONES.md`. Usa PHP con PDO MySQL, Fileinfo, sesiones y Zlib; para navegador, instala Playwright y Chromium. Define `PORTAL_CONFIG_FILE` con una configuración privada `config.test.php`, una base exclusiva terminada en `_test` y cookies sin requisito HTTPS solo para HTTP local.

```sh
node tests/demo-content.cjs
php tests/demo-validation.php
# Requiere una base de pruebas nueva y vacía:
php tests/demo-installation.php
# Las siguientes pruebas recrean sus tablas y escenarios de pruebas:
php tests/run.php
php tests/parity.php
```

Las suites de navegador usan los usuarios sintéticos preparados por `parity.php`. Algunas necesitan escenarios previos: `browser-workflows.cjs` crea expedientes y reclamos; `claims-verification.php`, `regressions.php` y los controles de atención se ejecutan después de preparar sus registros. El escenario de compatibilidad de cargos requiere un expediente antiguo sintético con código de 32 caracteres. `PISCO_TEST_CREDENTIALS` apunta a un archivo privado con la clave de instalación y la contraseña de pruebas para las suites que lo necesitan. Nunca se deben ejecutar estas pruebas sobre la base de un cliente.
