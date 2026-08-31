#!/usr/bin/env python3
import csv
import html
import os
import re
import sqlite3
import subprocess
import sys
import threading
import time
from concurrent.futures import ThreadPoolExecutor, as_completed
from pathlib import Path
from urllib.parse import urljoin


def extract_image(page_html: str, base_url: str) -> str:
    if "Security Check | MedEx" in page_html or "captcha" in page_html[:3000].lower():
        raise RuntimeError("MedEx security check response")
    patterns = [
        r'<a[^>]+href=["\']([^"\']*storage/images/packaging/[^"\']+)["\']',
        r'<img[^>]+data-src=["\']([^"\']*storage/images/packaging/[^"\']+)["\']',
        r'<img[^>]+class=["\'][^"\']*(?:brand|pack|medicine|product)[^"\']*["\'][^>]+src=["\']([^"\']+)["\']',
        r'<img[^>]+src=["\']([^"\']+)["\'][^>]+class=["\'][^"\']*(?:brand|pack|medicine|product)[^"\']*["\']',
        r'<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']',
        r'<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']',
    ]
    for pattern in patterns:
        match = re.search(pattern, page_html, re.I)
        if match:
            src = html.unescape(match.group(1)).strip()
            if src and "dosage" not in src.lower() and "logo" not in src.lower():
                return urljoin(base_url, src)
    return ""


def main() -> int:
    if len(sys.argv) < 3:
        print("Usage: scrape_medex_images.py medex.db output.csv [limit] [delay_seconds] [workers] [progress_every]", file=sys.stderr)
        return 1

    db_path = Path(sys.argv[1])
    out_path = Path(sys.argv[2])
    limit = int(sys.argv[3]) if len(sys.argv) > 3 else 0
    delay = float(sys.argv[4]) if len(sys.argv) > 4 else 0.25
    workers = int(sys.argv[5]) if len(sys.argv) > 5 else 1
    progress_every = int(sys.argv[6]) if len(sys.argv) > 6 else 1

    done = set()
    if out_path.exists():
        with out_path.open(newline="", encoding="utf-8") as existing:
            for row in csv.DictReader(existing):
                if row.get("source_id"):
                    done.add(int(row["source_id"]))

    conn = sqlite3.connect(db_path)
    conn.row_factory = sqlite3.Row
    rows = conn.execute("SELECT id, url FROM medicines WHERE url IS NOT NULL ORDER BY id").fetchall()
    if limit:
        rows = rows[:limit]

    write_header = not out_path.exists()
    def fetch_one(source_id: int, url: str) -> tuple[int, str]:
        if delay > 0:
            time.sleep(delay)
        try:
            headers = [
                "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7",
                "Accept-Language: en-US,en;q=0.9,nl;q=0.8",
                "Cache-Control: max-age=0",
                "Referer: https://medex.com.bd/captcha-challenge",
                'sec-ch-ua: "Not=A?Brand";v="99", "Google Chrome";v="151", "Chromium";v="151"',
                "sec-ch-ua-mobile: ?0",
                'sec-ch-ua-platform: "macOS"',
                "sec-fetch-dest: document",
                "sec-fetch-mode: navigate",
                "sec-fetch-site: same-origin",
                "sec-fetch-user: ?1",
                "upgrade-insecure-requests: 1",
            ]
            cookie = os.environ.get("MEDEX_COOKIE", "").strip()
            if cookie:
                headers.append(f"Cookie: {cookie}")
            result = subprocess.run(
                [
                    "curl",
                    "-L",
                    "-s",
                    "--max-time",
                    "25",
                    "-A",
                    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36",
                    *sum((["-H", h] for h in headers), []),
                    url,
                ],
                check=False,
                capture_output=True,
            )
            body = result.stdout.decode("utf-8", errors="ignore")
            return source_id, extract_image(body, url)
        except Exception as exc:
            print(f"{source_id}: {exc}", file=sys.stderr)
            return source_id, ""

    pending = [(int(row["id"]), row["url"]) for row in rows if int(row["id"]) not in done]

    with out_path.open("a", newline="", encoding="utf-8") as out:
        writer = csv.DictWriter(out, fieldnames=["source_id", "image_url"])
        if write_header:
            writer.writeheader()

        if workers <= 1:
            iterator = (fetch_one(source_id, url) for source_id, url in pending)
            for index, (source_id, image_url) in enumerate(iterator, 1):
                writer.writerow({"source_id": source_id, "image_url": image_url})
                out.flush()
                if progress_every > 0 and (index % progress_every == 0 or image_url):
                    print(f"{index}/{len(pending)} {source_id} {'image' if image_url else 'no-image'}")
        else:
            completed = 0
            write_lock = threading.Lock()
            with ThreadPoolExecutor(max_workers=workers) as pool:
                futures = [pool.submit(fetch_one, source_id, url) for source_id, url in pending]
                for future in as_completed(futures):
                    source_id, image_url = future.result()
                    with write_lock:
                        writer.writerow({"source_id": source_id, "image_url": image_url})
                        out.flush()
                    completed += 1
                    if progress_every > 0 and (completed % progress_every == 0 or image_url):
                        print(f"{completed}/{len(pending)} {source_id} {'image' if image_url else 'no-image'}")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
