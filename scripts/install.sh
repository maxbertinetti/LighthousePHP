#!/bin/sh
set -eu

PREFIX=${LIGHTHOUSE_PREFIX:-"$HOME/.local"}
BIN_DIR=${LIGHTHOUSE_BIN_DIR:-"$PREFIX/bin"}
SHARE_DIR=${LIGHTHOUSE_SHARE_DIR:-"$PREFIX/share/lighthouse"}
FRAMEWORK_DIR="$SHARE_DIR/current"
SCRIPT_DIR=$(CDPATH='' cd -- "$(dirname -- "$0")" && pwd)
REPO_ROOT=$(dirname "$SCRIPT_DIR")
REPO_SLUG=${LIGHTHOUSE_REPO:-${1:-}}
REPO_SELECTOR=${LIGHTHOUSE_REF:-${2:-}}
INSTALL_URL=${LIGHTHOUSE_INSTALL_URL:-}
TEMP_DIR=
REPO_REF=
REPO_REF_TYPE=
BUNDLE_ASSET_NAME=
RESOLVED_VERSION=

cleanup() {
    if [ -n "${TEMP_DIR:-}" ] && [ -d "$TEMP_DIR" ]; then
        rm -rf "$TEMP_DIR"
    fi
}

trap cleanup EXIT INT TERM

resolve_ref() {
    selector=$1

    if [ -z "$selector" ]; then
        REPO_REF_TYPE=latest
        REPO_REF=$(latest_release_tag)
        return 0
    fi

    case "$selector" in
        branch:*)
            REPO_REF_TYPE=branch
            REPO_REF=${selector#branch:}
            ;;
        tag:*)
            REPO_REF_TYPE=tag
            REPO_REF=${selector#tag:}
            ;;
        version:*)
            REPO_REF_TYPE=tag
            REPO_REF=${selector#version:}
            ;;
        latest)
            REPO_REF_TYPE=latest
            REPO_REF=$(latest_release_tag)
            ;;
        *)
            REPO_REF_TYPE=branch
            REPO_REF=$selector
            ;;
    esac

    if [ -z "$REPO_REF" ]; then
        printf '%s\n' "Repository ref cannot be empty." >&2
        exit 1
    fi
}

latest_release_tag() {
    if [ "${LIGHTHOUSE_LATEST_RELEASE:-}" != "" ]; then
        printf '%s\n' "$LIGHTHOUSE_LATEST_RELEASE"
        return 0
    fi

    if [ -z "$REPO_SLUG" ]; then
        printf '%s\n' "Set LIGHTHOUSE_REPO=owner/repo or pass 'owner/repo' to install.sh when installing from GitHub." >&2
        exit 1
    fi

    tag=$(curl -fsSL "https://api.github.com/repos/$REPO_SLUG/releases/latest" \
        | sed -n 's/.*"tag_name":[[:space:]]*"\([^"]*\)".*/\1/p' \
        | head -n 1)

    if [ -z "$tag" ]; then
        printf '%s\n' "Unable to determine the latest Lighthouse release." >&2
        exit 1
    fi

    printf '%s\n' "$tag"
}

bundle_asset_name() {
    printf '%s\n' "lighthousephp-$REPO_REF.tar.gz"
}

resolve_version() {
    if [ "${LIGHTHOUSE_RELEASE_VERSION:-}" != "" ]; then
        printf '%s\n' "$LIGHTHOUSE_RELEASE_VERSION"
        return 0
    fi

    if [ "$REPO_REF_TYPE" = "tag" ]; then
        printf '%s\n' "$REPO_REF"
        return 0
    fi

    if command -v git >/dev/null 2>&1 && [ -d "$REPO_ROOT/.git" ]; then
        version=$(git -C "$REPO_ROOT" describe --tags --always --dirty 2>/dev/null || true)

        if [ -n "$version" ]; then
            printf '%s\n' "$version"
            return 0
        fi
    fi

    printf '%s\n' "$REPO_REF"
}

