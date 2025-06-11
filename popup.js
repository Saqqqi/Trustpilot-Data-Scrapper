document.addEventListener("DOMContentLoaded", () => {
  const scrapeButton = document.getElementById("scrapeButton");
  const downloadButton = document.getElementById("downloadButton");
  const clearButton = document.getElementById("clearButton");

  // Restore collected data from localStorage
  const savedData = localStorage.getItem('scrapedData');
  if (savedData) {
    // Display the saved data in the popup
    document.getElementById('output').innerHTML = savedData;
  }

  // Start scraping business URLs when "Scrape Business Data" button is clicked
  scrapeButton?.addEventListener("click", () => {
    console.log("Scrape Button Clicked"); // Check if this is triggered multiple times
    if (scrapeButton.disabled) return;
  
    scrapeButton.disabled = true; // Disable the button
    scrapeButton.innerText = "Processing..."; // Change button text to show processing
    updateOutput("Collecting data... Please wait...");

    // Show initial log in the log section
    appendLog("Starting data collection...");

    chrome.tabs.query({ active: true, currentWindow: true }, (tabs) => {
      chrome.scripting.executeScript({
        target: { tabId: tabs[0].id },
        function: collectBusinessUrls
      });
    });
  });
  chrome.runtime.onMessage.addListener((message) => {
    if (message.action === "dataCollected") {
      collectedData.push(message.data);
      processedUrls++;
  
      // Log and update progress
      appendLog(`Processed ${processedUrls}/${totalUrls}. Data collected: ${collectedData.length}`);
      if (processedUrls === totalUrls) {
        appendLog("All current URLs processed. Moving to the next page...");
        moveToNextPage(); // Trigger fetching the next page's URLs
      }
    }
  });
  
  // Listen for messages and handle them
// Listener for incoming messages
chrome.runtime.onMessage.addListener((message) => {
  if (message.action === "dataCollected") {
    console.log("Data Collected:", message.data);

    const output = document.getElementById("output");
    const scrapeButton = document.getElementById("scrapeButton"); // Ensure scrapeButton exists in your HTML

    // Disable the button to prevent multiple clicks
    scrapeButton.disabled = true;
    scrapeButton.innerText = "Processing..."; // Update button text to indicate processing

    // Update the log
    appendLog("Data collection complete!");
    updateOutput("Data collection done!");

    // Retrieve existing data from localStorage
    const existingData = JSON.parse(localStorage.getItem('scrapedData') || "[]");

    // Append the new data to the existing data
    existingData.push(message.data);

    // Save the combined data back to localStorage
    localStorage.setItem('scrapedData', JSON.stringify(existingData));

    // Clear and update the output to show all data
    output.innerHTML = ""; // Clear the output content

    // Display all stored JSON data
    existingData.forEach((data, index) => {
      const pre = document.createElement('pre');
      pre.textContent = `Entry ${index + 1}:\n` + JSON.stringify(data, null, 2);
      output.appendChild(pre);
    });

    // Send the entire combined data to the backend PHP script
    fetch("http://localhost/loadupob_trustpilot_leads_scrapper.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(existingData), // Send the combined data as JSON
    })
      .then((response) => response.json())
      .then((result) => {
        if (result.status === "success") {
          console.log("Data stored successfully:", result.message);
          
        } else {
          console.error("Error storing data:", result.message);
          updateOutput("Error storing data: " + result.message);
        }
      })
      .catch((error) => {
        console.error("Failed to send data to the server:", error);
        updateOutput("Failed to send data to the server.");
      })
      .finally(() => {
        // Re-enable the scrape button after the process is complete
        scrapeButton.disabled = false;
        scrapeButton.innerText = "Scrape Business Data"; // Reset the button text
      });
  }
});

  
  

  // Download collected data when "Download Collected Data" button is clicked
  downloadButton?.addEventListener("click", () => {
    const output = document.getElementById("output");
    if (output) {
      const data = output.textContent;  // Get the raw JSON data from the div
      if (data) {
        downloadDataAsJson(data);  // Call the function to download the data
      } else {
        appendLog("No data available to download.");
        console.error("No data available to download.");
      }
    }
  });

  // Add event listener for "Clear Data" button
  clearButton?.addEventListener("click", () => {
    // Clear the output box content
    const output = document.getElementById("output");
    if (output) {
      output.innerHTML = "No data collected yet. Click 'Scrape Business Data' to begin."; // Reset the output box
    }

    // Clear the data from localStorage
    localStorage.removeItem('scrapedData');
    console.log("Data cleared from output and localStorage.");
    appendLog("Data cleared from localStorage.");
  });
});

// Function to update the output with the given message and status class
function updateOutput(message, statusClass = "loading") {
  const output = document.getElementById("output");
  if (output) {
    output.innerHTML = message;
    output.className = statusClass; // Set the appropriate status class (loading, done, error)
  }
}

// Append log message to the log section
function appendLog(message) {
  const log = document.getElementById("log");
  if (log) {
    const logEntry = document.createElement('div');
    logEntry.textContent = message;
    log.appendChild(logEntry);

    // Auto-scroll the log to the bottom
    log.scrollTop = log.scrollHeight;
  }
}
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
  if (message.action === "nextButtonClicked") {
    console.log("Received 'nextButtonClicked' message in popup.js.");
    
    
    // Call the scraping function to collect the new page's data after the next button click
    collectBusinessUrls();
  }
});
// Collect business URLs from the category page
function collectBusinessUrls() {
  const businessUrls = Array.from(document.querySelectorAll('a[name="business-unit-card"]')).map(link => link.href);
  console.log("Collected Business URLs:", businessUrls);

  // Send the URLs to the background script for further processing
  chrome.runtime.sendMessage({ action: "processBatches", urls: businessUrls });
}

// Function to download data as JSON
function downloadDataAsJson(data) {
  const jsonData = JSON.parse(data);  // Convert text back to JSON object
  const jsonString = JSON.stringify(jsonData, null, 2);  // Create pretty-printed JSON string

  // Create a Blob from the JSON string
  const blob = new Blob([jsonString], { type: 'application/json' });

  // Create a link element to trigger the download
  const link = document.createElement("a");
  link.href = URL.createObjectURL(blob);
  link.download = "collectedData.json";  // Name of the file to download
  link.click();  // Trigger the download
}

