#!/usr/bin/env python3
"""
MedEx Image Crawler - Cloudflare Turnstile Auto-Bypass
=====================================================
Chrome profile use kore captcha verify thake, tarpor auto-calcolates.

How it works:
1. First run: Browser open hobe, captcha solve korben manually (25-60s wait korle auto-verify o hote pare)
2. Tarpor: Profile save hoy, ar captcha dekhabe na
3. Future runs: Full automatic

Usage:
    python3 medex_crawler_py.py                    # First run (manual solve once)
    python3 medex_crawler_py.py --2captcha KEY     # No manual solve needed
    python3 medex_crawler_py.py --capsolver KEY    # No manual solve needed
    python3 medex_crawler_py.py --test             # Test with 2 items
"""

import argparse
import csv
import json
import os
import random
import re
import sys
import time
from urllib.parse import urljoin

from DrissionPage import ChromiumPage, ChromiumOptions

MIN_DELAY = 3.0
MAX_DELAY = 6.0
PAGE_LOAD_TIMEOUT = 60
CAPTCHA_WAIT_TIMEOUT = 90
MAX_RETRIES = 3
AUTOSAVE_EVERY = 50
CHROME_PATH = "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"


def get_script_dir():
    return os.path.dirname(os.path.abspath(__file__))


class MedexCrawler:
    def __init__(self, items, api_key_2captcha=None, capsolver_key=None,
                 min_delay=MIN_DELAY, max_delay=MAX_DELAY, output_csv=None):
        self.items = items
        self.api_key_2captcha = api_key_2captcha
        self.capsolver_key = capsolver_key
        self.min_delay = min_delay
        self.max_delay = max_delay
        self.output_csv = output_csv or os.path.join(get_script_dir(), "medex_images.csv")
        self.progress_file = os.path.join(get_script_dir(), "medex_progress.json")
        self.profile_dir = os.path.join(get_script_dir(), "chrome_profile")
        self.output = []
        self.scraped_ids = set()
        self.failed_ids = set()
        self.page = None
        self._load_progress()

    def _init_browser(self):
        """Start Chrome with persistent profile so captcha verification is remembered."""
        os.makedirs(self.profile_dir, exist_ok=True)
        co = ChromiumOptions()
        co.set_browser_path(CHROME_PATH)
        co.set_argument("--disable-blink-features=AutomationControlled")
        co.set_argument("--no-first-run")
        co.set_argument("--no-default-browser-check")
        co.set_argument("--disable-gpu")
        co.set_argument("--window-size=1280,800")
        co.set_argument(f"--user-data-dir={self.profile_dir}")
        co.auto_port()
        self.page = ChromiumPage(addr_or_opts=co)
        print(f"  [Browser] Profile: {self.profile_dir}")

    def _load_progress(self):
        if os.path.exists(self.progress_file):
            try:
                with open(self.progress_file, "r") as f:
                    data = json.load(f)
                self.scraped_ids = set(data.get("scraped_ids", []))
                self.failed_ids = set(data.get("failed_ids", []))
                print(f"  [Resume] {len(self.scraped_ids)} scraped, {len(self.failed_ids)} failed")
            except (json.JSONDecodeError, IOError):
                pass

    def _save_progress(self):
        data = {"scraped_ids": list(self.scraped_ids), "failed_ids": list(self.failed_ids)}
        with open(self.progress_file, "w") as f:
            json.dump(data, f)

    def _random_delay(self):
        time.sleep(random.uniform(self.min_delay, self.max_delay))

    def _is_security_page(self, html):
        lower = html[:5000].lower()
        return any(x in lower for x in [
            "security check | medex", "cf-challenge", "challenge-platform",
            "verify you are human", "captcha-verify", "cf-turnstile",
        ])

    def _solve_turnstile_2captcha(self):
        """Solve Cloudflare Turnstile via 2Captcha API."""
        import requests as req

        sitekey_match = re.search(r'data-sitekey="([^"]+)"', self.page.html)
        if not sitekey_match:
            print("  [2Captcha] No sitekey found")
            return False

        sitekey = sitekey_match.group(1)
        page_url = self.page.url or "https://medex.com.bd/captcha-challenge"
        print(f"  [2Captcha] Solving Turnstile...")

        # Submit task
        resp = req.post("http://2captcha.com/in.php", data={
            "key": self.api_key_2captcha,
            "method": "turnstile",
            "sitekey": sitekey,
            "pageurl": page_url,
        }, timeout=30)
        text = resp.text.strip()
        if not text.startswith("OK|"):
            print(f"  [2Captcha] Submit failed: {text}")
            return False

        task_id = text.split("|")[1]
        print(f"  [2Captcha] Waiting for solution...")

        # Poll result
        for _ in range(30):
            time.sleep(5)
            res = req.get(f"http://2captcha.com/res.php?key={self.api_key_2captcha}&action=get&id={task_id}", timeout=30)
            res_text = res.text.strip()
            if res_text.startswith("OK|"):
                token = res_text.split("|")[1]
                print(f"  [2Captcha] Token received, injecting...")
                self.page.run_js(f"""
                    (function() {{
                        let el = document.querySelector('[name="cf-turnstile-response"]');
                        if (el) el.value = '{token}';
                        let form = document.querySelector('#captchaForm');
                        if (form) form.submit();
                        return 'done';
                    }})();
                """)
                time.sleep(3)
                return True
            if res_text != "CAPCHA_NOT_READY":
                print(f"  [2Captcha] Error: {res_text}")
                return False
        return False

    def _solve_turnstile_capsolver(self):
        """Solve Cloudflare Turnstile via Capsolver API."""
        import requests as req

        sitekey_match = re.search(r'data-sitekey="([^"]+)"', self.page.html)
        if not sitekey_match:
            return False

        sitekey = sitekey_match.group(1)
        page_url = self.page.url or "https://medex.com.bd/captcha-challenge"
        print(f"  [Capsolver] Solving Turnstile...")

        resp = req.post("https://api.capsolver.com/createTask", json={
            "clientKey": self.capsolver_key,
            "task": {"type": "TurnstileTaskProxyless", "websiteURL": page_url, "websiteKey": sitekey},
        }, timeout=30)
        data = resp.json()
        if data.get("errorId") != 0:
            print(f"  [Capsolver] Error: {data.get('errorDescription')}")
            return False

        task_id = data["taskId"]
        for _ in range(30):
            time.sleep(3)
            res = req.post("https://api.capsolver.com/getTaskResult",
                           json={"clientKey": self.capsolver_key, "taskId": task_id}, timeout=30)
            res_data = res.json()
            if res_data.get("status") == "ready":
                token = res_data["solution"]["token"]
                print(f"  [Capsolver] Token received, injecting...")
                self.page.run_js(f"""
                    (function() {{
                        let el = document.querySelector('[name="cf-turnstile-response"]');
                        if (el) el.value = '{token}';
                        let form = document.querySelector('#captchaForm');
                        if (form) form.submit();
                        return 'done';
                    }})();
                """)
                time.sleep(3)
                return True
        return False

    def _wait_for_captcha_solve(self):
        """Wait for captcha to be solved, then auto-click Continue."""
        print(f"  [Captcha] Waiting up to {CAPTCHA_WAIT_TIMEOUT}s for verification...")
        print("  [Captcha] Tip: Wait 25-60s for auto-verify, or click the checkbox manually.")

        started = time.time()
        last_log = 0
        while time.time() - started < CAPTCHA_WAIT_TIMEOUT:
            time.sleep(2)

            # Check if already on target page (auto-redirect happened)
            if not self._is_security_page(self.page.html):
                print("  [Captcha] Verified and page loaded!")
                return True

            # Check if captcha is verified but Continue button still needs clicking
            result = self.page.run_js("""
                (function() {
                    // Check if turnstile response is filled (verified)
                    let turnstileResp = document.querySelector('[name="cf-turnstile-response"]');
                    let isVerified = turnstileResp && turnstileResp.value && turnstileResp.value.length > 20;
                    
                    // Check for success indicator
                    let successEl = document.querySelector('.cf-turnstile-bracket');
                    let bracketHidden = successEl && successEl.style.display === 'none';
                    
                    if (isVerified || bracketHidden) {
                        // Try to click the Continue button
                        let form = document.querySelector('#captchaForm');
                        if (form) {
                            form.submit();
                            return 'form_submitted';
                        }
                        let btn = document.querySelector('button[type="submit"]');
                        if (btn) {
                            btn.click();
                            return 'button_clicked';
                        }
                        let continueBtn = document.querySelector('.captcha-button');
                        if (continueBtn) {
                            continueBtn.click();
                            return 'continue_clicked';
                        }
                        return 'verified_no_button';
                    }
                    return 'waiting';
                })();
            """)

            if result in ('form_submitted', 'button_clicked', 'continue_clicked'):
                print(f"  [Captcha] Auto-clicked Continue ({result})")
                time.sleep(3)
                return True

            if result == 'verified_no_button':
                print("  [Captcha] Verified! Waiting for redirect...")
                time.sleep(5)
                continue

            elapsed = int(time.time() - started)
            if elapsed - last_log >= 10:
                remaining = int(CAPTCHA_WAIT_TIMEOUT - (time.time() - started))
                print(f"  [Captcha] Still waiting... ({remaining}s left)")
                last_log = elapsed

        print("  [Captcha] Timeout!")
        return False

    def _handle_captcha(self):
        """Multi-strategy captcha handling."""
        if not self._is_security_page(self.page.html):
            return True

        print("\n  === SECURITY CHECK DETECTED ===")

        # Strategy 1: Capsolver API
        if self.capsolver_key:
            print("  [Mode] Capsolver auto-solve")
            return self._solve_turnstile_capsolver()

        # Strategy 2: 2Captcha API
        if self.api_key_2captcha:
            print("  [Mode] 2Captcha auto-solve")
            return self._solve_turnstile_2captcha()

        # Strategy 3: Manual solve (auto-verify or click)
        print("  [Mode] Manual solve - browser window is open")
        return self._wait_for_captcha_solve()

    def _fetch_page(self, url, item_id):
        for attempt in range(MAX_RETRIES):
            try:
                self.page.get(url)
                time.sleep(3)

                if self._is_security_page(self.page.html):
                    if not self._handle_captcha():
                        if attempt < MAX_RETRIES - 1:
                            continue
                        raise Exception("Captcha not solved")

                # Wait for target page
                started = time.time()
                while time.time() - started < PAGE_LOAD_TIMEOUT:
                    html = self.page.html
                    page_url = self.page.url
                    if not self._is_security_page(html) and f"/brands/{item_id}/" in page_url:
                        return html
                    time.sleep(2)

                if not self._is_security_page(self.page.html):
                    return self.page.html
                raise Exception("Page load timeout")

            except Exception as e:
                if "Captcha not solved" in str(e):
                    raise
                print(f"  Fetch error (attempt {attempt + 1}): {e}")
                if attempt < MAX_RETRIES - 1:
                    time.sleep(3)
        raise Exception(f"Failed after {MAX_RETRIES} attempts")

    def _extract_image_url(self, html, base_url):
        if self._is_security_page(html):
            return ""
        for pattern in [
            r'<a[^>]+href=["\']([^"\']*storage/images/packaging/[^"\']+)["\']',
            r'<img[^>]+data-src=["\']([^"\']*storage/images/packaging/[^"\']+)["\']',
            r'<img[^>]+src=["\']([^"\']*storage/images/packaging/[^"\']+)["\']',
        ]:
            match = re.search(pattern, html, re.IGNORECASE)
            if match:
                return urljoin(base_url, match.group(1))
        return ""

    def _download_csv(self):
        with open(self.output_csv, "w", newline="", encoding="utf-8") as f:
            writer = csv.writer(f)
            writer.writerow(["source_id", "image_url"])
            writer.writerows(self.output)
        print(f"  CSV saved: {self.output_csv} ({len(self.output)} rows)")

    def run(self):
        total = len(self.items)
        mode = "Manual solve (first time only)" if not (self.api_key_2captcha or self.capsolver_key) else "Auto-solve API"
        print(f"\n{'='*50}")
        print(f"MedEx Image Crawler")
        print(f"Total items: {total}")
        print(f"Mode: {mode}")
        print(f"Already scraped: {len(self.scraped_ids)}")
        print(f"{'='*50}\n")

        self._init_browser()

        try:
            for index, item in enumerate(self.items):
                item_id = item["id"]
                url = item["url"]

                if item_id in self.scraped_ids:
                    continue

                try:
                    html = self._fetch_page(url, item_id)
                    image_url = self._extract_image_url(html, url)
                    self.output.append([item_id, image_url])
                    self.scraped_ids.add(item_id)
                    self.failed_ids.discard(item_id)

                    status = image_url if image_url else "no image"
                    print(f"  [{index + 1}/{total}] ID {item_id}: {status}")

                    if len(self.output) % 500 == 0:
                        self._download_csv()

                except Exception as e:
                    print(f"  [{index + 1}/{total}] ID {item_id}: FAILED - {e}")
                    self.failed_ids.add(item_id)
                    self.output.append([item_id, ""])
                    self.scraped_ids.add(item_id)

                if (index + 1) % AUTOSAVE_EVERY == 0:
                    self._save_progress()
                    self._download_csv()

                self._random_delay()

        finally:
            self._save_progress()
            self._download_csv()
            if self.page:
                try:
                    self.page.quit()
                except Exception:
                    pass

        print(f"\n{'='*50}")
        print(f"Done! Scraped: {len(self.output)}, Failed: {len(self.failed_ids)}")
        print(f"{'='*50}\n")


