#!/usr/bin/env python3
"""
MedEx Image Browser Crawler - Python Version
Auto bypasses Cloudflare protection and scrapes medicine images.

Features:
- Cloudflare IUAM & Captcha challenge bypass via cloudscraper
- Automatic retry with exponential backoff
- Resume capability (saves progress to CSV)
- Configurable delays to avoid detection
- Progress autosave

Usage:
    python3 medex_crawler.py

For 2Captcha integration (if cloudscraper alone fails):
    python3 medex_crawler.py --2captcha-apikey YOUR_API_KEY
"""

import argparse
import csv
import json
import os
import random
import re
import sys
import time
from pathlib import Path
from urllib.parse import urljoin, urlparse

import cloudscraper
from bs4 import BeautifulSoup

# Configuration
DEFAULT_INPUT_JSON = None
PROGRESS_FILE = None
OUTPUT_CSV = None


def _set_default_paths():
    """Set default file paths based on script location."""
    global DEFAULT_INPUT_JSON, PROGRESS_FILE, OUTPUT_CSV
    base = os.path.dirname(os.path.abspath(__file__))
    DEFAULT_INPUT_JSON = os.path.join(base, "medex_items.json")
    PROGRESS_FILE = os.path.join(base, "medex_progress.json")
    OUTPUT_CSV = os.path.join(base, "medex_images.csv")

MIN_DELAY = 2.5
MAX_DELAY = 6.5
PAGE_LOAD_TIMEOUT = 45
MAX_RETRIES = 3
AUTOSAVE_EVERY = 50
USER_AGENTS = [
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
]


