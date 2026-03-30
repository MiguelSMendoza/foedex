# Technical Specification

## 1. Resumen técnico

Aplicación web monolítica en Symfony con renderizado server-side, persistencia en MySQL y despliegue en contenedores Docker. La primera versión prioriza simplicidad operativa, trazabilidad de revisiones y una arquitectura clara basada en módulos de dominio.

## 2. Stack técnico

- PHP 8.3
- Symfony 8
- Twig
- Doctrine ORM
- Doctrine Migrations
- Symfony Security
- Symfony Validator
- Symfony Form
- Symfony Messenger
- MySQL 8.4
- Nginx
- Docker Compose

## 3. Estilo arquitectónico

- Monolito modular.
- Renderizado server-side con Twig.
- Controladores finos.
- Casos de uso en servicios de aplicación.
- Persistencia con Doctrine.
- Mensajería para tareas secundarias.
- Historial de páginas modelado como snapshots inmutables.

## 4. Módulos de dominio

### 4.1 Identity

Responsable de usuarios, autenticación y perfil.

Entidades previstas:

- `User`
- `PasswordResetToken`

### 4.2 Knowledge

Responsable de páginas, revisiones, slugs históricos y enlaces internos.

Entidades previstas:

- `Page`
- `PageRevision`
- `PageSlugRedirect`

### 4.3 Taxonomy

Responsable de categorías y relación con páginas.

Entidades previstas:

- `Category`

### 4.4 Discovery

Responsable de búsquedas, listados, portada y navegación.

Puede empezar con consultas Doctrine específicas sin motor de búsqueda externo.

### 4.5 Audit

Responsable de trazabilidad operativa adicional si se necesita más allá de revisiones.

Inicialmente puede vivir dentro de `Knowledge` mediante metadatos de revisión.

## 5. Modelo de datos inicial

### 5.1 User

Campos mínimos:

- `id`
- `email` único
- `passwordHash`
- `displayName`
- `bio` nullable
- `createdAt`
- `updatedAt`

### 5.2 Page

Campos mínimos:

- `id`
- `currentSlug` único
- `currentTitle`
- `currentExcerpt` nullable
- `currentMarkdown`
- `currentHtml`
- `createdBy`
- `lastEditedBy`
- `createdAt`
- `updatedAt`
- `isArchived` boolean false por defecto

Notas:

- `Page` representa el estado actual materializado.
- El histórico completo vive en `PageRevision`.

### 5.3 PageRevision

Campos mínimos:

- `id`
- `page`
- `revisionNumber`
- `titleSnapshot`
- `excerptSnapshot` nullable
- `markdownSnapshot`
- `htmlSnapshot`
- `changeSummary` nullable
- `author`
- `createdAt`
- `restoredFromRevision` nullable

Relaciones:

- many-to-one con `Page`
- many-to-one con `User`
- many-to-many snapshot con categorías mediante tabla dedicada

Notas:

- Las revisiones son inmutables.
- Restaurar una revisión crea otra revisión nueva con referencia a la restaurada.

### 5.4 Category

Campos mínimos:

- `id`
- `name`
- `slug` único
- `description` nullable
- `createdBy`
- `createdAt`
- `updatedAt`

### 5.5 PageCategory

Tabla de relación del estado actual entre página y categoría.

### 5.6 PageRevisionCategory

Tabla snapshot para conservar qué categorías tenía una revisión concreta.

### 5.7 PageSlugRedirect

Campos mínimos:

- `id`
- `page`
- `oldSlug` único
- `createdAt`
- `createdBy`

## 6. Casos de uso clave

### 6.1 Registro de usuario

1. Usuario envía email, nombre visible y contraseña.
2. Se valida unicidad de email.
3. Se persiste `User`.
4. Se autentica o redirige a login según la estrategia final.

### 6.2 Crear página

1. Usuario autenticado abre formulario.
2. Escribe título, slug, contenido y categorías.
3. El sistema renderiza Markdown a HTML sanitizado.
4. Se crea `Page`.
5. Se crea `PageRevision` número 1.
6. Se persisten relaciones actuales y snapshot de categorías.

### 6.3 Editar página

