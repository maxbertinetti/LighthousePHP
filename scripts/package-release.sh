#!/bin/sh
set -eu

SCRIPT_DIR=$(CDPATH='' cd -- "$(dirname -- "$0")" && pwd)
REPO_ROOT=$(dirname "$SCRIPT_DIR")
OUTPUT_DIR=${1:-"$REPO_ROOT/dist"}
STAGING_DIR=
VERSION=

cleanup() {
    if [ -n "${STAGING_DIR:-}" ] && [ -d "$STAGING_DIR" ]; then
        rm -rf "$STAGING_DIR"
    fi
}

trap cleanup EXIT INT TERM

resolve_version() {
    if [ "${LIGHTHOUSE_RELEASE_VERSION:-}" != "" ]; then
        printf '%s\n' "$LIGHTHOUSE_RELEASE_VERSION"
        return 0
    fi

    if command -v git >/dev/null 2>&1 && [ -d "$REPO_ROOT/.git" ]; then
        version=$(git -C "$REPO_ROOT" describe --tags --exact-match 2>/dev/null || true)

        if [ -n "$version" ]; then
            printf '%s\n' "$version"
            return 0
        fi

        version=$(git -C "$REPO_ROOT" describe --tags --always --dirty 2>/dev/null || true)

        if [ -n "$version" ]; then
            printf '%s\n' "$version"
            return 0
        fi
    fi

    printf '%s\n' "Set LIGHTHOUSE_RELEASE_VERSION or run from a Git checkout with tags." >&2
    exit 1
}

VERSION=$(resolve_version)
mkdir -p "$OUTPUT_DIR"
STAGING_DIR=$(mktemp -d)
PACKAGE_ROOT="$STAGING_DIR/lighthousephp"
ARCHIVE_PATH="$OUTPUT_DIR/lighthousephp-$VERSION.tar.gz"

mkdir -p "$PACKAGE_ROOT"

for path in LICENSE README.md lighthouse lighthousephp src; do
    cp -R "$REPO_ROOT/$path" "$PACKAGE_ROOT/$path"
done

chmod 0755 "$PACKAGE_ROOT/lighthouse" "$PACKAGE_ROOT/lighthousephp"

tar -czf "$ARCHIVE_PATH" -C "$STAGING_DIR" lighthousephp

printf '%s\n' "Created release bundle."
printf '%s\n' "  Archive: $ARCHIVE_PATH"
printf '%s\n' "  Version: $VERSION"
