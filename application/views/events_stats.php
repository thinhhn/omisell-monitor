<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Statistics - Omisell Supervisord</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <?php $this->load->view('shared/header_styles'); ?>
</head>
<body class="bg-slate-50">

    <div class="min-h-screen flex flex-col lg:flex-row">
        
        <?php 
        $active_menu = 'events';
        $this->load->view('shared/sidebar'); 
        ?>

        <!-- Main Content -->
        <main class="flex-1 p-4 lg:p-8 main-content-offset">
            
            <!-- Header -->
            <header class="mb-8">
                <h2 class="text-3xl font-bold text-slate-900 mb-2">Event Statistics</h2>
                <p class="text-slate-500 text-sm">
                    Real-time event queue monitoring for all processes
                </p>
            </header>

            <!-- Flash Messages -->
            <?php if($this->session->flashdata('success')): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-700">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo $this->session->flashdata('success'); ?>
            </div>
            <?php endif; ?>
            
            <?php if($this->session->flashdata('error')): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo $this->session->flashdata('error'); ?>
            </div>
            <?php endif; ?>

            <!-- Event Stats Card -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-4 text-white">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-chart-bar text-2xl opacity-80"></i>
                            <div>
                                <h5 class="font-bold text-lg">Event Queue Statistics</h5>
                                <p class="text-xs opacity-90">Real-time process event monitoring</p>
                            </div>
                        </div>
                        <button onclick="loadStats()" 
                                class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition">
                            <i class="fas fa-sync-alt mr-2"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <!-- Stats Content -->
                <div id="stats-container" class="p-6">
                    <!-- Loading state will be inserted here -->
                </div>
            </div>

        </main>
    </div>

    <script>
    // Load stats (single call)
    function loadStats() {
        const container = document.getElementById('stats-container');
        
        // Show loading state
        container.innerHTML = `
            <div class="flex flex-col items-center justify-center py-8">
                <div class="loading-spinner mb-4"></div>
                <p class="text-slate-500 text-sm">Loading event statistics...</p>
                <p class="text-slate-400 text-xs mt-2">Connecting to server...</p>
            </div>
        `;
        
        // Fetch stats via AJAX
        fetch('<?php echo site_url('events/get_stats'); ?>')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayStats(data);
                } else {
                    displayError(data);
                }
            })
            .catch(error => {
                displayError({
                    error: 'Network error: ' + error.message
                });
            });
    }
    
    // Display stats
    function displayStats(data) {
        const container = document.getElementById('stats-container');
        const stats = data.stats;
        const total = data.total_events || 0;
        
        if (Object.keys(stats).length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-slate-400">
                    <i class="fas fa-inbox text-4xl mb-3"></i>
                    <p>No events found for this server</p>
                </div>
            `;
            return;
        }
        
        // Sort by event count descending
        const sortedStats = Object.entries(stats).sort((a, b) => b[1] - a[1]);
        
        let html = `
            <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-3 bg-blue-50 rounded-lg border border-blue-200">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-blue-700">Total Events</span>
                        <span class="text-2xl font-bold text-blue-600">${total.toLocaleString()}</span>
                    </div>
                </div>
                <div class="p-3 bg-emerald-50 rounded-lg border border-emerald-200">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-emerald-700">Active Processes</span>
                        <span class="text-2xl font-bold text-emerald-600">${Object.keys(stats).length}</span>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="p-3 text-left text-xs font-semibold text-slate-500 uppercase">Process</th>
                            <th class="p-3 text-right text-xs font-semibold text-slate-500 uppercase">Events</th>
                            <th class="p-3 text-right text-xs font-semibold text-slate-500 uppercase">Percentage</th>
                            <th class="p-3 text-right text-xs font-semibold text-slate-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
        `;
        
        sortedStats.forEach(([processName, count]) => {
            const percentage = total > 0 ? ((count / total) * 100).toFixed(1) : 0;
            const barWidth = percentage;
            
            html += `
                <tr class="hover:bg-slate-50">
                    <td class="p-3">
                        <div class="font-medium text-slate-700">${escapeHtml(processName)}</div>
                        <div class="mt-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600 transition-all duration-500" 
                                 style="width: ${barWidth}%"></div>
                        </div>
                    </td>
                    <td class="p-3 text-right">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-blue-100 text-blue-700">
                            ${count.toLocaleString()}
                        </span>
                    </td>
                    <td class="p-3 text-right text-slate-600 font-mono">${percentage}%</td>
                    <td class="p-3 text-right">
                        <button onclick="killProcess('${escapeHtml(processName)}')" 
                                class="text-slate-400 hover:text-red-600 transition" 
                                title="Kill process">
                            <i class="fas fa-skull-crossbones"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-xs text-slate-400 text-center">
                <i class="fas fa-info-circle mr-1"></i>
                Last updated: ${new Date().toLocaleTimeString()}
            </div>
        `;
        
        container.innerHTML = html;
    }
    
    // Display error
    function displayError(data) {
        const container = document.getElementById('stats-container');
        container.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-exclamation-triangle text-5xl text-red-300 mb-4"></i>
                <p class="text-red-600 font-medium mb-2">Failed to load stats</p>
                <p class="text-sm text-slate-500">${escapeHtml(data.error || 'Unknown error')}</p>
                ${data.raw_output ? `<details class="mt-4"><summary class="cursor-pointer text-xs text-slate-400">Show raw output</summary><pre class="mt-2 p-3 bg-slate-50 rounded text-xs text-left overflow-auto">${escapeHtml(data.raw_output)}</pre></details>` : ''}
                <button onclick="loadStats()" 
                        class="mt-4 px-4 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition">
                    <i class="fas fa-redo mr-2"></i>Retry
                </button>
            </div>
        `;
    }
    
    // Kill process
    function killProcess(processName) {
        if (!confirm(`Are you sure you want to KILL process "${processName}"?\n\nThis will force terminate the process immediately.`)) {
            return;
        }
        
        window.location.href = `<?php echo site_url('events/kill'); ?>/${encodeURIComponent(processName)}`;
    }
    
    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('mobile-menu-toggle');
        const sidebar = document.querySelector('.sidebar-fixed');
        const menuIcon = document.getElementById('menu-icon');
        const overlay = document.getElementById('mobile-overlay');
        
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                sidebar.classList.toggle('mobile-menu-open');
                if (overlay) overlay.classList.toggle('active');
                
                // Toggle icon
                if (sidebar.classList.contains('mobile-menu-open')) {
                    menuIcon.classList.remove('fa-bars');
                    menuIcon.classList.add('fa-times');
                } else {
                    menuIcon.classList.remove('fa-times');
                    menuIcon.classList.add('fa-bars');
                }
            });
            
            // Close menu when clicking overlay
            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('mobile-menu-open');
                    overlay.classList.remove('active');
                    menuIcon.classList.remove('fa-times');
                    menuIcon.classList.add('fa-bars');
                });
            }
        }
        
        // Load stats
        loadStats();
    });
    </script>

</body>
</html>
