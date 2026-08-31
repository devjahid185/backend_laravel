/*
Paste this script in a medex.com.bd tab console after passing the security check.
It opens medicine detail pages in a visible same-origin window and downloads a CSV.
Replace __MEDEX_ITEMS_JSON__ with [{"id":1,"url":"https://..."}].
*/
(async () => {
  const items = __MEDEX_ITEMS_JSON__;
  const minDelayMs = 2500;
  const maxDelayMs = 6500;
  const pageLoadTimeoutMs = 45000;
  const securityPollMs = 2000;
  const autosaveEvery = 50;
  const storageKey = "medex_image_scrape_rows";
  const failedStorageKey = "medex_image_scrape_failed_ids";
  const restoredRows = JSON.parse(localStorage.getItem(storageKey) || "[]");
  const failedIds = new Set(JSON.parse(localStorage.getItem(failedStorageKey) || "[]").map(Number));
  const output = [["source_id", "image_url"], ...restoredRows];
  const scrapedIds = new Set(restoredRows.map((row) => Number(row[0])));
  const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
  const nextDelayMs = () => minDelayMs + Math.floor(Math.random() * (maxDelayMs - minDelayMs + 1));
  const csvCell = (value) => `"${String(value ?? "").replaceAll('"', '""')}"`;
  let scrapeWindow = null;
  let paused = false;
  let stopped = false;

  function isSecurityHtml(html) {
    return html.includes("Security Check | MedEx") || html.slice(0, 3000).toLowerCase().includes("captcha");
  }

  function extractImage(html) {
    if (isSecurityHtml(html)) {
      throw new Error("Security check response");
    }
    const patterns = [
      /<a[^>]+href=["']([^"']*storage\/images\/packaging\/[^"']+)["']/i,
      /<img[^>]+data-src=["']([^"']*storage\/images\/packaging\/[^"']+)["']/i,
    ];
    for (const pattern of patterns) {
      const match = html.match(pattern);
      if (match?.[1]) {
        return new URL(match[1], location.origin).toString();
      }
    }
    return "";
  }

  function downloadCsv(rows) {
    const csv = rows.map((row) => row.map(csvCell).join(",")).join("\n");
    const blob = new Blob([csv], { type: "text/csv;charset=utf-8" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `medex_images_${Date.now()}.csv`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
  }

  function saveProgress() {
    localStorage.setItem(storageKey, JSON.stringify(output.slice(1)));
    localStorage.setItem(failedStorageKey, JSON.stringify([...failedIds]));
  }

  function parseCsvLine(line) {
    const cells = [];
    let cell = "";
    let quoted = false;
    for (let index = 0; index < line.length; index += 1) {
      const char = line[index];
      if (char === '"' && quoted && line[index + 1] === '"') {
        cell += '"';
        index += 1;
      } else if (char === '"') {
        quoted = !quoted;
      } else if (char === "," && !quoted) {
        cells.push(cell);
        cell = "";
      } else {
        cell += char;
      }
    }
    cells.push(cell);
    return cells;
  }

  window.medexDownloadImagesCsv = () => downloadCsv(output);
  window.medexPauseImageScrape = () => {
    paused = true;
    console.log("MedEx image scrape paused. Run medexResumeImageScrape() to continue.");
  };
  window.medexResumeImageScrape = () => {
    paused = false;
    console.log("MedEx image scrape resumed.");
  };
  window.medexStopImageScrape = () => {
    stopped = true;
    saveProgress();
    console.log("MedEx image scrape will stop after the current page. Run medexDownloadImagesCsv() for the saved CSV.");
  };
  window.medexImportImagesCsv = (csvText) => {
    const rows = String(csvText || "")
      .trim()
      .split(/\r?\n/)
      .slice(1)
      .map((line) => parseCsvLine(line).slice(0, 2))
      .filter(([id]) => id && !scrapedIds.has(Number(id)));
    for (const row of rows) {
      output.push(row);
      scrapedIds.add(Number(row[0]));
    }
    saveProgress();
    console.log(`Imported ${rows.length} rows. Total saved rows: ${output.length - 1}.`);
  };
  window.medexClearImageScrapeProgress = () => {
    localStorage.removeItem(storageKey);
    localStorage.removeItem(failedStorageKey);
    console.log("MedEx image scrape progress cleared.");
  };

  async function loadHtmlInVisibleWindow(item) {
    if (!scrapeWindow || scrapeWindow.closed) {
      scrapeWindow = window.open("about:blank", "medex_image_scrape_window", "width=1100,height=850");
    }
    if (!scrapeWindow) {
      throw new Error("Popup blocked. Allow popups for medex.com.bd and rerun this script.");
    }

    scrapeWindow.location.href = item.url;
    const startedAt = Date.now();

    while (true) {
      await sleep(500);
      let html = "";
      let href = "";
      let readyState = "";
      try {
        href = scrapeWindow.location.href;
        readyState = scrapeWindow.document.readyState;
        html = scrapeWindow.document.documentElement?.outerHTML || "";
      } catch (error) {
        if (Date.now() - startedAt > pageLoadTimeoutMs) {
          throw new Error("Could not access scrape window.");
        }
        continue;
      }

      if (html && isSecurityHtml(html)) {
        console.warn("Security check opened in the scrape window. Solve it there; this script will wait and continue automatically.");
        while (true) {
          await sleep(securityPollMs);
          href = scrapeWindow.location.href;
          html = scrapeWindow.document.documentElement?.outerHTML || "";
          if (html && !isSecurityHtml(html) && href.includes(`/brands/${item.id}/`)) {
            return html;
          }
        }
      }

      if (html && href.includes(`/brands/${item.id}/`) && (readyState === "interactive" || readyState === "complete")) {
        return html;
      }

      if (Date.now() - startedAt > pageLoadTimeoutMs) {
        throw new Error("Timed out loading medicine page.");
      }
    }
  }

  console.log(`Starting MedEx image scrape for ${items.length} medicines...`);
  for (let index = 0; index < items.length; index += 1) {
    while (paused && !stopped) {
      await sleep(1000);
    }
    if (stopped) {
      break;
    }
    const item = items[index];
    if (scrapedIds.has(Number(item.id))) {
      continue;
    }
    try {
      const html = await loadHtmlInVisibleWindow(item);
      const imageUrl = extractImage(html);
      output.push([item.id, imageUrl]);
      scrapedIds.add(Number(item.id));
      failedIds.delete(Number(item.id));
      saveProgress();
      if (imageUrl || (index + 1) % autosaveEvery === 0) {
        console.log(`${index + 1}/${items.length}`, item.id, imageUrl || "no image");
      }
      if ((output.length - 1) % 500 === 0) {
        downloadCsv(output);
        console.log(`Autosaved CSV at ${output.length - 1} rows.`);
      }
    } catch (error) {
      console.warn(`${index + 1}/${items.length}`, item.id, error.message);
      if (String(error.message).includes("Security check")) {
        saveProgress();
        console.warn("Security check returned. Solve it in the scrape window; if it does not continue, rerun this script. No CSV was auto-downloaded. Run medexDownloadImagesCsv() if you want the saved partial CSV.");
        return;
      }
      failedIds.add(Number(item.id));
      output.push([item.id, ""]);
      scrapedIds.add(Number(item.id));
      saveProgress();
    }
    await sleep(nextDelayMs());
  }

  downloadCsv(output);
  console.log("Done. CSV downloaded.");
})();
