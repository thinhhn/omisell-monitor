<style>
    body { 
        font-family: 'Inter', sans-serif;
        margin: 0;
        overflow: hidden;
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
    
    /* Fixed sidebar with independent scroll */
    .sidebar-fixed {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        overflow: hidden;
        z-index: 1000;
        transition: transform 0.3s ease;
    }
    
    .main-content-offset {
        margin-left: 16rem; /* 256px = w-64 */
        height: 100vh;
        overflow-y: auto;
    }
    
    /* Mobile styles */
    @media (max-width: 1024px) {
        .sidebar-fixed {
            transform: translateX(-100%);
            width: 280px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.3);
        }
        
        .sidebar-fixed.mobile-menu-open {
            transform: translateX(0);
        }
        
        .main-content-offset {
            margin-left: 0;
            height: auto;
            min-height: 100vh;
        }
        
        body {
            overflow: auto;
        }
        
        /* Mobile menu overlay */
        .sidebar-fixed.mobile-menu-open::before {
            content: '';
            position: fixed;
            top: 0;
            left: 280px;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: -1;
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
