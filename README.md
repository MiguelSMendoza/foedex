# Foedex

Foedex es una aplicación web colaborativa, construida con Symfony y MySQL, orientada a compartir conocimiento categorizado mediante páginas editables con Markdown. Cualquier usuario registrado puede crear páginas, editarlas, categorizarlas, enlazarlas y restaurar versiones anteriores desde el historial.

El proyecto se desarrolla siguiendo Spec Driven Development. La documentación fuente de verdad está en:

- `specs/product-spec.md`
- `specs/technical-spec.md`
- `specs/tasks.md`

## Stack previsto

- PHP 8.3
- Symfony 8
- MySQL 8.4
- Nginx
- Docker y Docker Compose
- Twig para SSR
- Doctrine ORM y Migrations
- Symfony Security, Messenger y Validator

## Estructura documental

- `BOOTSTRAP.md`: orden de carga y reglas de arranque de sesión.
- `MEMORY.md`: decisiones duraderas del proyecto.
- `memory/`: memoria diaria.
- `specs/product-spec.md`: alcance funcional y criterios de aceptación.
- `specs/technical-spec.md`: arquitectura, dominio, seguridad y despliegue.
- `specs/tasks.md`: backlog completo por fases.

## Objetivo del MVP

Entregar una wiki colaborativa funcional donde:

- cualquier usuario pueda registrarse e iniciar sesión;
- cualquier usuario autenticado pueda crear, editar y categorizar páginas;
- toda edición genere una revisión con autoría y diff;
- se pueda consultar el histórico y restaurar versiones previas;
- las páginas se puedan descubrir por categorías, enlaces internos y búsqueda básica.

## Estado implementado

La base del proyecto ya está funcionando y validada en Docker. Ahora mismo incluye:

- registro, login y edición de perfil;
- creación, edición y visualización de páginas Markdown;
- categorías colaborativas en selección o creación inline;
- historial de revisiones, diff textual y restauración;
- slugs históricos con redirección;
- búsqueda simple desde la portada;
- comandos `app:user:create` y `app:pages:rebuild-html`;
- migración inicial validada sobre MySQL y tests básicos.

En desarrollo local, la aplicación queda publicada en `http://127.0.0.1:8081`.

## Despliegue en servidor con Docker

Estas instrucciones están pensadas para un servidor Linux limpio, por ejemplo Ubuntu 24.04.

### 1. Preparar el servidor

Instala Docker y el plugin de Compose:

```bash
sudo apt update
sudo apt install -y ca-certificates curl gnupg
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo usermod -aG docker $USER
```

Cierra sesión y vuelve a entrar para que el grupo `docker` aplique.

### 2. Clonar el proyecto

```bash
git clone <REPO_URL> /opt/foedex
cd /opt/foedex
```

### 3. Preparar variables de entorno

Crea el fichero `.env.prod.local` a partir de la plantilla que tendrá el proyecto:

```bash
cp .env.prod.local.example .env.prod.local
```

Configura al menos estas variables:

```dotenv
APP_ENV=prod
APP_SECRET=change_this_secret
DATABASE_URL="mysql://foedex:change_this_password@db:3306/foedex?serverVersion=8.4.0&charset=utf8mb4"
MESSENGER_TRANSPORT_DSN=doctrine://default
APP_DOMAIN=wiki.example.com
```

Si se usa proxy externo con TLS, la variable del host debe reflejar el dominio público real.

### 4. Levantar los contenedores

```bash
docker compose -f compose.yaml -f compose.prod.yaml up -d --build
```

El despliegue final debería incluir, como mínimo:

- `app`: PHP-FPM con Symfony
- `web`: Nginx sirviendo la aplicación
- `db`: MySQL
- `worker`: proceso Messenger para trabajos asíncronos

### 5. Instalar dependencias y preparar la aplicación

La imagen de producción ya instala dependencias durante el build. Después ejecuta:

```bash
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec app php bin/console cache:clear
docker compose exec app php bin/console cache:warmup
```

### 6. Crear el primer usuario

El proyecto debería exponer un comando de consola para bootstrap del primer usuario:

```bash
docker compose exec app php bin/console app:user:create admin@example.com "Nombre Visible" "ChangeMeNow123!"
```

Aunque no existan administradores, este comando sirve para crear el primer usuario operativo del sistema.

### 7. Verificar la instalación

Comprueba que los contenedores estén sanos:

```bash
docker compose ps
docker compose logs -f app
docker compose logs -f web
```

Puntos a verificar:

- la página principal carga;
- el registro funciona;
- se puede iniciar sesión;
- se puede crear una página;
- se genera historial al editarla.

### 8. Actualizar la aplicación

Para desplegar cambios futuros:

```bash
cd /opt/foedex
git pull
docker compose -f compose.yaml -f compose.prod.yaml up -d --build
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec app php bin/console cache:clear
docker compose exec app php bin/console cache:warmup
```

### 9. Copias de seguridad

Respalda como mínimo:

- la base de datos MySQL;
- los ficheros subidos si se habilitan adjuntos o imágenes;
- el fichero `.env.prod.local`.

Ejemplo de backup manual de MySQL:

```bash
docker compose exec db mysqldump -ufoedex -p foedex > foedex-$(date +%F).sql
```

## Estado actual

El repositorio contiene ya la implementación base y la documentación guía del proyecto. Las siguientes iteraciones deben seguir actualizando las specs a medida que cambie el alcance real.
