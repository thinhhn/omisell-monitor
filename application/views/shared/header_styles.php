<style>
    body { 
        font-family: 'Inter', sans-serif;
        margin: 0;
        overflow: auto;
    }
    
    html {
        height: 100%;
        overflow: auto;
    }
    .status-running { 
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
    }
    .status-stopped { 
        background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
        color: white;
        border: none;
    }
    .status-starting { 
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        border: none;
    }
    .status-backoff { 
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        color: white;
        border: none;
    }
    .status-fatal { 
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        border: none;
    }
    .status-exited { 
        background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
        color: white;
        border: none;
    }
    
    tr { transition: all 0.2s ease; }
    tr:hover { background-color: #f9fafb; }
    
    .group-card { transition: all 0.3s ease; }
    .group-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    
    /* Top navigation menu - single row */
    .sidebar-fixed {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 80px;
        width: 100%;
        overflow: visible;
        z-index: 1000;
        transition: none;
        padding: 0;
        display: flex !important;
        align-items: center;
    }
    
    .main-content-offset {
        margin-left: 0;
        margin-top: 80px; /* Height of top menu */
        height: calc(100vh - 80px);
        overflow-y: auto;
        overflow-x: hidden;
    }
    
    /* Sidebar content - single row layout */
    #sidebar-content {
        display: flex !important;
        flex-direction: row !important;
        height: 100% !important;
        width: 100% !important;
        align-items: center !important;
    }
    
    /* Logo section */
    .sidebar-fixed > #sidebar-content > div:first-child {
        display: flex !important;
        align-items: center !important;
        gap: 0.75rem !important;
        padding: 0 1rem !important;
        border-right: 1px solid rgba(51, 65, 85, 0.3) !important;
        border-bottom: none !important;
        flex-shrink: 0;
    }
    
    /* Navigation menu horizontal */
    .sidebar-fixed nav {
        display: flex !important;
        flex-direction: row !important;
        gap: 0 !important;
        padding: 0 !important;
        align-items: center;
        flex: 1;
        width: auto;
        margin: 0 !important;
        height: 100%;
    }
    
    .sidebar-fixed nav a {
        padding: 0 1.5rem !important;
        border-radius: 0 !important;
        border-bottom: 3px solid transparent !important;
        white-space: nowrap !important;
        text-decoration: none !important;
        transition: all 0.3s ease !important;
        background: transparent !important;
        color: rgb(100, 116, 139) !important;
        flex-shrink: 0;
        height: 100%;
        display: flex !important;
        align-items: center !important;
        font-size: 0.95rem;
    }
    
    .sidebar-fixed nav a:hover {
        background: rgba(255,255,255,0.05) !important;
        color: white !important;
    }
    
    .sidebar-fixed nav a.active,
    .sidebar-fixed nav a[href*="active"] {
        border-bottom-color: rgb(96, 165, 250) !important;
        background: rgba(59, 130, 246, 0.1) !important;
        color: rgb(96, 165, 250) !important;
    }
    
    /* User info right side */
    .sidebar-fixed > #sidebar-content > div:last-child {
        position: relative !important;
        bottom: auto !important;
        right: auto !important;
        top: auto !important;
        width: auto !important;
        border-left: 1px solid rgba(51, 65, 85, 0.3) !important;
        border-top: none !important;
        background: transparent !important;
        padding: 0 1rem !important;
        height: 100%;
        display: flex !important;
        align-items: center !important;
        flex-shrink: 0;
    }
    
    /* Mobile styles - keep hamburger menu */
    @media (max-width: 1024px) {
        body {
            overflow: auto;
            padding-top: 60px; /* Space for fixed top menu */
        }
        
        /* Mobile top navigation bar */
        .mobile-top-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
        }
        
        .mobile-top-nav .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
        }
        
        .mobile-top-nav .logo img {
            width: 36px;
            height: 36px;
            border-radius: 0.5rem;
        }
        
        .mobile-top-nav .logo h1 {
            font-size: 1.125rem;
            font-weight: bold;
        }
        
        /* Sidebar transforms to dropdown on mobile */
        .sidebar-fixed {
            display: flex !important;
            position: fixed;
            top: 60px;
            left: 0;
            right: 0;
            width: 100%;
            height: auto;
            max-height: 0;
            overflow: hidden;
            flex-direction: column;
            transition: max-height 0.3s ease;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            z-index: 1999;
        }
        
        .sidebar-fixed.mobile-menu-open {
            max-height: 100vh;
        }
        
        /* Hide logo on mobile menu */
        .sidebar-fixed > #sidebar-content > div:first-child {
            display: none !important;
        }
        
        /* Mobile menu layout - flex column */
        #sidebar-content {
            flex-direction: column !important;
            height: auto !important;
        }
        
        /* Mobile nav styling */
        .sidebar-fixed nav {
            flex-direction: column !important;
            gap: 0 !important;
            padding: 1rem !important;
            width: 100% !important;
            flex: 1;
        }
        
        .sidebar-fixed nav a {
            width: 100% !important;
            text-align: left !important;
            border-bottom: none !important;
            border-left: 3px solid transparent !important;
            padding: 1rem !important;
            border-radius: 0.375rem !important;
            height: auto !important;
            display: block !important;
        }
        
        .sidebar-fixed nav a:hover {
            background: rgba(255,255,255,0.15) !important;
        }
        
        .sidebar-fixed nav a.active,
        .sidebar-fixed nav a[href*="active"] {
            border-left-color: rgb(59, 130, 246) !important;
            background: rgba(59, 130, 246, 0.2) !important;
        }
        
        /* User info at bottom of mobile menu */
        .sidebar-fixed > #sidebar-content > div:last-child {
            position: relative !important;
            right: auto !important;
            top: auto !important;
            width: 100% !important;
            padding: 1rem !important;
            border-top: 1px solid rgba(255,255,255,0.1) !important;
            border-left: none !important;
            margin-top: auto;
            flex-shrink: 0;
        }
        
        /* Adjust user info display on mobile */
        .sidebar-fixed > #sidebar-content > div:last-child > div {
            justify-content: center !important;
            gap: 1rem !important;
        }
        
        .main-content-offset {
            margin-left: 0;
            margin-top: 60px;
            height: auto;
            min-height: calc(100vh - 60px);
        }
        
        /* Hamburger button in top nav */
        #mobile-menu-toggle {
            display: flex !important;
            position: relative;
            background: rgba(255,255,255,0.1);
            border: none;
            padding: 0.5rem;
            border-radius: 0.5rem;
        }
    }
    
    /* Hide mobile elements on desktop */
    @media (min-width: 1025px) {
        .mobile-top-nav {
            display: none !important;
        }
        
        #mobile-menu-toggle {
            display: none !important;
        }
    }
    
    /* Show on mobile */
    @media (max-width: 1024px) {
        .mobile-top-nav {
            display: flex !important;
        }
        
        #mobile-menu-toggle {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }
    }
    
    .mobile-overlay {
        display: none;
    }
    
    /* Process table responsive */
    @media (max-width: 768px) {
        /* Summary cards - 1 column on mobile */
        .grid.grid-cols-1.sm\\:grid-cols-2.lg\\:grid-cols-4 {
            grid-template-columns: 1fr !important;
        }
        
        /* Table responsive */
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        table {
            min-width: 100%;
            white-space: nowrap;
        }
        
        /* Optimize column widths */
        table th, table td {
            padding: 0.75rem 0.5rem;
        }
        
        /* Process name column */
        table td:first-child, table th:first-child {
            min-width: 180px;
            max-width: 200px;
            white-space: normal;
            word-break: break-word;
        }
        
        /* Status column - auto width */
        table td:nth-child(2), table th:nth-child(2) {
            width: auto;
            min-width: 80px;
        }
        
        /* Actions column - compact */
        table td:last-child, table th:last-child {
            width: 70px;
            text-align: center;
        }
    }
    
    .loading-spinner {
        border: 3px solid #f3f4f6;
        border-top: 3px solid #3b82f6;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .pulse-dot {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .5; }
    }
</style>
