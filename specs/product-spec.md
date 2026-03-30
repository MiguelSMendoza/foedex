# Product Specification

## 1. Visión

Foedex es una wiki colaborativa inspirada en la simplicidad de edición de Notion, centrada en páginas Markdown, enlaces de conocimiento y organización por categorías. El objetivo es permitir que cualquier persona registrada pueda compartir y mejorar conocimiento colectivo sin jerarquías de administración.

## 2. Objetivos del producto

- Facilitar creación rápida de contenido en Markdown.
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
- Ver metadatos:
  - autor de creación
  - última persona editora
  - fechas de creación y actualización
  - categorías asociadas

### 6.3 Editor Markdown

- Editor con sintaxis Markdown.
- Vista previa antes de guardar.
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

- Portada con últimas páginas actualizadas.
- Listado paginado de páginas.
- Búsqueda simple por título y contenido.
- Navegación por categorías.
- Mostrar enlaces internos detectados entre páginas si el slug existe.

### 6.7 Enlaces compartidos

- Una página puede contener enlaces externos relevantes.
- Los enlaces viven dentro del contenido Markdown o como bloque opcional estructurado de referencias.

## 7. Reglas de negocio

- Solo usuarios autenticados pueden crear o editar.
- Toda edición sobre una página existente debe crear revisión.
- No se puede eliminar físicamente una revisión.
- El slug de página es único y estable; puede cambiarse solo mediante edición explícita.
- Si se cambia el slug, deben mantenerse redirecciones desde slugs históricos.
- Las categorías duplicadas deben evitarse por slug normalizado.
- La restauración de una revisión debe registrar quién la ejecutó.
- No se necesita aprobación para publicar o editar.

## 8. Requisitos de calidad

- Interfaz clara, rápida y utilizable en móvil y escritorio.
- Renderizado Markdown seguro frente a XSS.
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
