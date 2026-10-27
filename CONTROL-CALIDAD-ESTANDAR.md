# Control de calidad del portal estándar

Revisión ampliada del 27 de octubre de 2026. Sustituye como referencia de preparación a `REVISION-DEMO-ICA.md`.

Se revisó la implementación y se ejecutaron los **15 scripts PHP y 38 scripts JavaScript** de `tests/`, además de preparar escenarios comunes y comprobar la programación de respaldos. Los resultados finales no presentan fallos ni avisos PHP en los registros. El detalle por script y su resultado está en [RESULTADOS-CALIDAD-ESTANDAR.json](RESULTADOS-CALIDAD-ESTANDAR.json).

Esto verifica la edición local revisada, con PHP 8.4.27, MariaDB 11.8.6 y Chromium/Playwright. No se modificó la instalación de Hostinger ni una base de datos de un cliente.

## Problemas encontrados y corregidos

| Problema reproducido o comprobado | Comportamiento corregido |
| --- | --- |
| Guardar mientras subía un PDF podía cerrar el editor y anunciar éxito sin conservar el adjunto. | El guardado espera a que terminen las cargas. Una respuesta de error, incluso HTML, conserva los campos y permite reintentar sin errores JavaScript sin manejar. |
| Las cargas repetibles y los resultados de Admisión tampoco compartían esa protección. | Las cargas del editor, imágenes, filas con PDF y resultados usan el mismo seguimiento. El historial de reemplazo se registra después de una carga exitosa. |
| Dos de los cuatro accesos de la demo dependían de servicios externos sin activar. El editor no indicaba por qué se ocultaban. | La demo nueva incluye cuatro servicios internos funcionales. El editor muestra el número de accesos visibles y explica los destinos externos vacíos o inactivos, con enlace para configurarlos. Se rechazan filas sin título o destino. |
| Quedaban nombres y logotipos institucionales fijos en servicios y documentos. El sitio académico se derivaba del dominio del correo. | Los servicios, el membrete, el sello y el logo de los PDF utilizan la identidad configurada. El sitio se toma del dominio donde corre el portal. Se probó con otra denominación larga, otro logo y un correo de Gmail. |
| La franja de Inicio anunciaba siempre “Admisión 2026 abierta”. | Refleja el año del proceso publicado vigente y desaparece cuando no existe un proceso vigente. |
| Tras eliminar el único registro de la última página, el paginador podía indicar la página anterior mientras la tabla quedaba vacía. | El listado ajusta la página antes de seleccionar los registros, también en bandejas y auditoría. |
| Crear un documento que reutilizara el reglamento de Admisión podía exigir otro PDF. Cambiar el origen no actualizaba el formulario inmediatamente. | El formulario adapta sus campos al origen elegido y el servidor acepta la referencia al reglamento vigente. Al reemplazar el reglamento, el documento de gestión utiliza el mismo archivo, descargable según sus permisos. |
| Las fichas de personal de la demo carecían de DNI y varias de condición; cinco docentes académicos carecían del vínculo requerido a su ficha. | La semilla incluye datos ficticios completos y vínculos coherentes. Se editaron y restituyeron las 52 fichas mediante 104 guardados reales. |
| La semilla tenía nueve beneficios mientras la portada y su editor admitían ocho; además, afirmaba más de 40 años con una fecha de creación de ejemplo del año 2000. | Se ajustó a ocho beneficios y se sustituyó la afirmación por contenido neutral. |
| Algunos enlaces del carrusel solo se validaban en el navegador. | El servidor rechaza destinos ejecutables y URL incompletas. La presentación filtra destinos no permitidos y oculta botones cuyo servicio externo no puede resolverse. |

El cierre de sesión corregido previamente se volvió a comprobar mediante la solicitud `DELETE` sin cuerpo y los botones de salida del escritorio y del módulo académico. La sesión se revoca, el panel vuelve a pedir identificación y los recursos privados rechazan la sesión cerrada.

## Evidencia de funcionamiento

