#!/bin/bash

# Build Script per Cronaca di Viterbo Plugin
# Concatena e minifica assets per produzione

echo "🚀 Build Cronaca di Viterbo Plugin"
echo "=================================="

# Colori per output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Directory
ASSETS_DIR="assets"
BUILD_DIR="build"
CSS_DIR="${ASSETS_DIR}/css"
JS_DIR="${ASSETS_DIR}/js"

# Crea directory build se non esiste
mkdir -p ${BUILD_DIR}/css
mkdir -p ${BUILD_DIR}/js

echo ""
echo -e "${BLUE}📦 Step 1: Concatenazione CSS...${NC}"

# Concatena CSS Frontend
cat ${CSS_DIR}/components/forms.css \
    ${CSS_DIR}/components/cards.css \
    ${CSS_DIR}/components/layouts.css \
    ${CSS_DIR}/components/responsive.css \
    > ${BUILD_DIR}/css/frontend.css

echo -e "${GREEN}✓ CSS Frontend concatenato${NC}"

# Concatena CSS Admin
cat ${CSS_DIR}/admin/dashboard.css \
    ${CSS_DIR}/admin/settings.css \
    ${CSS_DIR}/admin/tables.css \
    > ${BUILD_DIR}/css/admin.css

echo -e "${GREEN}✓ CSS Admin concatenato${NC}"

echo ""
echo -e "${BLUE}📦 Step 2: Concatenazione JavaScript...${NC}"

# Concatena JS Frontend
cat ${JS_DIR}/modules/utils.js \
    ${JS_DIR}/modules/analytics-tracker.js \
    ${JS_DIR}/modules/form-handler.js \
    ${JS_DIR}/modules/voting-system.js \
    ${JS_DIR}/modules/petition-handler.js \
    ${JS_DIR}/modules/poll-handler.js \
    ${JS_DIR}/main.js \
    > ${BUILD_DIR}/js/frontend.js

echo -e "${GREEN}✓ JS Frontend concatenato${NC}"

# Concatena JS Admin
cat ${JS_DIR}/admin/dashboard.js \
    ${JS_DIR}/admin/moderation.js \
    ${JS_DIR}/admin/settings.js \
    ${JS_DIR}/admin-main.js \
    > ${BUILD_DIR}/js/admin.js

echo -e "${GREEN}✓ JS Admin concatenato${NC}"

# Minificazione (se disponibili i tool)
echo ""
echo -e "${BLUE}📦 Step 3: Minificazione...${NC}"

# Verifica se csso è disponibile (npm install -g csso-cli)
if command -v csso &> /dev/null; then
    csso ${BUILD_DIR}/css/frontend.css -o ${BUILD_DIR}/css/frontend.min.css
    csso ${BUILD_DIR}/css/admin.css -o ${BUILD_DIR}/css/admin.min.css
    echo -e "${GREEN}✓ CSS minificato${NC}"
else
    echo -e "${YELLOW}⚠ csso non trovato. Installa con: npm install -g csso-cli${NC}"
    # Copia senza minificazione
    cp ${BUILD_DIR}/css/frontend.css ${BUILD_DIR}/css/frontend.min.css
    cp ${BUILD_DIR}/css/admin.css ${BUILD_DIR}/css/admin.min.css
fi

# Verifica se uglifyjs è disponibile (npm install -g uglify-js)
if command -v uglifyjs &> /dev/null; then
    uglifyjs ${BUILD_DIR}/js/frontend.js -c -m -o ${BUILD_DIR}/js/frontend.min.js
    uglifyjs ${BUILD_DIR}/js/admin.js -c -m -o ${BUILD_DIR}/js/admin.min.js
    echo -e "${GREEN}✓ JavaScript minificato${NC}"
else
    echo -e "${YELLOW}⚠ uglifyjs non trovato. Installa con: npm install -g uglify-js${NC}"
    # Copia senza minificazione
    cp ${BUILD_DIR}/js/frontend.js ${BUILD_DIR}/js/frontend.min.js
    cp ${BUILD_DIR}/js/admin.js ${BUILD_DIR}/js/admin.min.js
fi

# Statistiche
echo ""
echo -e "${BLUE}📊 Statistiche Build:${NC}"
echo "--------------------------------"

if [ -f ${BUILD_DIR}/css/frontend.css ]; then
    FRONTEND_CSS_SIZE=$(wc -c < ${BUILD_DIR}/css/frontend.css)
    FRONTEND_CSS_MIN_SIZE=$(wc -c < ${BUILD_DIR}/css/frontend.min.css)
    echo -e "Frontend CSS: ${FRONTEND_CSS_SIZE} bytes → ${GREEN}${FRONTEND_CSS_MIN_SIZE} bytes${NC}"
fi

if [ -f ${BUILD_DIR}/css/admin.css ]; then
    ADMIN_CSS_SIZE=$(wc -c < ${BUILD_DIR}/css/admin.css)
    ADMIN_CSS_MIN_SIZE=$(wc -c < ${BUILD_DIR}/css/admin.min.css)
    echo -e "Admin CSS: ${ADMIN_CSS_SIZE} bytes → ${GREEN}${ADMIN_CSS_MIN_SIZE} bytes${NC}"
fi

if [ -f ${BUILD_DIR}/js/frontend.js ]; then
    FRONTEND_JS_SIZE=$(wc -c < ${BUILD_DIR}/js/frontend.js)
    FRONTEND_JS_MIN_SIZE=$(wc -c < ${BUILD_DIR}/js/frontend.min.js)
    echo -e "Frontend JS: ${FRONTEND_JS_SIZE} bytes → ${GREEN}${FRONTEND_JS_MIN_SIZE} bytes${NC}"
fi

if [ -f ${BUILD_DIR}/js/admin.js ]; then
    ADMIN_JS_SIZE=$(wc -c < ${BUILD_DIR}/js/admin.js)
    ADMIN_JS_MIN_SIZE=$(wc -c < ${BUILD_DIR}/js/admin.min.js)
    echo -e "Admin JS: ${ADMIN_JS_SIZE} bytes → ${GREEN}${ADMIN_JS_MIN_SIZE} bytes${NC}"
fi

echo ""
echo -e "${GREEN}✅ Build completato con successo!${NC}"
echo ""
echo -e "${YELLOW}📁 File generati in: ${BUILD_DIR}/${NC}"
echo "  - ${BUILD_DIR}/css/frontend.min.css"
echo "  - ${BUILD_DIR}/css/admin.min.css"
echo "  - ${BUILD_DIR}/js/frontend.min.js"
echo "  - ${BUILD_DIR}/js/admin.min.js"
echo ""
echo -e "${BLUE}💡 Per usare i file minificati, modifica Bootstrap.php${NC}"
