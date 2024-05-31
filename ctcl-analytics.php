<?php
/*
 Plugin Name:CTCL Analytics
 Plugin URI:
 Description: CT Commerce Lite ecommerce plugin's analytics
 Version: 1.0.0
 Author: Ujwol Bastakoti
 Author URI:https://ujw0l.github.io/
 Text Domain:  ctcl-analytics
 License: GPLv2
*/

namespace ctclAnalytics;

if ( ! defined( 'ABSPATH' ) ) exit; 

use DateTime;
if(class_exists('ctcLite')){ 
class ctclAnalytics{

   
public function __construct() {
    define('CTCLA_DIR_PATH',plugin_dir_url(__FILE__) );  

   
    add_action( 'admin_enqueue_scripts', array($this,'ctclAEnequeScript' ));
    add_action('admin_enqueue_scripts', function(){ wp_enqueue_style( 'ctclAnalyticsAdminCss', CTCLA_DIR_PATH.'css/analytics.css');  });
    add_filter('ctcl-info-tab-sub-tab', array($this, 'ctclSubTabHtml'),20, 1 );
    add_filter('script_loader_tag', array($this,'AddTagToScript' ) , 10, 3);

}

/**
 * @since 1.0.0
 * 
 * function to display sub tab under information tab
 * 
 * @param $infoTabHtml Array to be filtered 
 * 
 * @return $infoTabHtml array with added information for sub tab
 */

 public function ctclSubTabHtml($infoTabHtml){

    $html = array (

        'title'=> __('Analytics','ctcl-analytics'),
        'icon'=> 'dashicons-chart-line',
        'html'=> $this->subTabHtml(),

    );

    array_push($infoTabHtml, $html);

    return $infoTabHtml;
 }


 /**
 * @since 1.0.0
 * 
 * function to display HTML for sub tab
 * 
 * @return variable varible for html of the subtab
 */

 public function subTabHtml(){
    
    $data = $this->ctclaChartData();
    $sales =  0;
    foreach($data as $key => $val):

        $sales += $val[1];

    endforeach;

    ob_start();
    ?>

<div class="ctcl-analytics-tab-main">
<h3 class=" dashicons-before  dashicons-chart-line  ctcl-basic-info-header"><?=__("Store Analytics",'ctcl-analytics')?></h3>
<div class="ctcl-analytics-tab">

<fieldset>
<legend class='dashicons-before dashicons-chart-line'><?php echo __('Chart','ctcl-analytics');?></legend>

<div class="ctclAChart">
  <canvas id="myChart"></canvas>
</div>

<div class='ctclASales'>
    <span><?php echo __('Total Sales in last 12 months ')."(".get_option('ctcl_currency')."):" ?></span>
    <span><?php echo number_format((float)$sales, 2, '.', ''); ?></span>
 </div>
 </fieldset>
 </div>
 </div>
<?php
return ob_get_clean();
 }

  /**
 * @since 1.0.0
 * 
 * function to eneque scripts 
 * 
 * 
 */

public function ctclAEnequeScript(){

    wp_enqueue_script('chartJs', 'https://cdn.jsdelivr.net/npm/chart.js',array());

    wp_enqueue_script('ctclAnalyticsAdminJs', CTCLA_DIR_PATH.'js/analytics.js',array('chartJs'));
     wp_localize_script('ctclAnalyticsAdminJs','ctclAnalyticsObject', array(
        'sales'=>__('Total Sales (', 'ctcl-analytics').get_option('ctcl_currency').')',
        'data'=>$this->ctclaChartData(),
    ));
}


  /**
 * @since 1.0.0
 * 
 * Function to get data to be used by chart in subtab
 * 
 * @return $data array of months and sales
 */

public function ctclaChartData(){

    global $wpdb;

    $data = array();
 

    // Get the current date and time
    $currentDate = new \DateTime('first day of this month');
    
    // Loop through the last 12 months
    for ($i = 0; $i < 12; $i++) {

        $sales = 0;

        // Clone the current date object to avoid modifying the original
        $startDate = clone $currentDate;
        $endDate = clone $currentDate;
        
        // Get the start of the month
        $startDate->modify('-' . $i . ' months');
        $startDateTimestamp = $startDate->getTimestamp();
        
        // Get the end of the month
        $endDate->modify('-' . $i . ' months');
        $endDate->modify('last day of this month');
        $endDateTimestamp = $endDate->getTimestamp();



        $sql =   $wpdb->prepare('SELECT orderDetail FROM '. $wpdb->prefix.'ctclOrders WHERE orderId BETWEEN %d AND %d; ',$startDateTimestamp,$endDateTimestamp );
        

        $order =  $wpdb->get_results($sql,ARRAY_A);

        foreach($order as $key=>$val):
            $or = json_decode($val['orderDetail'],ARRAY_A);

          $sales += $or['items-total'];
  
        endforeach;
      
        $data[] = [ $startDate->format('F') ." ". $startDate->format('Y') , $sales ] ;

    }
    return array_reverse( $data);
}

// Add type="module" attribute to the script
public function AddTagToScript($tag, $handle, $src) {
    // Check if the script is the one we want to modify
    if ('ctclAnalyticsAdminJs' === $handle) {
        // Add the type attribute
        $tag = '<script type="module" src="' . esc_url($src) . '"></script>';
    }
    return $tag;
}




}

new ctclAnalytics();
}
else{
    add_thickbox();
		/**
		 * If main plugin CTC lite is not installed
		 */
		 add_action( 'admin_notices', function(){
			 echo '<div class="notice notice-error is-dismissible"><p>';
			  _e( 'CTCL Analytics plugin requires CT Commerce Lite plugin installed and activated to work, please do so first.', 'ctcl-analytics' );
			  echo '<a href="'.admin_url('plugin-install.php').'?tab=plugin-information&plugin=ctc-lite&TB_iframe=true&width=640&height=500" class="thickbox">'.__('Click Here to install it','ctcl-analytics').' </a>'; 
			 echo '</p></div>';
		 } );
}