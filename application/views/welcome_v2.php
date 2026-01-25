<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Omisell Supervisord Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .status-running { @apply bg-emerald-100 text-emerald-700 border-emerald-200; }
        .status-stopped { @apply bg-slate-200 text-slate-700 border-slate-300; }
        .status-starting { @apply bg-yellow-100 text-yellow-700 border-yellow-200; }
        .status-backoff { @apply bg-orange-100 text-orange-700 border-orange-200; }
        .status-fatal { @apply bg-red-100 text-red-700 border-red-200; }
        .status-exited { @apply bg-slate-200 text-slate-600 border-slate-300; }
        
        tr { transition: all 0.2s ease; }
        tr:hover { background-color: #f9fafb; }
        
        .group-card { transition: all 0.3s ease; }
        .group-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    </style>
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

    <div class="min-h-screen flex flex-col lg:flex-row">
        
        <!-- Sidebar -->
        <aside class="w-full lg:w-64 bg-slate-900 text-white flex-shrink-0">
            <div class="p-6 border-b border-slate-800 flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center font-bold text-lg shadow-lg">
                    O
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-wide">Omisell</h1>
                    <p class="text-xs text-slate-400">Supervisor Center</p>
                </div>
            </div>
            
            <nav class="p-4 space-y-2">
                <a href="<?php echo site_url('welcome'); ?>" class="flex items-center gap-3 px-4 py-3 bg-blue-600 rounded-lg shadow-lg text-white">
                    <i class="fas fa-server"></i>
                    <span class="font-medium">Dashboard</span>
                </a>
                <a href="<?php echo site_url('debug/testConnections'); ?>" class="flex items-center gap-3 px-4 py-3 text-slate-400 hover:bg-slate-800 hover:text-white rounded-lg transition">
                    <i class="fas fa-network-wired"></i>
                    <span>Test Connections</span>
                </a>
                <a href="<?php echo site_url('welcome'); ?>" class="flex items-center gap-3 px-4 py-3 text-slate-400 hover:bg-slate-800 hover:text-white rounded-lg transition">
                    <i class="fas fa-th-large"></i>
                    <span>Classic View</span>
                </a>
            </nav>
            
            <div class="absolute bottom-0 w-full lg:w-64 p-4 border-t border-slate-800 space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-400">Auto-refresh</span>
                    <span class="px-2 py-1 bg-slate-800 rounded text-xs font-mono" id="countdown"><?php echo $this->config->item('refresh'); ?></span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                    <span class="text-sm text-slate-400">System Online</span>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-4 lg:p-8 overflow-y-auto">
            
            <!-- Header -->
            <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center mb-8 gap-4">
                <div>
                    <h2 class="text-3xl font-bold text-slate-900">Supervisord Dashboard</h2>
                    <p class="text-slate-500 text-sm mt-1">
                        Last updated: <span class="font-mono"><?php echo date('H:i:s'); ?></span>
                    </p>
                </div>
                <div class="flex gap-3 flex-wrap">
                    <button onclick="location.reload();" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-sm font-medium hover:bg-slate-50 transition shadow-sm">
                        <i class="fas fa-sync-alt mr-2 text-slate-400"></i> Refresh
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
                        <?php echo $total_processes > 0 ? round($running_processes/$total_processes*100) : 0; ?>% active
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
            <section class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-slate-800 flex items-center gap-3">
                        <span class="w-1 h-8 bg-<?php echo $group_config['color']; ?>-500 rounded-full"></span>
                        <i class="fas <?php echo $group_config['icon']; ?> text-<?php echo $group_config['color']; ?>-500"></i>
                        <?php echo $group_name; ?>
                    </h3>
                </div>
                
                <?php foreach ($group_config['servers'] as $type_name => $server_names): ?>
                <div class="mb-6">
                    <h4 class="text-sm font-semibold text-slate-600 uppercase tracking-wide mb-3 flex items-center gap-2">
                        <i class="fas fa-<?php echo $type_name === 'web' ? 'globe' : 'cog'; ?> text-slate-400"></i>
                        <?php echo ucfirst($type_name); ?> Servers
                    </h4>
                    
                    <div class="grid grid-cols-1 gap-4">
                        <?php foreach ($server_names as $server_name): 
                            if (!isset($servers[$server_name])) continue;
                            $server_config = $servers[$server_name];
                            $procs = isset($list[$server_name]) ? $list[$server_name] : ['error' => 'No data'];
                            $has_error = isset($procs['error']);
                        ?>
                        
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden group-card">
                            <!-- Server Header -->
                            <div class="bg-gradient-to-r from-<?php echo $group_config['color']; ?>-500 to-<?php echo $group_config['color']; ?>-600 p-4 text-white">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-3">
                                        <i class="fas fa-server text-2xl opacity-80"></i>
                                        <div>
                                            <h5 class="font-bold text-lg"><?php echo $server_name; ?></h5>
                                            <div class="flex items-center gap-3 text-xs opacity-90 mt-1">
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
                                                    <span class="font-mono text-<?php echo $cpu_color; ?>-300"><?php echo round($cpu_percent, 1); ?>%</span>
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <i class="fas fa-memory mr-1"></i>
                                                    <span class="font-mono text-<?php echo $mem_color; ?>-300"><?php echo round($mem_percent, 1); ?>%</span>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
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
                                        <span class="px-3 py-1 bg-white bg-opacity-20 rounded-full text-xs font-bold">
                                            <?php echo $server_running; ?>/<?php echo $server_total; ?> Running
                                        </span>
                                        <div class="flex gap-1">
                                            <a href="/control/startall/<?php echo $server_name; ?>" class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition" title="Start All">
                                                <i class="fas fa-play text-sm"></i>
                                            </a>
                                            <a href="/control/restartall/<?php echo $server_name; ?>" class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition" title="Restart All">
                                                <i class="fas fa-redo text-sm"></i>
                                            </a>
                                            <a href="/control/stopall/<?php echo $server_name; ?>" class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition" title="Stop All">
                                                <i class="fas fa-stop text-sm"></i>
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                    </div>
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
                                            <th class="p-3 w-40">Status</th>
                                            <th class="p-3 text-right w-32">Actions</th>
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
                                                    $pid = isset($desc_parts[0]) ? trim($desc_parts[0]) : '';
                                                    $uptime = isset($desc_parts[1]) ? str_replace('uptime ', '', trim($desc_parts[1])) : '';
                                                }
                                                
                                                $status_lower = strtolower($status);
                                                $status_class = "status-$status_lower";
                                        ?>
                                        <tr class="hover:bg-slate-50">
                                            <td class="p-3">
                                                <div class="font-medium text-slate-700"><?php echo htmlspecialchars($item_name); ?></div>
                                                <?php if ($pid): ?>
                                                <div class="text-xs text-slate-400 font-mono mt-1">pid: <?php echo $pid; ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3">
                                                <div>
                                                    <span class="<?php echo $status_class; ?> px-2 py-1 rounded-full text-xs font-bold border inline-flex items-center gap-1.5">
                                                        <div class="w-1.5 h-1.5 rounded-full bg-current <?php echo $status === 'FATAL' ? 'animate-pulse' : ''; ?>"></div>
                                                        <?php echo $status; ?>
                                                    </span>
                                                </div>
                                                <?php if ($uptime): ?>
                                                <div class="text-xs text-slate-400 mt-2">
                                                    <i class="fas fa-clock mr-1"></i><?php echo $uptime; ?>
                                                </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3 text-right space-x-2">
                                                <?php if ($status == 'RUNNING'): ?>
                                                    <a href="/control/stop/<?php echo $server_name . '/' . urlencode($item_name); ?>" 
                                                       class="text-slate-400 hover:text-red-600 transition" title="Stop">
                                                        <i class="fas fa-stop"></i>
                                                    </a>
                                                    <a href="/control/restart/<?php echo $server_name . '/' . urlencode($item_name); ?>" 
                                                       class="text-slate-400 hover:text-blue-600 transition" title="Restart">
                                                        <i class="fas fa-redo"></i>
                                                    </a>
                                                <?php elseif (in_array($status, ['STOPPED', 'EXITED', 'FATAL'])): ?>
                                                    <a href="/control/start/<?php echo $server_name . '/' . urlencode($item_name); ?>" 
                                                       class="text-slate-400 hover:text-emerald-600 transition" title="Start">
                                                        <i class="fas fa-play"></i>
                                                    </a>
                                                <?php endif; ?>
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
            </section>
            <?php endforeach; ?>

        </main>
    </div>

    <script>
    // Auto-refresh countdown
    var refreshInterval = <?php echo $this->config->item('refresh'); ?>;
    var currentCountdown = refreshInterval;
    
    function updateCountdown() {
        currentCountdown--;
        if (document.getElementById('countdown')) {
            document.getElementById('countdown').textContent = currentCountdown;
        }
        
        if (currentCountdown <= 0) {
            window.location.reload();
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
