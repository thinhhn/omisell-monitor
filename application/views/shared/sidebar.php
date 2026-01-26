<!-- Mobile Top Navigation Bar -->
<div class="mobile-top-nav">
    <div class="logo">
        <img src="https://storage.googleapis.com/omisell-cloud/logo/logo-simple-omisell.jpg" alt="Omisell">
        <h1>Omisell</h1>
    </div>
    <button id="mobile-menu-toggle" class="text-white">
        <i class="fas fa-bars text-xl" id="menu-icon"></i>
    </button>
</div>

<!-- Mobile Overlay -->
<div class="mobile-overlay" id="mobile-overlay"></div>

<!-- Top Navigation Menu - Single Row -->
<aside class="w-full bg-slate-900 text-white sidebar-fixed">
    
    <div id="sidebar-content" class="flex items-center w-full h-full">
        
        <!-- Logo Section -->
        <div class="flex items-center gap-2 px-4 border-r border-slate-700 flex-shrink-0">
            <img src="https://storage.googleapis.com/omisell-cloud/logo/logo-simple-omisell.jpg" 
                 alt="Omisell" 
                 class="w-8 h-8 rounded shadow-lg object-cover">
            <div class="hidden sm:block">
                <h1 class="text-sm font-bold">Omisell</h1>
                <p class="text-xs text-slate-400 leading-none">Monitor</p>
            </div>
        </div>
        
        <!-- Navigation Menu - Horizontal -->
        <nav class="flex items-center flex-1 h-full">
            <a href="<?php echo site_url('welcome'); ?>" 
               class="flex items-center gap-2 px-4 h-full whitespace-nowrap text-slate-400 hover:text-white border-b-2 border-transparent hover:border-blue-400 transition <?php echo (isset($active_menu) && $active_menu == 'dashboard') ? 'text-blue-400 border-b-2 border-blue-400' : ''; ?>">
                <i class="fas fa-server text-sm"></i>
                <span class="hidden md:inline text-sm">Supervisord Dashboard</span>
                <span class="md:hidden text-sm">Dashboard</span>
            </a>
            <a href="<?php echo site_url('events'); ?>" 
               class="flex items-center gap-2 px-4 h-full whitespace-nowrap text-slate-400 hover:text-white border-b-2 border-transparent hover:border-blue-400 transition <?php echo (isset($active_menu) && $active_menu == 'events') ? 'text-blue-400 border-b-2 border-blue-400' : ''; ?>">
                <i class="fas fa-chart-bar text-sm"></i>
                <span class="text-sm">Event Statistics</span>
            </a>
            <a href="<?php echo site_url('debug/testConnections'); ?>" 
               class="flex items-center gap-2 px-4 h-full whitespace-nowrap text-slate-400 hover:text-white border-b-2 border-transparent hover:border-blue-400 transition <?php echo (isset($active_menu) && $active_menu == 'debug') ? 'text-blue-400 border-b-2 border-blue-400' : ''; ?>">
                <i class="fas fa-network-wired text-sm"></i>
                <span class="text-sm">Test Connections</span>
            </a>
        </nav>
        
        <!-- User Info - Right Side -->
        <div class="flex items-center gap-3 px-4 border-l border-slate-700 flex-shrink-0 h-full">
            <?php
            // Get logged-in user from session
            $logged_user = $this->session->userdata('username') ?: 'Admin';
            ?>
            <div class="flex items-center gap-2 text-sm">
                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-xs font-bold flex-shrink-0">
                    <?php echo strtoupper(substr($logged_user, 0, 1)); ?>
                </div>
                <span class="text-slate-300 text-xs hidden sm:inline"><?php echo htmlspecialchars($logged_user); ?></span>
            </div>
            <a href="<?php echo site_url('auth/logout'); ?>" 
               class="text-slate-400 hover:text-red-400 transition text-sm" 
               title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
        
    </div>
    
</aside>