class MedexCrawler:
    def __init__(self, items, api_key_2captcha=None, min_delay=MIN_DELAY, max_delay=MAX_DELAY):
        self.items = items
        self.api_key_2captcha = api_key_2captcha
        self.min_delay = min_delay
        self.max_delay = max_delay
        self.scraper = self._create_scraper()
        self.output = []
        self.scraped_ids = set()
        self.failed_ids = set()
        self._load_progress()

    def _create_scraper(self):
        """Create a cloudscraper instance with Cloudflare bypass."""
        scraper = cloudscraper.create_scraper(
            browser={
                "browser": "chrome",
                "platform": "darwin",
                "desktop": True,
            },
            delay=10,
        )
        scraper.headers.update({
            "User-Agent": random.choice(USER_AGENTS),
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
            "Accept-Language": "en-US,en;q=0.9,bn;q=0.8",
            "Accept-Encoding": "gzip, deflate, br",
            "Connection": "keep-alive",
            "Upgrade-Insecure-Requests": "1",
            "Sec-Fetch-Dest": "document",
            "Sec-Fetch-Mode": "navigate",
            "Sec-Fetch-Site": "none",
            "Sec-Fetch-User": "?1",
            "Cache-Control": "max-age=0",
        })
        return scraper

    def _load_progress(self):
        """Load previously scraped progress."""
        if os.path.exists(PROGRESS_FILE):
            try:
                with open(PROGRESS_FILE, "r") as f:
                    data = json.load(f)
                self.scraped_ids = set(data.get("scraped_ids", []))
                self.failed_ids = set(data.get("failed_ids", []))
                print(f"Resumed: {len(self.scraped_ids)} already scraped, {len(self.failed_ids)} failed.")
            except (json.JSONDecodeError, IOError):
                pass

    def _save_progress(self):
        """Save current progress to file."""
        data = {
            "scraped_ids": list(self.scraped_ids),
            "failed_ids": list(self.failed_ids),
        }
        with open(PROGRESS_FILE, "w") as f:
            json.dump(data, f)

    def _random_delay(self):
        """Sleep for a random duration between min and max delay."""
        delay = random.uniform(self.min_delay, self.max_delay)
        time.sleep(delay)

    def _is_security_page(self, html):
        """Check if the response is a Cloudflare/security challenge page."""
        indicators = [
            "security check | medex",
            "cf-challenge",
            "challenge-platform",
            "verify you are human",
            "captcha",
            "recaptcha",
            "hcaptcha",
            "turnstile",
            "checking your browser",
            "just a moment",
            "ray id",
            "cloudflare",
        ]
        html_lower = html[:5000].lower()
        return any(indicator in html_lower for indicator in indicators)

    def _solve_turnstile_2captcha(self, site_key, page_url):
        """Solve Cloudflare Turnstile using 2Captcha API."""
        if not self.api_key_2captcha:
            return None

        try:
            import requests as req

            print("  [2Captcha] Submitting Turnstile challenge...")
            submit_url = "https://2captcha.com/in.php"
            submit_data = {
                "key": self.api_key_2captcha,
                "method": "turnstile",
                "sitekey": site_key,
                "pageurl": page_url,
                "json": 1,
            }
            resp = req.post(submit_url, data=submit_data, timeout=30)
            result = resp.json()

            if result.get("status") != 1:
                print(f"  [2Captcha] Submit error: {result.get('request')}")
                return None

            task_id = result["request"]
            print(f"  [2Captcha] Task ID: {task_id}, waiting for solution...")

            for _ in range(60):
                time.sleep(5)
                res_url = f"https://2captcha.com/res.php?key={self.api_key_2captcha}&action=get&id={task_id}&json=1"
                res_resp = req.get(res_url, timeout=30)
                res_data = res_resp.json()

                if res_data.get("status") == 1:
                    print("  [2Captcha] Solution received.")
                    return res_data["request"]
                if res_data.get("request") != "CAPCHA_NOT_READY":
                    print(f"  [2Captcha] Error: {res_data.get('request')}")
                    return None

        except Exception as e:
            print(f"  [2Captcha] Exception: {e}")
        return None

    def _extract_turnstile_sitekey(self, html):
        """Extract Turnstile sitekey from page HTML."""
        patterns = [
            r'data-sitekey=["\']([^"\']+)["\']',
            r'sitekey["\']?\s*:\s*["\']([^"\']+)["\']',
            r'cf-turnstile["\'][^>]*data-sitekey=["\']([^"\']+)["\']',
            r'"sitekey"\s*:\s*"([^"]+)"',
        ]
        for pattern in patterns:
            match = re.search(pattern, html, re.IGNORECASE)
            if match:
                return match.group(1)
        return None

    def _handle_security_challenge(self, response, url):
        """Handle Cloudflare/security challenge with multiple strategies."""
        html = response.text

        if not self._is_security_page(html):
            return response

        print("  Security challenge detected. Attempting bypass...")

        # Strategy 1: cloudscraper already handles IUAM, retry with fresh scraper
        print("  [Strategy 1] Retrying with fresh scraper session...")
        self.scraper = self._create_scraper()
        for attempt in range(MAX_RETRIES):
            time.sleep(2 ** attempt)
            try:
                resp = self.scraper.get(url, timeout=PAGE_LOAD_TIMEOUT)
                if not self._is_security_page(resp.text):
                    print("  Bypassed via fresh session.")
                    return resp
            except Exception as e:
                print(f"  Retry {attempt + 1} failed: {e}")

        # Strategy 2: 2Captcha for Turnstile
        if self.api_key_2captcha:
            site_key = self._extract_turnstile_sitekey(html)
            if site_key:
                print(f"  [Strategy 2] Turnstile sitekey found: {site_key[:20]}...")
                token = self._solve_turnstile_2captcha(site_key, url)
                if token:
                    # Submit the token
                    try:
                        resp = self.scraper.post(url, data={"cf-turnstile-response": token}, timeout=PAGE_LOAD_TIMEOUT)
                        if not self._is_security_page(resp.text):
                            print("  Bypassed via 2Captcha.")
                            return resp
                    except Exception as e:
                        print(f"  2Captcha submit failed: {e}")

        # Strategy 3: Wait and retry (some challenges auto-resolve)
        print("  [Strategy 3] Waiting 15s for auto-resolution...")
        time.sleep(15)
        try:
            resp = self.scraper.get(url, timeout=PAGE_LOAD_TIMEOUT)
            if not self._is_security_page(resp.text):
                print("  Auto-resolved after wait.")
                return resp
        except Exception:
            pass

        return None

    def _fetch_page(self, url, item_id):
        """Fetch a page with Cloudflare bypass and retry logic."""
        for attempt in range(MAX_RETRIES):
            try:
                response = self.scraper.get(url, timeout=PAGE_LOAD_TIMEOUT)

                if response.status_code == 403 or self._is_security_page(response.text):
                    result = self._handle_security_challenge(response, url)
                    if result:
                        return result.text
                    if attempt == MAX_RETRIES - 1:
                        raise Exception("Cloudflare challenge could not be bypassed.")
                    continue

                if response.status_code == 200:
                    return response.text

                if response.status_code == 429:
                    wait = 30 * (attempt + 1)
                    print(f"  Rate limited. Waiting {wait}s...")
                    time.sleep(wait)
                    continue

                if response.status_code >= 500:
                    time.sleep(5 * (attempt + 1))
                    continue

            except cloudscraper.exceptions.CloudflareChallengeError:
                print(f"  Cloudflare challenge error (attempt {attempt + 1}).")
                self.scraper = self._create_scraper()
                time.sleep(5)
            except Exception as e:
                print(f"  Fetch error (attempt {attempt + 1}): {e}")
                time.sleep(3)

        raise Exception(f"Failed to fetch after {MAX_RETRIES} attempts.")

    def _extract_image_url(self, html, base_url):
        """Extract medicine image URL from page HTML."""
        if self._is_security_page(html):
            return ""

        patterns = [
            r'<a[^>]+href=["\']([^"\']*storage/images/packaging/[^"\']+)["\']',
            r'<img[^>]+data-src=["\']([^"\']*storage/images/packaging/[^"\']+)["\']',
            r'<img[^>]+src=["\']([^"\']*storage/images/packaging/[^"\']+)["\']',
        ]
        for pattern in patterns:
            match = re.search(pattern, html, re.IGNORECASE)
            if match:
                return urljoin(base_url, match.group(1))

        # Fallback: parse with BeautifulSoup
        soup = BeautifulSoup(html, "lxml")
        for img in soup.find_all("img"):
            src = img.get("data-src") or img.get("src") or ""
            if "storage/images/packaging" in src:
                return urljoin(base_url, src)
        for a in soup.find_all("a", href=True):
            if "storage/images/packaging" in a["href"]:
                return urljoin(base_url, a["href"])

        return ""

    def _download_csv(self, filename=None):
        """Save results to CSV."""
        if filename is None:
            filename = getattr(self, "output_csv_path", OUTPUT_CSV)
        with open(filename, "w", newline="", encoding="utf-8") as f:
            writer = csv.writer(f)
            writer.writerow(["source_id", "image_url"])
            writer.writerows(self.output)
        print(f"  CSV saved: {filename} ({len(self.output)} rows)")

    def run(self):
        """Main crawl loop."""
        total = len(self.items)
        print(f"Starting MedEx image scrape for {total} medicines...")
        print(f"Already scraped: {len(self.scraped_ids)}, Failed: {len(self.failed_ids)}")

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

                if image_url or (index + 1) % AUTOSAVE_EVERY == 0:
                    print(f"  [{index + 1}/{total}] ID {item_id}: {image_url or 'no image'}")

                if len(self.output) % 500 == 0:
                    self._download_csv()
                    print(f"  Autosaved CSV at {len(self.output)} rows.")

            except Exception as e:
                print(f"  [{index + 1}/{total}] ID {item_id}: FAILED - {e}")
                self.failed_ids.add(item_id)
                self.output.append([item_id, ""])
                self.scraped_ids.add(item_id)

            if (index + 1) % AUTOSAVE_EVERY == 0:
                self._save_progress()

            self._random_delay()

        self._save_progress()
        self._download_csv()
        print(f"\nDone! Total scraped: {len(self.output)}, Failed: {len(self.failed_ids)}")


