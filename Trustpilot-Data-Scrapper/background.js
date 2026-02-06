// background.js - Sequential Intelligence Engine

function logProgress(message) {
  console.log(`[Background] ${message}`);
  chrome.runtime.sendMessage({ action: "logUpdate", log: message }).catch(() => { });
}

let pendingUrls = [];
let currentBatchData = [];
let workerTabId = null;

chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
  if (message.action === "processBatches") {
    logProgress(`Batch Start: Discovery found ${message.urls.length} companies.`);
    pendingUrls = [...message.urls];
    currentBatchData = [];
    workerTabId = null;
    processNextSequential();
  } else if (message.action === "dataCollected") {
    currentBatchData.push(message.data);

    // Backup
    chrome.storage.local.get({ scrapedData: [] }, function (result) {
      let current = result.scrapedData;
      current.push(message.data);
      chrome.storage.local.set({ scrapedData: current });
    });

    // Move to next URL in the same tab
    processNextSequential();
  } else if (message.action === "logUpdate") {
    chrome.runtime.sendMessage({ action: "logUpdate", log: message.log }).catch(() => { });
  }
});

async function processNextSequential() {
  if (pendingUrls.length === 0) {
    if (workerTabId) {
      chrome.tabs.remove(workerTabId).catch(() => { });
      workerTabId = null;
    }
    logProgress(`Batch complete. Syncing ${currentBatchData.length} records...`);
    syncWithSQL(currentBatchData);
    return;
  }

  const nextUrl = pendingUrls.shift();
  logProgress(`Scraping Lead: ${pendingUrls.length} remaining in batch...`);

  if (!workerTabId) {
    // Open the first tab in the background
    const tab = await chrome.tabs.create({ url: nextUrl, active: false });
    workerTabId = tab.id;
    monitorWorkerTab(workerTabId);
  } else {
    // Re-use the SAME tab for the next URL to reduce clutter
    chrome.tabs.update(workerTabId, { url: nextUrl });
    monitorWorkerTab(workerTabId);
  }
}

function monitorWorkerTab(tabId) {
  const listener = (updatedTabId, changeInfo) => {
    if (updatedTabId === tabId && changeInfo.status === "complete") {
      chrome.tabs.onUpdated.removeListener(listener);
      // Wait for content script to send 'dataCollected'
      // No timeout needed here as dataCollected triggers the next step
    }
  };
  chrome.tabs.onUpdated.addListener(listener);
}

function syncWithSQL(data) {
  fetch("http://localhost/Trustpilot/index.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  })
    .then(() => {
      logProgress("✅ Sync Success. Commanding Page 2...");
      initiatePaginationLoop();
    })
    .catch(err => {
      logProgress(`⚠️ Sync Error. Trying Page 2 anyway...`);
      initiatePaginationLoop();
    });
}

function initiatePaginationLoop() {
  chrome.tabs.query({}, (tabs) => {
    const tpTab = tabs.find(t =>
      t.url && t.url.includes("trustpilot.com") &&
      (t.url.includes("/search") || t.url.includes("/categories/"))
    );

    if (tpTab) {
      logProgress(`Pagination: Messaging Trustpilot Tab [${tpTab.id}]...`);
      chrome.tabs.sendMessage(tpTab.id, { action: "clickNextButton" }, (response) => {
        if (chrome.runtime.lastError) {
          chrome.scripting.executeScript({
            target: { tabId: tpTab.id },
            files: ['content.js']
          }).then(() => {
            setTimeout(() => {
              chrome.tabs.sendMessage(tpTab.id, { action: "clickNextButton" });
            }, 1000);
          });
        }
      });
    } else {
      logProgress("❌ Stop: Trustpilot tab was closed.");
      chrome.storage.local.set({ scrapingStatus: "idle" });
    }
  });
}
