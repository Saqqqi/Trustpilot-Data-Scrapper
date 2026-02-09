<?php
// export.php - Dynamic CSV Export with Filter Support
require_once 'db.php';

// Build WHERE clause based on filters
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
// ... [rest of filters stay same]
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

// Map database fields to CSV headers
$all_possible_fields = [
    'company_name' => 'Company Name',
    'category' => 'Category',
    'trust_score' => 'Trust Rating',
    'total_reviews' => 'Total Reviews',
    'email' => 'Email Address',
    'website_url' => 'Website URL',
    'phone_number' => 'Phone Number',
    'address' => 'Physical Address',
    'is_claimed' => 'Verification Status',
    'scraped_at' => 'Extraction Date'
];

// Get selected fields from request, fallback to defaults if empty
$selected_field_ids = $_GET['export_fields'] ?? array_keys($all_possible_fields);
$selected_fields = [];
foreach ($selected_field_ids as $fid) {
    if (isset($all_possible_fields[$fid])) {
        $selected_fields[$fid] = $all_possible_fields[$fid];
    }
}

// Ensure at least company_name is exported if something goes wrong
if (empty($selected_fields)) {
    $selected_fields = ['company_name' => 'Company Name'];
}

// Fetch data (always include ID for export marking but don't add to CSV headers unless requested)
$select_query_fields = array_keys($selected_fields);
if (!in_array('id', $select_query_fields)) {
    $select_query_fields[] = 'id';
}
$select_clause = implode(', ', $select_query_fields);

$query = "SELECT $select_clause FROM trustpilot_leads $where_clause ORDER BY scraped_at DESC";
$result = $conn->query($query);

if ($result) {
    $filename = "Trustpilot_Leads_" . date('Y-m-d_His') . ".csv";
    
    // Set headers for download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    
    // Dynamic Column Headers
    fputcsv($output, array_values($selected_fields));
    
    $exported_ids = [];
    
    // Data Rows
    while ($row = $result->fetch_assoc()) {
        $csv_row = [];
        foreach (array_keys($selected_fields) as $fid) {
            $val = $row[$fid] ?? '';
            
            // Cleanup Website URL if requested (Optional: Strip UTMs)
            if ($fid === 'website_url' && !empty($val)) {
                $val = explode('?', $val)[0];
            }
            
            $csv_row[] = $val;
        }
        fputcsv($output, $csv_row);
        $exported_ids[] = (int)$row['id'];
    }
    
    // Mark as exported in DB
    if (!empty($exported_ids)) {
        $id_string = implode(',', $exported_ids);
        $conn->query("UPDATE trustpilot_leads SET is_exported = 1 WHERE id IN ($id_string)");
    }
    
    fclose($output);
    exit;
} else {
    die("Error exporting data: " . $conn->error);
}
?>


