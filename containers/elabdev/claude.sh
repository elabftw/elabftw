#!/usr/bin/env bash
# start Claude Code inside the elabftw container, with outbound traffic restricted to an allowlist
# execute this from project's root, arguments are passed to claude
set -eu

WORKER_UID=$(id -u) WORKER_GID=$(id -g) docker compose -f containers/elabdev/docker-compose.yml --profile claude up -d --wait --wait-timeout 64 egress-firewall

# never start Claude without the restrictions: 1.1.1.1 is not in the allowlist, so it must be unreachable
if ! docker exec elabftw php -r 'exit(@fsockopen("1.1.1.1", 443, $errno, $errstr, 3) ? 1 : 0);'; then
    echo "Outbound traffic is not restricted, refusing to start Claude Code." >&2
    echo "Check: docker logs elabftw-egress-firewall" >&2
    exit 1
fi

docker exec -it -w /elabftw elabftw claude "$@"
