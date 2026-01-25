<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Omisell Supervisord Monitoring</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link type="text/css" rel="stylesheet" href="<?php echo base_url('/css/bootstrap.min.css');?>"/>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
	<link type="text/css" rel="stylesheet" href="<?php echo base_url('/css/custom.css');?>"/>
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
					<a class="dropdown-item" href="<?php echo site_url('logout'); ?>">
						<i class="bi bi-box-arrow-right"></i> Đăng xuất
					</a>
				</div>
			</li>
		</ul>
		<?php endif; ?>
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
				$alert = false;
				foreach($list as $name=>$procs){
					$parsed_url = parse_url($cfg[$name]['url']);
					if ( isset($cfg[$name]['username']) && isset($cfg[$name]['password']) ){
						$base_url = 'http://' . $cfg[$name]['username'] . ':' . $cfg[$name]['password'] . '@';
					}else{
						$base_url = 'http://';
					}
					$ui_url = $base_url . $parsed_url['host'] . ':' . $cfg[$name]['port']. '/';
				?>
				<div class="col-xl-4 col-lg-6 col-md-6">
					<table class="table table-bordered table-condensed table-striped">
						<tr><th colspan="4">
							<a href="<?php echo $ui_url; ?>"><?php echo $name; ?></a> <?php if($this->config->item('show_host')){ ?><i><?php echo $parsed_url['host']; ?></i><?php } ?>
							<?php
							if(isset($cfg[$name]['username'])){echo '<i class="icon-lock icon-green" style="color:blue" title="Authenticated server connection"></i>';}
							echo '&nbsp;<i>'.$version[$name].'</i>';
							if(!isset($procs['error'])){
							?>
							<span class="server-btns float-right">
								<a href="<?php echo site_url('/control/stopall/'.$name); ?>" class="btn btn-sm btn-dark" type="button"><i class="glyphicon glyphicon-stop"></i> Stop all</a>
								<a href="<?php echo site_url('/control/startall/'.$name); ?>" class="btn btn-sm btn-success" type="button"><i class="glyphicon glyphicon-play"></i> Start all</a>
								<a href="<?php echo site_url('/control/restartall/'.$name); ?>" class="btn btn-sm btn-primary" type="button"><i class="glyphicon glyphicon-refresh"></i> Restart all</a>
							</span>
							<?php
							}
							?>
						</th></tr>
						<?php
						$CI = &get_instance();
						foreach($procs as $item){

							if($item['group'] != $item['name']) $item_name = $item['group'].":".$item['name'];
							else $item_name = $item['name'];
							
							$check = $CI->_request($name,'readProcessStderrLog',array($item_name,-1000,0));
							if(is_array($check)) $check = print_r($check,1);
							
							if(!is_array($item)){
									// Not having array means that we have error.
									echo '<tr><td colspan="4">'.$item.'</td></tr>';
									echo '<tr><td colspan="4">For Troubleshooting <a href="https://github.com/mlazarov/supervisord-monitor#troubleshooting" target="_blank">check this guide</a></td></tr>';
									continue;
							}

							$pid = $uptime = '&nbsp;';
							$status = $item['statename'];
							if($status=='RUNNING'){
								$class = 'success';
								list($pid,$uptime) = explode(",",$item['description']);
							}
							elseif($status=='STARTING') $class = 'info';
							elseif($status=='FATAL') { $class = 'danger'; $alert = true; }
							elseif($status=='STOPPED') $class = 'dark';
							else $class = 'error';

							$uptime = str_replace("uptime ","",$uptime);
							?>
							<tr>
								<td><?php
									echo $item_name;
									// if($check){
									// 	$alert = true;
									// 	echo '<span class="float-right">
									// 			<a href="'.site_url('/control/clear/'.$name.'/'.$item_name).'" id="'.$name.'_'.$item_name.
									// 			'" onclick="return false" data-toggle="popover" data-message="'.htmlspecialchars($check).'" data-original-title="'.
									// 			$item_name.'@'.$name.'" class="pop btn btn-mini btn-danger"><img src="' . base_url('/img/alert_icon.png') . '" /></a>
									// 		</span>';
									// }
									// ?>
								</td>
								<td width="10"><span class="badge badge-<?php echo $class;?>"><?php echo $status;?></span></td>
								<td width="80" style="text-align:right"><?php echo $uptime;?></td>
								<td style="width:1%">
									<!--div class="btn-group">
										<button class="btn btn-mini">Action</button>
										<button class="btn btn-mini dropdown-toggle" data-toggle="dropdown">
											<span class="caret"></span>
										</button>
										<ul class="dropdown-menu">
											<li><a href="test">Restart</a></li>
											<li><a href="zz">Stop</a></li>
										</ul>
									</div//-->
									<div class="actions">
										<?php if($status=='RUNNING'){ ?>
										<a href="<?php echo site_url('/control/stop/'.$name.'/'.$item_name);?>" class="btn btn-sm omi-btn-sm btn-dark" type="button"><i class="bi bi-stop-fill"></i></a>
										<a href="<?php echo site_url('/control/restart/'.$name.'/'.$item_name);?>" class="btn btn-sm omi-btn-sm btn-dark" type="button"><i class="bi bi-arrow-counterclockwise"></i></a>
										<?php } if($status=='STOPPED' || $status == 'EXITED' || $status=='FATAL'){ ?>
										<a href="<?php echo site_url('/control/start/'.$name.'/'.$item_name);?>" class="btn btn-sm omi-btn-sm btn-dark" type="button"><i class="bi bi-play-fill"></i></a>
										<?php } ?>
									</div>
								</td>
							</tr>
							<?php
						}

						?>
					</table>				
				</div>
				<?php
				}
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
			window.location.href = SupervisorMonitor.config.baseUrl + '/welcome/clear_cache';
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
