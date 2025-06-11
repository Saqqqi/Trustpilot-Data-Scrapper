// background.js

let collectedData = [];
let totalUrls = 0;
let processedUrls = 0;


function logProgress(message) {
  console.log(message);
  chrome.tabs.query({ url: "*://www.trustpilot.com/categories/*" }, (tabs) => {
    if (tabs.length > 0) {
      chrome.tabs.sendMessage(tabs[0].id, { action: "logProgress", log: message });
    }
  });
}

// Handle messages from other parts of the extension
chrome.runtime.onMessage.addListener((message) => {
  if (message.action === "processBatches") {
    totalUrls = message.urls.length;
    processedUrls = 0;
    collectedData = [];
    processBatches(message.urls);
    logProgress(`Starting batch processing of ${totalUrls} URLs...`);
  } else if (message.action === "dataCollected") {
    collectedData.push(message.data);
    processedUrls++;
    logProgress(`Processed ${processedUrls}/${totalUrls} URLs. Collected data so far: ${collectedData.length}`);


    if (processedUrls === totalUrls) {
      logProgress("All batches processed. Data collection complete!");
      chrome.runtime.sendMessage({ action: "dataCollectionComplete", data: collectedData });
    }
  } else if (message.action === "downloadData") {
    saveDataToFile(collectedData);
    logProgress("Collected data has been downloaded.");
  }
});

// Process batches of URLs
async function processBatches(urls) {
  try {
    const tabs = await Promise.all(
      urls.map((url) => chrome.tabs.create({ url, active: false }))
    );
    logProgress(`${tabs.length} tabs opened for processing.`);

    await Promise.all(tabs.map((tab) => waitForTabToLoad(tab.id)));

    await Promise.all(
      tabs.map(async (tab) => {
        try {
          const [result] = await chrome.scripting.executeScript({
            target: { tabId: tab.id },
            func: checkContentScriptActive,
          });

          if (result?.result) {
            const response = await new Promise((resolve) =>
              chrome.tabs.sendMessage(tab.id, { action: "clickNextButton" }, resolve)
            );

            if (response?.status === "success") {
              console.log(`Clicked the "Next" button on tab ${tab.id}`);
            } else {
              console.log(`Failed to click the "Next" button on tab ${tab.id}`);
            }
          } else {
            console.log(`Content script not active on tab ${tab.id}`);
          }
        } catch (scriptError) {
          console.error(`Error executing script on tab ${tab.id}:`, scriptError);
        } finally {
          // Attempt to close the tab, even if the above operations fail
          try {
            await chrome.tabs.remove(tab.id);
          } catch (removeError) {
            console.error(`Error closing tab ${tab.id}:`, removeError);
          }
        }
      })
    );

    logProgress("All tabs processed and closed.");
    moveToNextPage();
  } catch (error) {
    logProgress(`Error during batch processing: ${error.message}`);
  }
}




// Function to check if the content script is active
function checkContentScriptActive() {
  return typeof chrome.runtime !== "undefined";
}





// Utility function to log progress
function logProgress(message) {
  console.log(message);
}





// Wait for tab to load fully
function waitForTabToLoad(tabId) {
  return new Promise((resolve) => {
    chrome.tabs.onUpdated.addListener(function listener(tabIdUpdated, changeInfo) {
      if (tabIdUpdated === tabId && changeInfo.status === "complete") {
        chrome.tabs.onUpdated.removeListener(listener);
        resolve();
      }
    });
  });
}


// Function to handle the next button click and fetch data for new pages
function moveToNextPage() {
  console.log("Simulating the next page navigation...");


  chrome.tabs.query({ url: "*://www.trustpilot.com/categories/*" }, (tabs) => {
    if (tabs.length > 0) {
      chrome.tabs.sendMessage(tabs[0].id, { action: "clickNextButton" }, (response) => {
        if (response && response.status === "success") {
          console.log("Successfully clicked the Next button. Waiting for new URLs...");
        } else {
          console.log("Failed to click the Next button.");
        }
      });
    } else {
      console.log("No active tab found for the categories page.");
    }
  });
}





// Save collected data to a file
function saveDataToFile(data) {
  const jsonData = JSON.stringify(data, null, 2);

  if (chrome.downloads) {
    chrome.downloads.download({
      url: "data:application/json;charset=utf-8," + encodeURIComponent(jsonData),
      filename: "collectedData.json",
      saveAs: true,
    });
  } else {
    logProgress("Error: chrome.downloads API is not available.");
  }
}
