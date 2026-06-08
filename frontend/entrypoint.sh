#!/bin/sh
# Render passes PORT (default 10000).
# BACKEND_URL is the internal or external URL of the backend service.
# e.g. https://school-cms-backend.onrender.com
#      or http://school-cms-backend:10000  (if same Render account, internal)

export PORT=${PORT:-10000}
export BACKEND_URL=${BACKEND_URL:-https://school-cms-dgae.onrender.com}

# nginx official image supports envsubst via /etc/nginx/templates/
# Files there with .template suffix are processed and written to /etc/nginx/conf.d/
envsubst '${PORT} ${BACKEND_URL}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

echo "Starting nginx on port ${PORT} — proxying /api and /uploads to ${BACKEND_URL}"
exec nginx -g 'daemon off;'
