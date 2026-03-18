#!/bin/sh
set -eu

PREFIX=${LIGHTHOUSE_PREFIX:-"$HOME/.local"}
BIN_DIR=${LIGHTHOUSE_BIN_DIR:-"$PREFIX/bin"}
SHARE_DIR=${LIGHTHOUSE_SHARE_DIR:-"$PREFIX/share/lighthouse"}

rm -f "$BIN_DIR/lighthouse"
rm -rf "$SHARE_DIR"

printf '%s\n' "Lighthouse removed."
printf '%s\n' "  Removed binary: $BIN_DIR/lighthouse"
printf '%s\n' "  Removed data dir: $SHARE_DIR"
