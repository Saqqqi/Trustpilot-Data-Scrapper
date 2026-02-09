x`<?php
// header.php - Dynamic Premium Header for Trustpilot Intel
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="sticky top-0 z-[100] w-full transition-all duration-300 border-b border-white/[0.05] bg-[#0b0f1a]/80 backdrop-blur-xl">
    <div class="max-w-[1800px] mx-auto px-6 h-20 flex items-center justify-between">
        
        <!-- Logo Section -->
        <div class="flex items-center gap-4 group cursor-pointer" onclick="window.location.href='index.php'">
            <div class="relative">
                <div class="absolute -inset-1 bg-gradient-to-r from-primary to-emerald-400 rounded-xl blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
                <div class="relative bg-dark-card p-2.5 rounded-xl border border-white/10 flex items-center justify-center">
                    <i data-lucide="shield-check" class="text-primary w-6 h-6"></i>
                </div>
            </div>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-white leading-none">
                    Trustpilot <span class="bg-gradient-to-r from-primary to-emerald-400 bg-clip-text text-transparent italic">Intel</span>
                </h1>
                <p class="text-[9px] text-slate-500 font-black uppercase tracking-[0.2em] mt-1 opacity-70">Extraction Suite Pro</p>
            </div>
        </div>

        <!-- Navigation Section -->
        <div class="flex items-center bg-slate-900/50 p-1 rounded-2xl border border-white/5">
            <a href="index.php" 
               class="px-6 py-2.5 rounded-xl text-sm font-bold uppercase tracking-wider transition-all duration-300 flex items-center gap-2
               <?php echo ($current_page == 'index.php') ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5'; ?>">
                <i data-lucide="database" class="w-4 h-4"></i>
                Leads Explorer
            </a>
            <a href="categories.php" 
               class="px-6 py-2.5 rounded-xl text-sm font-bold uppercase tracking-wider transition-all duration-300 flex items-center gap-2
               <?php echo ($current_page == 'categories.php') ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'text-slate-400 hover:text-white hover:bg-white/5'; ?>">
                <i data-lucide="layers" class="w-4 h-4"></i>
                Categories Area
            </a>
        </div>

        <!-- Action Section -->
        <div class="flex items-center gap-4">
            <div class="hidden xl:flex items-center gap-3 px-4 py-2 bg-slate-900/40 rounded-full border border-white/5">
                <div class="w-2 h-2 rounded-full bg-primary animate-pulse"></div>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">System Operational</span>
            </div>
            
            <div class="h-8 w-px bg-white/10 mx-2"></div>
            
            <button onclick="openExportModal()" 
                    class="group relative px-6 py-2.5 bg-white text-dark font-black text-xs uppercase tracking-widest rounded-xl transition-all hover:scale-105 active:scale-95 flex items-center gap-2 overflow-hidden shadow-xl shadow-white/5">
                <span class="relative z-10 flex items-center gap-2">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    Export Intel
                </span>
                <div class="absolute inset-0 bg-gradient-to-r from-slate-100 to-white opacity-0 group-hover:opacity-100 transition-opacity"></div>
            </button>
        </div>

    </div>
</nav>

<!-- Export Modal -->
<div id="exportModal" class="fixed inset-0 z-[200] hidden items-center justify-center p-4">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-[#0b0f1a]/95 backdrop-blur-sm" onclick="closeExportModal()"></div>
    
    <!-- Modal Content -->
    <div class="relative bg-[#161b2c] w-full max-w-2xl rounded-[32px] border border-white/10 shadow-2xl p-8 overflow-hidden">
        <div class="absolute top-0 right-0 p-8">
            <button onclick="closeExportModal()" class="text-slate-500 hover:text-white transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>

        <div class="mb-8">
            <h3 class="text-3xl font-black text-white uppercase tracking-tighter">
                Export <span class="text-primary italic">Configuration</span>
            </h3>
            <p class="text-slate-500 text-xs font-bold uppercase tracking-widest mt-2">Customize your lead intelligence package</p>
        </div>

        <form id="exportForm" action="export.php" method="GET" class="space-y-6">
            <!-- Categories Grid -->
            <div class="space-y-3">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Target Categories</label>
                <div class="grid grid-cols-2 gap-3 max-h-[160px] overflow-y-auto pr-2 custom-scrollbar">
                    <?php
                    require_once 'db.php';
                    $cat_query = $conn->query("SELECT DISTINCT category FROM trustpilot_leads WHERE category IS NOT NULL AND category != '' ORDER BY category");
                    if ($cat_query):
                        while($cat = $cat_query->fetch_assoc()):
                    ?>
                    <label class="flex items-center gap-3 bg-slate-900/50 border border-white/5 p-3 rounded-xl cursor-pointer hover:bg-white/5 transition-colors group">
                        <input type="checkbox" name="filter_category[]" value="<?php echo htmlspecialchars($cat['category']); ?>" 
                               onchange="updateExportCount()" class="w-4 h-4 rounded border-white/10 bg-dark text-primary focus:ring-primary/20">
                        <span class="text-[11px] font-bold text-slate-300 group-hover:text-white"><?php echo htmlspecialchars($cat['category']); ?></span>
                    </label>
                    <?php endwhile; endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <!-- Rating Filter -->
                <div class="space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Min Rating</label>
                    <select name="filter_rating_min" onchange="updateExportCount()" class="w-full bg-slate-900 border border-white/5 rounded-xl px-4 py-3 text-xs font-bold text-white outline-none focus:border-primary/50 transition-colors">
                        <option value="">Any Rating</option>
                        <option value="4.5">4.5+ ★ Premium</option>
                        <option value="4.0">4.0+ ★ Good</option>
                        <option value="3.5">3.5+ ★ Average</option>
                        <option value="3.0">3.0+ ★ Below Avg</option>
                    </select>
                </div>

                <!-- Website Filter -->
                <div class="space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Website Type</label>
                    <select name="filter_website" onchange="updateExportCount()" class="w-full bg-slate-900 border border-white/5 rounded-xl px-4 py-3 text-xs font-bold text-white outline-none focus:border-primary/50 transition-colors">
                        <option value="">All Leads</option>
                        <option value="has">Has Website Link</option>
                        <option value="none">Ghost Leads (No Site)</option>
                    </select>
                </div>

                <!-- Company Size Filter -->
                <div class="space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Company Size (Reviews)</label>
                    <select name="filter_company_size" onchange="updateExportCount()" class="w-full bg-slate-900 border border-white/5 rounded-xl px-4 py-3 text-xs font-bold text-white outline-none focus:border-primary/50 transition-colors">
                        <option value="">All Sizes</option>
                        <option value="enterprise">🏢 Enterprise (1000+ reviews)</option>
                        <option value="large">🏭 Large (500-999)</option>
                        <option value="medium">🏪 Medium (100-499)</option>
                        <option value="small">🏠 Small (10-99)</option>
                        <option value="startup">🚀 Startup (<10)</option>
                    </select>
                </div>

                <!-- Memory Filter -->
                <div class="space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Export Memory</label>
                    <div class="flex gap-2">
                        <div class="flex-1 bg-slate-900/50 border border-white/5 rounded-xl px-4 py-2 flex items-center justify-between">
                            <span class="text-[9px] font-bold text-slate-400">Ignore Previous?</span>
                            <label class="relative inline-flex items-center cursor-pointer scale-90">
                                <input type="checkbox" name="include_exported" value="0" checked onchange="this.value = this.checked ? '0' : '1'; updateExportCount()" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            </label>
                        </div>
                        <button type="button" onclick="resetExportHistory()" class="bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 text-red-500 px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all flex items-center gap-2">
                            <i data-lucide="rotate-ccw" class="w-3 h-3"></i>
                            Reset History
                        </button>
                    </div>
                </div>
            </div>

            <!-- Fields Selection Grid -->
            <div class="space-y-3">
                <div class="flex items-center justify-between ml-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Data Fields Selection</label>
                    <button type="button" onclick="toggleAllFields(this)" class="text-[9px] font-black text-primary uppercase tracking-widest hover:text-white transition-colors">Select None</button>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <?php
                    $fields = [
                        ['id' => 'company_name', 'label' => 'Company Name'],
                        ['id' => 'category', 'label' => 'Category'],
                        ['id' => 'trust_score', 'label' => 'Trust Score'],
                        ['id' => 'total_reviews', 'label' => 'Total Reviews'],
                        ['id' => 'email', 'label' => 'Email Address'],
                        ['id' => 'website_url', 'label' => 'Website URL'],
                        ['id' => 'phone_number', 'label' => 'Phone Number'],
                        ['id' => 'address', 'label' => 'Physical Address'],
                        ['id' => 'is_claimed', 'label' => 'Verification Status'],
                        ['id' => 'scraped_at', 'label' => 'Extraction Date']
                    ];
                    foreach($fields as $f):
                    ?>
                    <label class="flex items-center gap-3 bg-slate-900/50 border border-white/5 p-3 rounded-xl cursor-pointer hover:bg-white/5 transition-colors group">
                        <input type="checkbox" name="export_fields[]" value="<?php echo $f['id']; ?>" checked 
                               class="field-checkbox w-4 h-4 rounded border-white/10 bg-dark text-primary focus:ring-primary/20">
                        <span class="text-[10px] font-bold text-slate-300 group-hover:text-white"><?php echo $f['label']; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Stats & Footer -->
            <div class="pt-6 border-t border-white/5 flex items-center justify-between">
                <div>
                    <span class="block text-[9px] font-black text-slate-500 uppercase tracking-widest">Total Matching Leads</span>
                    <span id="exportCountDisplay" class="text-2xl font-black text-primary">--</span>
                </div>
                <button type="submit" class="bg-primary hover:bg-emerald-500 text-white px-10 py-4 rounded-2xl text-xs font-black uppercase tracking-widest transition-all shadow-xl shadow-primary/20 hover:scale-105 active:scale-95 flex items-center gap-2">
                    <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                    Process Export
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #00b67a; }
</style>

<script>
    function openExportModal() {
        const modal = document.getElementById('exportModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        updateExportCount();
    }

    function closeExportModal() {
        const modal = document.getElementById('exportModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function toggleAllFields(btn) {
        const checkboxes = document.querySelectorAll('.field-checkbox');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        
        checkboxes.forEach(cb => cb.checked = !allChecked);
        btn.innerText = allChecked ? 'Select All' : 'Select None';
    }

    async function resetExportHistory() {
        if (!confirm('Are you sure you want to reset all leads to "Unexported"? This will allow you to download all leads again.')) return;
        
        try {
            const response = await fetch('reset_exports.php', { method: 'POST' });
            const data = await response.json();
            if (data.success) {
                alert(data.message);
                updateExportCount();
                if (window.location.pathname.includes('index.php')) {
                    window.location.reload();
                }
            } else {
                alert('Error resetting history: ' + data.message);
            }
        } catch (e) {
            alert('A technical error occurred.');
        }
    }

    async function updateExportCount() {
        const form = document.getElementById('exportForm');
        const formData = new FormData(form);
        const params = new URLSearchParams();
        
        for (const [key, value] of formData) {
            params.append(key, value);
        }

        const display = document.getElementById('exportCountDisplay');
        display.innerHTML = '<span class="animate-pulse opacity-50">...</span>';

        try {
            const response = await fetch('get_count.php?' + params.toString());
            const data = await response.json();
            display.innerText = data.total.toLocaleString();
        } catch (e) {
            display.innerText = 'Error';
        }
    }

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>

