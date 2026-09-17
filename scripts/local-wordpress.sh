#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE_FILE="$PROJECT_ROOT/compose.yaml"
LOCAL_PORT="${DAKABRAND_LOCAL_PORT:-8080}"
LOCAL_URL="${DAKABRAND_LOCAL_URL:-http://localhost:${LOCAL_PORT}}"
ADMIN_USER="${DAKABRAND_ADMIN_USER:-admin}"
ADMIN_PASSWORD="${DAKABRAND_ADMIN_PASSWORD:-admin}"
ADMIN_EMAIL="${DAKABRAND_ADMIN_EMAIL:-admin@example.test}"

compose() {
    docker compose --project-directory "$PROJECT_ROOT" -f "$COMPOSE_FILE" "$@"
}

wp() {
    compose run --rm --no-deps wpcli "$@"
}

wait_for_core() {
    local attempts=0

    until wp core version >/dev/null 2>&1; do
        attempts=$((attempts + 1))
        if (( attempts >= 30 )); then
            echo "WordPress files were not ready after 60 seconds." >&2
            compose logs --tail=50 wordpress >&2
            return 1
        fi
        sleep 2
    done
}

start() {
    compose up -d db wordpress
    wait_for_core
}

setup() {
    start

    if ! wp core is-installed >/dev/null 2>&1; then
        wp core install \
            --url="$LOCAL_URL" \
            --title="DakaBrand Local" \
            --admin_user="$ADMIN_USER" \
            --admin_password="$ADMIN_PASSWORD" \
            --admin_email="$ADMIN_EMAIL" \
            --skip-email
    fi

    wp option update home "$LOCAL_URL"
    wp option update siteurl "$LOCAL_URL"
    wp rewrite structure '/%postname%/' --hard
    wp plugin install woocommerce --activate
    wp eval 'WC_Install::create_pages();'
    wp theme activate dakabrand
    wp eval-file /opt/dakabrand-local/seed-catalog.php
    wp cache flush
    # WP-CLI bootstrap can log HTTP-context notices that do not occur in web
    # requests. Start the developer-facing log clean after setup succeeds.
    compose exec -T wordpress sh -c ': > /var/www/html/wp-content/debug.log'

    echo
    echo "DakaBrand local is ready: $LOCAL_URL"
    echo "Admin: $LOCAL_URL/wp-admin/"
    echo "Login: $ADMIN_USER / $ADMIN_PASSWORD"
}

smoke_test() {
    start

    local failed=0
    local path status
    for path in / /man/ /woman/ /shop/ /cart/ /checkout/; do
        status="$(curl --location --silent --output /dev/null --write-out '%{http_code}' "$LOCAL_URL$path")"
        if [[ "$status" == "200" ]]; then
            printf 'PASS  %s  %s\n' "$status" "$path"
        else
            printf 'FAIL  %s  %s\n' "$status" "$path"
            failed=1
        fi
    done
    status="$(curl --location --silent --output /dev/null --write-out '%{http_code}' "$LOCAL_URL/my-account/")"
    if [[ "$status" == "404" ]]; then
        printf 'PASS  %s  %s\n' "$status" /my-account/
    else
        printf 'FAIL  %s  %s\n' "$status" /my-account/
        failed=1
    fi

    wp core verify-checksums
    wp plugin status woocommerce
    wp theme status dakabrand
    return "$failed"
}

usage() {
    cat <<'EOF'
Usage: scripts/local-wordpress.sh COMMAND

Commands:
  setup    Start WordPress, install WooCommerce, activate the theme, and seed demo products
  start    Start the existing local environment
  stop     Stop containers while preserving WordPress and database data
  status   Show container status
  logs     Follow WordPress logs
  test     Smoke-test the important storefront routes and installation
  wp ...   Run a WP-CLI command in the local WordPress installation
  reset    Delete the local WordPress/database volumes, then recreate everything
EOF
}

command="${1:-}"
case "$command" in
    setup)
        setup
        ;;
    start)
        start
        echo "DakaBrand local is running at $LOCAL_URL"
        ;;
    stop)
        compose stop
        ;;
    status)
        compose ps
        ;;
    logs)
        compose logs --follow wordpress
        ;;
    test)
        smoke_test
        ;;
    wp)
        shift
        wp "$@"
        ;;
    reset)
        if [[ "${DAKABRAND_CONFIRM_RESET:-}" != "yes" ]]; then
            echo "This deletes only the local Docker database and WordPress volumes." >&2
            echo "Run DAKABRAND_CONFIRM_RESET=yes scripts/local-wordpress.sh reset to continue." >&2
            exit 1
        fi
        compose down --volumes
        setup
        ;;
    *)
        usage
        exit 1
        ;;
esac
