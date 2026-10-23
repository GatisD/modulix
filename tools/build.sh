#!/usr/bin/env bash
# Builds the runtime plugin: build/modulix/ and build/modulix-<version>.zip (no tests, docs, tools, vendor).
set -euo pipefail
cd "$(dirname "$0")/.."
ver=$(sed -n 's/^ \* Version: //p' modulix.php)
rm -rf build && mkdir -p build/modulix
cp -R modulix.php NOTICE.md license.txt src base core modules assets build/modulix/
(cd build && zip -qr "modulix-$ver.zip" modulix)
echo "build/modulix-$ver.zip"
