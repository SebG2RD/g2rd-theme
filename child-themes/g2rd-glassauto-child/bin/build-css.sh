#!/usr/bin/env bash
# Régénère assets/css/glassauto.min.css à partir des 4 feuilles sources.
# Usage : bash bin/build-css.sh   (Node 18+ requis)
set -euo pipefail
cd "$(dirname "$0")/../assets/css"
cat global.css icons.css header-footer.css sections.css > /tmp/glassauto-all.css
npx --yes lightningcss-cli@1 --minify --targets ">= 0.5%, last 2 versions, not dead" /tmp/glassauto-all.css -o glassauto.min.css
echo "glassauto.min.css : $(wc -c < glassauto.min.css) octets"
