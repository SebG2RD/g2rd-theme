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

grep -qF 'g2rd-theme/style.css'    <<< "$LIST" || fail "g2rd-theme/style.css absent du ZIP"
grep -qF 'g2rd-theme/functions.php' <<< "$LIST" || fail "g2rd-theme/functions.php absent du ZIP"
grep -qF 'g2rd-theme/index.php'    <<< "$LIST" || fail "g2rd-theme/index.php absent du ZIP"

# Ces chemins ne doivent pas apparaître dans le paquet distribué.
if grep -qE 'g2rd-theme/(vendor|node_modules)/' <<< "$LIST"; then
  fail "vendor ou node_modules présent dans le ZIP (exclusion attendue)"
fi
if grep -qE 'g2rd-theme/(composer\.json|composer\.lock|package\.json|package-lock\.json)$' <<< "$LIST"; then
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
