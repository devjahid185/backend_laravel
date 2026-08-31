#!/usr/bin/env python3
"""
Export items from the JS crawler script to JSON for use with the Python crawler.
Run: python3 export_items_from_js.py
"""

import json
import re
import sys

JS_PATH = "/Users/zahid/Documents/bholavashi/backend_laravel/scripts/medex_image_browser_crawler.generated.js"
OUTPUT_PATH = "/Users/zahid/Documents/bholavashi/backend_laravel/scripts/medex_items.json"


def main():
    with open(JS_PATH, "r") as f:
        content = f.read()

    match = re.search(r"const\s+items\s*=\s*(\[.*?\]);", content, re.DOTALL)
    if not match:
        print("Could not find items array in JS file.")
        sys.exit(1)

    items = json.loads(match.group(1))
    with open(OUTPUT_PATH, "w") as f:
        json.dump(items, f, indent=2)

    print(f"Exported {len(items)} items to {OUTPUT_PATH}")


if __name__ == "__main__":
    main()