1. Usuario autenticado abre edición.
2. Se cargan datos actuales.
3. Envía cambios.
4. Se recalcula HTML sanitizado.
5. Se actualiza `Page`.
6. Se crea nueva `PageRevision`.
7. Si cambia slug, se crea `PageSlugRedirect`.

### 6.4 Restaurar revisión

1. Usuario selecciona revisión antigua.
2. El sistema muestra diff y confirma acción.
3. Se copian snapshots a `Page`.
4. Se crea nueva `PageRevision` marcando el origen restaurado.

## 7. Seguridad

- Autenticación basada en email y contraseña.
- Hash de contraseña con algoritmo recomendado por Symfony.
- CSRF en formularios mutantes.
- Solo usuarios autenticados pueden mutar contenido.
- Cualquier usuario autenticado puede editar cualquier página.
- Sanitización estricta de HTML renderizado desde Markdown.
- Limitación básica de intentos de login.

## 8. Markdown y renderizado

- Usar una librería madura de Markdown compatible con CommonMark.
- Convertir Markdown a HTML en el backend.
- Pasar el HTML por sanitización antes de persistirlo o servirlo.
- Guardar Markdown original y HTML sanitizado.
- Añadir parser de enlaces internos estilo wiki en una segunda iteración si no entra limpio en MVP.

## 9. Historial y diff

- Las revisiones son snapshots completos, no parches incrementales.
- El diff visible al usuario puede generarse bajo demanda entre revisiones.
- Se mostrará:
  - autor
  - fecha
  - resumen del cambio si existe
  - diferencias de título y contenido

## 10. Búsqueda

MVP:

- búsqueda simple con `LIKE` sobre título, extracto y contenido Markdown;
- índices en columnas relevantes;
- paginación.

Post-MVP:

- fulltext MySQL o motor dedicado si el volumen crece.

## 11. UI y experiencia

- SSR con Twig.
- Layout responsive.
- Formularios claros y accesibles.
- Navegación principal:
  - inicio
  - páginas
  - categorías
  - crear página
  - perfil
- Editor con preview y ayuda de sintaxis básica.

## 12. Estructura de proyecto sugerida

```text
src/
  Identity/
  Knowledge/
  Taxonomy/
  Discovery/
  Shared/
templates/
assets/
config/
migrations/
tests/
```

Dentro de cada módulo:

- `Application/`
- `Domain/`
- `Infrastructure/`
- `UI/Web/`

## 13. Estrategia de testing

### 13.1 Unit tests

- slugification
- sanitización
- reglas de creación de revisiones
- restauración de revisiones

### 13.2 Integration tests

- repositorios Doctrine
- servicios de aplicación
- autenticación y seguridad

### 13.3 Functional tests

- registro
- login
- creación de página
- edición de página
- creación inline de categoría
- histórico de revisiones
- restauración
- búsqueda

## 14. Observabilidad y operación

- logs estructurados a stdout para Docker
- páginas de error amigables
- healthcheck HTTP
- comando CLI para crear usuarios
- comando CLI para recalcular HTML si cambia el pipeline Markdown

## 15. Despliegue Docker

Servicios previstos:

- `app`: PHP-FPM
- `web`: Nginx
- `db`: MySQL
- `worker`: Messenger consumer

Ficheros previstos:

- `Dockerfile`
- `compose.yaml`
- `compose.prod.yaml`
- `docker/nginx/default.conf`
- `.env.prod.local.example`

## 16. Decisiones técnicas explícitas

- No usar SPA en el MVP.
- No usar WebSockets ni edición colaborativa en tiempo real.
- No introducir Elasticsearch ni OpenSearch al inicio.
- No depender de un backoffice de terceros; la UI será propia y centrada en el producto.
- Sí usar Messenger aunque inicialmente el transporte pueda ser Doctrine para simplificar operación.

## 17. Riesgos técnicos

- El historial completo puede crecer rápido si el contenido es grande.
- El diff de Markdown puede ser costoso si no se pagina o limita.
- La edición abierta exige muy buena trazabilidad y UX de restauración.
- La sanitización Markdown debe blindarse bien para evitar XSS.

## 18. Definición de hecho técnica

Una funcionalidad se considera terminada cuando:

- su comportamiento está cubierto por la spec;
- tiene migraciones si toca persistencia;
- incluye tests adecuados;
- queda operativa en Docker local;
- actualiza documentación si cambia setup o arquitectura.
