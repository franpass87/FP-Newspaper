#!/bin/bash

# Watch Script per Cronaca di Viterbo Plugin
# Monitora modifiche e rebuilda automaticamente

echo "👀 Watching assets for changes..."
echo "Press Ctrl+C to stop"
echo ""

# Colori
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

ASSETS_DIR="assets"

# Verifica se chokidar è disponibile
if command -v chokidar &> /dev/null; then
    # Usa chokidar se disponibile (npm install -g chokidar-cli)
    chokidar "${ASSETS_DIR}/**/*.{js,css}" -c "bash build.sh"
else
    # Fallback con fswatch (macOS) o inotifywait (Linux)
    if command -v fswatch &> /dev/null; then
        fswatch -o ${ASSETS_DIR} | while read f; do
            echo -e "${BLUE}📝 File modificato, rebuilding...${NC}"
            bash build.sh
        done
    elif command -v inotifywait &> /dev/null; then
        while inotifywait -r -e modify,create,delete ${ASSETS_DIR}; do
            echo -e "${BLUE}📝 File modificato, rebuilding...${NC}"
            bash build.sh
        done
    else
        echo -e "${YELLOW}⚠ Nessun tool di watch disponibile${NC}"
        echo "Installa uno dei seguenti:"
        echo "  - chokidar: npm install -g chokidar-cli"
        echo "  - fswatch: brew install fswatch (macOS)"
        echo "  - inotify-tools: apt install inotify-tools (Linux)"
        exit 1
    fi
fi
