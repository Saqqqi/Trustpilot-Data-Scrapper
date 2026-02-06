<?php
// categories.php - Category Browse with Direct Filtering
require_once 'db.php';

// Fetch distribution
$result = $conn->query("SELECT category, COUNT(*) as lead_count FROM trustpilot_leads GROUP BY category ORDER BY lead_count DESC");
$categories = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $name = $row['category'] ?: 'Uncategorized';
        $categories[] = [
            'name' => $name,
            'count' => (int)$row['lead_count']
        ];
    }
}

// Grouping logic for "Market Density"
$ranges = [
    'Mega Hubs' => [],
    'Strategic' => [],
    'Emerging' => [],
    'Niche' => []
];

foreach ($categories as $cat) {
    if ($cat['count'] > 50) $ranges['Mega Hubs'][] = $cat;
    elseif ($cat['count'] >= 21) $ranges['Strategic'][] = $cat;
    elseif ($cat['count'] >= 11) $ranges['Emerging'][] = $cat;
    else $ranges['Niche'][] = $cat;
}
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <title>Categories Area | Trustpilot Intel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        .cat-card:hover { transform: translateY(-2px); border-color: #00b67a; background: rgba(0, 182, 122, 0.05); }
        .tab-active { background: #00b67a; color: white; border-color: #00b67a; }
    </style>
</head>
<body class="min-h-screen pb-20 grid-bg">
    
    <?php include 'header.php'; ?>

    <main class="max-w-[1700px] mx-auto px-6 mt-10">
        
        <!-- Search & Compact Title -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-6 mb-10">
            <div>
                <h2 class="text-3xl font-black text-white uppercase tracking-tighter">Market <span class="text-primary italic">Architecture</span></h2>
                <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mt-1"><?php echo count($categories); ?> Segments Discovered</p>
            </div>
            
            <div class="relative w-full md:w-96">
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 w-4 h-4"></i>
                <input type="text" id="catSearch" placeholder="Quick find category..." 
                       class="w-full bg-slate-900/80 border border-white/10 rounded-xl py-2.5 pl-10 pr-4 text-sm outline-none focus:ring-2 focus:ring-primary/40 text-slate-200">
            </div>
        </div>

        <!-- Density Tabs -->
        <div class="flex flex-wrap gap-2 mb-8 border-b border-white/5 pb-6">
            <button onclick="switchTab('all')" id="btn-all" class="tab-btn tab-active px-5 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest border border-white/10 transition-all">All Groups</button>
            <?php foreach($ranges as $label => $group): ?>
                <button onclick="switchTab('<?php echo preg_replace('/[^a-z]/i', '', $label); ?>')" 
                        id="btn-<?php echo preg_replace('/[^a-z]/i', '', $label); ?>"
                        class="tab-btn px-5 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest border border-white/10 text-slate-400 hover:text-white transition-all">
                    <?php echo $label; ?> (<?php echo count($group); ?>)
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Compressed Content Area -->
        <div id="categoryContainer">
            <?php foreach ($ranges as $label => $group): if (empty($group)) continue; 
                $slug = preg_replace('/[^a-z]/i', '', $label);
            ?>
                <div class="group-section mb-10" id="section-<?php echo $slug; ?>" data-slug="<?php echo $slug; ?>">
                    <div class="flex items-center gap-4 mb-6">
                        <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-primary bg-primary/10 px-3 py-1 rounded-md"><?php echo $label; ?></h3>
                        <div class="h-px bg-white/5 flex-1"></div>
                    </div>
                    
                    <!-- Dense Grid: 6 columns on desktop -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                        <?php foreach ($group as $cat): ?>
                            <a href="index.php?filter_category=<?php echo urlencode($cat['name']); ?>" 
                               class="cat-card glass p-4 rounded-xl border border-white/5 transition-all flex flex-col items-center group relative min-h-[100px] justify-center text-center">
                                
                                <div class="w-10 h-10 bg-slate-900 rounded-xl flex items-center justify-center mb-2 border border-white/5 group-hover:bg-primary transition-all shadow-md">
                                    <i data-lucide="folder" class="text-slate-500 group-hover:text-white w-5 h-5 transition-colors"></i>
                                </div>
                                
                                <h4 class="font-bold text-white text-xs mb-1 group-hover:text-primary transition-all line-clamp-1 px-2"><?php echo htmlspecialchars($cat['name']); ?></h4>
                                
                                <div class="text-[9px] font-black text-slate-500 uppercase tracking-widest">
                                    <?php echo $cat['count']; ?> Companies
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Empty Results -->
        <div id="noResults" class="hidden glass p-20 rounded-[40px] text-center">
            <i data-lucide="search-slash" class="w-12 h-12 text-slate-700 mx-auto mb-4"></i>
            <h3 class="text-xl font-bold text-white uppercase tracking-tighter">No Segments Found</h3>
            <p class="text-slate-500 text-sm">Try a different search term or check another density group.</p>
        </div>

    </main>

    <script>
        lucide.createIcons();

        // 1. Tab Switching Logic
        function switchTab(slug) {
            // Update buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('tab-active', 'text-white');
                btn.classList.add('text-slate-400');
            });
            document.getElementById('btn-' + slug).classList.add('tab-active', 'text-white');

            // Show/Hide sections
            const sections = document.querySelectorAll('.group-section');
            if (slug === 'all') {
                sections.forEach(s => s.style.display = 'block');
            } else {
                sections.forEach(s => {
                    s.style.display = (s.dataset.slug === slug) ? 'block' : 'none';
                });
            }
        }

        // 2. Real-time Category Search
        const searchInput = document.getElementById('catSearch');
        const cards = document.querySelectorAll('.cat-card');
        const sections = document.querySelectorAll('.group-section');
        const noResults = document.getElementById('noResults');

        searchInput.addEventListener('input', (e) => {
            const val = e.target.value.toLowerCase();
            let totalFound = 0;

            cards.forEach(card => {
                const text = card.innerText.toLowerCase();
                if (text.includes(val)) {
                    card.style.display = 'flex';
                    totalFound++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Hide/Show sections based on if they have visible children
            sections.forEach(section => {
                const visibleCards = section.querySelectorAll('.cat-card[style="display: flex;"]');
                section.style.display = (visibleCards.length > 0) ? 'block' : 'none';
            });

            // If nothing found globally
            noResults.style.display = (totalFound === 0) ? 'block' : 'none';
            
            // If searching, reset tab state visually to "All"
            if (val.length > 0) {
                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('tab-active'));
                document.getElementById('btn-all').classList.add('tab-active');
            }
        });
    </script>
</body>
</html>