def load_items_from_json(filepath):
    with open(filepath, "r") as f:
        return json.load(f)


def load_items_from_js(filepath):
    with open(filepath, "r") as f:
        content = f.read()
    match = re.search(r"const\s+items\s*=\s*(\[.*?\]);", content, re.DOTALL)
    if match:
        return json.loads(match.group(1))
    raise ValueError("Could not find items array in JS file.")


def generate_sample_items():
    base = "https://medex.com.bd/brands"
    return [
        {"id": 1, "url": f"{base}/1/celofen-100-mg-tablet"},
        {"id": 2, "url": f"{base}/2/flexi-100-mg-tablet"},
    ]


def main():
    parser = argparse.ArgumentParser(description="MedEx Image Crawler")
    parser.add_argument("--input", "-i", help="JSON file with items")
    parser.add_argument("--from-js", help="Extract items from JS script")
    parser.add_argument("--output", "-o", help="Output CSV path")
    parser.add_argument("--2captcha", dest="captcha_apikey", help="2Captcha API key")
    parser.add_argument("--capsolver", dest="capsolver_key", help="Capsolver API key")
    parser.add_argument("--min-delay", type=float, default=MIN_DELAY)
    parser.add_argument("--max-delay", type=float, default=MAX_DELAY)
    parser.add_argument("--test", action="store_true", help="Test with 2 items")
    args = parser.parse_args()

    if args.test:
        items = generate_sample_items()
    elif args.input:
        items = load_items_from_json(args.input)
    elif args.from_js:
        items = load_items_from_js(args.from_js)
    else:
        json_path = os.path.join(get_script_dir(), "medex_items.json")
        js_path = os.path.join(get_script_dir(), "medex_image_browser_crawler.generated.js")
        if os.path.exists(json_path):
            items = load_items_from_json(json_path)
        elif os.path.exists(js_path):
            items = load_items_from_js(js_path)
        else:
            print("No input found. Use --input, --from-js, or --test")
            sys.exit(1)

    output_csv = args.output or os.path.join(get_script_dir(), "medex_images.csv")

    crawler = MedexCrawler(
        items=items,
        api_key_2captcha=args.captcha_apikey,
        capsolver_key=args.capsolver_key,
        min_delay=args.min_delay,
        max_delay=args.max_delay,
        output_csv=output_csv,
    )
    crawler.run()


if __name__ == "__main__":
    main()
