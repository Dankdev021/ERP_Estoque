#!/bin/sh
set -e
cd /app

if [ -f yarn.lock ]; then
  yarn install
elif [ -f package-lock.json ]; then
  npm ci || npm install
else
  npm install
fi

exec "$@"
