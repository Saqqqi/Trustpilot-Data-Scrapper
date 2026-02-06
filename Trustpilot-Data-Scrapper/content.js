// content.js - Automation Engine

(function () {
  // CRITICAL: Exit if not on Trustpilot
  if (!window.location.hostname.includes("trustpilot.com")) {
    return;
  }

  console.log("%c[Trustpilot Scrapper] Engine Active on Trustpilot.", "color: #00ff00; font-weight: bold;");

  const pathname = window.location.pathname;
  const isListingPage = pathname.includes("/categories/") || pathname.includes("/search");
  const isProfilePage = pathname.includes("/review/");

  // Auto-Start on Page Load
  if (isListingPage) {
    chrome.storage.local.get({ scrapingStatus: "idle" }, (res) => {
      if (res.scrapingStatus === "active") {
        console.log("[Content] Automation active. Starting discovery in 5s...");
        setTimeout(discoverAndSend, 5000);
      }
    });
  }

  chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
    if (message.action === "ping") {
      sendResponse({ status: "alive" });
    } else if (message.action === "startDiscovery") {
      discoverAndSend();
      sendResponse({ status: "started" });
    } else if (message.action === "clickNextButton") {
      console.log("%c[Content] Pagination Message Received!", "color: cyan; font-weight: bold;");
      handleNavigation();
      sendResponse({ status: "navigating" });
    }
    return true;
  });

  if (isProfilePage) {
    scrapeProfile();
  }

  function discoverAndSend() {
    console.log("[Content] Discovering business links...");
    const cards = document.querySelectorAll('a[name="business-unit-card"]');
    const links = Array.from(cards)
      .map(a => a.href)
      .filter(h => h && h.includes('/review/'));

    if (links.length > 0) {
      console.log(`[Content] Found ${links.length} leads.`);
      chrome.runtime.sendMessage({ action: "logUpdate", log: `Found ${links.length} companies. Starting capture...` });
      chrome.runtime.sendMessage({ action: "processBatches", urls: links });
    } else {
      console.warn("[Content] No leads found on this page.");
      chrome.storage.local.get({ scrapingStatus: "idle" }, (res) => {
        if (res.scrapingStatus === "active") {
          chrome.runtime.sendMessage({ action: "logUpdate", log: "End of list reached." });
          chrome.storage.local.set({ scrapingStatus: "idle" });
        }
      });
    }
  }

  function handleNavigation() {
    console.log("[Content] --- NAVIGATING TO NEXT PAGE ---");

    // Explicitly target the button you provided
    const nextBtn = document.querySelector('a[name="pagination-button-next"]') ||
      document.querySelector('a[data-pagination-button-next-link="true"]') ||
      document.querySelector('a[rel="next"]') ||
      document.querySelector('a[aria-label="Next page"]');

    if (nextBtn) {
      // Check if disabled
      const disabled = nextBtn.classList.contains('pagination-link_disabled__UYSG0') ||
        nextBtn.classList.contains('link_disabled__vpJqK') ||
        nextBtn.getAttribute('aria-disabled') === 'true';

      if (!disabled) {
        console.log("%c[Content] Found Next button! Clicking in 1s...", "color: yellow;");
        nextBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(() => {
          nextBtn.click();
          setTimeout(discoverAndSend, 8000); // Discovery on new page
        }, 1000);
      } else {
        console.log("[Content] Next button is disabled (Final Page).");
        chrome.runtime.sendMessage({ action: "logUpdate", log: "🏁 reached last page." });
        chrome.storage.local.set({ scrapingStatus: "idle" });
      }
    } else {
      // Fallback: Numeric Search
      const current = document.querySelector('a[aria-current="page"]');
      if (current) {
        const nextNum = parseInt(current.innerText.trim()) + 1;
        const numBtn = document.querySelector(`a[name="pagination-button-${nextNum}"]`);
        if (numBtn) {
          console.log(`[Content] Found page link for ${nextNum}. Clicking...`);
          numBtn.click();
          setTimeout(discoverAndSend, 8000);
          return;
        }
      }
      console.error("[Content] UNABLE TO FIND PAGINATION.");
      chrome.runtime.sendMessage({ action: "logUpdate", log: "❌ Error: Could not find Page 2 button." });
      chrome.storage.local.set({ scrapingStatus: "idle" });
    }
  }

  function scrapeProfile() {
    const getText = (s) => (document.querySelector(s)?.innerText || "None").trim();
    const getAttr = (s, a) => document.querySelector(s)?.getAttribute(a) || "None";
    const data = {
      company_name: getText('h1.title_title__pKuza > span.title_displayName__9lGaz'),
      total_reviews: getText('span.styles_reviewsAndRating__OIRXy').replace(/[^\d,]/g, ''),
      trust_score: (() => {
        const img = document.querySelector('div.styles_rating__kLMDv > img');
        return img?.alt.match(/[\d.]+/)?.[0] || getText('div.styles_rating__kLMDv > p');
      })(),
      category: getText('div.styles_breadcrumb__klHaT > a'),
      address: getText('ul.styles_itemsColumn__N6BEW > li:nth-child(1) > p'),
      phone_number: getText('ul.styles_itemsColumn__N6BEW > li:nth-child(2) > a'),
      email: (() => {
        const mail = document.querySelector('ul.styles_itemsColumn__N6BEW a[href^="mailto:"]');
        return mail ? mail.getAttribute('href').replace('mailto:', '') : "None";
      })(),
      website_url: getAttr('ul.styles_itemsColumn__N6BEW > li:nth-child(4) > a', 'href'),
      is_claimed: document.body.innerText.includes('Claimed profile') ? 'Claimed profile' : 'No',
      has_paid_subscription: document.body.innerText.includes('Paid Trustpilot subscription') ? 'Paid Trustpilot subscription' : 'No',
      negative_reply_rate: getText('div.variant_container__05pif h4'),
      reply_time: getText('div.variant_container__05pif p'),
      use_ai: document.body.innerText.includes('AI-assist') ? 'May use AI-assist with replies' : 'No',
      logo_url: getAttr('picture.business-profile-image_containmentWrapper__xJZjr > img', 'src')
    };
    chrome.runtime.sendMessage({ action: "dataCollected", data });
  }
})();
