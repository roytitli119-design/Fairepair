#!/usr/bin/env bash
# ============================================================
#  Fair'repair — créer une copie « version à rendre »
#
#  Produit une copie propre du projet dans ../Fair'repair-rendu
#  (sans le dépôt .git), figée sur la révision demandée.
#
#  Usage :
#     ./creer-version-rendue.sh              → copie l'état actuel (HEAD)
#     ./creer-version-rendue.sh HEAD        → idem
#     ./creer-version-rendue.sh bdf2883     → copie une version précise
#     ./creer-version-rendue.sh HEAD~1      → la version d'avant le dernier commit
#
#  Pense à committer tes changements avant de lancer le script,
#  sinon la copie ne contiendra pas ce que tu viens de faire.
# ============================================================
set -euo pipefail

REV="${1:-HEAD}"
PROJET="$(cd "$(dirname "$0")" && pwd)"
DESTINATION="$PROJET/../Fair'repair-rendu"

cd "$PROJET"

if ! git rev-parse --verify "$REV" >/dev/null 2>&1; then
    echo "❌ Révision introuvable : $REV"
    echo "   Versions disponibles :"
    git log --oneline | sed 's/^/     /'
    exit 1
fi

echo "▸ Version à rendre : $REV  ($(git log --oneline -1 "$REV"))"

if [ -n "$(git status --porcelain)" ]; then
    echo "⚠️  Des modifications ne sont pas commitées : elles ne seront PAS dans la copie."
    git status --short | sed 's/^/     /'
fi

rm -rf "$DESTINATION"
mkdir -p "$DESTINATION"
git archive "$REV" | tar -x -C "$DESTINATION"

echo "▸ Copie créée : $DESTINATION"
echo "▸ Contenu : $(find "$DESTINATION" -type f | wc -l) fichiers, $(du -sh "$DESTINATION" | cut -f1)"
echo
echo "Pour la tester :"
echo "    cd \"$DESTINATION/code\""
echo "    php -S 127.0.0.1:8001 -t public"
echo "    → http://127.0.0.1:8001/index.php?route=accueil"
