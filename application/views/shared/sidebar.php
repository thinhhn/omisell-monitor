<!-- Sidebar -->
<aside class="w-full lg:w-64 bg-slate-900 text-white flex-shrink-0 sidebar-fixed">
    <!-- Mobile Menu Toggle Button -->
    <button id="mobile-menu-toggle" class="lg:hidden absolute top-4 right-4 z-50 text-white p-2 rounded-lg bg-slate-800 hover:bg-slate-700">
        <i class="fas fa-bars" id="menu-icon"></i>
    </button>
    
    <div id="sidebar-content" class="h-full flex flex-col">
        <!-- Header -->
        <div class="p-6 border-b border-slate-800 flex items-center gap-3">
            <img src="https://storage.googleapis.com/omisell-cloud/logo/logo-simple-omisell.jpg" 
                 alt="Omisell" 
                 class="w-10 h-10 rounded-lg shadow-lg object-cover">
            <div>
                <h1 class="text-xl font-bold tracking-wide">Omisell</h1>
                <p class="text-xs text-slate-400">Monitor Center</p>
            </div>
        </div>
        
        <!-- Navigation - scrollable on mobile -->
        <nav class="p-4 space-y-2 flex-1 overflow-y-auto">
            <a href="<?php echo site_url('welcome'); ?>" 
               class="flex items-center gap-3 px-4 py-3 <?php echo (isset($active_menu) && $active_menu == 'dashboard') ? 'bg-blue-600 rounded-lg shadow-lg text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white rounded-lg transition'; ?>">
                <i class="fas fa-server"></i>
                <span class="font-medium">Supervisord</span>
            </a>
            <a href="<?php echo site_url('events'); ?>" 
               class="flex items-center gap-3 px-4 py-3 <?php echo (isset($active_menu) && $active_menu == 'events') ? 'bg-blue-600 rounded-lg shadow-lg text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white rounded-lg transition'; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Event Statistics</span>
            </a>
            <a href="<?php echo site_url('debug/testConnections'); ?>" 
               class="flex items-center gap-3 px-4 py-3 <?php echo (isset($active_menu) && $active_menu == 'debug') ? 'bg-blue-600 rounded-lg shadow-lg text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white rounded-lg transition'; ?>">
                <i class="fas fa-network-wired"></i>
                <span>Test Connections</span>
            </a>
        </nav>
        
        <!-- User Info - at bottom, not absolute -->
        <div class="p-4 border-t border-slate-800 bg-slate-900">
            <?php
            // Get logged-in user from session
            $logged_user = $this->session->userdata('username') ?: 'Admin';
            ?>
            <div class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-2 min-w-0 flex-1">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-xs font-bold flex-shrink-0">
                        <?php echo strtoupper(substr($logged_user, 0, 1)); ?>
                    </div>
                    <span class="text-slate-300 text-xs truncate"><?php echo htmlspecialchars($logged_user); ?></span>
                </div>
                <a href="<?php echo site_url('auth/logout'); ?>" 
                   class="text-slate-400 hover:text-red-400 transition ml-2 flex-shrink-0" 
                   title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
</aside>

<script>
// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar-fixed');
    const menuIcon = document.getElementById('menu-icon');
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-menu-open');
            
            // Toggle icon
            if (sidebar.classList.contains('mobile-menu-open')) {
                menuIcon.classList.remove('fa-bars');
                menuIcon.classList.add('fa-times');
            } else {
                menuIcon.classList.remove('fa-times');
                menuIcon.classList.add('fa-bars');
            }
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 1024) {
                if (!sidebar.contains(event.target) && sidebar.classList.contains('mobile-menu-open')) {
                    sidebar.classList.remove('mobile-menu-open');
                    menuIcon.classList.remove('fa-times');
                    menuIcon.classList.add('fa-bars');
                }
            }
        });
    }
});
</script>
