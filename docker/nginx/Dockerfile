FROM node:22-alpine AS frontend_builder

WORKDIR /frontend
COPY package.json package.json
COPY package-lock.json package-lock.json
COPY vite.config.js vite.config.js
COPY frontend frontend
RUN npm ci
RUN npm run build

FROM nginx:1.29-alpine

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY public /var/www/html/public
COPY --from=frontend_builder /frontend/public/app /var/www/html/public/app
