CREATE DATABASE IF NOT EXISTS trustpilot_db;
USE trustpilot_db;

CREATE TABLE IF NOT EXISTS trustpilot_leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255),
    total_reviews VARCHAR(50),
    trust_score VARCHAR(50),
    category VARCHAR(255),
    address TEXT,
    phone_number VARCHAR(100),
    email VARCHAR(255) UNIQUE,
    website_url TEXT,
    is_claimed VARCHAR(50),
    has_paid_subscription VARCHAR(50),
    negative_reply_rate VARCHAR(100),
    reply_time VARCHAR(100),
    use_ai VARCHAR(50),
    logo_url TEXT,
    scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
