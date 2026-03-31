# Product Specification

## 1. Visión

Foedex es una wiki colaborativa inspirada en la simplicidad de edición de Notion, centrada en páginas escritas con editor Markdown, enlaces de conocimiento y organización por categorías. El objetivo es permitir que cualquier persona registrada pueda compartir y mejorar conocimiento colectivo sin jerarquías de administración.

## 2. Objetivos del producto

- Facilitar creación rápida de contenido con editor Markdown, sin exponer Markdown crudo en lectura.
- Organizar conocimiento por categorías reutilizables.
- Permitir colaboración abierta entre usuarios autenticados.
- Mantener trazabilidad total de cambios y autores.
- Habilitar restauración segura de versiones previas.
- Favorecer descubrimiento de contenido mediante navegación, enlaces y búsqueda.

## 3. No objetivos del MVP

- Permisos granulares por página o categoría.
- Moderación avanzada o flujos de aprobación.
- Edición simultánea en tiempo real tipo CRDT.
- Comentarios inline o menciones.
- Integraciones externas.
- Aplicación móvil nativa.

## 4. Tipos de usuario

### 4.1 Visitante

- Puede navegar páginas públicas si el producto se publica en abierto.
- Puede ver portada, listados y páginas.
- No puede crear ni editar.

### 4.2 Usuario registrado

- Puede registrarse e iniciar sesión.
- Puede crear páginas y categorías.
- Puede editar cualquier página.
- Puede revisar historial y restaurar una versión anterior.
- Puede gestionar su perfil básico.

## 5. Principios funcionales

- Sin administradores en la primera versión.
- Toda edición debe quedar auditada.
- La colaboración abierta prevalece sobre la propiedad individual.
- Las categorías son colaborativas y reutilizables.
- El sistema debe minimizar fricción de publicación.

## 6. Capacidades principales

### 6.1 Registro y sesión

- Registro con email, nombre visible y contraseña.
- Inicio y cierre de sesión.
- Recuperación de contraseña.
- Perfil editable con nombre visible y biografía corta opcional.

### 6.2 Páginas

- Crear página con:
  - título
  - slug único
  - contenido Markdown
  - extracto opcional
  - categorías
  - enlaces externos opcionales
- Editar cualquier página existente.
- Ver página renderizada con HTML sanitizado.
- Mostrar en portada y listados contenido interpretado desde Markdown, nunca Markdown crudo.
- El título visible de una página no puede superar 60 caracteres.
- Ver metadatos:
  - autor de creación
  - última persona editora
  - fechas de creación y actualización
  - categorías asociadas
- Desde la portada, un usuario autenticado puede abrir el editor de cualquier página.
- Desde la portada, un usuario autenticado puede borrar solo páginas creadas por sí mismo.

### 6.3 Editor Markdown

- Editor con sintaxis Markdown.
- El editor es el único lugar donde se muestra el Markdown crudo.
- Fuera del editor, el contenido siempre se presenta como HTML interpretado y sanitizado.
- Vista previa antes de guardar o renderizado de apoyo si entra en alcance.
- Validación mínima de campos requeridos.
- Soporte inicial para:
  - encabezados
  - listas
  - tablas
  - bloques de código
  - enlaces
  - imágenes remotas por URL

### 6.4 Categorías

- Seleccionar categorías existentes.
- Crear nuevas categorías durante la edición de una página.
- Ver listado de categorías.
- Ver páginas asociadas a una categoría.
- Slug único por categoría.

### 6.5 Historial y revisiones

- Cada guardado genera una revisión.
- Una revisión almacena:
  - snapshot del título
  - snapshot del contenido Markdown
  - snapshot del HTML renderizado sanitizado
  - extracto
  - categorías asociadas en ese momento
  - usuario autor de la revisión
  - fecha y hora
  - motivo opcional del cambio
- Ver cronología de revisiones por página.
- Ver diff entre revisiones.
- Restaurar cualquier revisión previa.
- Una restauración genera una nueva revisión, no sobrescribe el pasado.

### 6.6 Descubrimiento y navegación

- Portada con últimas páginas actualizadas renderizadas a HTML.
- Listado paginado de páginas.
- Búsqueda simple por título y contenido.
- Navegación por categorías.
- Mostrar enlaces internos detectados entre páginas si el slug existe.

### 6.7 Captura rápida desde portada

- Cuando el usuario ha iniciado sesión, la portada muestra un cuadro de captura rápida.
- El cuadro permite pegar texto y enlaces, arrastrar y soltar imágenes o ficheros y seleccionar ficheros manualmente.
- Todo el proceso debe ejecutarse sin recargar la página.
- Si el usuario pega un enlace:
  - el sistema accede al enlace
  - extrae título, descripción e imagen destacada si existe
  - crea una página Markdown con esa previsualización
  - el título se trunca con puntos suspensivos si supera 60 caracteres
  - el contenido generado no debe repetir ese título como encabezado Markdown
