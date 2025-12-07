#!/bin/bash
# Script de Unificação de Usuários Duplicados - Plansul
# Uso: ./unify_users.sh [--dry-run] [--user=BEATRIZ.SC]
# Versão: 1.0
# Data: 2025-12-07

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Valores padrão
DRY_RUN=""
USER="BEATRIZ.SC"

# Parse argumentos
while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN="--dry-run"
            shift
            ;;
        --user=*)
            USER="${1#*=}"
            shift
            ;;
        *)
            echo -e "${RED}Argumento desconhecido: $1${NC}"
            exit 1
            ;;
    esac
done

echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}🔧 UNIFICAÇÃO DE USUÁRIOS DUPLICADOS - PLANSUL${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}Usuário Principal: $USER${NC}"
if [ ! -z "$DRY_RUN" ]; then
    echo -e "${YELLOW}Modo: DRY RUN (sem aplicar mudanças)${NC}"
else
    echo -e "${YELLOW}Modo: EXECUÇÃO REAL${NC}"
fi
echo ""

# Verificar se estamos no diretório correto
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ ERRO: arquivo 'artisan' não encontrado!${NC}"
    echo -e "${RED}Execute este script do diretório raiz da aplicação Laravel.${NC}"
    exit 1
fi

# Executar comando
echo -e "${BLUE}▶ Executando: php artisan users:unify --user=$USER $DRY_RUN${NC}"
echo ""

php artisan users:unify --user="$USER" $DRY_RUN

echo ""
echo -e "${GREEN}✅ Script finalizado!${NC}"
echo ""

# Se foi dry-run, oferecer opção de executar de verdade
if [ ! -z "$DRY_RUN" ]; then
    echo -e "${YELLOW}Para executar a consolidação de verdade, use:${NC}"
    echo -e "${BLUE}  ./unify_users.sh --user=$USER${NC}"
    echo ""
fi

exit 0