def load_items_from_json(filepath):
    """Load medicine items from JSON file."""
    with open(filepath, "r") as f:
        return json.load(f)


def load_items_from_js(filepath):
    """Extract items array from the JS crawler script."""
    with open(filepath, "r") as f:
        content = f.read()
    match = re.search(r"const\s+items\s*=\s*(\[.*?\]);", content, re.DOTALL)
    if match:
        return json.loads(match.group(1))
    raise ValueError("Could not find items array in JS file.")


def generate_sample_items():
    """Generate sample items for testing."""
    base_url = "https://medex.com.bd/brands"
    items = [
        {"id": 1, "url": f"{base_url}/1/celofen-100-mg-tablet"},
        {"id": 2, "url": f"{base_url}/2/flexi-100-mg-tablet"},
    ]
    return items


def main():
    """Main entry point."""
    _set_default_paths()

    parser = argparse.ArgumentParser(description="MedEx Image Crawler with Cloudflare Bypass")
    parser.add_argument("--input", "-i", help="JSON file with items array")
    parser.add_argument("--from-js", help="Extract items from JS crawler script")
    parser.add_argument("--output", "-o", help="Output CSV path", default=OUTPUT_CSV)
    parser.add_argument("--2captcha-apikey", dest="captcha_apikey", help="2Captcha API key for Turnstile challenges")
    parser.add_argument("--min-delay", type=float, default=MIN_DELAY, help="Min delay between requests")
    parser.add_argument("--max-delay", type=float, default=MAX_DELAY, help="Max delay between requests")
    parser.add_argument("--test", action="store_true", help="Run with sample items (5 medicines)")
    args = parser.parse_args()

    output_csv = args.output

    if args.test:
        items = generate_sample_items()
    elif args.input:
        items = load_items_from_json(args.input)
    elif args.from_js:
        items = load_items_from_js(args.from_js)
    else:
        if os.path.exists(DEFAULT_INPUT_JSON):
            items = load_items_from_json(DEFAULT_INPUT_JSON)
        else:
            js_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), "medex_image_browser_crawler.generated.js")
            if os.path.exists(js_path):
                items = load_items_from_js(js_path)
            else:
                print("No input found. Use --input, --from-js, or --test")
                sys.exit(1)

    crawler = MedexCrawler(
        items=items,
        api_key_2captcha=args.captcha_apikey,
        min_delay=args.min_delay,
        max_delay=args.max_delay,
    )
    crawler.output_csv_path = output_csv
    crawler.run()


if __name__ == "__main__":
    main()
