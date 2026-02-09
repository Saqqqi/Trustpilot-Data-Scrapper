<?php
// db.php - Database Configuration & Connection

$servername = "127.0.0.1:3307";
$username = "root";
$password = "";
$dbname = "trustpilot_db";

// Suppress errors during initial connection to handle it gracefully
$conn = @new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("<div style='padding:2rem; background:#0f172a; color:#ef4444; font-family:sans-serif; border-radius:12px; border:1px solid #1e293b; margin:2rem;'>
        <h2 style='margin-bottom:10px'>❌ Database Connection Critical Failure</h2>
        <p>Ensure XAMPP MySQL is running on port 3307.</p>
        <p style='font-size:0.8rem; opacity:0.6'>Error: {$conn->connect_error}</p>
    </div>");
}

$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

// Table Schema with Flag column
$table_sql = "CREATE TABLE IF NOT EXISTS trustpilot_leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255),
    total_reviews VARCHAR(50),
    trust_score VARCHAR(50),
    category VARCHAR(255),
    address TEXT,
    phone_number VARCHAR(100),
    email VARCHAR(255) NULL UNIQUE,
    website_url TEXT,
    is_claimed VARCHAR(50),
    has_paid_subscription VARCHAR(50),
    negative_reply_rate VARCHAR(100),
    reply_time VARCHAR(100),
    use_ai VARCHAR(50),
    logo_url TEXT,
    is_flagged TINYINT(1) DEFAULT 0,
    is_exported TINYINT(1) DEFAULT 0,
    scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($table_sql);

// Add columns if they don't exist (for existing databases)
$conn->query("ALTER TABLE trustpilot_leads ADD COLUMN IF NOT EXISTS is_flagged TINYINT(1) DEFAULT 0");
$conn->query("ALTER TABLE trustpilot_leads ADD COLUMN IF NOT EXISTS is_exported TINYINT(1) DEFAULT 0");
?>
