// Detect if it's the categories page or a review page
if (window.location.pathname.includes("/categories/")) {
  console.log("This is the main categories page...");

  // Listen for the message from the background script to click the "Next" button
  chrome.runtime.onMessage.addListener(function (message, sender, sendResponse) {
    if (message.action === "clickNextButton") {
      console.log("Received message to click the Next button.");
      simulateNextButtonClick();
      sendResponse({ status: "success" });
    }
  });
} else if (window.location.pathname.includes("/review/")) {
  console.log("Scraping data from a review page...");

  // Scrape data (category and email) from the review page
  const data = {
    categories: [
      document.querySelector('.breadcrumb_breadcrumb__lJO__ a')?.innerText.trim() || 'N/A'
    ],
    email: document.querySelector('.styles_contactInfoElements__YqQAJ a[href^="mailto:"]')?.innerText.trim() || 'N/A',
  };

  // Send the scraped data to the background script
  chrome.runtime.sendMessage({ action: "dataCollected", data });
}

// Function to simulate the "Next" button click on the categories page
// Function to simulate the "Next" button click and fetch new URLs
function simulateNextButtonClick() {
  console.log("Simulating the Next button click...");
  localStorage.removeItem('scrapedData');
  const nextButton = document.querySelector('a[name="pagination-button-next"][aria-label="Next page"].pagination-link_next__SDNU4');
  if (nextButton) {
    nextButton.click(); // Simulate the Next button click

    // Wait for the next page to load and fetch the new business URLs
    setTimeout(() => {
      const businessUrls = Array.from(document.querySelectorAll('a[name="business-unit-card"]')).map(link => link.href);
      console.log("Collected Business URLs from the Next page:", businessUrls);

      // Send the new URLs to the background script
      chrome.runtime.sendMessage({ action: "processBatches", urls: businessUrls });
    }, 3000); // Adjust delay as per the loading time of the page
  } else {
    console.log("Next button not found. No more pages to scrape.");
  }
}
