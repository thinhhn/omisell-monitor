<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Omisell Supervisord Monitoring</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link type="text/css" rel="stylesheet" href="<?php echo base_url('/css/bootstrap.min.css');?>"/>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
	<link type="text/css" rel="stylesheet" href="<?php echo base_url('/css/custom.css');?>"/>
	
	<!-- CSS Fixes for UI -->
	<style>
	.container { max-width: 100%; padding: 0 15px; }
	.card { border: 1px solid #dee2e6; border-radius: 0.25rem; margin-bottom: 1rem; background: white; }
	.card-header { background-color: #007bff; color: white; padding: 0.75rem 1rem; border-bottom: 1px solid #dee2e6; }
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
	.alert { padding: 0.75rem 1.25rem; margin-bottom: 1rem; border: 1px solid transparent; border-radius: 0.25rem; }
	.alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
	.text-muted { color: #6c757d !important; }
	.text-center { text-align: center; }
	.py-3 { padding-top: 1rem; padding-bottom: 1rem; }
	.col-xl-4 { width: 33.333333%; float: left; padding: 0 10px; }
	.col-xl-6 { width: 50%; float: left; padding: 0 10px; }
	.row::after { content: ""; clear: both; display: table; }
	.btn-group .btn { margin-right: 2px; }
	.server-controls { margin-top: 10px; }
	.mb-3 { margin-bottom: 1rem; }
	.table-responsive { overflow-x: auto; }
	.table-hover tbody tr:hover { background-color: rgba(0,0,0,.075); }
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
	  	<a class="navbar-brand" href="<?php echo site_url('');?>">Omisell Supervisord Center</a>
		<ul class="navbar-nav mr-auto mt-2 mt-lg-0">
			<li class="nav-item">
				<a class="nav-link" href="?mute=<?php echo ($muted?-1:1);?>"><i class="icon-music icon-white"></i>&nbsp;<?php
					if($muted){
						echo "Unmute";
					}else{
						echo "Mute";
					}
				;?></a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="<?php echo site_url();?>">Refresh <b id="refresh">(<?php echo $this->config->item('refresh');?>)</b> &nbsp;</a>
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
		
		<!-- Flash Messages -->
		<div class="container mt-3">
			<?php if ($this->session->flashdata('success')): ?>
				<div class="alert alert-success alert-dismissible fade show" role="alert">
					<i class="bi bi-check-circle"></i> <?php echo $this->session->flashdata('success'); ?>
					<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
				</div>
			<?php endif; ?>
			
			<?php if ($this->session->flashdata('error')): ?>
				<div class="alert alert-danger alert-dismissible fade show" role="alert">
					<i class="bi bi-exclamation-triangle"></i> <?php echo $this->session->flashdata('error'); ?>
					<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
				</div>
			<?php endif; ?>
			
			<?php if ($this->session->flashdata('warning')): ?>
				<div class="alert alert-warning alert-dismissible fade show" role="alert">
					<i class="bi bi-exclamation-triangle"></i> <?php echo $this->session->flashdata('warning'); ?>
					<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
				</div>
			<?php endif; ?>
		</div>
      </div>
    </div>
	

	<div class="container mt-3">
		<!-- Performance Stats Bar -->
		<div class="row mb-3">
			<div class="col-12">
				<div class="card bg-light">
					<div class="card-body p-2">
						<div class="d-flex justify-content-between align-items-center">
							<div>
								<small class="text-muted">
									<i class="bi bi-clock"></i> Load Time: <span id="load-time"><?php echo isset($load_time) ? $load_time : '0'; ?>ms</span>
									| <i class="bi bi-hdd"></i> Cache: <?php echo $cache_stats['total_files']; ?> files (<?php echo $cache_stats['total_size']; ?>KB)
									| <i class="bi bi-arrow-clockwise"></i> Auto-refresh: <span id="countdown"><?php echo $this->config->item('refresh'); ?></span>s
								</small>
							</div>
							<div>
								<button class="btn btn-sm btn-outline-primary" onclick="toggleAutoRefresh()">
									<i class="bi bi-pause-circle" id="refresh-icon"></i> <span id="refresh-text">Pause</span>
								</button>
								<button class="btn btn-sm btn-outline-secondary" onclick="clearCache()">
									<i class="bi bi-trash"></i> Clear Cache
								</button>
								<button class="btn btn-sm btn-outline-success" onclick="manualRefresh()">
									<i class="bi bi-arrow-clockwise"></i> Refresh Now
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	
		<?php
		if($muted){
			echo '<div class="row"><div class="span4 offset4 label label-important" style="padding:10px;margin-bottom:20px;text-align:center;">';
			echo 'Sound muted for '.timespan(time(),$muted).' <span class="float-right"><a href="?mute=-1" style="color:white;"><i class="icon-music icon-white"></i> Unmute</a></span></div></div>';
		}
	
		?>
		<div class="row">
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
				$break_count = 0;
				$total_servers = count($cfg);
				$cols_per_row = $this->config->item('supervisor_cols') ?: 3;
				
				foreach($list as $name => $procs){
					// Handle server configuration
					if (!isset($cfg[$name])) {
						continue; // Skip if server config not found
					}
					
					$server_config = $cfg[$name];
					$parsed_url = parse_url($server_config['url']);
					
					// Build UI URL for supervisor web interface
					if (isset($server_config['username']) && isset($server_config['password'])) {
						$ui_url = 'http://' . $server_config['username'] . ':' . $server_config['password'] . '@' . $parsed_url['host'] . ':' . $server_config['port'] . '/';
					} else {
						$ui_url = 'http://' . $parsed_url['host'] . ':' . $server_config['port'] . '/';
					}
					
					// Determine column width based on configuration
					$col_class = 'col-xl-4 col-lg-6 col-md-6'; // Default for 3 columns
					if ($cols_per_row == 2) {
						$col_class = 'col-xl-6 col-lg-6 col-md-6';
					}
				?>
				<div class="<?php echo $col_class; ?>">
					<div class="card mb-3">
						<div class="card-header bg-primary text-white">
							<div class="d-flex justify-content-between align-items-center">
								<div>
									<h6 class="mb-0">
										<a href="<?php echo $ui_url; ?>" class="text-white text-decoration-none" target="_blank">
											<i class="bi bi-server"></i> <?php echo $name; ?>
										</a>
										<?php if($this->config->item('show_host')): ?>
											<small class="ms-2 opacity-75">(<?php echo $parsed_url['host']; ?>)</small>
										<?php endif; ?>
									</div>
								<div class="server-info">
									<?php if(isset($server_config['username'])): ?>
										<i class="bi bi-shield-lock text-warning" title="Authenticated Connection"></i>
									<?php endif; ?>
									<small class="ms-1 opacity-75">
										<?php 
										if (isset($version[$name]) && !isset($version[$name]['error'])) {
											echo $version[$name];
										} else {
											echo 'Unknown';
										}
										?>
									</small>
								</div>
							</div>
							
							<?php if (!isset($procs['error'])): ?>
							<div class="server-controls mt-2">
								<div class="btn-group" role="group">
									<a href="<?php echo site_url('control/startall/' . $name); ?>" 
									   class="btn btn-success btn-sm" title="Start All">
										<i class="bi bi-play-fill"></i> Start All
									</a>
									<a href="<?php echo site_url('control/restartall/' . $name); ?>" 
									   class="btn btn-warning btn-sm" title="Restart All">
										<i class="bi bi-arrow-clockwise"></i> Restart All
									</a>
									<a href="<?php echo site_url('control/stopall/' . $name); ?>" 
									   class="btn btn-danger btn-sm" title="Stop All">
										<i class="bi bi-stop-fill"></i> Stop All
									</a>
								</div>
							</div>
							<?php endif; ?>
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
															<a href="<?php echo site_url('control/stop/' . $name . '/' . $item_name); ?>" 
															   class="btn btn-outline-danger btn-sm" title="Stop">
																<i class="bi bi-stop-fill"></i>
															</a>
															<a href="<?php echo site_url('control/restart/' . $name . '/' . $item_name); ?>" 
															   class="btn btn-outline-warning btn-sm" title="Restart">
																<i class="bi bi-arrow-clockwise"></i>
															</a>
														<?php elseif (in_array($status, ['STOPPED', 'EXITED', 'FATAL'])): ?>
															<a href="<?php echo site_url('control/start/' . $name . '/' . $item_name); ?>" 
															   class="btn btn-outline-success btn-sm" title="Start">
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
				
				<?php
					// Handle column breaks for better layout
					if (isset($server_config['is_break']) && $server_config['is_break']) {
						echo '</div><div class="row">';
						$break_count = 0;
					} else {
						$break_count++;
						if ($break_count % $cols_per_row == 0) {
							echo '</div><div class="row">';
						}
					}
				?>
				<?php } // End foreach servers ?>
		</div> <!-- End row -->
		
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
		<p>Powered by <a href="https://github.com/mlazarov/supervisord-monitor" target="_blank">Supervisord Monitor</a> | Page rendered in <strong>{elapsed_time}</strong> seconds</p>
	</div>
	<script>
	// Enhanced Supervisor Monitor JavaScript with Performance Optimization
	var SupervisorMonitor = {
		config: {
			refresh: <?php echo $this->config->item('refresh');?>,
			baseUrl: '<?php echo site_url(); ?>',
			ajaxTimeout: 10000,
			maxRetries: 3
		},
		
		state: {
			autoRefresh: true,
			currentRefresh: <?php echo $this->config->item('refresh');?>,
			timer: null,
			countdownTimer: null,
			lastUpdate: null,
			errorCount: 0,
			isUpdating: false
		},
		
		init: function() {
			this.initEventHandlers();
			this.startAutoRefresh();
			this.initWebSocket();
			this.startCountdown();
		},
		
		initEventHandlers: function() {
			var self = this;
			
			// Handle page visibility for performance
			document.addEventListener('visibilitychange', function() {
				if (document.hidden) {
					self.pauseAutoRefresh();
				} else {
					if (self.state.autoRefresh) {
						self.resumeAutoRefresh();
					}
				}
			});
			
			// Handle connection errors
			$(document).ajaxError(function(event, xhr, settings, error) {
				self.handleAjaxError(xhr, error);
			});
		},
		
		startAutoRefresh: function() {
			if (this.config.refresh > 0 && this.state.autoRefresh) {
				var self = this;
				this.state.timer = setInterval(function() {
					self.updateData();
				}, this.config.refresh * 1000);
			}
		},
		
		startCountdown: function() {
			if (this.config.refresh > 0) {
				var self = this;
				this.state.countdownTimer = setInterval(function() {
					self.updateCountdown();
				}, 1000);
			}
		},
		
		updateCountdown: function() {
			if (this.state.currentRefresh > 0) {
				this.state.currentRefresh--;
				$('#countdown').text(this.state.currentRefresh);
			} else {
				this.state.currentRefresh = this.config.refresh;
			}
		},
		
		updateData: function() {
			if (this.state.isUpdating) return;
			
			this.state.isUpdating = true;
			var startTime = performance.now();
			var self = this;
			
			// Show loading indicator
			this.showLoadingIndicator();
			
			// Use AJAX to update data without full page reload
			$.ajax({
				url: this.config.baseUrl + '/welcome/ajax_update',
				type: 'GET',
				dataType: 'json',
				timeout: this.config.ajaxTimeout,
				data: {
					timestamp: Date.now(),
					partial: 1
				},
				success: function(response) {
					self.handleUpdateSuccess(response, startTime);
				},
				error: function(xhr, status, error) {
					self.handleUpdateError(xhr, status, error);
				},
				complete: function() {
					self.state.isUpdating = false;
					self.hideLoadingIndicator();
				}
			});
		},
		
		handleUpdateSuccess: function(response, startTime) {
			var loadTime = Math.round(performance.now() - startTime);
			
			// Update load time display
			$('#load-time').text(loadTime + 'ms');
			
			// Reset error count on success
			this.state.errorCount = 0;
			this.state.lastUpdate = new Date();
			
			// Update process status if data received
			if (response && !response.error) {
				this.updateProcessStatus(response);
			}
			
			// Update connection status indicator
			this.updateConnectionStatus('connected');
		},
		
		handleUpdateError: function(xhr, status, error) {
			this.state.errorCount++;
			console.warn('Update failed:', status, error);
			
			// Show error indicator
			this.updateConnectionStatus('error');
			
			// Implement exponential backoff for retries
			if (this.state.errorCount < this.config.maxRetries) {
				var retryDelay = Math.pow(2, this.state.errorCount) * 1000;
				setTimeout(() => this.updateData(), retryDelay);
			}
		},
		
		handleAjaxError: function(xhr, error) {
			if (xhr.status === 401) {
				// Session expired
				window.location.href = this.config.baseUrl + '/auth';
			}
		},
		
		updateProcessStatus: function(data) {
			// Update process cards with new data
			// This would be implemented based on your specific UI structure
			console.log('Updating process status', data);
		},
		
		updateConnectionStatus: function(status) {
			var indicator = $('#connection-status');
			if (indicator.length === 0) {
				// Create indicator if it doesn't exist
				$('.navbar-nav').append('<li class="nav-item"><span class="nav-link" id="connection-status"></span></li>');
				indicator = $('#connection-status');
			}
			
			switch(status) {
				case 'connected':
					indicator.html('<i class="bi bi-wifi text-success"></i>').attr('title', 'Connected');
					break;
				case 'error':
					indicator.html('<i class="bi bi-wifi-off text-danger"></i>').attr('title', 'Connection Error');
					break;
				case 'updating':
					indicator.html('<i class="bi bi-arrow-clockwise text-warning"></i>').attr('title', 'Updating...');
					break;
			}
		},
		
		showLoadingIndicator: function() {
			this.updateConnectionStatus('updating');
		},
		
		hideLoadingIndicator: function() {
			// Loading indicator will be updated by success/error handlers
		},
		
		pauseAutoRefresh: function() {
			if (this.state.timer) {
				clearInterval(this.state.timer);
				this.state.timer = null;
			}
		},
		
		resumeAutoRefresh: function() {
			if (!this.state.timer && this.state.autoRefresh) {
				this.startAutoRefresh();
			}
		},
		
		initWebSocket: function() {
			// WebSocket implementation for real-time updates (optional)
			// This would require a WebSocket server implementation
		}
	};
	
	// Global functions for UI controls
	function toggleAutoRefresh() {
		if (SupervisorMonitor.state.autoRefresh) {
			SupervisorMonitor.state.autoRefresh = false;
			SupervisorMonitor.pauseAutoRefresh();
			$('#refresh-icon').removeClass('bi-pause-circle').addClass('bi-play-circle');
			$('#refresh-text').text('Resume');
		} else {
			SupervisorMonitor.state.autoRefresh = true;
			SupervisorMonitor.resumeAutoRefresh();
			$('#refresh-icon').removeClass('bi-play-circle').addClass('bi-pause-circle');
			$('#refresh-text').text('Pause');
		}
	}
	
	function clearCache() {
		if (confirm('Xóa tất cả cache? Điều này có thể làm chậm tải trang tiếp theo.')) {
			window.location.href = SupervisorMonitor.config.baseUrl + '/welcome/clearCache';
		}
	}
	
	function manualRefresh() {
		SupervisorMonitor.updateData();
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
	
	// Initialize when document is ready
	$(document).ready(function() {
		SupervisorMonitor.init();
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