default_download_url() {
    if [ -z "$REPO_SLUG" ]; then
        printf '%s\n' "Set LIGHTHOUSE_REPO=owner/repo or pass 'owner/repo' to install.sh when installing from GitHub." >&2
        exit 1
    fi

    if [ "$REPO_REF_TYPE" = "tag" ] || [ "$REPO_REF_TYPE" = "latest" ]; then
        printf '%s\n' "https://github.com/$REPO_SLUG/releases/download/$REPO_REF/$BUNDLE_ASSET_NAME"
        return 0
    fi

    printf '%s\n' "https://github.com/$REPO_SLUG/archive/refs/heads/$REPO_REF.tar.gz"
}

copy_framework_tree() {
    source_dir=$1
    target_dir=$2

    mkdir -p "$target_dir"

    for path in LICENSE README.md lighthouse lighthousephp src; do
        cp -R "$source_dir/$path" "$target_dir/$path"
    done
}

download_framework_tree() {
    archive_url=${LIGHTHOUSE_DOWNLOAD_URL:-}

    if [ -z "$archive_url" ]; then
        archive_url=$(default_download_url)
    fi

    TEMP_DIR=$(mktemp -d)
    archive_path="$TEMP_DIR/lighthouse.tar.gz"
    curl -fsSL "$archive_url" -o "$archive_path"
    mkdir -p "$TEMP_DIR/source"
    tar -xzf "$archive_path" -C "$TEMP_DIR/source" --strip-components=1
    copy_framework_tree "$TEMP_DIR/source" "$FRAMEWORK_DIR"
}

write_metadata() {
    cat > "$SHARE_DIR/metadata.env" <<EOF
LIGHTHOUSE_INSTALLED_VERSION=$RESOLVED_VERSION
LIGHTHOUSE_REPO=$REPO_SLUG
LIGHTHOUSE_REF=$REPO_REF
LIGHTHOUSE_REF_TYPE=$REPO_REF_TYPE
LIGHTHOUSE_INSTALL_URL=$INSTALL_URL
LIGHTHOUSE_PREFIX=$PREFIX
LIGHTHOUSE_BIN_DIR=$BIN_DIR
LIGHTHOUSE_SHARE_DIR=$SHARE_DIR
EOF
}

install_wrapper() {
    mkdir -p "$BIN_DIR"

    cat > "$BIN_DIR/lighthouse" <<EOF
#!/bin/sh
set -eu
LIGHTHOUSE_HOME="$FRAMEWORK_DIR"
export LIGHTHOUSE_HOME
exec "\$LIGHTHOUSE_HOME/lighthouse" "\$@"
EOF

    chmod 0755 "$BIN_DIR/lighthouse"
}

mkdir -p "$SHARE_DIR"
rm -rf "$FRAMEWORK_DIR"
resolve_ref "$REPO_SELECTOR"
BUNDLE_ASSET_NAME=$(bundle_asset_name)
RESOLVED_VERSION=$(resolve_version)

if [ -f "$REPO_ROOT/src/core/cli.php" ] && [ -f "$REPO_ROOT/lighthousephp" ] && [ -f "$REPO_ROOT/lighthouse" ]; then
    copy_framework_tree "$REPO_ROOT" "$FRAMEWORK_DIR"
else
    download_framework_tree
fi

chmod 0755 "$FRAMEWORK_DIR/lighthouse" "$FRAMEWORK_DIR/lighthousephp"
write_metadata
install_wrapper

printf '%s\n' "Lighthouse installed."
printf '%s\n' "  Binary: $BIN_DIR/lighthouse"
printf '%s\n' "  Framework bundle: $FRAMEWORK_DIR"
printf '%s\n' "  Version: $RESOLVED_VERSION"
printf '%s\n' ""
printf '%s\n' "Add this to your shell profile if needed:"
printf '%s\n' "  export PATH=\"$BIN_DIR:\$PATH\""
