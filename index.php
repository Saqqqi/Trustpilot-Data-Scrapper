<?php
// index.php - Lead Explorer with Advanced Filters & Flag System
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db.php';

// Handle incoming data from scraper
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if ($data) {
        $stmt = $conn->prepare("INSERT IGNORE INTO trustpilot_leads 
            (company_name, total_reviews, trust_score, category, address, phone_number, email, website_url, is_claimed, has_paid_subscription, negative_reply_rate, reply_time, use_ai, logo_url) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($data as $entry) {
            $email = ($entry['email'] === 'None' || empty($entry['email'])) ? null : $entry['email'];
            $stmt->bind_param("ssssssssssssss", 
                $entry['company_name'], $entry['total_reviews'], $entry['trust_score'], $entry['category'], 
                $entry['address'], $entry['phone_number'], $email, $entry['website_url'], 
                $entry['is_claimed'], $entry['has_paid_subscription'], $entry['negative_reply_rate'], 
                $entry['reply_time'], $entry['use_ai'], $entry['logo_url']
            );
            $stmt->execute();
        }
        $stmt->close();
        echo json_encode(["status" => "success"]); exit;
    }
}

// Build WHERE clause based on filters
$where_conditions = [];
$filter_category = $_GET['filter_category'] ?? '';
$filter_rating_min = $_GET['filter_rating_min'] ?? '';
$filter_rating_max = $_GET['filter_rating_max'] ?? '';
$filter_claimed = $_GET['filter_claimed'] ?? '';
$filter_website = $_GET['filter_website'] ?? '';
$filter_flagged = $_GET['filter_flagged'] ?? '';
$filter_company_size = $_GET['filter_company_size'] ?? '';

if ($filter_category) {
    $where_conditions[] = "category = '" . $conn->real_escape_string($filter_category) . "'";
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
if ($filter_flagged === '1') {
    $where_conditions[] = "is_flagged = 1";
}

// Company Size Filter (based on review count)
if ($filter_company_size === 'enterprise') {
    $where_conditions[] = "CAST(REPLACE(total_reviews, ',', '') AS UNSIGNED) >= 1000";
}
if ($filter_company_size === 'large') {
    $where_conditions[] = "CAST(REPLACE(total_reviews, ',', '') AS UNSIGNED) >= 500 AND CAST(REPLACE(total_reviews, ',', '') AS UNSIGNED) < 1000";
}
if ($filter_company_size === 'medium') {
    $where_conditions[] = "CAST(REPLACE(total_reviews, ',', '') AS UNSIGNED) >= 100 AND CAST(REPLACE(total_reviews, ',', '') AS UNSIGNED) < 500";
}
if ($filter_company_size === 'small') {
    $where_conditions[] = "CAST(REPLACE(total_reviews, ',', '') AS UNSIGNED) >= 10 AND CAST(REPLACE(total_reviews, ',', '') AS UNSIGNED) < 100";
}
if ($filter_company_size === 'startup') {
    $where_conditions[] = "CAST(REPLACE(total_reviews, ',', '') AS UNSIGNED) < 10";
}

$where_clause = count($where_conditions) > 0 ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Pagination Setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Get total count with filters
$count_query = "SELECT COUNT(*) as total FROM trustpilot_leads $where_clause";
$total_result = $conn->query($count_query);
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $per_page);

// Fetch paginated data with filters
$leads = [];
$query = "SELECT * FROM trustpilot_leads $where_clause ORDER BY scraped_at DESC LIMIT $per_page OFFSET $offset";
$result = $conn->query($query);
if ($result) {
    while($row = $result->fetch_assoc()) $leads[] = $row;
}

// Get all categories for filter
$cat_result = $conn->query("SELECT DISTINCT category FROM trustpilot_leads WHERE category IS NOT NULL AND category != '' ORDER BY category");
$all_categories = [];
if ($cat_result) {
    while($row = $cat_result->fetch_assoc()) {
        $all_categories[] = $row['category'];
    }
}

