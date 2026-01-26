<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisord</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <?php $this->load->view('shared/header_styles'); ?>
</head>
<body class="bg-slate-50">

    <?php
    // Calculate overall statistics
    $total_servers = count($servers);
    $total_processes = 0;
    $running_processes = 0;
    $stopped_processes = 0;
    $fatal_processes = 0;
    $errors = 0;
    
    foreach ($list as $server_name => $procs) {
        if (isset($procs['error'])) {
            $errors++;
            continue;
        }
        if (is_array($procs)) {
            foreach ($procs as $proc) {
                if (is_array($proc)) {
                    $total_processes++;
                    $status = isset($proc['statename']) ? $proc['statename'] : 'UNKNOWN';
                    if ($status === 'RUNNING') $running_processes++;
                    else if (in_array($status, ['STOPPED', 'EXITED'])) $stopped_processes++;
                    else if ($status === 'FATAL') $fatal_processes++;
                }
            }
        }
    }
    
    // Server groups for organization
    $server_groups = [
        'Main Platform' => [
            'icon' => 'fa-globe',
            'color' => 'blue',
            'servers' => [
                'web' => ['web_001', 'web_002'],
                'celery' => ['celery_hook', 'celery_001', 'celery_002', 'celery_003', 'celery_004', 'celery_005', 'celery_006', 'celery_007']
            ]
        ],
        'Omni Platform' => [
            'icon' => 'fa-shopping-cart',
            'color' => 'purple',
            'servers' => [
                'web' => ['web_omni_001', 'web_omni_002'],
                'celery' => ['celery_omni']
            ]
        ]
    ];
    ?>

    <div class="min-h-screen flex flex-col">
        
        <?php 
        $active_menu = 'dashboard';
        $this->load->view('shared/sidebar'); 
        ?>

        <!-- Main Content -->
        <main class="flex-1 p-4 lg:p-8 main-content-offset">
            
            <!-- Header -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-4">
                <div>
                    <h2 class="text-3xl font-bold text-slate-900">Supervisord</h2>
                    <p class="text-slate-500 text-sm mt-1">
                        <i class="fas fa-sync-alt mr-1"></i>Auto-refresh in <span id="countdown-header" class="font-mono font-bold text-blue-600"><?php echo $this->config->item('refresh'); ?></span> seconds
                    </p>
                </div>
                <div class="flex gap-3 flex-wrap">
                    <button onclick="refreshData();" id="refresh-btn" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-sm font-medium hover:bg-slate-50 transition shadow-sm">
                        <i class="fas fa-sync-alt mr-2 text-slate-400" id="refresh-icon"></i> Refresh
                    </button>
                    <a href="?mute=<?php echo ($muted?-1:1);?>" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-sm font-medium hover:bg-slate-50 transition shadow-sm">
                        <i class="fas fa-<?php echo $muted ? 'volume-mute' : 'volume-up'; ?> mr-2"></i> <?php echo $muted ? 'Unmute' : 'Mute'; ?>
                    </a>
                </div>
            </header>

            <!-- Statistics Dashboard -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
                <!-- Total Processes -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 hover:shadow-md transition">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-slate-500 text-sm font-medium">Total Processes</h3>
                        <i class="fas fa-cubes text-2xl text-slate-300"></i>
                    </div>
                    <p class="text-3xl font-bold text-slate-800"><?php echo $total_processes; ?></p>
                    <p class="text-xs text-slate-400 mt-2"><?php echo $total_servers; ?> servers</p>
                </div>
                
                <!-- Running -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-emerald-200 hover:shadow-md transition">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-emerald-600 text-sm font-medium">Running</h3>
                        <i class="fas fa-check-circle text-2xl text-emerald-200"></i>
                    </div>
                    <p class="text-3xl font-bold text-emerald-600"><?php echo $running_processes; ?></p>
                    <p class="text-xs text-emerald-500 mt-2">
                        <?php echo $running_processes; ?> / <?php echo $total_processes; ?> processes (<?php echo $total_processes > 0 ? round($running_processes/$total_processes*100) : 0; ?>%)
                    </p>
                </div>
                
                <!-- Stopped / Fatal -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-red-200 hover:shadow-md transition">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-red-600 text-sm font-medium">Stopped / Fatal</h3>
                        <i class="fas fa-exclamation-circle text-2xl text-red-200"></i>
                    </div>
                    <p class="text-3xl font-bold text-red-600"><?php echo $stopped_processes + $fatal_processes; ?></p>
                    <p class="text-xs text-red-500 mt-2">
                        <?php if ($fatal_processes > 0): ?>
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $fatal_processes; ?> fatal
                        <?php else: ?>
                            All systems operational
                        <?php endif; ?>
                    </p>
                </div>
                
                <!-- Server Errors -->
                <div class="bg-white p-5 rounded-xl shadow-sm border border-<?php echo $errors > 0 ? 'orange' : 'slate'; ?>-200 hover:shadow-md transition">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-<?php echo $errors > 0 ? 'orange' : 'blue'; ?>-600 text-sm font-medium">Connection</h3>
                        <i class="fas fa-network-wired text-2xl text-<?php echo $errors > 0 ? 'orange' : 'blue'; ?>-200"></i>
                    </div>
                    <p class="text-3xl font-bold text-<?php echo $errors > 0 ? 'orange' : 'blue'; ?>-600">
                        <?php echo $total_servers - $errors; ?>/<?php echo $total_servers; ?>
                    </p>
                    <p class="text-xs text-slate-400 mt-2">
                        <?php echo $errors > 0 ? "$errors connection errors" : "All servers online"; ?>
                    </p>
                </div>
            </div>

            <!-- Server Groups -->
            <?php foreach ($server_groups as $group_name => $group_config): ?>
            <?php 
                $safe_group_name = preg_replace('/[^a-zA-Z0-9]/', '_', $group_name);
            ?>
            <section class="mb-8">
                <div class="flex items-center justify-between mb-4 p-4 bg-slate-50 rounded-lg cursor-pointer hover:bg-slate-100 transition" onclick="toggleGroup('group-<?php echo $safe_group_name; ?>')">
                    <h3 class="text-xl font-bold text-slate-800 flex items-center gap-3 flex-1">
                        <span class="w-1 h-8 bg-<?php echo $group_config['color']; ?>-500 rounded-full"></span>
                        <i class="fas <?php echo $group_config['icon']; ?> text-<?php echo $group_config['color']; ?>-500"></i>
                        <?php echo $group_name; ?>
                    </h3>
                    <button class="toggle-icon-<?php echo $safe_group_name; ?> px-3 py-1 bg-<?php echo $group_config['color']; ?>-500 text-white rounded-lg">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                </div>
                
                <div id="group-<?php echo $safe_group_name; ?>" class="group-content">
                
                <?php foreach ($group_config['servers'] as $type_name => $server_names): ?>
                <div class="mb-6">
                    <h4 class="text-sm font-semibold text-slate-600 uppercase tracking-wide mb-3 flex items-center gap-2">
                        <i class="fas fa-<?php echo $type_name === 'web' ? 'globe' : 'cog'; ?> text-slate-400"></i>
                        <?php echo ucfirst($type_name); ?> Servers
                    </h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($server_names as $server_name): 
                            if (!isset($servers[$server_name])) continue;
                            $server_config = $servers[$server_name];
                            $procs = isset($list[$server_name]) ? $list[$server_name] : ['error' => 'No data'];
                            $has_error = isset($procs['error']);
                        ?>
                        
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden group-card">
                            <!-- Server Header -->
                            <div class="bg-gradient-to-r from-<?php echo $group_config['color']; ?>-500 to-<?php echo $group_config['color']; ?>-600 p-4 text-white">
                                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-3 flex-wrap">
                                                <div class="flex items-center gap-2">
                                    <i class="fas fa-server text-2xl opacity-80"></i>
                                    <h5 class="font-bold text-lg"><?php echo $server_name; ?></h5>
                                </div>
                                                <?php if (!$has_error): 
                                                    $server_procs = is_array($procs) ? $procs : [];
                                                    $server_total = count($server_procs);
                                                    $server_running = 0;
                                                    foreach ($server_procs as $p) {
                                                        if (is_array($p) && isset($p['statename']) && $p['statename'] === 'RUNNING') {
                                                            $server_running++;
                                                        }
                                                    }
                                                ?>
                                                <!-- Process Summary: X/Y Running -->
                                                <span id="server-<?php echo $server_name; ?>-summary" class="px-3 py-1 bg-white bg-opacity-20 rounded-full text-sm font-bold">
                                                    <?php echo $server_running; ?>/<?php echo $server_total; ?> Running
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex items-center gap-3 text-xs opacity-90 mt-2 flex-wrap">
                                                <span>
                                                    <i class="fas fa-network-wired mr-1"></i>
                                                    <?php 
                                                    $url = parse_url($server_config['url']);
                                                    echo $url['host'] . ':' . $url['port'];
                                                    ?>
                                                </span>
                                                <?php 
                                                $server_stats = isset($stats[$server_name]) ? $stats[$server_name] : ['available' => false];
                                                if ($server_stats['available']):
                                                    $cpu_percent = isset($server_stats['cpu_percent']) ? $server_stats['cpu_percent'] : 0;
                                                    $mem_percent = isset($server_stats['memory_percent']) ? $server_stats['memory_percent'] : 0;
                                                    $cpu_color = $cpu_percent > 80 ? 'red' : ($cpu_percent > 60 ? 'yellow' : 'emerald');
                                                    $mem_color = $mem_percent > 80 ? 'red' : ($mem_percent > 60 ? 'yellow' : 'emerald');
                                                ?>
                                                <span class="flex items-center gap-1">
                                                    <i class="fas fa-microchip mr-1"></i>
                                                    <span id="server-<?php echo $server_name; ?>-cpu" class="font-mono text-<?php echo $cpu_color; ?>-300"><?php echo round($cpu_percent, 1); ?>%</span>
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <i class="fas fa-memory mr-1"></i>
                                                    <span id="server-<?php echo $server_name; ?>-mem" class="font-mono text-<?php echo $mem_color; ?>-300"><?php echo round($mem_percent, 1); ?>%</span>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if (!$has_error): ?>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <button onclick="controlAction('startall', '<?php echo $server_name; ?>', '')" class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition" title="Start All">
                                            <i class="fas fa-play text-sm"></i>
                                        </button>
                                        <button onclick="controlAction('restartall', '<?php echo $server_name; ?>', '')" class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition" title="Restart All">
                                            <i class="fas fa-redo text-sm"></i>
                                        </button>
                                        <button onclick="controlAction('stopall', '<?php echo $server_name; ?>', '')" class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition" title="Stop All">
                                            <i class="fas fa-stop text-sm"></i>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Server Content -->
                            <?php if ($has_error): ?>
                            <div class="p-6 text-center">
                                <i class="fas fa-exclamation-triangle text-5xl text-red-300 mb-4"></i>
                                <p class="text-red-600 font-medium mb-2">Connection Error</p>
                                <p class="text-sm text-slate-500"><?php echo htmlspecialchars($procs['error']); ?></p>
                                <a href="<?php echo site_url('debug/testConnections'); ?>" class="inline-block mt-4 px-4 py-2 bg-red-100 text-red-600 rounded-lg text-sm hover:bg-red-200 transition">
                                    Test Connection
                                </a>
                            </div>
                            <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-xs font-semibold">
                                        <tr>
                                            <th class="p-3">Process</th>
                                            <th class="p-3 text-right" width="180">Status</th>
                                            <th class="p-3 text-right" width="80">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <?php 
                                        if (is_array($procs) && !empty($procs)):
                                            foreach($procs as $item):
                                                if (!is_array($item)) continue;
                                                
                                                $item_name = ($item['group'] != $item['name']) 
                                                    ? $item['group'] . ':' . $item['name'] 
                                                    : $item['name'];
                                                
                                                $status = $item['statename'] ?? 'UNKNOWN';
                                                $description = $item['description'] ?? '';
                                                $pid = $uptime = '';
                                                
                                                if ($status == 'RUNNING' && $description) {
                                                    $desc_parts = explode(',', $description);
                                                    $pid = isset($desc_parts[0]) ? str_replace('pid ', '', trim($desc_parts[0])) : '';
                                                    $uptime = isset($desc_parts[1]) ? str_replace('uptime ', '', trim($desc_parts[1])) : '';
                                                }
                                                
                                                $status_lower = strtolower($status);
                                                $status_class = "status-$status_lower";
                                        ?>
                                        <?php
                                            $row_id = 'process-' . $server_name . '-' . preg_replace('/[^a-zA-Z0-9]/', '_', $item_name);
                                        ?>
                                        <tr class="hover:bg-slate-50" id="<?php echo $row_id; ?>">
                                            <td class="p-3">
                                                <div class="font-medium text-slate-700"><?php echo htmlspecialchars($item_name); ?></div>
                                                <?php if ($uptime): ?>
                                                <div class="text-xs text-slate-400 mt-2 uptime-cell">
                                                    <i class="fas fa-clock mr-1"></i><?php echo $uptime; ?>
                                                </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3 text-right">
                                                <div class="inline-block text-left">
                                                    <span class="status-cell <?php echo $status_class; ?> px-3 py-1.5 rounded-lg text-xs font-bold inline-flex items-center gap-1.5 shadow-sm">
                                                        <?php echo $status; ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="p-3 text-right actions-cell">
                                                <div class="inline-flex gap-2">
                                                    <?php if ($status == 'RUNNING'): ?>
                                                        <button onclick="controlAction('stop', '<?php echo $server_name; ?>', '<?php echo addslashes($item_name); ?>')" 
                                                           class="text-slate-400 hover:text-red-600 transition" title="Stop">
                                                            <i class="fas fa-stop"></i>
                                                        </button>
                                                        <button onclick="controlAction('restart', '<?php echo $server_name; ?>', '<?php echo addslashes($item_name); ?>')" 
                                                           class="text-slate-400 hover:text-blue-600 transition" title="Restart">
                                                            <i class="fas fa-redo"></i>
                                                        </button>
                                                    <?php elseif (in_array($status, ['STOPPED', 'EXITED', 'FATAL'])): ?>
                                                        <button onclick="controlAction('start', '<?php echo $server_name; ?>', '<?php echo addslashes($item_name); ?>')" 
                                                           class="text-slate-400 hover:text-emerald-600 transition" title="Start">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php 
                                            endforeach;
                                        else:
                                        ?>
                                        <tr>
                                            <td colspan="3" class="p-6 text-center text-slate-400">
                                                <i class="fas fa-inbox text-3xl mb-2"></i>
                                                <p>No processes found</p>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </section>
            <?php endforeach; ?>

        </main>
    </div>

    <script>
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
    });
    
    // Toast notification
    function showNotification(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed top-20 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
        toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} mr-2"></i>${message}`;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
    
    // Loading overlay
    function showLoading(message = 'Processing...') {
        let overlay = document.getElementById('loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]';
            overlay.innerHTML = `
                <div class="bg-white rounded-lg p-6 shadow-xl flex flex-col items-center gap-4">
                    <div class="loading-spinner"></div>
                    <p class="text-slate-700 font-medium" id="loading-message">${message}</p>
                </div>
            `;
            document.body.appendChild(overlay);
        } else {
            document.getElementById('loading-message').textContent = message;
            overlay.classList.remove('hidden');
        }
    }
    
    function hideLoading() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.classList.add('hidden');
        }
    }
    
    // AJAX Control Action
    function controlAction(action, server, worker) {
        const url = worker 
            ? `<?php echo site_url(); ?>/control/${action}/${server}/${encodeURIComponent(worker)}?ajax=1`
            : `<?php echo site_url(); ?>/control/${action}/${server}?ajax=1`;
        
        const actionNames = {
            'start': 'Starting',
            'stop': 'Stopping',
            'restart': 'Restarting',
            'startall': 'Starting all processes',
            'stopall': 'Stopping all processes',
            'restartall': 'Restarting all processes'
        };
        
        const loadingMsg = actionNames[action] || 'Processing';
        showLoading(`${loadingMsg}... Please wait.`);
        
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Server returned HTML instead of JSON. Please check server configuration.');
                }
                return response.json();
            })
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification(data.message, 'success');
                    // Refresh data after 2 seconds
                    setTimeout(() => refreshData(), 2000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                showNotification('Error: ' + error.message, 'error');
                console.error('Control action error:', error);
            });
    }
    
    // AJAX Refresh Data - Update DOM without page reload
    function refreshData() {
        const refreshIcon = document.getElementById('refresh-icon');
        const refreshBtn = document.getElementById('refresh-btn');
        
        if (refreshIcon) {
            refreshIcon.classList.add('fa-spin');
        }
        if (refreshBtn) {
            refreshBtn.disabled = true;
        }
        
        fetch('<?php echo site_url("welcome/getData"); ?>', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.servers) {
                    updateServerData(data.servers);
                    console.log('Data refreshed successfully');
                } else {
                    showNotification('Failed to refresh data', 'error');
                }
            })
            .catch(error => {
                console.error('Refresh error:', error);
                showNotification('Error: ' + error.message, 'error');
            })
            .finally(() => {
                if (refreshIcon) {
                    refreshIcon.classList.remove('fa-spin');
                }
                if (refreshBtn) {
                    refreshBtn.disabled = false;
                }
            });
    }
    
    // Update server data in DOM
    function updateServerData(servers) {
        Object.keys(servers).forEach(serverName => {
            const serverData = servers[serverName];
            const processes = serverData.processes;
            
            if (!Array.isArray(processes)) {
                console.warn('Invalid process data for server:', serverName);
                return;
            }
            
            // Update each process status
            processes.forEach(proc => {
                const processName = proc.name || proc.group + ':' + proc.name;
                const rowId = 'process-' + serverName + '-' + processName.replace(/[^a-zA-Z0-9]/g, '_');
                const row = document.getElementById(rowId);
                
                if (row) {
                    // Update status badge
                    const statusCell = row.querySelector('.status-cell');
                    if (statusCell) {
                        const status = proc.statename || 'UNKNOWN';
                        const statusClass = getStatusClass(status);
                        statusCell.className = `status-cell px-3 py-1.5 rounded-lg text-xs font-bold inline-flex items-center gap-1.5 shadow-sm ${statusClass}`;
                        statusCell.textContent = status;
                    }
                    
                    // Update action buttons based on status
                    const actionsCell = row.querySelector('.actions-cell');
                    if (actionsCell) {
                        const status = proc.statename || 'UNKNOWN';
                        actionsCell.innerHTML = getActionButtons(serverName, processName, status);
                    }
                    
                    // Update uptime - extract only uptime, remove PID
                    const uptimeCell = row.querySelector('.uptime-cell');
                    if (uptimeCell) {
                        if (proc.description) {
                            // Extract uptime from description
                            // Description format: "pid 12345, uptime 2 days, 5:30:45"
                            let uptime = proc.description;
                            // Remove "pid XXXX," part
                            uptime = uptime.replace(/pid\s+\d+,\s*/i, '');
                            // Remove "uptime " prefix if present
                            uptime = uptime.replace(/uptime\s+/i, '');
                            // Trim whitespace
                            uptime = uptime.trim();
                            
                            uptimeCell.innerHTML = `<i class="fas fa-clock mr-1"></i>${uptime}`;
                            uptimeCell.style.display = 'block';
                        } else {
                            uptimeCell.style.display = 'none';
                        }
                    }
                }
            });
            
            // Update server summary (X/Y Running)
            const summaryEl = document.querySelector(`#server-${serverName}-summary`);
            if (summaryEl) {
                const total = processes.length;
                const running = processes.filter(p => p.statename === 'RUNNING').length;
                summaryEl.textContent = `${running}/${total} Running`;
            }
            
            // Update CPU/Memory stats if available
            const stats = serverData.stats;
            if (stats && stats.available) {
                const cpuEl = document.querySelector(`#server-${serverName}-cpu`);
                const memEl = document.querySelector(`#server-${serverName}-mem`);
                
                if (cpuEl && stats.cpu_percent !== undefined) {
                    cpuEl.textContent = Math.round(stats.cpu_percent * 10) / 10 + '%';
                }
                if (memEl && stats.memory_percent !== undefined) {
                    memEl.textContent = Math.round(stats.memory_percent * 10) / 10 + '%';
                }
            }
        });
    }
    
    // Get status class for badge styling
    function getStatusClass(status) {
        const statusClasses = {
            'RUNNING': 'status-running',
            'STOPPED': 'status-stopped',
            'STARTING': 'status-starting',
            'BACKOFF': 'status-backoff',
            'FATAL': 'status-fatal',
            'EXITED': 'status-exited'
        };
        return statusClasses[status] || 'bg-gray-500';
    }
    
    // Generate action buttons HTML
    function getActionButtons(server, process, status) {
        if (status === 'RUNNING') {
            return `
                <button onclick="controlAction('stop', '${server}', '${process.replace(/'/g, "\\'")}')" 
                   class="text-slate-400 hover:text-red-600 transition" title="Stop">
                    <i class="fas fa-stop"></i>
                </button>
                <button onclick="controlAction('restart', '${server}', '${process.replace(/'/g, "\\'")}')" 
                   class="text-slate-400 hover:text-blue-600 transition" title="Restart">
                    <i class="fas fa-redo"></i>
                </button>
            `;
        } else if (['STOPPED', 'EXITED', 'FATAL'].includes(status)) {
            return `
                <button onclick="controlAction('start', '${server}', '${process.replace(/'/g, "\\'")}')" 
                   class="text-slate-400 hover:text-emerald-600 transition" title="Start">
                    <i class="fas fa-play"></i>
                </button>
            `;
        }
        return '';
    }
    
    // Toggle group expand/collapse
    function toggleGroup(groupId) {
        const groupContent = document.getElementById(groupId);
        const safeName = groupId.replace('group-', '');
        const toggleIcon = document.querySelector(`.toggle-icon-${safeName} i`);
        
        if (groupContent) {
            groupContent.classList.toggle('hidden');
            if (toggleIcon) {
                toggleIcon.classList.toggle('fa-chevron-up');
                toggleIcon.classList.toggle('fa-chevron-down');
            }
            
            // Save state to localStorage
            localStorage.setItem(`group-${groupId}`, groupContent.classList.contains('hidden') ? 'closed' : 'open');
        }
    }
    
    // Restore expand/collapse state from localStorage
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[id^="group-"]').forEach(el => {
            const state = localStorage.getItem('group-' + el.id);
            if (state === 'closed') {
                el.classList.add('hidden');
                const safeName = el.id.replace('group-', '');
                const toggleIcon = document.querySelector(`.toggle-icon-${safeName} i`);
                if (toggleIcon) {
                    toggleIcon.classList.remove('fa-chevron-up');
                    toggleIcon.classList.add('fa-chevron-down');
                }
            }
        });
    });
    
    // Auto-refresh countdown with AJAX
    var refreshInterval = <?php echo $this->config->item('refresh'); ?>;
    var currentCountdown = refreshInterval;
    
    function updateCountdown() {
        currentCountdown--;
        const sidebarEl = document.getElementById('countdown');
        const headerEl = document.getElementById('countdown-header');
        
        if (sidebarEl) {
            sidebarEl.textContent = currentCountdown;
        }
        if (headerEl) {
            headerEl.textContent = currentCountdown;
        }
        
        if (currentCountdown <= 0) {
            refreshData(); // Use AJAX instead of reload
            currentCountdown = refreshInterval; // Reset countdown
        }
    }
    
    if (refreshInterval > 0) {
        setInterval(updateCountdown, 1000);
    }
    </script>

    <?php
    if($alert && !$muted && $this->config->item('enable_alarm')){
        echo '<embed height="0" width="0" src="'.base_url('/sounds/alert.mp3').'">';
    }
    if($alert){
        echo '<title>!!! WARNING !!!</title>';
    }else{
        echo '<title>Supervisord Dashboard</title>';
    }
    ?>

</body>
</html>
