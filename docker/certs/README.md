# Local HTTPS certificates (host → Docker)

Place **two PEM files** here (names must match `docker/caddy/Caddyfile`):

| File       | Role        |
|-----------|-------------|
| `cert.pem` | certificate |
| `key.pem`  | private key |

## Example with mkcert (on your machine, not inside the container)

```bash
mkcert -install
mkcert aidesk-mini.loc localhost 127.0.0.1 ::1
# Then copy/rename to cert.pem and key.pem in this folder, or symlink.
```

Files in this directory are **gitignored** (except this README).

## Enable the proxy

```bash
docker compose --profile https up -d
```

Browse: `https://aidesk-mini.loc` (add `127.0.0.1 aidesk-mini.loc` to `/etc/hosts` if needed).

Set `APP_URL` and `SAIL_HTTPS_DOMAIN` in `.env` to match.