- Si el usuario pega texto libre:
  - el sistema crea una página rápida usando ese contenido como base
- Si el usuario sube una imagen:
  - el sistema la almacena
  - genera thumbnail
  - crea una página con la imagen incrustada y enlace de descarga
- Si el usuario sube otro fichero permitido:
  - el sistema crea una página con enlace de descarga al fichero
- Las imágenes subidas deben poder abrirse en modal a tamaño completo con opción de descarga.

### 6.8 Reglas especiales por plataforma

- Si el enlace importado pertenece a Instagram:
  - el contenido debe comenzar por la primera imagen encontrada
  - esa imagen debe enlazar a la publicación original
- Si el enlace importado pertenece a YouTube:
  - el título visible de la página se trunca con puntos suspensivos si hace falta
  - el contenido debe mostrar el título del vídeo antes del reproductor
  - el vídeo debe quedar embebido dentro del post
- Si el enlace importado pertenece a X o Twitter:
  - el contenido no debe incluir encabezado Markdown con el título
  - el cuerpo debe contener el texto del post en plano
  - al final debe aparecer un enlace a la publicación original

### 6.9 Enlaces compartidos

- Una página puede contener enlaces externos relevantes.
- Los enlaces viven dentro del contenido Markdown o como bloque opcional estructurado de referencias.

## 7. Reglas de negocio

- Solo usuarios autenticados pueden crear o editar.
- Solo la persona creadora de una página puede borrarla.
- Toda edición sobre una página existente debe crear revisión.
- No se puede eliminar físicamente una revisión.
- El borrado de página en el MVP es archivado lógico, no borrado físico.
- El slug de página es único y estable; si no se define manualmente al crear, el sistema genera un código alfanumérico único de 12 caracteres.
- El slug puede cambiarse solo mediante edición explícita.
- El título manual no puede superar 60 caracteres.
- Si se cambia el slug, deben mantenerse redirecciones desde slugs históricos.
- Las categorías duplicadas deben evitarse por slug normalizado.
- La restauración de una revisión debe registrar quién la ejecutó.
- No se necesita aprobación para publicar o editar.
- Las páginas archivadas no deben aparecer en portada, búsqueda, categorías ni detalle público.
- La captura rápida debe bloquear URLs no públicas o inseguras.
- La subida de ficheros debe aceptar solo tipos permitidos y tamaños limitados.
- Los textos obtenidos de páginas externas deben limpiarse antes de mostrarse.
- Todos los enlaces renderizados en la web deben abrirse en pestaña nueva.

## 8. Requisitos de calidad

- Interfaz clara, rápida y utilizable en móvil y escritorio.
- Frontend React desacoplado del backend Symfony mediante API HTTP.
- Renderizado Markdown seguro frente a XSS.
- Captura rápida segura frente a SSRF, XSS y subidas peligrosas.
- La portada debe cargar las páginas completas de forma progresiva con infinity scroll.
- Auditoría íntegra y consultable.
- Rendimiento aceptable en listados e historial.
- Accesibilidad básica AA en formularios y navegación.

## 9. Historias de usuario prioritarias

1. Como visitante, quiero registrarme para empezar a colaborar.
2. Como usuario autenticado, quiero crear una página en Markdown para compartir conocimiento.
3. Como usuario autenticado, quiero asignar categorías existentes o nuevas a una página.
4. Como usuario autenticado, quiero editar cualquier página para mejorarla.
5. Como usuario autenticado, quiero ver quién cambió qué y cuándo.
6. Como usuario autenticado, quiero restaurar una versión anterior si una edición reciente empeora el contenido.
7. Como usuario, quiero descubrir páginas por categoría y búsqueda.

## 10. Criterios de aceptación del MVP

- Registro, login y logout operativos.
- CRUD funcional de páginas, salvo borrado definitivo.
- Asignación de categorías existentes y creación inline de nuevas categorías.
- Historial completo con autoría y fecha.
- Restauración de revisiones operativa.
- Búsqueda básica funcional.
- Despliegue reproducible por Docker en servidor.

## 11. Riesgos de producto

- Edición abierta puede introducir vandalismo o ruido.
- Sin moderación, el historial y la restauración deben ser especialmente robustos.
- El alcance tipo “clon de Notion” puede crecer demasiado si no se limita a wiki Markdown colaborativa.

## 12. Decisiones de alcance

- El MVP se enfoca en wiki colaborativa, no en bloques complejos tipo Notion.
- La colaboración concurrente será secuencial, no en tiempo real.
- La publicación será inmediata al guardar; no habrá borradores separados en la primera fase.
- El frontend objetivo del producto es React consumiendo una API Symfony; Twig no es la capa principal de experiencia de usuario.
