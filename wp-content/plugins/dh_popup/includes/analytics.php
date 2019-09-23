<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class DH_Popup_Analytics{
	
	public static function init(){
		if(!dh_popup_stats_data())
			return;
		add_action('wp_ajax_dh_popup_update_stats_ajax', array(__CLASS__,'ajax_update_stats'));
		add_action('wp_ajax_nopriv_dh_popup_update_stats_ajax', array(__CLASS__,'ajax_update_stats'));
	}
	
	public static function admin_init(){
		if(!dh_popup_stats_data())
			return;
		add_action( 'admin_menu', array(__CLASS__,'admin_menu'),15);
	}
	
	public static function ajax_update_stats(){
		$nonce = $_POST['nonce'];
		if(false===wp_verify_nonce($nonce,'dh_popup_ajax'))
			return;
		$object_id = absint($_POST['object_id']);
		$campaign_id = absint($_POST['campaign_id']);
		$type = $_POST['type'];
		if($campaign_id)
			self::update_stats($campaign_id,$type);
		return self::update_stats($object_id,$type);
	}
	
	public static function update_stats($object_id,$type='impressions'){
		if (!dh_popup_stats_data() || current_user_can('manage_options'))
			return;
		global $wpdb;
		$table_name = self::get_table_name();
		$object_impressions = dh_popup_get_post_meta($type,$object_id,0);
		dh_popup_update_post_meta($object_id,$type,(absint($object_impressions) + 1));
		if($type === 'impressions')
			$result =  $wpdb->query("INSERT INTO $table_name (`object_id`,impressions,`date`) VALUES ({$object_id},1,CURDATE()) ON DUPLICATE KEY UPDATE impressions = impressions + 1;");
		else 
			$result = $wpdb->query("INSERT INTO $table_name (`object_id`,conversions,`date`) VALUES ({$object_id},1,CURDATE()) ON DUPLICATE KEY UPDATE conversions = conversions + 1;");
		return;
	}
	
	public static function get_table_name(){
		global $wpdb;
		return $wpdb->prefix.'dh_popup_stats';
	}
	
	public static function create_tables(){
		// Create the database table
		global $wpdb;
		$table_name = self::get_table_name();
		$wpdb->hide_errors();
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		$collate = '';
		
		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty($wpdb->charset ) ) {
				$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty($wpdb->collate ) ) {
				$collate .= " COLLATE $wpdb->collate";
			}
		}
		
		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			object_id bigint(20) NOT NULL,
			impressions bigint(20) NOT NULL,
			conversions bigint(20) NOT NULL,
			date date NOT NULL,
			UNIQUE KEY `date_id` (`date`,`object_id`),
			KEY `object_id` (`object_id`),
			KEY `date` (`date`)) " . $collate . ";";
		dbDelta($sql);
	}
	
	public static function admin_menu(){
		$page = add_submenu_page( 'dh_popup', __('Popup Analytics','dh_popup'), __('Analytics','dh_popup'), 'manage_options', 'dh_popup_analytics',array(__CLASS__,'render') );
		add_action( 'load-' . $page, array(__CLASS__,'page_load') );
	}
	
	public static function page_load(){
		wp_enqueue_style('dh_popup_analytics',DH_POPUP_URL.'/assets/css/analytics.css',array('dh_popup_jqueryui'));
		wp_register_script( 'dh_popup_analytics', DH_POPUP_URL.'/assets/js/analytics.js', array( 'jquery-ui-datepicker' ), DH_POPUP_VERSION, true );
		wp_localize_script('dh_popup_analytics', 'dh_popup_analytics', array(
			'loading'=>__('Loading data&hellip;','dh_popup'),
			'ajax_url'=>admin_url( 'admin-ajax.php', 'relative' ),
			'nonce'=>wp_create_nonce('dh_setting_select_ajax'),
			'notfound'=>__('Nothing Found&hellip;','dh_popup'),
			'delete_confirm'=>__('Are your sure?','dh_popup'),
		));
		wp_enqueue_script('dh_popup_analytics');
	}
	

	private static function _get_date_range($start,$end,$formart){
		$interval = new DateInterval('P1D');
	
		$realEnd = new DateTime($end);
		$realEnd->add($interval);
	
		$period = new DatePeriod(
			new DateTime($start),
			$interval,
			$realEnd
		);
		$range = array();
		foreach($period as $date) {
			$range[] = $date->format($formart);
		}
		return $range;
	}
	
	private static function _clear_analytics(){
		global $wpdb;
		$table_name = self::get_table_name();
		return  $wpdb->query( "TRUNCATE TABLE $table_name" );
	}
	
	public static function render(){
		global $wpdb;
		$popup_options = array();
		$popups = get_posts(array(
			'post_type'=>'dh_popup',
			'posts_per_page'=> -1,
			'post_status'=>'publish',
		));
		foreach ($popups as $popup){
			$popup_options[$popup->ID] = __('Popup: ','dh_popup').$popup->post_title;
		}
		$campaigns = get_posts(array(
			'post_type'=>'dh_popup_campaign',
			'posts_per_page'=> -1,
			'post_status'=>'publish',
		));
		foreach ($campaigns as $campaign){
			$popup_options[$campaign->ID] = __('Campaign: ','dh_popup').$campaign->post_title;
		}
		if(isset($_POST['clear_analytics']))
			self::_clear_analytics();
		
		$start_date = isset($_POST['date_start']) ? $_POST['date_start'] : '';
		$end_date = isset($_POST['date_end']) ? $_POST['date_end'] : '';
		$popup_id = isset($_POST['popup_id']) ? absint($_POST['popup_id']) : 0;
		
		if(empty($start_date) && empty($end_date)){
			$end_date = current_time('Y-m-d');
			$date = date_create($end_date);
			date_sub($date, date_interval_create_from_date_string('1 weeks'));
			$start_date = date_format($date, 'Y-m-d');
		}
		$date_range = self::_get_date_range($start_date,$end_date,'M d');
		
		$table_name = self::get_table_name();
		$chart_title = __("All popup",'dh_popup');
		$where = '';
		if($popup_id && $post = get_post($popup_id)){
			$where .= " AND object_id = '$popup_id'";
			$chart_title = $post->post_title;
		}
		if(!empty($start_date))
			$where .= " AND `date` >= '$start_date'";
		if(!empty($end_date))
			$where .= " AND `date` <= '$end_date'";
		$stats = $wpdb->get_results(
			"SELECT DATE_FORMAT(date, '%b %d') AS date,SUM(impressions) AS impressions, SUM(conversions) AS conversions,FORMAT((SUM(conversions)/SUM(impressions))*100,2) AS rate
			FROM $table_name
			WHERE 1=1 $where
			GROUP BY date
			ORDER BY date ASC"
		,OBJECT_K);
		$data = array();
		foreach ($date_range as $range){
			if(isset($stats[$range])){
				$data['impressions'][] = absint($stats[$range]->impressions);
				$data['conversions'][] = absint($stats[$range]->conversions);
				$data['rate'][] = absint($stats[$range]->rate);
			}else{
				$data['impressions'][] = 0;
				$data['conversions'][] = 0;
				$data['rate'][] =0;
			}
		}
		?>
		<div class="wrap">
			<h2><?php _e('Analytics','dh_popup')?></h2>
			<div class="dh-popup-analytics">
				<form method="post" action="<?php //menu_page_url('dh_popup_analytics') ?>">
					<div class="dh-popup-analytics-controls">
						<div class="dh-popup-analytics-control">
							<label class="dh-popup-analytics-label"><?php _e('Select Popup')?></label>
							<select name="popup_id" class="dh_popup_analytics_input">
								<option value="">
									<?php _e('All','dh_popup')?>
								</option>
								<?php foreach ($popup_options as $id=>$name){?>
								<option <?php selected($popup_id, $id)?> value="<?php echo esc_attr($id)?>">
									<?php echo esc_html($name)?>
								</option>
								<?php }?>
							</select>
						</div>
						<div class="dh-popup-analytics-control">
							<label class="dh-popup-analytics-label">
								<?php _e('From date (yy-mm-dd)','dh_popup')?>
							</label>
							<input type="text" value="<?php echo esc_attr($start_date)?>" class="dh_popup_analytics_input dh_popup_analytics_date" name="date_start">
						</div>
						<div class="dh-popup-analytics-control">
							<label class="dh-popup-analytics-label">
								<?php _e('To date (yy-mm-dd)','dh_popup')?>
							</label>
							<input type="text" value="<?php echo esc_attr($end_date)?>"  class="dh_popup_analytics_input dh_popup_analytics_date" name="date_end">
						</div>
						<div class="dh-popup-analytics-control">
							<button class="button" type="submit"><?php _e('Apply','dh_popup')?></button>
						</div>
						<div class="dh-popup-analytics-control" style="float: right; margin: 0px;">
							<button class="button button-primary" type="submit" id="clear_analytics" name="clear_analytics"><?php _e('Clear Analytics data','dh_popup')?></button>
						</div>
					</div>
					<div class="dh-popup-analytics-chart">
						 <canvas id="dh_popup_analytics_chart_canvas" style="width: 100%; height: 500px"></canvas>
					</div>
					<script type="text/javascript" src="<?php echo esc_attr(DH_POPUP_URL.'/assets/js/Chart.bundle.min.js')?>"></script>
					<script>
			       	var config = {
			            type: 'line',
			            data: {
			                labels: <?php echo json_encode($date_range)?>,
			                datasets: [{
			                    label: "<?php echo esc_js(__('Impressions','dh_popup'))?>",
			                    backgroundColor: 'rgb(255, 99, 132)',
			                    borderColor: 'rgb(255, 99, 132)',
			                    data: <?php echo json_encode($data['impressions'])?>,
			                    fill: false,
			                }, {
			                    label: "<?php echo esc_js(__('Conversions','dh_popup'))?>",
			                    fill: false,
			                    backgroundColor: 'rgb(54, 162, 235)',
			                    borderColor: 'rgb(54, 162, 235)',
			                    data: <?php echo json_encode($data['conversions'])?>,
			                }],
			                rate:<?php echo json_encode($data['rate'])?>
			            },
			            options: {
			                responsive: true,
			                title:{
			                    display:true,
			                    text:"<?php echo esc_js($chart_title)?>"
			                },
			                tooltips: {
			                    mode: 'index',
			                    intersect: false,
			                    callbacks: {
			                        // Use the footer callback to display the sum of the items showing in the tooltip
			                        footer: function(tooltipItems, data) {
			                            var rate = 0;

			                            tooltipItems.forEach(function(tooltipItem) {
			                            	rate = data.rate[tooltipItem.index];
			                            });
			                            return '<?php echo esc_js(__('Rate: ','dh_popup'))?>' + rate;
			                        },
			                    },
			                },
			                hover: {
			                    mode: 'nearest',
			                    intersect: true
			                },
			            }
			        };
			
			        window.onload = function() {
			            var ctx = document.getElementById("dh_popup_analytics_chart_canvas").getContext("2d");
			            window.myLine = new Chart(ctx, config);
			        };
			
			    </script>
				</form>
			</div>
		</div>
		<?php
	}
}