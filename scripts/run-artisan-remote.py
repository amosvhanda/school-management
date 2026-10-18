#!/usr/bin/env python3
"""Run one-off artisan commands on the cPanel host with no SSH shell.

Mirrors deploy-cpanel-local.py's trick: upload a token-gated PHP script to
public_html (the only place we've confirmed is web-reachable at APP_URL),
trigger it over HTTPS, read the artisan output, then delete the script.
Stops (without self-deleting) on the first command that fails, so you can
inspect public_html/<name>.php and re-run safely.

Usage:
  python3 scripts/run-artisan-remote.py migrate storage-link config-cache route-cache view-cache
"""

from __future__ import annotations

import secrets
import ssl
import sys
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ENV_FILE = ROOT / ".env.deploy.local"
RUNNER_PREFIX = "_sm_artisan_"

ALLOWED = {
    "migrate": ("migrate", {"--force": True}),
    "storage-link": ("storage:link", {}),
    "config-cache": ("config:cache", {}),
    "route-cache": ("route:cache", {}),
    "view-cache": ("view:cache", {}),
}


def load_env(path: Path) -> dict[str, str]:
    if not path.is_file():
        raise SystemExit(f"Missing {path}")
    values: dict[str, str] = {}
    for line in path.read_text().splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, _, val = line.partition("=")
        values[key.strip()] = val.strip().strip('"').strip("'")
    return values


def sftp_connect(cfg: dict[str, str]):
    try:
        import paramiko
    except ImportError as exc:  # pragma: no cover
        raise SystemExit("Install paramiko first: python3 -m pip install --user paramiko") from exc
    host = cfg["CPANEL_FTP_HOST"]
    user = cfg["CPANEL_FTP_USERNAME"]
    password = cfg["CPANEL_FTP_PASSWORD"]
    port = int(cfg.get("CPANEL_FTP_PORT") or "22")
    transport = paramiko.Transport((host, port))
    transport.connect(username=user, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    assert sftp is not None
    return sftp, transport


def write_runner(token: str, app_root: str) -> str:
    commands_php = ",\n    ".join(
        f"{json_str(k)} => [{json_str(cmd)}, {php_array(params)}]"
        for k, (cmd, params) in ALLOWED.items()
    )
    return f"""<?php
declare(strict_types=1);
@set_time_limit(120);
@ini_set('memory_limit', '512M');
header('Content-Type: text/plain; charset=utf-8');

$tokenExpected = {json_str(token)};
if (!hash_equals($tokenExpected, (string)($_GET['t'] ?? ''))) {{
    http_response_code(403);
    echo 'forbidden';
    exit;
}}

$appRoot = {json_str(app_root)};
$command = (string)($_GET['cmd'] ?? '');
$map = [
    {commands_php}
];
if (!isset($map[$command])) {{
    http_response_code(400);
    echo 'bad command';
    exit;
}}

chdir($appRoot);
require $appRoot . '/vendor/autoload.php';
$app = require $appRoot . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\\Contracts\\Console\\Kernel::class);

[$artisanCmd, $params] = $map[$command];
$exit = $kernel->call($artisanCmd, $params);
echo 'exit=' . $exit . "\\n";
echo $kernel->output();

if ((int)($_GET['selfdestruct'] ?? 0) === 1) {{
    @unlink(__FILE__);
}}
"""


def json_str(value: str) -> str:
    import json

    return json.dumps(value)


def php_array(params: dict) -> str:
    if not params:
        return "[]"
    parts = []
    for key, value in params.items():
        if isinstance(value, bool):
            parts.append(f"{json_str(key)} => {'true' if value else 'false'}")
        else:
            parts.append(f"{json_str(key)} => {json_str(str(value))}")
    return "[" + ", ".join(parts) + "]"


def http_get(url: str, timeout: int = 150) -> tuple[int, str]:
    ctx = ssl.create_default_context()
    req = urllib.request.Request(url, headers={"User-Agent": "school-management-deploy"})
    try:
        with urllib.request.urlopen(req, context=ctx, timeout=timeout) as resp:
            return resp.status, resp.read().decode("utf-8", errors="replace")
    except urllib.error.HTTPError as exc:
        return exc.code, exc.read().decode("utf-8", errors="replace")


def main(argv: list[str]) -> int:
    commands = argv or ["migrate", "storage-link", "config-cache", "route-cache", "view-cache"]
    for cmd in commands:
        if cmd not in ALLOWED:
            raise SystemExit(f"Unknown command {cmd!r}. Allowed: {', '.join(ALLOWED)}")

    cfg = load_env(ENV_FILE)
    remote_root = (cfg.get("CPANEL_FTP_REMOTE_DIR") or "school-management/").strip("/")
    app_url = (cfg.get("APP_URL") or cfg.get("VITE_API_URL") or "").rstrip("/")
    if not app_url:
        raise SystemExit("Set APP_URL or VITE_API_URL in .env.deploy.local")

    cpanel_user = cfg["CPANEL_FTP_USERNAME"]
    app_root_abs = f"/home/{cpanel_user}/{remote_root}"
    token = secrets.token_urlsafe(24)
    runner_name = f"{RUNNER_PREFIX}{secrets.token_hex(4)}.php"
    runner_body = write_runner(token, app_root_abs)

    sftp, transport = sftp_connect(cfg)
    try:
        try:
            for name in sftp.listdir("public_html"):
                if name.startswith(RUNNER_PREFIX) and name.endswith(".php"):
                    try:
                        sftp.remove(f"public_html/{name}")
                    except OSError:
                        pass
        except FileNotFoundError:
            pass
        with sftp.open(f"public_html/{runner_name}", "w") as remote:
            remote.write(runner_body.encode("utf-8"))
    finally:
        sftp.close()
        transport.close()

    print(f"Runner staged at public_html/{runner_name}")
    ok = True
    for i, cmd in enumerate(commands):
        last = i == len(commands) - 1
        qs = urllib.parse.urlencode({"t": token, "cmd": cmd, "selfdestruct": 1 if last else 0})
        url = f"{app_url}/{runner_name}?{qs}"
        print(f"==> {cmd}", flush=True)
        status, body = http_get(url)
        print(body.strip())
        if status != 200 or "exit=0" not in body:
            print(f"!! {cmd} failed (HTTP {status}). Leaving runner in place for inspection.")
            ok = False
            break

    if not ok:
        return 1

    print("All commands completed; runner self-deleted.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv[1:]))
