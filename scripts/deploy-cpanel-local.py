#!/usr/bin/env python3
"""Deploy staged app bundle to cPanel via SFTP + chunked PHP zip extract.

Shared hosting on this account has SFTP but no SSH shell and no Fileman
extract UAPI. We upload a single zip, drop a self-deleting extractor into
public_html, then call it repeatedly (chunked) so LiteSpeed request timeouts
do not abort a 15k-file extract.
"""

from __future__ import annotations

import json
import secrets
import ssl
import time
import urllib.error
import urllib.parse
import urllib.request
import zipfile
from pathlib import Path

try:
    import paramiko
except ImportError as exc:  # pragma: no cover
    raise SystemExit("Install paramiko first: python3 -m pip install --user paramiko") from exc

ROOT = Path(__file__).resolve().parents[1]
ENV_FILE = ROOT / ".env.deploy.local"
BUNDLE = ROOT / ".deploy-bundle"
ZIP_NAME = "deploy-bundle.zip"
EXTRACTOR_NAME_PREFIX = "_sm_extract_"
CHUNK_SIZE = 250


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


def build_zip(bundle: Path, zip_path: Path) -> int:
    if zip_path.exists():
        zip_path.unlink()
    count = 0
    with zipfile.ZipFile(zip_path, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=6) as zf:
        for path in bundle.rglob("*"):
            if path.is_file():
                zf.write(path, path.relative_to(bundle).as_posix())
                count += 1
    return count