| Área | Resultado final |
| --- | --- |
| Instalación | Instalación nueva de la demo, tablas, administrador, bloqueo de reinstalación, acceso de los cinco alumnos/egresados y edición persistente: aprobado. |
| Datos de demostración | **255 registros** de contenido y catálogos académicos pasan la validación como altas nuevas; no se depende de las excepciones para campos vacíos de registros antiguos. Identificadores, correos de ejemplo y relaciones: aprobados. |
| Portal público | **72 rutas**, 5.736 referencias de enlaces, menús, búsqueda, filtros y tamaños de 320, 375, 768 y 1440 píxeles: sin enlaces internos rotos, errores JavaScript ni desbordamientos. |
| Administración | **694 recorridos de rutas**, 32.984 referencias de enlaces y **11 perfiles**: accesos y menús aprobados, sin errores de enlaces o diseño detectados. |
| Contenido y publicación | 50 operaciones de creación/edición y 101 operaciones del ciclo de vida; aprobación, versiones, papelera, restauración y eliminación: aprobadas. |
| Portada y configuración | Orden/visibilidad de los 11 bloques, accesos, franja de Admisión, comunicado, identidad, temas, estadísticas y UIT: aprobados. |
| Personal | 52 fichas editadas y restituidas desde los formularios; perfiles, CV y archivos: aprobados. |
| Gestión académica | Alumnos, unidades, módulos, ambientes, carga docente, cruces de horario, publicación, notas, certificados y títulos: aprobados. |
| Admisión, matrícula y becas | Publicación/vigencia, requisitos, importación, rechazo de filas inválidas y consulta con identificación enmascarada: aprobados. |
| Servicios | Contacto, seguimiento, atención, derivación, historial, Mesa de Partes y Libro de Reclamaciones con adjuntos reales: aprobados. |
| Bolsa laboral | Empresas, validación, ofertas, postulaciones y acceso autorizado a CV: aprobados. |
| Documentos | PDF, Excel, QR, referencias compartidas, emisión, consulta y revocación: aprobados. Cargos actuales de 16 caracteres y antiguos de 32, con descarga y sin superposición con el sello: aprobados. |
| Accesos y errores | Cierre de sesión, CSRF, contraseñas, revocación de permisos/sesiones, recuperación privada y fallos de guardado/carga: aprobados. |
| Concurrencia | 20 cambios simultáneos, dos perfiles, una revisión inicial común y cuatro procesos de servicio: sin pérdida de cambios. |
| Respaldos | Exportación SQL con archivos, copia portable, restauración de contenido y cuentas, auditoría y CSV: aprobados. Programación: se creó la copia diaria y una segunda ejecución no la duplicó. |
| Actualización | Rechazo de clave incorrecta y actualización repetible conservando datos y contraseñas: aprobado. |

Los recuentos administrativos incluyen recorridos por perfil; las cifras de enlaces incluyen referencias repetidas. No representan miles de páginas únicas.

## Cómo se verificó

Se utilizaron bases exclusivas terminadas en `_test`, sin datos de producción. Se comprobó la instalación con la semilla neutra y se preparó otra base con los perfiles, archivos y escenarios de prueba. Antes de cada suite se restauró una instantánea común, para evitar dependencias entre pruebas, cuentas bloqueadas o datos de ensayos anteriores.

Las pruebas nuevas cubren cargas lentas/fallidas, accesos visibles, identidad configurable, referencias al reglamento, eliminación de la última fila y edición del personal precargado. También se ampliaron los contratos de servidor y la validación estricta de la semilla. Se corrigieron esperas y selectores de las pruebas donde no correspondían a la interfaz real; se repitieron los casos afectados conservando sus aserciones funcionales.

Las solicitudes a fuentes externas se bloquearon en las pruebas de navegador; las bibliotecas PDF/Excel/QR se cargaron desde los archivos incluidos. Las capturas, bases de pruebas, contraseñas sintéticas y copias de respaldo no forman parte de la distribución pública.

## Aplicar esta revisión en una instalación existente

1. Guarda una copia de archivos y base desde hPanel.
2. Del ZIP actualizado, reemplaza:
   - `portal-private/Domain.php`
   - `portal-private/Rules.php`
   - `portal-private/views/portal.php`
   - `public_html/portal-server.js`
   - `public_html/index.php`, para incluir también la corrección anterior del cierre de sesión.
3. Conserva `portal-private/config.php` y las carpetas privadas de respaldos. Esta revisión no requiere reinstalar, importar SQL ni cambiar el esquema.
4. Limpia la caché del sitio en hPanel y recarga con Ctrl+F5. Comprueba también desde una ventana privada.
5. Prueba cierre de sesión, un guardado con PDF, la edición de portada y la consulta de un documento.

`portal-private/data/portal.json` contiene las mejoras de datos para **instalaciones nuevas**. Reemplazar ese archivo no sobrescribe la base existente. Tus accesos, personal y contenido actuales se conservan: los servicios externos que aún estén vacíos o inactivos seguirán ocultos y el editor explicará el motivo. Para tener cuatro accesos visibles, activa sus destinos o elige rutas internas desde Portada.

Los identificadores internos, tablas `pisco_*`, cookie y formato `.piscobak` mantienen la compatibilidad.

## Alcance pendiente del hosting

La comprobación en tu dominio queda pendiente. En Hostinger verifica HTTPS, conexión MySQL, permisos, límites PHP, caché y aplicación de `.htaccess`/`.user.ini`; el entorno de pruebas utilizó el servidor de desarrollo de PHP.

El correo saliente y la recuperación por correo siguen pendientes. Los mensajes y respuestas sí se guardan en el portal; la recuperación privada por clave de propietario está probada. Los respaldos automáticos requieren crear la tarea cron en hPanel. La disponibilidad de servicios externos y la carga de producción no se han validado en este control.

Las personas, números, empresas e imágenes de la demo son ficticios o referenciales y sus documentos no tienen validez oficial.
