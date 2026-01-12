#!/usr/bin/env bash
set -euo pipefail

missing=()
require_var() {
  local name="$1"
  if [ -z "${!name:-}" ]; then
    missing+=("$name")
  fi
}

require_var APP_KEY
require_var APP_URL
require_var SINGLE_USER_EMAIL
require_var DB_CONNECTION

if [ "${DB_CONNECTION:-}" != "sqlite" ]; then
  require_var DB_HOST
  require_var DB_DATABASE
  require_var DB_USERNAME
  require_var DB_PASSWORD
fi

if [ "${#missing[@]}" -ne 0 ]; then
  echo "Missing required environment variables: ${missing[*]}" >&2
  exit 1
fi
