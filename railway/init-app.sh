#!/usr/bin/env bash
set -euo pipefail

# Fail fast if required env vars are missing
bash railway/check-env.sh

# Run database migrations for production
php artisan migrate --force

# Cache framework files for faster boot
php artisan optimize

# Optional: add background jobs or cache warmers here.
# Example: php artisan queue:restart
# Example: php artisan cache:clear && php artisan config:cache
