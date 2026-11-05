# elabdev container

This container is for development purposes. It builds upon the prod container elabimg.

## Claude Code

Claude Code is installed in this image. It runs inside the `elabftw` container, so it can run `php`, `composer`, `yarn` and `bin/console` directly, without access to the host or to the Docker socket.

1. Put your API key in `containers/elabdev/env.local` (ignored by git):

   ```
   ANTHROPIC_API_KEY=sk-ant-...
   ```

2. Recreate the container so it gets the key and the new image: `./containers/elabdev/start.sh` (after a `docker compose build` if the image is older than this change).
3. From the project's root: `./containers/elabdev/claude.sh` (arguments are passed to `claude`).

Its configuration and history are kept in `~/.cache/elabdev/claude` on the host.

### Outbound allowlist

`claude.sh` first starts the `egress-firewall` service. It shares the network namespace of the `elabftw` container and sets iptables rules so that only the stack network (mysql, opencloning…) and the entries of `egress-firewall/allowed-domains.txt` can be reached. Domains are resolved again every 5 minutes, and edits to the file are picked up then. `claude.sh` refuses to start Claude if a non-allowed address is reachable.

The restrictions apply to the whole `elabftw` container, not only to Claude. They stay in place after the firewall stops (fail closed). To remove them, stop the firewall and recreate the web container:

```bash
docker stop elabftw-egress-firewall
```
```bash
WORKER_UID=$(id -u) WORKER_GID=$(id -g) docker compose -f containers/elabdev/docker-compose.yml up -d --force-recreate web
```

Limits: the allowlist is IP based, so other sites behind the same CDN addresses are reachable, and DNS queries still go out through Docker's resolver.