def sftp_connect(cfg: dict[str, str]) -> tuple[paramiko.SFTPClient, paramiko.Transport]:
    host = cfg["CPANEL_FTP_HOST"]
    user = cfg["CPANEL_FTP_USERNAME"]
    password = cfg["CPANEL_FTP_PASSWORD"]
    port = int(cfg.get("CPANEL_FTP_PORT") or "22")
    print(f"Connecting SFTP {user}@{host}:{port} …", flush=True)
    transport = paramiko.Transport((host, port))
    transport.default_window_size = paramiko.common.MAX_WINDOW_SIZE
    transport.packetizer.REKEY_BYTES = pow(2, 40)
    transport.packetizer.REKEY_PACKETS = pow(2, 40)
    transport.connect(username=user, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    assert sftp is not None
    return sftp, transport


def ensure_remote_dir(sftp: paramiko.SFTPClient, remote_dir: str) -> None:
    parts = [p for p in remote_dir.strip("/").split("/") if p]
    path = ""
    for part in parts:
        path = f"{path}/{part}" if path else part
        try:
            sftp.stat(path)
        except FileNotFoundError:
            sftp.mkdir(path)


def write_extractor(token: str, remote_app: str, zip_remote: str) -> str:
    return f"""<?php
declare(strict_types=1);
@set_time_limit(120);
@ini_set('memory_limit', '512M');
@ini_set('max_execution_time', '120');

header('Content-Type: text/plain; charset=utf-8');

$tokenExpected = {json.dumps(token)};
if (!hash_equals($tokenExpected, (string)($_GET['t'] ?? ''))) {{
    http_response_code(403);
    echo 'forbidden';
    exit;
}}

$target = {json.dumps(remote_app)};
$zipPath = {json.dumps(zip_remote)};
$offset = max(0, (int)($_GET['offset'] ?? 0));
$limit = max(1, min(500, (int)($_GET['limit'] ?? {CHUNK_SIZE})));

if (!is_file($zipPath)) {{
    http_response_code(500);
    echo 'missing zip';
    exit;
}}
if (!class_exists('ZipArchive')) {{
    http_response_code(500);
    echo 'ZipArchive missing';
    exit;
}}
if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {{
    http_response_code(500);
    echo 'cannot create target';
    exit;
}}

$zip = new ZipArchive();
$opened = $zip->open($zipPath);
if ($opened !== true) {{
    http_response_code(500);
    echo 'zip open failed:' . $opened;
    exit;
}}

$total = $zip->numFiles;
$end = min($offset + $limit, $total);
$done = 0;
for ($i = $offset; $i < $end; $i++) {{
    $name = $zip->getNameIndex($i);
    if ($name === false) {{
        continue;
    }}
    // Skip directory-only entries; extractTo creates parents as needed.
    if (substr($name, -1) === '/') {{
        $dir = $target . '/' . rtrim($name, '/');
        if (!is_dir($dir)) {{
            @mkdir($dir, 0755, true);
        }}
        $done++;
        continue;
    }}
    if (!$zip->extractTo($target, $name)) {{
        $zip->close();
        http_response_code(500);
        echo 'extract failed at ' . $i . ' ' . $name;
        exit;
    }}
    $done++;
}}
$zip->close();

$next = $end;
$complete = $next >= $total ? 1 : 0;
if ($complete) {{
    @unlink($zipPath);
    @unlink(__FILE__);
}}

echo 'OK offset=' . $offset . ' next=' . $next . ' total=' . $total . ' done=' . $done . ' complete=' . $complete;
"""


def http_get(url: str, timeout: int = 180) -> tuple[int, str]:
    ctx = ssl.create_default_context()
    req = urllib.request.Request(url, headers={"User-Agent": "school-management-deploy"})
    try:
        with urllib.request.urlopen(req, context=ctx, timeout=timeout) as resp:
            return resp.status, resp.read().decode("utf-8", errors="replace")
    except urllib.error.HTTPError as exc:
        body = exc.read().decode("utf-8", errors="replace")
        return exc.code, body


def run_chunked_extract(app_url: str, extractor_name: str, token: str) -> None:
    offset = 0
    while True:
        qs = urllib.parse.urlencode({"t": token, "offset": offset, "limit": CHUNK_SIZE})
        url = f"{app_url}/{extractor_name}?{qs}"
        status, body = http_get(url)
        print(f"  chunk offset={offset} → HTTP {status}: {body[:200]}", flush=True)
        if status != 200 or not body.startswith("OK"):
            raise SystemExit(
                "Chunked extract failed. Open cPanel File Manager and extract "
                f"{ZIP_NAME} into school-management/ manually, then delete leftover "
                f"public_html/{extractor_name}."
            )
        parts = dict(
            item.split("=", 1) for item in body.split() if "=" in item and item != "OK"
        )
        next_offset = int(parts.get("next", "0"))
        total = int(parts.get("total", "0"))
        complete = parts.get("complete") == "1"
        if complete or next_offset >= total:
            print(f"  extract complete ({total} zip entries)", flush=True)
            return
        if next_offset <= offset:
            raise SystemExit(f"Extract did not advance past offset {offset}")
        offset = next_offset


def main() -> int:
    cfg = load_env(ENV_FILE)
    if not BUNDLE.is_dir():
        raise SystemExit("Missing .deploy-bundle. Run: bash scripts/stage-cpanel-bundle.sh")

    remote_root = (cfg.get("CPANEL_FTP_REMOTE_DIR") or "school-management/").strip("/")
    app_url = (cfg.get("APP_URL") or cfg.get("VITE_API_URL") or "").rstrip("/")
    if not app_url:
        raise SystemExit("Set APP_URL or VITE_API_URL in .env.deploy.local for extract trigger")

    zip_local = ROOT / ".deploy-bundle.zip"
    reuse_zip = zip_local.is_file() and zip_local.stat().st_size > 1_000_000
    if reuse_zip:
        print(f"Reusing existing {zip_local.name} ({zip_local.stat().st_size / (1024 * 1024):.1f} MiB)", flush=True)
    else:
        print(f"Building {zip_local.name} from {BUNDLE} …", flush=True)
        file_count = build_zip(BUNDLE, zip_local)
        print(f"  {file_count} files → {zip_local.stat().st_size / (1024 * 1024):.1f} MiB", flush=True)

    token = secrets.token_urlsafe(24)
    extractor_name = f"{EXTRACTOR_NAME_PREFIX}{secrets.token_hex(4)}.php"
    cpanel_user = cfg["CPANEL_FTP_USERNAME"]
    remote_app_abs = f"/home/{cpanel_user}/{remote_root}"
    zip_remote_rel = f"{remote_root}/{ZIP_NAME}"
    zip_remote_abs = f"/home/{cpanel_user}/{zip_remote_rel}"
    extractor_body = write_extractor(token, remote_app_abs, zip_remote_abs)

    sftp, transport = sftp_connect(cfg)
    started = time.time()
    try:
        ensure_remote_dir(sftp, remote_root)
        # Clean previous leftover extractors
        try:
            for name in sftp.listdir("public_html"):
                if name.startswith(EXTRACTOR_NAME_PREFIX) and name.endswith(".php"):
                    try:
                        sftp.remove(f"public_html/{name}")
                        print(f"  removed leftover public_html/{name}")
                    except OSError:
                        pass
        except FileNotFoundError:
            pass

        need_upload = True
        try:
            remote_stat = sftp.stat(zip_remote_rel)
            if remote_stat.st_size == zip_local.stat().st_size:
                print(f"Remote zip already present ({remote_stat.st_size} bytes) — skipping upload", flush=True)
                need_upload = False
        except FileNotFoundError:
            pass

        if need_upload:
            print(f"Uploading zip → {zip_remote_rel}", flush=True)
            sftp.put(str(zip_local), zip_remote_rel)
            print(f"  zip uploaded in {time.time() - started:.1f}s", flush=True)

        print(f"Uploading extractor → public_html/{extractor_name}", flush=True)
        ensure_remote_dir(sftp, "public_html")
        with sftp.open(f"public_html/{extractor_name}", "w") as remote:
            remote.write(extractor_body.encode("utf-8"))
    finally:
        sftp.close()
        transport.close()

    print(f"Triggering chunked extract via {app_url}/{extractor_name}", flush=True)
    run_chunked_extract(app_url, extractor_name, token)

    sftp, transport = sftp_connect(cfg)
    try:
        for rel in ("artisan", "public/index.php", "vendor/autoload.php", "public/app/index.html"):
            path = f"{remote_root}/{rel}"
            try:
                st = sftp.stat(path)
                print(f"  OK {path} ({st.st_size} bytes)")
            except FileNotFoundError:
                raise SystemExit(f"Missing after extract: {path}") from None

        for leftover in (f"public_html/{extractor_name}", zip_remote_rel):
            try:
                sftp.remove(leftover)
                print(f"  cleaned {leftover}")
            except FileNotFoundError:
                pass
    finally:
        sftp.close()
        transport.close()

    print(f"Deploy finished in {time.time() - started:.1f}s")
    print("Next on server (cPanel Terminal — enable shell if needed):")
    print(f"  cd ~/{remote_root}")
    print("  # create .env if missing, then:")
    print("  php artisan migrate --force && php artisan storage:link && php artisan config:cache")
    print("Point a subdomain document root at school-management/public (leave public_html alone).")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
