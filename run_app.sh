#!/usr/bin/env sh
set -e
cd "$(dirname "$0")"
php setup.php
echo "Open http://127.0.0.1:8000 in your browser"
exec php -S 127.0.0.1:8000 router.php
