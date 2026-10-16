# Hero de la portada

La portada sigue la referencia proporcionada: fondo claro con formas azules, marca y accesos en la cabecera, título Tu salud mejor organizada a la izquierda y retrato del médico a la derecha. Se mantiene la portada sin un botón adicional de reserva.

El calendario y el reloj se construyeron como SVG independientes para animarlos sobre la fotografía. El calendario flota y pulsa su confirmación; el reloj flota y gira su manecilla. Los iconos inferiores de Especialistas, Horarios y Citas también se animan. El pie permite pausar y reanudar el movimiento. Se respeta la preferencia del sistema para reducir animaciones.

La composición cambia a una columna en pantallas pequeñas. Los recursos son locales y no requieren una biblioteca externa. El retrato JPEG pesa 149151 bytes; se generó con la herramienta integrada ImageGen y se guardó en `img/hero-doctor-v2.jpg`. La fuente original `img/bg01.jpg` permanece disponible.

## Comprobaciones

Portada, estilos, JavaScript, fotografía, tipografía y enlaces de acceso responden HTTP 200 en el servidor local. La sintaxis de `js/landing.js` y `router.php` es correcta y `git diff --check` no detecta errores de espacios. No había navegador controlable en esta sesión, por lo que la composición y el movimiento no se verificaron visualmente en escritorio o móvil.

## Prompt del retrato

Herramienta: ImageGen integrada. Imagen de origen: `img/bg01.jpg`. Fondo opaco. El resultado se convirtió a JPEG con calidad 90 para su entrega en la web.

```text
Use case: identity-preserve. Asset type: photorealistic portrait photograph for a medical appointment website hero. Edit target: supplied photo of medical team. Create a single-doctor portrait of the CENTRAL male doctor in the supplied image, retain his recognizable identity, black rectangular glasses, brown hair slightly grey at temples, short groomed salt and pepper beard, warm confident slight smile, white coat, pale blue shirt and navy blue tie and stethoscope. Remove the other foreground doctors, change pose to arms comfortably crossed at waist, no folder. Show from top of head to below crossed arms. Bright airy contemporary clinic with frosted windows, a subtle small green plant on the far right, one very softly blurred blue-uniform clinician far back on the left. Camera frontal, doctor centered slightly right; leave room near upper right edge and lower left edge for separately coded floating icons, but DO NOT render any icons. Crop square 1:1, generous space over head, full shoulders and crossed arms visible. Beautiful realistic natural daylight, cool white and extremely pale blue palette, editorial medical photography. The photo fills the canvas, no border, no rounding, no layout, no text, no logos, no clock, no calendar, no illustration, no watermark. This is only the image asset, NOT a screenshot or a web page.
```
