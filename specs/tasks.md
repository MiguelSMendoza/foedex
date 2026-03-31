# Project Tasks

## 1. Enfoque de ejecución

Las tareas están organizadas por fases para construir el proyecto de forma incremental, validable y alineada con las specs.

## 2. Fase 0 - Bootstrap del proyecto

### Objetivo

Dejar una base Symfony API + React operativa, dockerizada y lista para desarrollo.

### Tareas

- Inicializar repositorio Git si todavía no existe.
- Crear proyecto Symfony base.
- Crear base frontend React con Vite.
- Configurar PHP CS Fixer o ECS.
- Configurar PHPStan.
- Configurar PHPUnit.
- Configurar build frontend y salida estática en `public/app`.
- Configurar Dockerfile para `app`.
- Configurar Nginx para `web`.
- Configurar `compose.yaml` para desarrollo.
- Configurar `compose.prod.yaml` para producción.
- Crear `.env` y `.env.prod.local.example`.
- Verificar arranque local con Docker.
- Documentar comandos frecuentes en `README.md`.

## 3. Fase 1 - Identidad y seguridad

### Objetivo

Permitir registro y autenticación seguras.

### Tareas

- Modelar entidad `User`.
- Crear migración inicial de usuarios.
- Implementar registro.
- Implementar login.
- Implementar logout.
- Implementar edición de perfil.
- Implementar recuperación de contraseña.
- Añadir rate limiting básico en login.
- Añadir tests funcionales de auth.

## 4. Fase 2 - Núcleo de páginas

### Objetivo

Permitir creación y visualización de páginas Markdown con lectura en HTML interpretado.

### Tareas

- Modelar entidad `Page`.
- Implementar slugifier reutilizable.
- Implementar endpoint de creación de página.
- Implementar renderizado Markdown a HTML.
- Implementar sanitización de HTML.
- Persistir estado actual materializado de la página.
- Crear detalle React de página.
- Crear listado API de páginas.
- Crear portada React con últimas páginas editadas.
- Garantizar que la portada renderiza HTML derivado del Markdown, nunca Markdown crudo.
- Añadir tests unitarios e integración del pipeline Markdown.

## 5. Fase 3 - Categorías

### Objetivo

Organizar páginas mediante taxonomía colaborativa.

### Tareas

- Modelar entidad `Category`.
- Modelar relación actual página-categoría.
- Implementar selector de categorías existentes.
- Implementar creación inline de categorías.
- Implementar slug único por categoría.
- Crear listado de categorías.
- Crear vista de páginas por categoría.
- Añadir validación para evitar duplicados normalizados.
- Añadir tests funcionales de categorización.

## 6. Fase 4 - Revisiones e historial

### Objetivo

Convertir la edición abierta en una operación auditable y reversible.

### Tareas

- Modelar entidad `PageRevision`.
- Modelar snapshot de categorías por revisión.
- Crear revisión inicial al crear una página.
- Crear nueva revisión en cada edición.
- Añadir campo opcional de resumen de cambio.
- Mostrar cronología de revisiones por página.
- Implementar vista diff entre revisiones.
- Implementar restauración de revisión.
- Registrar referencia `restoredFromRevision`.
- Añadir tests de regresión sobre restauración y numeración.

## 7. Fase 5 - Slugs históricos y enlaces internos

### Objetivo

Evitar enlaces rotos y mejorar navegación entre páginas.

### Tareas

- Modelar `PageSlugRedirect`.
- Guardar slug antiguo al cambiar slug actual.
- Resolver redirecciones 301 desde slugs históricos.
- Detectar enlaces internos a páginas conocidas.
- Mostrar backlinks básicos en la vista de página si el tiempo lo permite.
- Añadir tests funcionales de redirección.

## 8. Fase 6 - Descubrimiento y búsqueda

### Objetivo

Facilitar encontrar conocimiento relevante.

### Tareas

- Implementar formulario de búsqueda.
- Añadir consultas Doctrine para búsqueda básica.
- Indexar columnas frecuentes.
- Implementar paginación de resultados.
- Añadir filtros por categoría si entra en MVP.
- Añadir tests funcionales de búsqueda.

## 8.1 Fase 6B - Captura rápida e ingesta

### Objetivo

