#!/bin/bash
SCRIPT_DIR=$(cd "$(dirname "$0")/../api" && pwd)
cd "$SCRIPT_DIR"
./vendor/bin/php-cs-fixer "$@"
