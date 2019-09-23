<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if(!class_exists('DH_Popup_Log')){
	class DH_Popup_Log{
		public static function admin_init(){
			if(!dh_popup_log_data())
				return;
			add_action( 'admin_init', array( __CLASS__, 'actions' ) );
			add_action( 'admin_menu', array(__CLASS__,'admin_menu'),20);
			
		}
		
		public static function admin_menu(){
			if(!dh_popup_log_data())
				return;
			$page = add_submenu_page( 'dh_popup', __('Popup Log','dh_popup'), __('Log','dh_popup'), 'manage_options', 'dh_popup_log',array(__CLASS__,'render') );
			add_action( 'load-' . $page, array(__CLASS__,'page_load') );
		}
		
		public static function get_table_name(){
			global $wpdb;
			return $wpdb->prefix.'dh_popup_log';
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
				id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				popup_id bigint(20) UNSIGNED NOT NULL,
				email varchar(255) NOT NULL,
				browser varchar(255) NOT NULL,
				ip varchar(32) NOT NULL,
				status longtext NOT NULL,
				custom_fields longtext NOT NULL,
				date datetime NOT NULL,
				readed tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY  (id)
			) " . $collate . ";";
			dbDelta($sql);
		}
		
		public static function page_load(){
			wp_enqueue_style('dh_popup_log',DH_POPUP_URL.'/assets/css/log.css');
			wp_register_script( 'dh_popup_log', DH_POPUP_URL.'/assets/js/log.js', array( 'jquery' ), DH_POPUP_VERSION, true );
			wp_localize_script('dh_popup_log', 'dh_popup_log', array(
				'loading'=>__('Loading data&hellip;','dh_popup'),
				'delete_confirm'=>__('Are your sure?','dh_popup'),
				'ajax_url'=>admin_url( 'admin-ajax.php', 'relative' ),
				'nonce'=>wp_create_nonce('dh_setting_select_ajax'),
				'notfound'=>__('Nothing Found&hellip;','dh_popup')
			));
			wp_enqueue_script('dh_popup_log');
		}
		
		public static function render(){
			if(isset($_GET['action']) && $_GET['action'] == 'view'){
				if ( ! current_user_can('edit_posts') )
					wp_die( __( 'Cheatin&#8217; uh?' ) );
					
				self::_details_render();
			}else{
				if ( ! current_user_can('edit_posts'))
					wp_die( __( 'Cheatin&#8217; uh?' ) );
					
				self::_list_render();
			}
		}
		
		public static function add_log($popup_id=false,$email='',$status='',$custom_fields=''){
			if(!dh_popup_log_data())
				return;
			if(false===$popup_id)
				return;
			
			$data=array(
				'popup_id'=>$popup_id,
				'email'=>$email,
				'status'=>is_array($status) ? json_encode($status) : $status,
				'custom_fields'=>is_array($custom_fields) ? json_encode($custom_fields) : $custom_fields,
				'browser'=>dh_popup_get_remote_browser(),
				'ip'=>dh_popup_get_remote_ip_addr(),
				'date'=>current_time('mysql'),
			);
			global $wpdb;
			return $wpdb->insert( self::get_table_name(), $data);
		}
		
		private static function _read_log($log_ids){
			global $wpdb;
			$count = 0;
			foreach ((array) $log_ids as $log_id) {
				$sql = "UPDATE " . self::get_table_name() . " SET `readed` = 1 WHERE id = %d";
				$result = $wpdb->query($wpdb->prepare($sql, $log_id));
				$count += (int) $result;
			}
			return $count;
		}
		
		private static function _get_log($log_id=0){
			global $wpdb;
			$sql = "";
			$sql .= "SELECT * FROM `" . self::get_table_name() . "` `log` ";
			if($log_id > 0){
				$sql .= "WHERE `log`.`id` = $log_id ";
			}
			return $wpdb->get_row($sql);
		}
		
		private static function _delete_log($log_ids){
			global $wpdb;
			$count = 0;
			foreach ((array) $log_ids as $log_id) {
				$sql = "DELETE FROM " .  self::get_table_name() . " WHERE id = %d";
				$result = $wpdb->query($wpdb->prepare($sql, $log_id));
				$count += (int) $result;
			}
			return $count;
		}
		
		private static function _get_current_page_num(){
			$current = isset($_GET['paged']) ? absint($_GET['paged']) : 0;
			return  max(1, $current);
		}
		
		private static function _clear_log(){
			global $wpdb;
			$table_name = self::get_table_name();
			return  $wpdb->query( "TRUNCATE TABLE $table_name" );
		}
		
		private static function _get_pagination($per_page, $total_items, $which)
		{
			$total_pages = ceil( $total_items / $per_page );
			$current = self::_get_current_page_num();
			$output = '<span class="displaying-num">' . sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';
		
			$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		
			$page_links = array();
		
			$disable_first = $disable_last = '';
		
			if ( $current == 1 )
				$disable_first = ' disabled';
			if ( $current == $total_pages )
				$disable_last = ' disabled';
		
			$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
				'first-page' . $disable_first,
				esc_attr__( 'Go to the first page' ),
				esc_url( remove_query_arg( 'paged', $current_url ) ),
				'&laquo;'
			);
		
			$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
				'prev-page' . $disable_first,
				esc_attr__( 'Go to the previous page' ),
				esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
				'&lsaquo;'
			);
		
			if ( 'bottom' == $which )
				$html_current_page = $current;
			else
				$html_current_page = sprintf( "<input class='current-page' title='%s' type='text' name='%s' value='%s' size='%d' />",
					esc_attr__( 'Current page' ),
					esc_attr( 'paged' ),
					$current,
					strlen( $total_pages )
				);
		
			$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
			$page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';
		
			$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
				'next-page' . $disable_last,
				esc_attr__( 'Go to the next page' ),
				esc_url( add_query_arg( 'paged', min( $total_pages, $current+1 ), $current_url ) ),
				'&rsaquo;'
			);
		
			$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
				'last-page' . $disable_last,
				esc_attr__( 'Go to the last page' ),
				esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
				'&raquo;'
			);
		
			$output .= "\n<span class='pagination-links'>" . join( "\n", $page_links ) . '</span>';
			$page_class = '';
