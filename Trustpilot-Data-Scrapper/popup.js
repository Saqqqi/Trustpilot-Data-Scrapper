document.addEventListener("DOMContentLoaded", () => {
  const scrapeButton = document.getElementById("scrapeButton");
  const downloadButton = document.getElementById("downloadButton");
  const clearButton = document.getElementById("clearButton");
  const output = document.getElementById("output");
  const log = document.getElementById("log");

  if (!scrapeButton) return;

  // Load initial state
  chrome.storage.local.get({ scrapedData: [], scrapingStatus: "idle" }, (res) => {
    if (res.scrapedData && res.scrapedData.length > 0) updateDisplay(res.scrapedData);
    if (res.scrapingStatus === "active") {
      scrapeButton.disabled = true;
      scrapeButton.innerText = "Scraping Active...";
    }
  });

  scrapeButton.addEventListener("click", () => {
    if (scrapeButton.disabled) return;

    // NEW LOGIC: Always seek out the Trustpilot tab, don't just use whatever is Active!
    chrome.tabs.query({}, (tabs) => {
      const tpTab = tabs.find(t =>
        t.url && t.url.includes("trustpilot.com") &&
        (t.url.includes("/search") || t.url.includes("/categories/"))
      );

      if (!tpTab) {
        appendLog("❌ Error: No Trustpilot Search/Category tab found. Please open one first.");
        return;
      }

      scrapeButton.disabled = true;
      scrapeButton.innerText = "Processing...";
      chrome.storage.local.set({ scrapingStatus: "active" });

      appendLog(`Discovery started on tab: ${tpTab.title.substring(0, 20)}...`);

      // Send command to the SPECIFIC Trustpilot tab, not the active Google tab!
      chrome.tabs.sendMessage(tpTab.id, { action: "startDiscovery" }, (response) => {
        if (chrome.runtime.lastError) {
          appendLog("⚠️ Refreshing automation engine...");
          chrome.scripting.executeScript({
            target: { tabId: tpTab.id },
            files: ['content.js']
          }).then(() => {
            chrome.tabs.sendMessage(tpTab.id, { action: "startDiscovery" });
          });
        }
      });
    });
  });

  chrome.runtime.onMessage.addListener((message) => {
    if (message.action === "logUpdate") {
      appendLog(message.log);
    } else if (message.action === "statusUpdate") {
      appendLog(message.message);
      chrome.storage.local.get({ scrapedData: [] }, (res) => updateDisplay(res.scrapedData));
    }
  });

  downloadButton?.addEventListener("click", () => {
    chrome.storage.local.get({ scrapedData: [] }, (res) => {
      const blob = new Blob([JSON.stringify(res.scrapedData, null, 2)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `trustpilot_leads_${Date.now()}.json`;
      a.click();
    });
  });

  clearButton?.addEventListener("click", () => {
    if (confirm("Stop scraper and clear data?")) {
      chrome.storage.local.set({ scrapedData: [], scrapingStatus: "idle" }, () => {
        output.innerHTML = "No data collected yet.";
        appendLog("Scraper reset.");
        scrapeButton.disabled = false;
        scrapeButton.innerText = "Scrape Business Data";
      });
    }
  });

  function updateDisplay(data) {
    output.innerHTML = "";
    data.slice(-3).reverse().forEach((entry) => {
      const div = document.createElement('div');
      div.style.fontSize = "10px";
      div.style.marginBottom = "5px";
      div.textContent = `✅ ${entry.company_name}`;
      output.appendChild(div);
    });
    const info = document.createElement('div');
    info.innerHTML = `<b>Total leads: ${data.length}</b>`;
    output.appendChild(info);
  }

  function appendLog(message) {
    const entry = document.createElement('div');
    entry.textContent = `> ${message}`;
    log.appendChild(entry);
    log.scrollTop = log.scrollHeight;
  }
});
