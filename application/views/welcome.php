<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Supervisord Monitoring</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link type="text/css" rel="stylesheet" href="<?php echo base_url('/css/bootstrap.min.css');?>"/>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
	<link type="text/css" rel="stylesheet" href="<?php echo base_url('/css/custom.css');?>"/>
	
	<!-- CSS Fixes for UI -->
	<style>
	.container { max-width: 100%; padding: 0 15px; }
	.card { border: 1px solid #dee2e6; border-radius: 0.25rem; margin-bottom: 1rem; background: white; }
	.card-header { 
		background-color: #007bff; 
		color: white; 
		padding: 0.75rem 1rem; 
		border-bottom: 1px solid #dee2e6;
		display: flex;
		flex-direction: column;
		gap: 8px;
	}
	.card-header-row {
		display: flex;
		justify-content: space-between;
		align-items: center;
		width: 100%;
	}
	.card-header .header-left {
		display: flex;
		align-items: center;
		gap: 10px;
		flex: 1;
		min-width: 0;
	}
	.card-header .header-right {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-shrink: 0;
		white-space: nowrap;
	}
	.card-header .header-info {
		display: flex;
		justify-content: space-between;
		align-items: center;
		font-size: 0.8rem;
		opacity: 0.85;
		padding-top: 5px;
		border-top: 1px solid rgba(255,255,255,0.2);
	}
	.card-body { padding: 1rem; }
	.table { width: 100%; margin-bottom: 0; }
	.table-sm th, .table-sm td { padding: 0.3rem; }
	.badge { display: inline-block; padding: 0.25em 0.4em; font-size: 75%; font-weight: 700; line-height: 1; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: 0.25rem; }
	.bg-success { background-color: #28a745 !important; color: white; }
	.bg-danger { background-color: #dc3545 !important; color: white; }
	.bg-warning { background-color: #ffc107 !important; color: black; }
	.bg-info { background-color: #17a2b8 !important; color: white; }
	.bg-secondary { background-color: #6c757d !important; color: white; }
	.bg-dark { background-color: #343a40 !important; color: white; }
	.btn { display: inline-block; padding: 0.375rem 0.75rem; margin-bottom: 0; font-size: 1rem; font-weight: 400; line-height: 1.5; text-align: center; text-decoration: none; vertical-align: middle; border: 1px solid transparent; border-radius: 0.25rem; cursor: pointer; }
	.btn-sm { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
	.btn-success { color: #fff; background-color: #28a745; border-color: #28a745; }
	.btn-warning { color: #212529; background-color: #ffc107; border-color: #ffc107; }
	.btn-danger { color: #fff; background-color: #dc3545; border-color: #dc3545; }
	.btn-outline-success { color: #28a745; border-color: #28a745; background-color: transparent; }
	.btn-outline-warning { color: #ffc107; border-color: #ffc107; background-color: transparent; }
	.btn-outline-danger { color: #dc3545; border-color: #dc3545; background-color: transparent; }
	.btn-outline-light { color: #fff; border-color: rgba(255,255,255,0.5); background-color: transparent; }
	.btn-outline-light:hover { background-color: rgba(255,255,255,0.2); }
	.alert { padding: 0.75rem 1.25rem; margin-bottom: 1rem; border: 1px solid transparent; border-radius: 0.25rem; }
	.alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
	.text-muted { color: #6c757d !important; }
	.text-center { text-align: center; }
	.py-3 { padding-top: 1rem; padding-bottom: 1rem; }
	.row::after { content: ""; clear: both; display: table; }
	.btn-group .btn { margin-right: 2px; }
	.server-controls { margin-top: 10px; }
	.mb-3 { margin-bottom: 1rem; }
	.mb-4 { margin-bottom: 1.5rem; }
	.table-responsive { overflow-x: auto; }
	.table-hover tbody tr:hover { background-color: rgba(0,0,0,.075); }
	
	/* Responsive columns */
	.col-12 { width: 100%; }
	.col-lg-4 { width: 100%; padding: 0 10px; }
	.col-md-6 { width: 100%; padding: 0 10px; }
	
	@media (min-width: 768px) {
		.col-md-6 { width: 50%; float: left; }
	}
	
	@media (min-width: 992px) {
		.col-lg-4 { width: 33.333333%; float: left; }
	}
	
	/* Group section styling */
	.group-section {
		background: #f8f9fa;
		padding: 20px;
		border-radius: 8px;
		margin-bottom: 30px;
	}
	.group-header {
		border-bottom: 2px solid #dee2e6;
		padding-bottom: 10px;
		margin-bottom: 20px;
	}
	.server-type-section {
		margin-bottom: 25px;
	}
	.server-type-header {
		background: #e9ecef;
		padding: 8px 15px;
		border-radius: 5px;
		margin-bottom: 15px;
		font-weight: 600;
	}
	
	/* Server stats styling */
	.server-stats {
		display: flex;
		gap: 10px;
		align-items: center;
		font-size: 0.75rem;
	}
	.stat-badge {
		background: rgba(255,255,255,0.2);
		padding: 2px 8px;
		border-radius: 3px;
		display: inline-flex;
		align-items: center;
		gap: 4px;
	}
	
	/* Navbar responsive */
	.navbar {
		flex-wrap: wrap;
	}
	.navbar-brand {
		font-size: 1.1rem;
		padding: 0.5rem 1rem;
	}
	.navbar-nav {
		flex-direction: row;
		flex-wrap: wrap;
	}
	.nav-item {
		margin-right: 5px;
	}
	.nav-link {
		padding: 0.5rem 0.75rem;
		font-size: 0.9rem;
		white-space: nowrap;
	}
	
	/* Mobile navbar adjustments */
	@media (max-width: 768px) {
		.navbar-brand {
			font-size: 0.95rem;
			padding: 0.5rem;
		}
		.nav-link {
			padding: 0.4rem 0.5rem;
			font-size: 0.85rem;
		}
		.nav-link i {
			font-size: 0.9rem;
		}
		.badge {
			font-size: 0.7rem;
			padding: 0.15em 0.3em;
		}
		/* Hide text on mobile, keep icons */
		.nav-item .nav-link span:not(.badge) {
			display: none;
		}
		.nav-item .nav-link i {
			margin-right: 0 !important;
		}
	}
	
	@media (max-width: 576px) {
		.navbar {
			padding: 0.25rem 0.5rem;
		}
		.navbar-brand {
			font-size: 0.85rem;
		}
		.nav-link {
			padding: 0.3rem 0.4rem;
			font-size: 0.8rem;
		}
	}
	</style>
	<script type="text/javascript" src="<?php echo base_url('/js/jquery-1.10.1.min.js');?>"></script>
	<script type="text/javascript" src="<?php echo base_url('/js/bootstrap.min.js');?>"></script>
	<noscript>
	<?php
	if($this->config->item('refresh')){ ?>
	<meta http-equiv="refresh" content="<?php echo $this->config->item('refresh');?>">
	<?php } ?>
	</noscript>
</head>
<body>
	<div class="navbar navbar-dark bg-dark navbar-fixed-top py-0">
      <div class="navbar navbar-expand pl-0">
	  	<a class="navbar-brand" href="<?php echo site_url('');?>">
			<i class="bi bi-display"></i> <span class="d-none d-sm-inline">Supervisord Center</span>
		</a>
		<ul class="navbar-nav mr-auto">
			<li class="nav-item">
				<a class="nav-link" href="?mute=<?php echo ($muted?-1:1);?>" title="<?php echo $muted ? 'Click to unmute alarm sounds' : 'Click to mute alarm sounds'; ?>" data-bs-toggle="tooltip">
					<i class="bi bi-<?php echo $muted ? 'volume-mute' : 'volume-up'; ?>"></i> <span class="d-none d-md-inline"><?php echo $muted ? "Unmute" : "Mute"; ?></span>
				</a>
			</li>
			<li class="nav-item">
				<span class="nav-link" title="Page will auto-refresh every <?php echo $this->config->item('refresh'); ?> seconds" data-bs-toggle="tooltip">
					<i class="bi bi-arrow-clockwise"></i> <span class="d-none d-md-inline">Auto:</span> <span id="countdown" class="badge badge-light"><?php echo $this->config->item('refresh'); ?></span>s
				</span>
			</li>
			<li class="nav-item">
				<span class="nav-link" title="Time taken to load this page" data-bs-toggle="tooltip">
					<i class="bi bi-speedometer"></i> <span class="d-none d-md-inline">Load:</span> <span class="badge badge-success"><?php echo isset($load_time) ? $load_time : '0'; ?></span>ms
				</span>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="javascript:location.reload();" title="Manually refresh the page now" data-bs-toggle="tooltip">
					<i class="bi bi-arrow-repeat"></i> <span class="d-none d-md-inline">Refresh</span>
				</a>
			</li>
		</ul>
		
		<!-- User info and logout -->
		<?php if ($this->session->userdata('logged_in')): ?>
		<ul class="navbar-nav ml-auto">
			<li class="nav-item dropdown">
				<a class="nav-link dropdown-toggle text-light" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<i class="bi bi-person-circle"></i> <?php echo $this->session->userdata('username'); ?>
				</a>
				<div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
					<div class="dropdown-header">
						<small>Đăng nhập lúc: <?php echo date('H:i:s d/m/Y', $this->session->userdata('login_time')); ?></small>
					</div>
					<div class="dropdown-divider"></div>
					<a class="dropdown-item" href="<?php echo site_url('auth/logout'); ?>">
						<i class="bi bi-box-arrow-right"></i> Đăng xuất
					</a>
				</div>
			</li>
		</ul>
		<?php endif; ?>
		
		</div>
      </div>
    </div>
	

	<div class="container mt-3">
		
		<!-- Flash Messages -->
		<?php if ($this->session->flashdata('success')): ?>
			<div class="alert alert-success alert-dismissible fade show" role="alert">
				<strong>✅ Success!</strong> <?php echo $this->session->flashdata('success'); ?>
				<button type="button" class="close" data-dismiss="alert">&times;</button>
			</div>
		<?php endif; ?>
		
		<?php if ($this->session->flashdata('error')): ?>
			<div class="alert alert-danger alert-dismissible fade show" role="alert">
				<strong>❌ Error!</strong> <?php echo $this->session->flashdata('error'); ?>
				<button type="button" class="close" data-dismiss="alert">&times;</button>
			</div>
		<?php endif; ?>
		
		<?php if ($this->session->flashdata('warning')): ?>
			<div class="alert alert-warning alert-dismissible fade show" role="alert">
				<strong>⚠️ Warning!</strong> <?php echo $this->session->flashdata('warning'); ?>
				<button type="button" class="close" data-dismiss="alert">&times;</button>
			</div>
		<?php endif; ?>

	
		<?php
		if($muted){
			echo '<div class="row"><div class="span4 offset4 label label-important" style="padding:10px;margin-bottom:20px;text-align:center;">';
			echo 'Sound muted for '.timespan(time(),$muted).' <span class="float-right"><a href="?mute=-1" style="color:white;"><i class="icon-music icon-white"></i> Unmute</a></span></div></div>';
		}
	
		?>
		<?php
		// Debug data
		if (isset($_GET['debug'])) {
			echo "<pre>DEBUG DATA:\n";
			echo "List data: " . print_r($list, true) . "\n";
			echo "Config data: " . print_r($cfg, true) . "\n";
			echo "Version data: " . print_r($version, true) . "\n";
			echo "</pre>";
		}
		
		$alert = false;
		
		// Define server groups for better organization
		$server_groups = [
			'Omisell Platform' => [
				'web' => ['web_001', 'web_002'],
				'celery' => ['celery_hook', 'celery_001', 'celery_002', 'celery_003', 'celery_004', 'celery_005', 'celery_006', 'celery_007']
			],
			'Omni Platform' => [
				'web' => ['web_omni_001', 'web_omni_002'],
				'celery' => ['celery_omni']
			]
		];
		
		// Render grouped servers
		foreach ($server_groups as $group_name => $group_data): ?>
		<div class="group-section">
			<div class="group-header">
				<h4 class="text-primary mb-0">
					<i class="bi bi-collection"></i> <?php echo $group_name; ?>
				</h4>
			</div>
			
			<?php foreach ($group_data as $type_name => $server_names): ?>
			<div class="server-type-section">
				<div class="server-type-header">
					<i class="bi bi-<?php echo $type_name === 'web' ? 'globe' : 'cpu'; ?>"></i> 
					<?php echo ucfirst($type_name); ?> Servers
				</div>
				<div class="row">
					<?php foreach ($server_names as $name): 
						if (!isset($list[$name]) || !isset($cfg[$name])) continue;
						$procs = $list[$name];
						$server_config = $cfg[$name];
						$parsed_url = parse_url($server_config['url']);
						
						if (isset($server_config['username']) && isset($server_config['password'])) {
							$ui_url = 'http://' . $server_config['username'] . ':' . $server_config['password'] . '@' . $parsed_url['host'] . ':' . $server_config['port'] . '/';
						} else {
							$ui_url = 'http://' . $parsed_url['host'] . ':' . $server_config['port'] . '/';
						}
					?>
					
					<div class="col-lg-4 col-md-6 mb-3">
						<div class="card h-100">
							<div class="card-header text-white" style="background: <?php echo $type_name === 'web' ? 'linear-gradient(135deg, #17a2b8 0%, #138496 100%)' : 'linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%)'; ?>;">
								<?php 
								// Calculate process stats
								$total_procs = 0;
								$running_procs = 0;
								if (is_array($procs) && !isset($procs['error'])) {
									foreach($procs as $proc) {
										if (is_array($proc)) {
											$total_procs++;
											if (isset($proc['statename']) && $proc['statename'] === 'RUNNING') {
												$running_procs++;
											}
										}
									}
								}
								
								// Get system stats
								$server_stats = isset($stats[$name]) ? $stats[$name] : ['available' => false];
								$cpu_percent = isset($server_stats['cpu_percent']) ? $server_stats['cpu_percent'] : 0;
								$mem_percent = isset($server_stats['memory_percent']) ? $server_stats['memory_percent'] : 0;
								$mem_mb = isset($server_stats['memory_mb']) ? $server_stats['memory_mb'] : 0;
								
								// Determine color based on usage
								$cpu_color = $cpu_percent > 80 ? '#dc3545' : ($cpu_percent > 60 ? '#ffc107' : '#28a745');
								$mem_color = $mem_percent > 80 ? '#dc3545' : ($mem_percent > 60 ? '#ffc107' : '#28a745');
								?>
								
								<!-- Row 1: Server name and action buttons -->
								<div class="card-header-row">
									<div class="header-left">
										<a href="<?php echo $ui_url; ?>" class="text-white text-decoration-none" target="_blank" title="Open Supervisor UI" data-bs-toggle="tooltip">
											<i class="bi bi-server"></i> <strong><?php echo $name; ?></strong>
										</a>
									</div>
									<div class="header-right">
										<?php if (!isset($procs['error'])): ?>
										<div class="btn-group btn-group-sm" role="group">
											<a href="/control/startall/<?php echo $name; ?>" 
											   class="btn btn-outline-light btn-sm" title="Start All Processes" data-bs-toggle="tooltip" style="padding: 2px 8px; font-size: 0.75rem;">
												<i class="bi bi-play-fill"></i>
											</a>
											<a href="/control/restartall/<?php echo $name; ?>" 
											   class="btn btn-outline-light btn-sm" title="Restart All Processes" data-bs-toggle="tooltip" style="padding: 2px 8px; font-size: 0.75rem;">
												<i class="bi bi-arrow-clockwise"></i>
											</a>
											<a href="/control/stopall/<?php echo $name; ?>" 
											   class="btn btn-outline-light btn-sm" title="Stop All Processes" data-bs-toggle="tooltip" style="padding: 2px 8px; font-size: 0.75rem;">
												<i class="bi bi-stop-fill"></i>
											</a>
										</div>
										<?php endif; ?>
									</div>
								</div>
								
								<!-- Row 2: Stats, IP and Version -->
								<div class="header-info">
									<div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
										<!-- System stats -->
										<div class="server-stats">
											<?php if (!isset($procs['error'])): ?>
												<span class="stat-badge" title="Running / Total Processes" data-bs-toggle="tooltip">
													<i class="bi bi-cpu"></i> <?php echo $running_procs; ?>/<?php echo $total_procs; ?>
												</span>
											<?php endif; ?>
											
											<?php if ($server_stats['available']): ?>
												<span class="stat-badge" title="CPU Usage: <?php echo round($cpu_percent, 1); ?>%" data-bs-toggle="tooltip" style="border-left: 3px solid <?php echo $cpu_color; ?>;">
													<i class="bi bi-speedometer2"></i> <?php echo round($cpu_percent, 1); ?>%
												</span>
												<span class="stat-badge" title="Memory Usage: <?php echo round($mem_mb); ?> MB (<?php echo round($mem_percent, 1); ?>%)" data-bs-toggle="tooltip" style="border-left: 3px solid <?php echo $mem_color; ?>;">
													<i class="bi bi-memory"></i> <?php echo round($mem_percent, 1); ?>%
												</span>
											<?php endif; ?>
											
											<?php if(isset($server_config['username'])): ?>
												<i class="bi bi-shield-lock text-warning" title="Authentication Enabled" data-bs-toggle="tooltip"></i>
											<?php endif; ?>
										</div>
										
										<!-- IP info -->
										<?php if($this->config->item('show_host')): ?>
											<span>
												<i class="bi bi-hdd-network"></i> <span title="Server IP/Host" data-bs-toggle="tooltip"><?php echo $parsed_url['host']; ?></span>
											</span>
										<?php endif; ?>
										
										<!-- Version info -->
										<span title="Supervisor Version" data-bs-toggle="tooltip">
											<i class="bi bi-info-circle"></i>
											<?php 
											if (isset($version[$name]) && !isset($version[$name]['error'])) {
												echo $version[$name];
											} else {
												echo 'v?';
											}
											?>
										</span>
									</div>
								</div>
							</div>
							
							<div class="card-body p-0">
								<?php if (isset($procs['error'])): ?>
									<!-- Error State -->
									<div class="alert alert-danger m-3 mb-0">
										<h6><i class="bi bi-exclamation-triangle"></i> Connection Error</h6>
										<p class="mb-2"><?php echo htmlspecialchars($procs['error']); ?></p>
										<small class="text-muted">
											Troubleshooting: 
											<a href="<?php echo site_url('debug/testConnections'); ?>" target="_blank">Test Connection</a>
										</small>
									</div>
								<?php else: ?>
									<!-- Process List -->
									<div class="table-responsive">
										<table class="table table-sm table-hover mb-0">
											<thead class="table-light">
												<tr>
													<th>Process</th>
													<th>Status</th>
													<th>Uptime</th>
													<th width="100">Actions</th>
												</tr>
											</thead>
											<tbody>
												<?php 
												if (is_array($procs) && !empty($procs)):
													foreach($procs as $item):
														// Skip if not an array (error case)
														if (!is_array($item)) {
															echo '<tr><td colspan="4" class="text-danger">' . htmlspecialchars($item) . '</td></tr>';
															continue;
														}
														
														// Process name handling
														$item_name = ($item['group'] != $item['name']) 
															? $item['group'] . ':' . $item['name'] 
															: $item['name'];
														
														// Status and styling
														$status = $item['statename'] ?? 'UNKNOWN';
														$description = $item['description'] ?? '';
														$pid = $uptime = '';
														
														// Parse description for running processes
														if ($status == 'RUNNING' && $description) {
															$desc_parts = explode(',', $description);
															$pid = isset($desc_parts[0]) ? trim($desc_parts[0]) : '';
															$uptime = isset($desc_parts[1]) ? str_replace('uptime ', '', trim($desc_parts[1])) : '';
														}
														
														// Status badge classes
														$badge_class = 'secondary';
														switch ($status) {
															case 'RUNNING': $badge_class = 'success'; break;
															case 'STARTING': $badge_class = 'info'; break;
															case 'STOPPING': $badge_class = 'warning'; break;
															case 'STOPPED': $badge_class = 'secondary'; break;
															case 'EXITED': $badge_class = 'secondary'; break;
															case 'FATAL': $badge_class = 'danger'; $alert = true; break;
															case 'BACKOFF': $badge_class = 'warning'; break;
															default: $badge_class = 'dark';
														}
												?>
												<tr>
													<td>
														<strong><?php echo htmlspecialchars($item_name); ?></strong>
														<?php if ($pid): ?>
															<br><small class="text-muted">PID: <?php echo $pid; ?></small>
														<?php endif; ?>
													</td>
													<td>
														<span class="badge bg-<?php echo $badge_class; ?>">
															<?php echo $status; ?>
														</span>
													</td>
													<td>
														<small class="text-muted">
															<?php echo $uptime ? $uptime : '—'; ?>
														</small>
													</td>
													<td>
														<div class="btn-group btn-group-sm" role="group">
															<?php if ($status == 'RUNNING'): ?>
																<a href="/control/stop/<?php echo $name . '/' . urlencode($item_name); ?>" 
																   class="btn btn-outline-danger btn-sm" title="Stop this process" data-bs-toggle="tooltip">
																	<i class="bi bi-stop-fill"></i>
																</a>
																<a href="/control/restart/<?php echo $name . '/' . urlencode($item_name); ?>" 
																   class="btn btn-outline-warning btn-sm" title="Restart this process" data-bs-toggle="tooltip">
																	<i class="bi bi-arrow-clockwise"></i>
																</a>
															<?php elseif (in_array($status, ['STOPPED', 'EXITED', 'FATAL'])): ?>
																<a href="/control/start/<?php echo $name . '/' . urlencode($item_name); ?>" 
																   class="btn btn-outline-success btn-sm" title="Start this process" data-bs-toggle="tooltip">
																	<i class="bi bi-play-fill"></i>
																</a>
															<?php endif; ?>
														</div>
													</td>
												</tr>
												<?php 
													endforeach;
												else:
												?>
												<tr>
													<td colspan="4" class="text-center text-muted py-3">
														<i class="bi bi-inbox"></i> No processes found
													</td>
												</tr>
												<?php endif; ?>
											</tbody>
										</table>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
					
					<?php endforeach; ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endforeach; ?>
		
		<?php
		if($alert && !$muted && $this->config->item('enable_alarm')){
					echo '<embed height="0" width="0" src="'.base_url('/sounds/alert.mp3').'">';
				}
				if($alert){
					echo '<title>!!! WARNING !!!</title>';
				}else{
					echo '<title>Support center</title>';
				}
				
				?>
		</div>
	</div>

    </div> <!-- /container -->
	
	<div class="footer">
	</div>
	<script>
	// Auto-refresh with countdown - no AJAX, just page reload
	var refreshInterval = <?php echo $this->config->item('refresh'); ?>;
	var currentCountdown = refreshInterval;
	
	// Update countdown every second
	function updateCountdown() {
		currentCountdown--;
		if (document.getElementById('countdown')) {
			document.getElementById('countdown').textContent = currentCountdown;
		}
		
		if (currentCountdown <= 0) {
			// Reload page for fresh data
			window.location.reload();
		}
	}
	
	// Start countdown if refresh is enabled
	if (refreshInterval > 0) {
		setInterval(updateCountdown, 1000);
		console.log('Auto-refresh enabled: ' + refreshInterval + ' seconds');
	} else {
		console.log('Auto-refresh disabled');
	}
	
	// Legacy functions for backward compatibility
	function show_content($param){
		SupervisorMonitor.pauseAutoRefresh();
		$time = new Date();
		$message = $(this).data('message')+"\n"+$time+"\n"+$time.toDateString();
		$title = $(this).data('original-title');
		$content = '<div class="well" style="padding:20px;">'+nl2br($message)+'</div>';
		$content+= '<div class="float-left"><form method="get" action="<?php echo $this->config->item('redmine_url');?>" style="display:inline" target="_blank">';
		$content+= '<input type="hidden" name="issue[subject]" value="'+$title+'"/>';
		$content+= '<input type="hidden" name="issue[description]" value="'+$message+'"/>';
		$content+= '<input type="hidden" name="issue[assigned_to_id]" value="<?php echo $this->config->item('redmine_assigne_id');?>"/>';
		$content+= '<input type="submit" class="btn btn-small btn-inverse" value="Start New Ticket"/>';
		$content+= '</form></div>';
		$content+= '<div class="float-right"><a href="#" onclick="$(\'#'+$(this).attr('id')+'\').popover(\'hide\');SupervisorMonitor.resumeAutoRefresh();" class="btn btn-small btn-primary">ok</a>&nbsp;&nbsp;';
		$content+= '<a href="'+$(this).attr('href')+'" class="btn btn-small btn-danger">Clear</a> &nbsp; </div>';
		return $content;
	}
	
	$('.pop').popover({
		content: show_content,
		html: true,
		placement: 'bottom'
	});
	
	function nl2br (str, is_xhtml) {
		var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>';
		return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
	}
	
	// No initialization needed - pure page-based mode
	console.log('Supervisor Monitor loaded - Simple mode');
	
	// Initialize Bootstrap tooltips
	$(document).ready(function() {
		// Enable all tooltips
		$('[data-bs-toggle="tooltip"]').tooltip();
		$('[title]').tooltip();
		
		console.log('Tooltips initialized');
	});
	
	// Performance monitoring
	window.addEventListener('load', function() {
		// Log performance metrics
		if (window.performance && window.performance.timing) {
			var loadTime = window.performance.timing.loadEventEnd - window.performance.timing.navigationStart;
			console.log('Page load time:', loadTime + 'ms');
		}
	});
	</script>

</body>
</html>
