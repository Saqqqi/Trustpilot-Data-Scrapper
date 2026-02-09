<?php
// get_count.php - AJAX helper to get record count based on filters
require_once 'db.php';

$where_conditions = [];
$filter_category = $_GET['filter_category'] ?? '';
$filter_rating_min = $_GET['filter_rating_min'] ?? '';
$filter_rating_max = $_GET['filter_rating_max'] ?? '';
$filter_claimed = $_GET['filter_claimed'] ?? '';
$filter_website = $_GET['filter_website'] ?? '';
$filter_company_size = $_GET['filter_company_size'] ?? '';
$include_exported = $_GET['include_exported'] ?? '0';

if ($include_exported !== '1') {
    $where_conditions[] = "is_exported = 0";
}

if ($filter_category) {
    if (is_array($filter_category)) {
        $cats = array_map(function($c) use ($conn) { return "'" . $conn->real_escape_string($c) . "'"; }, $filter_category);
        $where_conditions[] = "category IN (" . implode(",", $cats) . ")";
    } else {
        $where_conditions[] = "category = '" . $conn->real_escape_string($filter_category) . "'";
    }
}
if ($filter_rating_min) {
    $where_conditions[] = "CAST(trust_score AS DECIMAL(3,1)) >= " . (float)$filter_rating_min;
}
if ($filter_rating_max) {
    $where_conditions[] = "CAST(trust_score AS DECIMAL(3,1)) <= " . (float)$filter_rating_max;
}
if ($filter_claimed === 'claimed') {
    $where_conditions[] = "is_claimed = 'Claimed profile'";
}
if ($filter_claimed === 'unclaimed') {
    $where_conditions[] = "(is_claimed IS NULL OR is_claimed != 'Claimed profile')";
}
if ($filter_website === 'has') {
    $where_conditions[] = "website_url IS NOT NULL AND website_url != 'None' AND website_url != ''";
}
if ($filter_website === 'none') {
    $where_conditions[] = "(website_url IS NULL OR website_url = 'None' OR website_url = '')";
}

// Company Size Filter
if ($filter_company_size === 'enterprise') {
    $where_conditions[] = "CAST(REPLACE(total_reviews, ',', '') AS UNSIGNED) >= 1000";
} elseif ($filter_company_size === 'large') {
    $where_conditions[] = "CAST(REPLACE(total_reviews, ',', '') AS UNSIGNED) >= 500 AND CAST(REPLACE(total_reviews, ',', '') AS UNSIGNED) < 1000";
} elseif ($filter_company_size === 'medium') {
    $where_conditions[] = "CAST(REPLACE(total_reviews, ',', '') AS UNSIGNED) >= 100 AND CAST(REPLACE(total_reviews, ',', '') AS UNSIGNED) < 500";
} elseif ($filter_company_size === 'small') {
    $where_conditions[] = "CAST(REPLACE(total_reviews, ',', '') AS UNSIGNED) >= 10 AND CAST(REPLACE(total_reviews, ',', '') AS UNSIGNED) < 100";
} elseif ($filter_company_size === 'startup') {
    $where_conditions[] = "CAST(REPLACE(total_reviews, ',', '') AS UNSIGNED) < 10";
}

$where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

$query = "SELECT COUNT(*) as total FROM trustpilot_leads $where_clause";
$result = $conn->query($query);
$row = $result->fetch_assoc();

echo json_encode(['total' => (int)$row['total']]);
?>