Permitir creación automática de páginas desde la portada mediante pegado, drag-and-drop y subida segura de ficheros.

### Tareas

- Añadir panel React de captura rápida visible solo para usuarios autenticados.
- Implementar creación rápida desde texto libre.
- Implementar creación rápida desde URL pegada.
- Implementar extractor seguro de título, descripción e imagen destacada.
- Truncar títulos automáticos a 60 caracteres con puntos suspensivos.
- Evitar repetir el título automático dentro del Markdown generado.
- Añadir reglas especiales de importación para Instagram y X/Twitter.
- Añadir regla especial de importación y embed para YouTube.
- Añadir protección frente a SSRF y hosts no públicos.
- Modelar `MediaAsset` para imágenes y ficheros asociados a páginas.
- Implementar subida segura de imágenes y ficheros permitidos.
- Generar thumbnails para imágenes subidas.
- Persistir assets y servirlos desde `public/uploads`.
- Mostrar thumbnails en la portada y detalle.
- Abrir imágenes en modal con enlace de descarga.
- Forzar apertura en pestaña nueva para enlaces renderizados.
- Añadir tests unitarios e integración de captura rápida.

## 9. Fase 7 - UX editorial

### Objetivo

Hacer la creación y edición cómodas para uso real dentro de un frontend React.

### Tareas

- Añadir preview en vivo o render auxiliar si entra en alcance.
- Añadir ayuda de sintaxis Markdown.
- Mejorar mensajes de validación.
- Convertir la portada en feed de contenido completo con infinity scroll.
- Añadir confirmación clara al restaurar revisiones.
- Añadir indicadores de última edición y autoría.
- Añadir acciones rápidas en portada para editar cualquier página.
- Añadir borrado lógico en portada solo para la persona creadora de cada página.
- Revisar responsive de páginas clave.
- Revisar accesibilidad básica.

## 10. Fase 8 - Operación y producción

### Objetivo

Asegurar que el sistema se despliega y mantiene sin fricción.

### Tareas

- Crear `Dockerfile` multi-stage.
- Integrar build frontend Node/Vite en Docker.
- Compartir almacenamiento de uploads entre `app` y `web` en producción.
- Endurecer imagen de producción.
- Configurar healthcheck.
- Configurar worker Messenger.
- Crear comando CLI `app:user:create`.
- Crear comando CLI para reconstruir HTML de páginas.
- Configurar logs a stdout/stderr.
- Añadir script o make targets de despliegue.
- Validar despliegue limpio en un servidor Docker real o entorno equivalente.

## 11. Fase 9 - Calidad y endurecimiento

### Objetivo

Reducir deuda y cerrar riesgos antes de abrir al uso real.

### Tareas

- Cobertura mínima de flujos críticos.
- Revisión de seguridad en sanitización y auth.
- Revisión de índices y queries lentas.
- Añadir protección ante doble envío de formularios.
- Añadir límites razonables de longitud de contenido.
- Revisar mensajes de error y páginas 404/500.
- Completar documentación operacional.

## 12. Backlog transversal

- Definir convención de namespaces por módulo.
- Definir estrategia de fixtures.
- Definir estrategia de datos demo.
- Añadir CI para tests y análisis estático.
- Añadir hooks o comandos de calidad local.
- Mantener specs actualizadas según decisiones reales.

## 13. Orden recomendado de implementación

1. Fase 0
2. Fase 1
3. Fase 2
4. Fase 3
5. Fase 4
6. Fase 6
7. Fase 5
8. Fase 7
9. Fase 8
10. Fase 9

## 14. Hitos de entrega

### Hito A - Vertical slice básica

- Registro y login.
- Crear página.
- Ver página.

### Hito B - Wiki útil

- Categorías.
- Listados.
- Búsqueda básica.

### Hito C - Colaboración real

- Historial.
- Diff.
- Restauración.
- Slugs históricos.

### Hito D - Producción

- Docker producción.
- CLI operativa.
- Documentación completa.

## 15. Definition of Ready para cada tarea

- objetivo claro;
- impacto en spec identificado;
- criterio de aceptación definido;
- dependencias conocidas.

## 16. Definition of Done para cada tarea

- código implementado;
- tests relevantes en verde;
- migraciones aplicables creadas;
- documentación actualizada;
- verificado en entorno Docker local.
