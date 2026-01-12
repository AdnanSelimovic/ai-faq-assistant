#!/usr/bin/env bash
set -euo pipefail

echo "Running init..."
bash railway/init-app.sh

echo "Starting server..."
exec php artisan serve --host=0.0.0.0 --port "${PORT:-8000}"
