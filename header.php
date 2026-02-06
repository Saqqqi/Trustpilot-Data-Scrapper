<?php
// header.php - Dynamic Premium Header for Trustpilot Intel
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="sticky top-0 z-[100] w-full transition-all duration-300 border-b border-white/[0.05] bg-[#0b0f1a]/80 backdrop-blur-xl">
    <div class="max-w-[1800px] mx-auto px-6 h-20 flex items-center justify-between">
        
        <!-- Logo Section -->
        <div class="flex items-center gap-4 group cursor-pointer">
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
            
            <button onclick="typeof exportToCSV === 'function' ? exportToCSV() : alert('Not available on this page')" 
                    class="group relative px-6 py-2.5 bg-white text-dark font-black text-xs uppercase tracking-widest rounded-xl transition-all hover:scale-105 active:scale-95 flex items-center gap-2 overflow-hidden">
                <span class="relative z-10 flex items-center gap-2">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    Export Data
                </span>
                <div class="absolute inset-0 bg-gradient-to-r from-slate-100 to-white opacity-0 group-hover:opacity-100 transition-opacity"></div>
            </button>
        </div>

    </div>
</nav>

<!-- Scripts for Lucide -->
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
