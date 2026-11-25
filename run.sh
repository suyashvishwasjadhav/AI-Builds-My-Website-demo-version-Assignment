#!/usr/bin/env bash

# Exit immediately if a command exits with a non-zero status
set -e

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}======================================${NC}"
echo -e "${BLUE}   AI Builds My Website - Runner      ${NC}"
echo -e "${BLUE}======================================${NC}"

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKEND_DIR="$PROJECT_DIR/backend"
FRONTEND_DIR="$PROJECT_DIR/frontend"
DATABASE_DIR="$PROJECT_DIR/database"

MODE="dev"
SEED=false
SETUP_ONLY=false

for arg in "$@"; do
    case "$arg" in
        --seed)
            SEED=true
            ;;
        --setup)
            SETUP_ONLY=true
            ;;
        --build)
            MODE="build"
            ;;
        --help|-h)
            echo ""
            echo "Usage: ./run.sh [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  (no args)   Build frontend landing page & start full stack (Laravel Server, Queue Worker, Vite & Logs)"
            echo "  --seed      Run database migrations and seed demo data before starting"
            echo "  --build     Pre-compile all frontend/backend assets and start Server + Queue"
            echo "  --setup     Install dependencies, prepare .env, and run migrations without starting servers"
            echo "  --help, -h  Show this help message"
            exit 0
            ;;
        *)
            echo -e "${YELLOW}Warning: Unknown option '$arg' ignored.${NC}"
            ;;
    esac
done

echo -e "\n${BLUE}[1/4] Checking prerequisites...${NC}"
command -v php >/dev/null 2>&1 || { echo -e "${RED}Error: PHP is not installed.${NC}"; exit 1; }
command -v composer >/dev/null 2>&1 || { echo -e "${RED}Error: Composer is not installed.${NC}"; exit 1; }
command -v node >/dev/null 2>&1 || { echo -e "${RED}Error: Node.js is not installed.${NC}"; exit 1; }
command -v npm >/dev/null 2>&1 || { echo -e "${RED}Error: npm is not installed.${NC}"; exit 1; }
echo -e "${GREEN}✓ PHP, Composer, Node.js, and npm are available.${NC}"

echo -e "\n${BLUE}[2/4] Verifying backend & frontend dependencies...${NC}"
cd "$BACKEND_DIR"

if [ ! -f .env ]; then
    echo -e "${YELLOW}Creating backend/.env from backend/.env.example...${NC}"
    cp .env.example .env
    php artisan key:generate
fi

if [ ! -d vendor ]; then
    echo -e "${YELLOW}Installing PHP dependencies...${NC}"
    composer install
fi

if [ ! -d node_modules ]; then
    echo -e "${YELLOW}Installing backend Node dependencies...${NC}"
    npm install
fi

if [ -d "$FRONTEND_DIR" ]; then
    cd "$FRONTEND_DIR"
    if [ ! -d node_modules ]; then
        echo -e "${YELLOW}Installing frontend (React + GSAP + Lenis) dependencies...${NC}"
        npm install
    fi
    if [ ! -f "$BACKEND_DIR/public/landing/.vite/manifest.json" ] || [ "$MODE" = "build" ]; then
        echo -e "${YELLOW}Building React + GSAP + Lenis landing page...${NC}"
        npm run build
    fi
fi

echo -e "\n${BLUE}[3/4] Preparing database...${NC}"
cd "$BACKEND_DIR"
DB_CONNECTION=$(grep -E "^DB_CONNECTION=" .env | head -n 1 | cut -d '=' -f2 | tr -d ' ' || echo "sqlite")

if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    if [ ! -f "$DATABASE_DIR/database.sqlite" ]; then
        echo -e "${YELLOW}Creating database/database.sqlite...${NC}"
        touch "$DATABASE_DIR/database.sqlite"
    fi
fi

if [ "$SEED" = true ]; then
    echo -e "${YELLOW}Running database migrations with seeders...${NC}"
    php artisan migrate --seed --force
else
    echo -e "${YELLOW}Running database migrations...${NC}"
    php artisan migrate --force
fi
echo -e "${GREEN}✓ Database is ready.${NC}"

if [ "$SETUP_ONLY" = true ]; then
    echo -e "\n${GREEN}✓ Setup completed successfully!${NC}"
    exit 0
fi

echo -e "\n${BLUE}[4/4] Starting application...${NC}"
echo -e "${GREEN}Application URL:${NC} http://127.0.0.1:8000"
echo -e "${YELLOW}Demo account:${NC}    demo@example.com / password"
echo -e "${YELLOW}Press Ctrl+C to stop all running services.${NC}\n"

if [ "$MODE" = "build" ]; then
    echo -e "${YELLOW}Building backend assets with Vite...${NC}"
    npm run build

    trap 'echo -e "\n${YELLOW}Stopping services...${NC}"; kill 0 2>/dev/null || true; exit 0' INT TERM EXIT

    php artisan queue:listen --tries=1 --timeout=0 &
    php artisan serve --host=0.0.0.0 --port=8000 &

    wait
else
    php artisan dev
fi
