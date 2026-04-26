#!/usr/bin/env bash
# Vérifie que la version attendue (tag ou saisie manuelle) est alignée partout.
# Usage: tools/verify-release-version.sh <version>
# Exemple: tools/verify-release-version.sh 1.6.9.5

set -euo pipefail

EXPECTED="${1:?version attendue requise (ex. 1.6.9.5)}"

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

fail() {
  echo "ERREUR: $*" >&2
  exit 1
}

STYLE_VER="$(grep -m1 '^Version:' style.css | sed -E 's/^Version:[[:space:]]*//;s/[[:space:]]*$//')"
PKG_VER="$(node -p "require('./package.json').version")"
README_VER="$(grep -m1 '^Stable tag:' readme.txt | sed -E 's/^Stable tag:[[:space:]]*//;s/[[:space:]]*$//')"

COMPOSER_VER="$(node -e "var d=require('./composer.json');process.stdout.write((d.extra&&d.extra.g2rd_theme_version)||'')")"

[ -n "$STYLE_VER" ] || fail "Version introuvable dans style.css"
[ -n "$PKG_VER" ] || fail "version introuvable dans package.json"
[ -n "$README_VER" ] || fail "Stable tag introuvable dans readme.txt"
[ -n "$COMPOSER_VER" ] || fail "extra.g2rd_theme_version introuvable dans composer.json"

if [ "$STYLE_VER" != "$EXPECTED" ]; then
  fail "style.css Version=$STYLE_VER (attendu $EXPECTED)"
fi
if [ "$PKG_VER" != "$EXPECTED" ]; then
  fail "package.json version=$PKG_VER (attendu $EXPECTED)"
fi
if [ "$README_VER" != "$EXPECTED" ]; then
  fail "readme.txt Stable tag=$README_VER (attendu $EXPECTED)"
fi
if [ "$COMPOSER_VER" != "$EXPECTED" ]; then
  fail "composer.json extra.g2rd_theme_version=$COMPOSER_VER (attendu $EXPECTED)"
fi

echo "OK: versions alignées sur $EXPECTED (style.css, package.json, readme.txt, composer.json)"