// 			if ( $total_pages )
// 				$page_class = $total_pages < 2 ? ' one-page' : '';
// 			else
// 				$page_class = ' no-pages';
		
			return "<div class='tablenav-pages{$page_class}'>$output</div>";
		}
		
		private static function _get_log_list($popup_id = 0,$orderby='date',$order='desc',$limit = 10,$offset=0){
			global $wpdb;
			$sql = "";
			$sql .= "SELECT * FROM `" . self::get_table_name() . "` `log` ";
			if($popup_id > 0){
				$sql .= "WHERE `log`.`popup_id` = $popup_id ";
			}
		
			$sql .="ORDER BY `$orderby` $order ";
			if($limit > 0)
				$sql .="LIMIT $limit OFFSET $offset ";
		
			return $wpdb->get_results($sql);
		}
		
		private static function _get_log_count($popup_id=0){
			global $wpdb;
			$sql = "";
			$sql .= "SELECT COUNT(*) FROM `" . self::get_table_name() . "`";
			if($popup_id > 0){
				$sql .= "WHERE `popup_id` = $popup_id";
			}
			return $wpdb->get_var($sql);
		}
		
		public static function actions(){
			if(isset($_GET['page']) && 'dh_popup_log'===$_GET['page'] && (isset($_GET['bulk_action']) || isset($_GET['bulk_action2']) || isset($_GET['clear_log']))){
				
				if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'dh_popup_log' ) ) {
					wp_die( __( 'Action failed. Please refresh the page and retry.', 'dh_popup' ) );
				}
				$sendback = remove_query_arg( array( 'action', 'log_id', 'clear_log','bulk_action','bulk_action2', 'log_ids','_wpnonce'), wp_get_referer() );
				
				$action = isset($_GET['action']) ? $_GET['action'] : '';
				switch ($action){
					case 'delete':
						$log_id = absint($_GET['log_id']);
						if(wp_verify_nonce($_GET['_wpnonce'], 'delete_log_' . $log_id)){
							$count = self::_delete_log($log_id);
							$message =  $count > 0 ?  sprintf(__("%s entry deleted",'dh_popup'),$count): '';
						}
						break;
					default:
						break;
				}
				if(isset($_GET['clear_log'])){
					self::_clear_log();
					wp_redirect( $sendback );
					exit();
				}
				$bulk_action = '';
				if (isset($_GET['bulk_action']) && $_GET['bulk_action'] != '-1') {
					$bulk_action = $_GET['bulk_action'];
				} elseif (isset($_GET['bulk_action2']) && $_GET['bulk_action2'] != '-1') {
					$bulk_action = $_GET['bulk_action2'];
				}
				switch ($bulk_action){
					case 'delete':
						$log_ids = isset($_GET['log_ids']) ? $_GET['log_ids'] : array();
						$count = self::_delete_log($log_ids);
						$message =  $count > 0 ?  sprintf(__("%s entry deleted",'dh_popup'),$count): '';
						break;
					default:
						break;
				}
				if(!empty($action) || !empty($bulk_action)){
					wp_redirect( $sendback );
					exit();
				}
			}
		}
		
		private static function _list_render(){
			$columns= array(
				'id'=>__('ID','dh_popup'),
				'popup_name'=>__('Popup','dh_popup'),
				'email'=>__('Email','dh_popup'),
				'date'=>__('Date','dh_popup'),
				'ip'=>__('IP Address','dh_popup'),
				'status'=>__('Status','dh_popup')
			);
			$orderby = (isset($_GET['orderby'])  ) ? $_GET['orderby'] : 'date';
			$order = isset($_GET['order']) && strtolower($_GET['order']) == 'asc' ? 'asc' : 'desc';
			$reverseOrder = $order == 'asc' ? 'desc' : 'asc';
			
			$popup_id = isset($_GET['popup_id']) ? $_GET['popup_id'] : 0;
			$limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
			$total = self::_get_log_count($popup_id);
			$offset =  $limit * (self::_get_current_page_num() - 1);
			$topPagination ='';
			$log_list = self::_get_log_list($popup_id,$orderby,$order,$limit,$offset);
			
			
?>
<div class="wrap">
	<h2><?php echo __('Log','dh_popup')?></h2>
	<?php if(!empty($message)):?>
	<div id="message" class="updated below-h2">
		<p><?php echo $message?></p>
	</div>
	<?php endif;?>
	<form id="dh_popup_log_form" action="" method="get">
		<?php /*
		<input type="hidden" name="_wp_http_referer" value="<?php menu_page_url('dh_popup_log'); ?>" />
		*/?>
		<input type="hidden" name="page" value="dh_popup_log" />
		<?php wp_nonce_field('dh_popup_log')?>
		<ul class="subsubsub">
			<li class="all">
				<a class="current" href="#"><?php echo __('All','dh_popup')?> <span class="count">(<?php echo (int) $total ?>)</span></a>
			</li>
		</ul>
		<div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="bulk_action">
                    <option selected="selected" value="-1"><?php esc_html_e('Bulk Actions', 'dh_popup'); ?></option>
                    <option value="delete"><?php esc_html_e('Delete', 'dh_popup'); ?></option>
                </select>
                <input type="submit" value="<?php esc_attr_e('Apply', 'dh_popup'); ?>" class="button action dh-popup-action" id="doaction" name="" />
           </div>
             <?php echo self::_get_pagination($limit, $total, 'top'); ?>
            <br class="clear" />
        </div>
        <table class="wp-list-table widefat fixed dh-popup-log-list">
            <thead>
                <tr>
                    <th class="manage-column column-cb check-column" id="cb" scope="col">
                        <input type="checkbox" class="headercb" />
                    </th>
                    <?php ob_start(); ?>
                    
                        <?php foreach ($columns as $key=>$label) : ?>
                        	<?php if($key=='popup_name' || $key=='status'):?>
                        	<th class="manage-column">
								<span><?php echo esc_html($label); ?></span>
							</th>
                        	<?php else:?>
	                            <?php if ($key == $orderby) : ?>
	                                <th class="manage-column log-<?php echo $key; ?> sorted <?php echo $order; ?>" scope="col">
	                                    <a href="<?php echo esc_url(add_query_arg(array('orderby' => $key, 'order' => strtolower($reverseOrder)))); ?>">
	                            <?php else : ?>
	                                <th class="manage-column log-<?php echo $key; ?> sortable desc" scope="col">
	                                    <a href="<?php echo esc_url(add_query_arg(array('orderby' => $key, 'order' => 'asc'))); ?>">
	                            <?php endif; ?>
	                                    <span><?php echo esc_html($label); ?></span>
	                                    <span class="sorting-indicator"></span>
	                                    </a>
	                                </th>
                             <?php endif;?>
                        <?php endforeach; ?>
                    <?php echo $headings = ob_get_clean(); ?>
                </tr>
            </thead>

            <tfoot>
                <tr>
                    <th class="manage-column column-cb check-column" scope="col">
                        <input type="checkbox" />
                    </th>
                    <?php echo $headings; ?>
                </tr>
            </tfoot>

            <tbody id="the-list">
                <?php if (count($log_list)) : ?>
                    <?php $i = 1; ?>
                    <?php foreach ($log_list as $log) : ?>
                        <tr valign="top" class="<?php echo (++$i % 2 == 1) ? 'alternate ' : ''; ?>" id="dh-popup-log-<?php echo $log->id; ?>">
                            <th class="check-column" scope="row">
                                <input type="checkbox" value="<?php echo $log->id; ?>" name="log_ids[]" />
                            </th>
                            <td class="dh-popup-log-id">
                            	<?php echo $log->id?>
                            </td>
                            <td class="dh-popup-log-popup-name">
                            	<a href="<?php echo get_edit_post_link($log->popup_id); ?>" title="<?php esc_attr_e('Edit Popup','dh_popup')?>"><?php echo get_the_title($log->popup_id)?></a>
                            </td>
                            <td class="dh-popup-log-popup-email">
                            	<?php echo isset($log->email) ? $log->email:'--'?>
                            </td>
                            <td class="dh-popup-log-date">
                            	<?php
                            	$t_time = sprintf( __( '%1$s at %2$s' ),
                            		mysql2date('M j,Y', $log->date),
                            		mysql2date('g:i a', $log->date )
                            	);
                            	echo $t_time;
                            	?>
                            </td>
                            <td class="dh-popup-log-popup-ip">
                            	<?php echo isset($log->ip) ? $log->ip:'--'?>
                            </td>
                             <td class="dh-popup-log-popup-status">
                            	<?php echo isset($log->status) ? $log->status:'--'?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="no-items">
                        <td colspan="<?php echo (count($columns) + 1); ?>" class="colspanchange"><p style="text-align: center; font-weight: bold; font-size: 20px;"><?php esc_html_e('No log found.', 'dh_popup'); ?></p></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <select name="bulk_action2">
                    <option selected="selected" value="-1"><?php esc_html_e('Bulk Actions', 'dh_popup'); ?></option>
                    <option value="delete"><?php esc_html_e('Delete', 'dh_popup'); ?></option>
                </select>
                <input type="submit" value="<?php esc_attr_e('Apply', 'dh_popup'); ?>" class="button action dh-popup-action2" id="doaction" name="" />
            	<input type="submit" value="<?php esc_attr_e('Clear Log Data', 'dh_popup'); ?>" class="button dh-popup-clear-log button-primary" id="clear_log" name="clear_log" />
            </div>
            <?php echo self::_get_pagination($limit, $total, 'buttom'); ?>
            <br class="clear" />
        </div>
	</form>
</div>
<?php
		}
		
		private static function _details_render(){
			
		}
	}
}