// Get flagged count
$flagged_result = $conn->query("SELECT COUNT(*) as count FROM trustpilot_leads WHERE is_flagged = 1");
$flagged_count = $flagged_result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <title>Lead Intelligence Bank | Trustpilot Intel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: { primary: '#00b67a', dark: '#0b0f1a', 'dark-card': '#161b2c' } } }
        }
    </script>
    <style>
        body { background: #0b0f1a; font-family: 'Plus Jakarta Sans', sans-serif; color: #f1f5f9; }
        .glass { background: rgba(22, 27, 44, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .grid-bg { background-image: radial-gradient(circle at 1px 1px, #1e293b 1px, transparent 0); background-size: 40px 40px; }
        tr:hover { background: rgba(255,255,255,0.02); }
        .pagination-btn { transition: all 0.2s; }
        .pagination-btn:hover:not(:disabled) { transform: translateY(-2px); }
        .pagination-active { background: #00b67a !important; color: white !important; }
        .flag-btn { cursor: pointer; transition: all 0.2s; }
        .flag-btn:hover { transform: scale(1.2); }
        .flag-active { color: #fbbf24; filter: drop-shadow(0 0 8px rgba(251, 191, 36, 0.5)); }
    </style>
</head>
<body class="min-h-screen pb-20 grid-bg">
    
    <?php include 'header.php'; ?>

    <main class="max-w-[1800px] mx-auto px-6 mt-10">
        
        <!-- Header Section -->
        <div class="flex flex-col lg:flex-row items-start lg:items-end justify-between gap-6 mb-10">
            <div>
                <h2 class="text-5xl font-black text-white mb-2 leading-none uppercase tracking-tighter">
                    Lead <span class="bg-gradient-to-r from-primary to-emerald-400 bg-clip-text text-transparent italic">Intelligence</span>
                </h2>
                <div class="flex items-center gap-4 mt-4">
                    <div class="flex items-center gap-2 px-4 py-2 bg-primary/10 rounded-full border border-primary/20">
                        <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                        <span class="text-[10px] font-black text-primary uppercase tracking-widest"><?php echo number_format($total_records); ?> FILTERED RECORDS</span>
                    </div>
                    <div class="flex items-center gap-2 px-4 py-2 bg-amber-500/10 rounded-full border border-amber-500/20">
                        <i data-lucide="flag" class="w-3 h-3 text-amber-500"></i>
                        <span class="text-[10px] font-black text-amber-500 uppercase tracking-widest"><?php echo $flagged_count; ?> FLAGGED</span>
                    </div>
                    <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">Page <?php echo $page; ?> of <?php echo max(1, $total_pages); ?></p>
                </div>
            </div>
        </div>

        <!-- Advanced Filters Bar -->
        <form method="GET" action="" class="glass p-6 rounded-3xl mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-4 mb-4">
                
                <!-- Category Filter -->
                <div class="flex flex-col gap-1">
                    <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest ml-2">Category</label>
                    <select name="filter_category" class="bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-xs font-semibold cursor-pointer outline-none">
                        <option value="">All Categories</option>
                        <?php foreach($all_categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $filter_category === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Rating Min -->
                <div class="flex flex-col gap-1">
                    <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest ml-2">Min Rating</label>
                    <select name="filter_rating_min" class="bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-xs font-semibold cursor-pointer outline-none">
                        <option value="">Any</option>
                        <option value="4.5" <?php echo $filter_rating_min === '4.5' ? 'selected' : ''; ?>>4.5+ ⭐</option>
                        <option value="4.0" <?php echo $filter_rating_min === '4.0' ? 'selected' : ''; ?>>4.0+ ⭐</option>
                        <option value="3.5" <?php echo $filter_rating_min === '3.5' ? 'selected' : ''; ?>>3.5+ ⭐</option>
                        <option value="3.0" <?php echo $filter_rating_min === '3.0' ? 'selected' : ''; ?>>3.0+ ⭐</option>
                    </select>
                </div>

                <!-- Rating Max -->
                <div class="flex flex-col gap-1">
                    <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest ml-2">Max Rating</label>
                    <select name="filter_rating_max" class="bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-xs font-semibold cursor-pointer outline-none">
                        <option value="">Any</option>
                        <option value="2.0" <?php echo $filter_rating_max === '2.0' ? 'selected' : ''; ?>>Below 2.0 ⭐</option>
                        <option value="3.0" <?php echo $filter_rating_max === '3.0' ? 'selected' : ''; ?>>Below 3.0 ⭐</option>
                        <option value="3.5" <?php echo $filter_rating_max === '3.5' ? 'selected' : ''; ?>>Below 3.5 ⭐</option>
                    </select>
                </div>

                <!-- Company Size Filter -->
                <div class="flex flex-col gap-1">
                    <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest ml-2">Company Size</label>
                    <select name="filter_company_size" class="bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-xs font-semibold cursor-pointer outline-none">
                        <option value="">All Sizes</option>
                        <option value="enterprise" <?php echo $filter_company_size === 'enterprise' ? 'selected' : ''; ?>>🏢 Enterprise (1000+ reviews)</option>
                        <option value="large" <?php echo $filter_company_size === 'large' ? 'selected' : ''; ?>>🏭 Large (500-999)</option>
                        <option value="medium" <?php echo $filter_company_size === 'medium' ? 'selected' : ''; ?>>🏪 Medium (100-499)</option>
                        <option value="small" <?php echo $filter_company_size === 'small' ? 'selected' : ''; ?>>🏠 Small (10-99)</option>
                        <option value="startup" <?php echo $filter_company_size === 'startup' ? 'selected' : ''; ?>>🚀 Startup (<10)</option>
                    </select>
                </div>

                <!-- Claimed Status -->
                <div class="flex flex-col gap-1">
                    <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest ml-2">Claim Status</label>
                    <select name="filter_claimed" class="bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-xs font-semibold cursor-pointer outline-none">
                        <option value="">All</option>
                        <option value="claimed" <?php echo $filter_claimed === 'claimed' ? 'selected' : ''; ?>>Claimed ✅</option>
                        <option value="unclaimed" <?php echo $filter_claimed === 'unclaimed' ? 'selected' : ''; ?>>Unclaimed 🔍</option>
                    </select>
                </div>

                <!-- Website Status -->
                <div class="flex flex-col gap-1">
                    <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest ml-2">Website</label>
                    <select name="filter_website" class="bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-xs font-semibold cursor-pointer outline-none">
                        <option value="">All</option>
                        <option value="has" <?php echo $filter_website === 'has' ? 'selected' : ''; ?>>Has Website ✅</option>
                        <option value="none" <?php echo $filter_website === 'none' ? 'selected' : ''; ?>>No Website ❌</option>
                    </select>
                </div>

                <!-- Flagged Filter -->
                <div class="flex flex-col gap-1">
                    <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest ml-2">Priority</label>
                    <select name="filter_flagged" class="bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-xs font-semibold cursor-pointer outline-none">
                        <option value="">All Leads</option>
                        <option value="1" <?php echo $filter_flagged === '1' ? 'selected' : ''; ?>>⭐ Flagged Only</option>
                    </select>
                </div>
            </div>

            <!-- Filter Actions -->
            <div class="flex gap-3">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-8 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-primary/20">
                    Apply Filters
                </button>
                <a href="index.php" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-8 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all border border-white/5">
                    Clear All
                </a>
            </div>
        </form>

        <!-- Data Table -->
        <div class="glass rounded-[40px] overflow-hidden border border-white/5 shadow-2xl mb-8">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white/[0.02] border-b border-white/5">
                        <tr>
                            <th class="px-4 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 text-center">Flag</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Company Profile</th>
                            <th class="px-6 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 text-center">Trust Score</th>
                            <th class="px-6 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Category</th>
                            <th class="px-6 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Contact Info</th>
                            <th class="px-6 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Status</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.03]">
                        <?php foreach ($leads as $l): 
                            $hasWeb = (!empty($l['website_url']) && $l['website_url'] !== 'None');
                            $isClaimed = ($l['is_claimed'] === 'Claimed profile');
                            $isFlagged = (int)$l['is_flagged'] === 1;
                        ?>
                            <tr class="lead-row group">
                                
                                <!-- Flag Column -->
                                <td class="px-4 py-6 text-center">
                                    <button onclick="toggleFlag(<?php echo $l['id']; ?>, this)" 
                                            class="flag-btn <?php echo $isFlagged ? 'flag-active' : 'text-slate-700'; ?>"
                                            data-flagged="<?php echo $isFlagged ? '1' : '0'; ?>">
                                        <i data-lucide="flag" class="w-5 h-5"></i>
                                    </button>
                                </td>

                                <!-- Company Profile -->
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-14 h-14 bg-white rounded-xl p-1.5 flex items-center justify-center shrink-0 shadow-lg border border-white/5">
                                            <img src="<?php echo $l['logo_url'] ?: 'https://ui-avatars.com/api/?name='.urlencode($l['company_name']).'&background=00b67a&color=fff'; ?>" 
                                                 class="max-w-full max-h-full object-contain rounded-lg">
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="font-bold text-base text-white truncate group-hover:text-primary transition-colors"><?php echo htmlspecialchars($l['company_name']); ?></h4>
                                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-1">ID-<?php echo str_pad($l['id'], 5, '0', STR_PAD_LEFT); ?></p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Trust Score -->
                                <td class="px-6 py-6">
                                    <div class="flex flex-col items-center">
                                        <span class="text-xl font-black text-primary mb-1">★ <?php echo (float)$l['trust_score']; ?></span>
                                        <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest"><?php echo $l['total_reviews']; ?> Reviews</span>
                                    </div>
                                </td>

                                <!-- Category -->
                                <td class="px-6 py-6">
                                    <span class="bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest inline-block">
                                        <?php echo htmlspecialchars($l['category']); ?>
                                    </span>
                                </td>

                                <!-- Contact -->
                                <td class="px-6 py-6">
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-300">
                                            <i data-lucide="mail" class="w-3.5 h-3.5 text-slate-500"></i>
                                            <span class="truncate max-w-[200px]"><?php echo $l['email'] ?: '<span class="italic opacity-30">Not Listed</span>'; ?></span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs font-semibold text-slate-300">
                                            <i data-lucide="phone" class="w-3.5 h-3.5 text-slate-500"></i>
                                            <span><?php echo $l['phone_number'] ?: '<span class="italic opacity-30">Unknown</span>'; ?></span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-6">
                                    <div class="flex flex-col gap-1.5">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full <?php echo $isClaimed ? 'bg-primary animate-pulse' : 'bg-slate-600'; ?>"></span>
                                            <span class="text-[10px] font-black uppercase tracking-widest <?php echo $isClaimed ? 'text-primary' : 'text-slate-500'; ?>">
                                                <?php echo $isClaimed ? 'Verified' : 'Unclaimed'; ?>
                                            </span>
                                        </div>
                                        <?php if ((int)($l['is_exported'] ?? 0) === 1): ?>
                                        <div class="flex items-center gap-2">
                                            <i data-lucide="check-check" class="w-3 h-3 text-indigo-400"></i>
                                            <span class="text-[9px] font-bold uppercase tracking-widest text-indigo-400">Exported</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Action -->
                                <td class="px-8 py-6 text-right">
                                    <?php if($hasWeb): ?>
                                        <a href="<?php echo $l['website_url']; ?>" target="_blank" 
                                           class="bg-primary hover:bg-primary-dark text-white px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-lg shadow-primary/20 inline-block">
                                            Visit Site
                                        </a>
                                    <?php else: ?>
                                        <span class="bg-red-500/10 text-red-500 border border-red-500/20 px-6 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest inline-block opacity-60">
                                            No Link
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Controls -->
        <?php if ($total_pages > 0): ?>
        <div class="flex flex-col md:flex-row items-center justify-between gap-6 glass p-6 rounded-3xl">
            <div class="text-sm font-bold text-slate-400">
                Showing <span class="text-primary"><?php echo $offset + 1; ?></span> to 
                <span class="text-primary"><?php echo min($offset + $per_page, $total_records); ?></span> of 
                <span class="text-primary"><?php echo number_format($total_records); ?></span> records
            </div>

            <div class="flex items-center gap-2">
                <?php
                $query_params = $_GET;
                unset($query_params['page']);
                $query_string = http_build_query($query_params);
                $base_url = '?' . ($query_string ? $query_string . '&' : '');
                ?>
                
                <!-- First Page -->
                <a href="<?php echo $base_url; ?>page=1" class="pagination-btn px-4 py-2 bg-slate-900 border border-white/5 rounded-xl text-sm font-bold <?php echo $page == 1 ? 'opacity-50 pointer-events-none' : 'hover:bg-slate-800'; ?>">
                    <i data-lucide="chevrons-left" class="w-4 h-4"></i>
                </a>

                <!-- Previous -->
                <a href="<?php echo $base_url; ?>page=<?php echo max(1, $page - 1); ?>" class="pagination-btn px-4 py-2 bg-slate-900 border border-white/5 rounded-xl text-sm font-bold <?php echo $page == 1 ? 'opacity-50 pointer-events-none' : 'hover:bg-slate-800'; ?>">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                </a>

                <!-- Page Numbers -->
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <a href="<?php echo $base_url; ?>page=<?php echo $i; ?>" 
                       class="pagination-btn px-4 py-2 bg-slate-900 border border-white/5 rounded-xl text-sm font-bold hover:bg-slate-800 <?php echo $i == $page ? 'pagination-active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <!-- Next -->
                <a href="<?php echo $base_url; ?>page=<?php echo min($total_pages, $page + 1); ?>" class="pagination-btn px-4 py-2 bg-slate-900 border border-white/5 rounded-xl text-sm font-bold <?php echo $page == $total_pages ? 'opacity-50 pointer-events-none' : 'hover:bg-slate-800'; ?>">
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </a>

                <!-- Last Page -->
                <a href="<?php echo $base_url; ?>page=<?php echo $total_pages; ?>" class="pagination-btn px-4 py-2 bg-slate-900 border border-white/5 rounded-xl text-sm font-bold <?php echo $page == $total_pages ? 'opacity-50 pointer-events-none' : 'hover:bg-slate-800'; ?>">
                    <i data-lucide="chevrons-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>

    </main>

    <script>
        lucide.createIcons();

        // Toggle Flag Function
        async function toggleFlag(leadId, button) {
            const currentStatus = parseInt(button.dataset.flagged);
            const newStatus = currentStatus === 1 ? 0 : 1;
            
            try {
                const response = await fetch('toggle_flag.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ lead_id: leadId, is_flagged: newStatus })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    button.dataset.flagged = newStatus;
                    if (newStatus === 1) {
                        button.classList.add('flag-active');
                        button.classList.remove('text-slate-700');
                    } else {
                        button.classList.remove('flag-active');
                        button.classList.add('text-slate-700');
                    }
                }
            } catch (error) {
                console.error('Flag toggle error:', error);
            }
        }

        function exportToCSV() {
            window.location.href = 'export.php<?php echo isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>';
        }
    </script>
</body>
</html>
