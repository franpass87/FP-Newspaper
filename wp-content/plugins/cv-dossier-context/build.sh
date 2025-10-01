#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PLUGIN_SLUG="$(basename "$SCRIPT_DIR")"
BUILD_DIR="$SCRIPT_DIR/build"
STAGING_DIR="$BUILD_DIR/$PLUGIN_SLUG"
BUMP_OPTION="patch"
SET_VERSION=""
ZIP_NAME=""
RUN_BUMP=false

while [ "$#" -gt 0 ]; do
    case "$1" in
        --set-version=*)
            SET_VERSION="${1#*=}"
            RUN_BUMP=true
            ;;
        --set-version)
            if [ "$#" -lt 2 ]; then
                echo "Missing value for --set-version" >&2
                exit 1
            fi
            shift
            SET_VERSION="$1"
            RUN_BUMP=true
            ;;
        --bump=*)
            BUMP_OPTION="${1#*=}"
            RUN_BUMP=true
            ;;
        --bump)
            if [ "$#" -lt 2 ]; then
                echo "Missing value for --bump" >&2
                exit 1
            fi
            shift
            BUMP_OPTION="$1"
            RUN_BUMP=true
            ;;
        --zip-name=*)
            ZIP_NAME="${1#*=}"
            ;;
        --zip-name)
            if [ "$#" -lt 2 ]; then
                echo "Missing value for --zip-name" >&2
                exit 1
            fi
            shift
            ZIP_NAME="$1"
            ;;
        --help|-h)
            cat <<'EOF'
Usage: build.sh [options]
  --set-version=X.Y.Z   Set the plugin version explicitly
  --bump=LEVEL          Bump the plugin version (patch, minor, major). Default: patch
  --zip-name=NAME       Custom name for the generated zip file (without path)
EOF
            exit 0
            ;;
        *)
            echo "Unknown option: $1" >&2
            exit 1
            ;;
    esac
    shift
done

if [ "$RUN_BUMP" = true ]; then
    if [ -n "$SET_VERSION" ]; then
        php "$SCRIPT_DIR/tools/bump-version.php" --set="$SET_VERSION"
    else
        case "$BUMP_OPTION" in
            patch|'')
                php "$SCRIPT_DIR/tools/bump-version.php" --patch
                ;;
            minor)
                php "$SCRIPT_DIR/tools/bump-version.php" --minor
                ;;
            major)
                php "$SCRIPT_DIR/tools/bump-version.php" --major
                ;;
            *)
                echo "Invalid bump level: $BUMP_OPTION" >&2
                exit 1
                ;;
        esac
    fi
fi

cd "$SCRIPT_DIR"

if ! command -v composer >/dev/null 2>&1; then
    echo "composer command not found" >&2
    exit 1
fi

rm -rf vendor
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
composer dump-autoload -o --classmap-authoritative

rm -rf "$STAGING_DIR"
mkdir -p "$STAGING_DIR"

RSYNC_EXCLUDES=(
    "--exclude=.git"
    "--exclude=.github"
    "--exclude=tests"
    "--exclude=docs"
    "--exclude=node_modules"
    "--exclude=*.md"
    "--exclude=.idea"
    "--exclude=.vscode"
    "--exclude=build"
    "--exclude=.gitattributes"
    "--exclude=.gitignore"
    "--exclude=build.sh"
    "--exclude=tools"
    "--exclude=composer.json"
    "--exclude=composer.lock"
)

rsync -a --delete "${RSYNC_EXCLUDES[@]}" "$SCRIPT_DIR/" "$STAGING_DIR/"

FINAL_VERSION=$(php -r '$dir = $argv[1]; foreach (glob($dir . "/*.php") as $file) { $data = file_get_contents($file); if ($data !== false && preg_match("/(Version:\\s*)([0-9]+\\.[0-9]+\\.[0-9]+)/i", $data, $m)) { echo $m[2]; exit; } } exit(1);' "$SCRIPT_DIR")

if [ -z "$FINAL_VERSION" ]; then
    echo "Unable to determine final plugin version." >&2
    exit 1
fi

TIMESTAMP="$(date +%Y%m%d%H%M)"
DEFAULT_ZIP_NAME="$PLUGIN_SLUG-$TIMESTAMP.zip"
FINAL_ZIP_NAME="$DEFAULT_ZIP_NAME"

if [ -n "$ZIP_NAME" ]; then
    case "$ZIP_NAME" in
        *.zip)
            FINAL_ZIP_NAME="$ZIP_NAME"
            ;;
        *)
            FINAL_ZIP_NAME="$ZIP_NAME.zip"
            ;;
    esac
fi

mkdir -p "$BUILD_DIR"
cd "$BUILD_DIR"
rm -f "$FINAL_ZIP_NAME"
zip -r "$FINAL_ZIP_NAME" "$PLUGIN_SLUG" >/dev/null

FULL_ZIP_PATH="$BUILD_DIR/$FINAL_ZIP_NAME"
echo "Version: $FINAL_VERSION"
echo "Zip: $FULL_ZIP_PATH"

if command -v unzip >/dev/null 2>&1; then
    echo "Top-level files:"
    unzip -Z1 "$FULL_ZIP_PATH" | awk -F/ '{if (NF==1 || (NF==2 && $2=="")) print $1}' | sort -u
fi
