#!/usr/bin/env bash
# scripts/package.sh
# Empacota a pasta ml-banner-suite/ em ml-banner-suite-vX.Y.Z.zip pronto pra GitHub Release.
# Estrutura esperada: pasta interna SOMENTE ml-banner-suite/ (sem wrapper, sem build dirs).

set -euo pipefail

PLUGIN_SLUG="ml-banner-suite"
ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
SRC_DIR="${ROOT_DIR}/${PLUGIN_SLUG}"

if [[ ! -d "${SRC_DIR}" ]]; then
  echo "ERRO: pasta ${SRC_DIR} nao encontrada." >&2
  exit 1
fi

# Detecta versao do header do plugin
VERSION=$(grep -E '^[[:space:]]*\*[[:space:]]*Version:' "${SRC_DIR}/${PLUGIN_SLUG}.php" | head -n1 | sed -E 's/.*Version:[[:space:]]*([0-9]+\.[0-9]+\.[0-9]+).*/\1/')
if [[ -z "${VERSION}" ]]; then
  echo "ERRO: nao foi possivel detectar a versao no header." >&2
  exit 1
fi

OUT_FILE="${ROOT_DIR}/${PLUGIN_SLUG}-v${VERSION}.zip"
echo "Empacotando ${PLUGIN_SLUG} v${VERSION}..."

# Remove zip anterior se existir
rm -f "${OUT_FILE}"

# Compacta com separador / e entrada explicita da pasta raiz
(cd "${ROOT_DIR}" && zip -r "${OUT_FILE}" "${PLUGIN_SLUG}" -x "*.DS_Store" "*/.*" >/dev/null)

echo "OK: ${OUT_FILE}"
echo "SHA-256: $(sha256sum "${OUT_FILE}" | awk '{print $1}')"
echo "Size: $(stat -c %s "${OUT_FILE}" 2>/dev/null || stat -f %z "${OUT_FILE}") bytes"