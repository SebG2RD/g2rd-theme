#!/usr/bin/env bash
# Contrôle d'intégrité du ZIP de thème (structure WordPress + exclusions release).
# Usage: tools/verify-theme-zip.sh <chemin/g2rd-theme.zip>

set -euo pipefail

ZIP="${1:?chemin du ZIP requis}"

fail() {
  echo "ERREUR ZIP: $*" >&2
  exit 1
}

[ -f "$ZIP" ] || fail "fichier introuvable: $ZIP"

unzip -t "$ZIP" >/dev/null || fail "archive ZIP corrompue (unzip -t)"

LIST="$(unzip -l "$ZIP")"

echo "$LIST" | grep -q 'g2rd-theme/style.css' || fail "g2rd-theme/style.css absent du ZIP"
echo "$LIST" | grep -q 'g2rd-theme/functions.php' || fail "g2rd-theme/functions.php absent du ZIP"
echo "$LIST" | grep -q 'g2rd-theme/index.php' || fail "g2rd-theme/index.php absent du ZIP"

# Ces chemins ne doivent pas apparaître dans le paquet distribué.
if echo "$LIST" | grep -E 'g2rd-theme/(vendor|node_modules)/' >/dev/null; then
  fail "vendor ou node_modules présent dans le ZIP (exclusion attendue)"
fi
if echo "$LIST" | grep -E 'g2rd-theme/(composer\.json|composer\.lock|package\.json|package-lock\.json)$' >/dev/null; then
  fail "fichiers composer/package racine présents dans le ZIP (exclusion attendue)"
fi

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
unzip -q "$ZIP" -d "$TMP"

THEME_DIR="$TMP/g2rd-theme"
[ -d "$THEME_DIR" ] || fail "dossier g2rd-theme absent après extraction"

php -l "$THEME_DIR/functions.php" >/dev/null || fail "syntaxe PHP invalide: functions.php"
php -l "$THEME_DIR/index.php" >/dev/null || fail "syntaxe PHP invalide: index.php"

echo "OK: ZIP valide ($ZIP)"
