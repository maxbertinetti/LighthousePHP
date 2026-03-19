#!/bin/sh
set -eu

SCRIPT_DIR=$(CDPATH='' cd -- "$(dirname -- "$0")" && pwd)
VERSION=$(cat "$SCRIPT_DIR/VERSION")
OUTPUT_DIR=${1:-"$SCRIPT_DIR/dist"}
STAGING_DIR=

cleanup() {
    if [ -n "${STAGING_DIR:-}" ] && [ -d "$STAGING_DIR" ]; then
        rm -rf "$STAGING_DIR"
    fi
}

trap cleanup EXIT INT TERM

mkdir -p "$OUTPUT_DIR"
STAGING_DIR=$(mktemp -d)
PACKAGE_ROOT="$STAGING_DIR/lighthousephp"
ARCHIVE_PATH="$OUTPUT_DIR/lighthousephp-$VERSION.tar.gz"

mkdir -p "$PACKAGE_ROOT"

for path in .gitignore VERSION config core docs migrations pages public tests view lighthouse lighthousephp remove.sh install.sh package-release.sh; do
    cp -R "$SCRIPT_DIR/$path" "$PACKAGE_ROOT/$path"
done

chmod 0755 "$PACKAGE_ROOT/lighthouse" "$PACKAGE_ROOT/lighthousephp"
chmod 0755 "$PACKAGE_ROOT/remove.sh" "$PACKAGE_ROOT/install.sh" "$PACKAGE_ROOT/package-release.sh"

tar -czf "$ARCHIVE_PATH" -C "$STAGING_DIR" lighthousephp

printf '%s\n' "Created release bundle."
printf '%s\n' "  Archive: $ARCHIVE_PATH"
printf '%s\n' "  Version: $VERSION"
