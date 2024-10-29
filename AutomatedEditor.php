<?php
/*
Plugin Name: Automated Editor
Plugin URI: http://www.automatededitor.com
Description: Add powerful functionality to your WordPress blog, automate editing, gain complete control of your content by leveraging the power of automation to fix, upgrade and supplement content.
Version: 1.3
Author: Automated Editor.com
Author URI: http://www.automatededitor.com
*/

#} Hooks

    #} Install/uninstall
    register_activation_hook(__FILE__,'aeplugin__install');
    register_deactivation_hook(__FILE__,'aeplugin__uninstall');
    
    #} general
    add_action('admin_menu', 'aeplugin__admin_menu'); #} admin menu
    add_action('admin_head', 'aeplugin__header_includes'); #} admin menu css + js
    add_action('wp_loaded','aeplugin_ops_doExportIfShould'); #} export if should
    
        
    #} Hooks for operations
    add_action('publish_post', 'aeplugin_ops_RunRulesPublishPost',6); #auto run after publish
    add_action('ae_runHourly', 'aeplugin_ops_RunRulesScheduleHour');
    
    function aeplugin_scheduling_RunHourlyActivation() {
    	if ( !wp_next_scheduled( 'ae_runHourly' ) ) {
    		wp_schedule_event(time(), 'hourly', 'ae_runHourly');
    	}
    }
    add_action('wp', 'aeplugin_scheduling_RunHourlyActivation');
    
    add_action('ae_runDaily', 'aeplugin_ops_RunRulesScheduleDay');
    function aeplugin_scheduling_RunDailyActivation() {
    	if ( !wp_next_scheduled( 'ae_runDaily' ) ) {
    		wp_schedule_event(time(), 'daily', 'ae_runDaily');
    	}
    }
    add_action('wp', 'aeplugin_scheduling_RunDailyActivation');


#} Initial Vars
global $aeplugin_db_version;
$aeplugin_db_version = "1.0";
$aeplugin_version = "1.0";


#} Table names
global $wpdb;
global $aeplugin_t;
$aeplugin_t['schedules'] = $wpdb->prefix . "AutomatedEditor_Schedule";
$aeplugin_t['rules'] = $wpdb->prefix . "AutomatedEditor_Rules";
$aeplugin_t['logs'] = $wpdb->prefix . "AutomatedEditor_Log";
$aeplugin_t['relationships'] = $wpdb->prefix . "AutomatedEditor_Relationships";


#} Urls
global $aeplugin_urls;
$aeplugin_urls['members'] = "http://members.automatededitor.com";
$aeplugin_urls['videos'] = "http://www.automatededitor.com/videos/";
$aeplugin_urls['faq'] = "http://www.automatededitor.com/faq/";
$aeplugin_urls['forum'] = "http://members.automatededitor.com/forum/";
$aeplugin_urls['ultrapro'] = "http://www.automatededitor.com/compare-versions/";
$aeplugin_urls['disclaimer'] = "http://www.automatededitor.com/disclaimer/";
$aeplugin_urls['timesapprox'] = "http://www.automatededitor.com/why-times-are-approximate-on-schedules/"; #} explanation as to approx timings
$aeplugin_urls['guide_importexport'] = "http://www.automatededitor.com"; #} on import export page, guide to importing exporting with template.


#} Page slugs
global $aeplugin_slugs;
$aeplugin_slugs['home'] = "ae-plugin-home";
$aeplugin_slugs['schedules'] = "ae-plugin-schedules";
$aeplugin_slugs['rules'] = "ae-plugin-rules";
$aeplugin_slugs['importexport'] = "ae-plugin-datadesk";
$aeplugin_slugs['options'] = "ae-plugin-options";
$aeplugin_slugs['logs'] = "ae-plugin-logs";
$aeplugin_slugs['plugindir'] = str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
$aeplugin_slugs['plugindir'] = substr($aeplugin_slugs['plugindir'],0,strlen($aeplugin_slugs['plugindir'])-1);

#} Includes
require_once(ABSPATH . 'wp-content/plugins/'.$aeplugin_slugs['plugindir'].'/prettyDate.php');

#} Install function
function aeplugin__install(){
	
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
    #} Create tables
    	
        
		#} Log table
		$sql = "CREATE TABLE " . $aeplugin_t['logs'] . " (
				  AE_LogID int(8) NOT NULL AUTO_INCREMENT,
				  AE_ScheduleID int(8) NOT NULL,
				  AE_RuleID int(8) NOT NULL,
				  AE_Flag int(3) NOT NULL,
				  AE_Msg varchar(500) NOT NULL,
                  AE_LogTime datetime,
				  UNIQUE KEY AE_LogID (AE_LogID)
		) COMMENT='Automated Editor Wordpress Plugin Log Table' AUTO_INCREMENT=1 ;";
		dbDelta($sql);
		
        
		#} Rules table
		$sql = "CREATE TABLE " . $aeplugin_t['rules'] . " (
				  AE_RuleID int(8) NOT NULL AUTO_INCREMENT,
				  AE_RuleName varchar(250) collate latin1_german2_ci NOT NULL,
				  AE_RuleType int(3) NOT NULL,
				  AE_Str longtext NOT NULL,
				  AE_RepStr longtext NOT NULL,
				  AE_RepInstance int(5) NOT NULL,
				  AE_HitCount int(8) NOT NULL,
				  UNIQUE KEY  (AE_RuleID)
		) COMMENT='Automated Editor Wordpress Plugin Rules Table' AUTO_INCREMENT=1 ;";
		dbDelta($sql);
		
        
		#} Schedules table
		$sql = "CREATE TABLE " . $aeplugin_t['schedules'] . " (
				  AE_ScheduleID int(8) NOT NULL AUTO_INCREMENT,
				  AE_PointOfAction int(3) NOT NULL,
				  AE_RuleID int(8) NOT NULL,
				  AE_LastRun timestamp NULL,
				  AE_Target int(2) NOT NULL,
				  AE_TargetVal varchar(1000) collate latin1_german2_ci NOT NULL,
				  AE_TargetValMultiplier int(10) NOT NULL,
				  AE_Status int(1) NOT NULL,
				  UNIQUE KEY  (AE_ScheduleID)
		) COMMENT='Automated Editor Wordpress Plugin Schedule Table' AUTO_INCREMENT=1 ;";
		dbDelta($sql);
		
        
		#} Relationships table
		$sql = "CREATE TABLE  ".$aeplugin_t['relationships']." (
				 AE_RelationID INT( 12 ) NOT NULL AUTO_INCREMENT ,
				 AE_ScheduleID INT( 5 ) NOT NULL ,
				 AE_RuleID INT( 5 ) NOT NULL ,
				 AE_Created TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL ,
				 UNIQUE KEY (  AE_RelationID )
				) COMMENT =  'Automated Editor Wordpress Plugin Relationship Table' AUTO_INCREMENT=1 ;";
		dbDelta($sql);
		
        
	#} Save initial options
	add_option("aeplugin_db_version", $aeplugin_db_version);
    add_option("aeplugin_version",$aeplugin_version);
	add_option("aeplugin_runruleson", 1);
	add_option("aeplugin_reg", "0");
    add_option("aeplugin_welcome", "1"); #} welcome msg on
    add_option("aeplugin_norules", "1"); #} y u no rules? msg on
    add_option("aeplugin_caution", "1"); #} just incase
    add_option("aeplugin_status","0"); #} on off switch
    add_option("aeplugin_loglimit",30);
		
}


#} Uninstall
function aeplugin__uninstall(){
	
	#} blows away some stuff
    update_option("aeplugin_status","0"); #} pause all schedules
    delete_option("aeplugin_welcome"); #} welcome message
    delete_option("aeplugin_norules"); #} y u no rules?
    
 }


#} Add CSS/JS to header
function aeplugin__header_includes() {
    
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
    #} Only needed on ae pages!
    if ($_GET['page'] == $aeplugin_slugs['home'] ||
        $_GET['page'] == $aeplugin_slugs['schedules'] || 
        $_GET['page'] == $aeplugin_slugs['rules'] ||
        $_GET['page'] == $aeplugin_slugs['options'] ||
        $_GET['page'] == $aeplugin_slugs['importexport'] ||
        $_GET['page'] == $aeplugin_slugs['logs']){
            
            #} Include wp jquery
            wp_enqueue_script("jquery");
            
            echo "<link rel='stylesheet' type='text/css' href='".aeplugin_file_url('css/AE.css')."' />\n";
            echo "<!--[if IE]><link rel='stylesheet' type='text/css' href='".aeplugin_file_url('css/AEIE.css')."' /><![endif]-->\n";
        	if ($_GET['page'] == $aeplugin_slugs['rules']){
        		echo "<script type='text/javascript' src='".aeplugin_file_url('js/js_rules.js')."'></script>\n";  //this isnt good on non rules pages :/
        	}
        	if ($_GET['page'] == $aeplugin_slugs['schedules']){
        		echo "<script type='text/javascript' src='".aeplugin_file_url('js/js_schedules.js')."'></script>\n";  //this isnt good on non rules pages :/
        	}    
    
    }
    
}


#} Add Ae admin menu
function aeplugin__admin_menu() {

	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
	add_menu_page( 'Automated Editor', 'Auto Editor', 'manage_options', $aeplugin_slugs['home'], 'aeplugin_pages_home', aeplugin_file_url('i/icon.png'));
	add_submenu_page( $aeplugin_slugs['home'], 'Schedules', 'Schedules', 'manage_options', $aeplugin_slugs['schedules'], 'aeplugin_pages_schedules' ); 
	add_submenu_page( $aeplugin_slugs['home'], 'Rules', 'Rules', 'manage_options', $aeplugin_slugs['rules'], 'aeplugin_pages_rules' );
	add_submenu_page( $aeplugin_slugs['home'], 'Import/Export', 'Import/Export', 'manage_options', $aeplugin_slugs['importexport'], 'aeplugin_pages_datadesk' );
	add_submenu_page( $aeplugin_slugs['home'], 'Options', 'Options', 'manage_options', $aeplugin_slugs['options'], 'aeplugin_pages_options' );
	add_submenu_page( $aeplugin_slugs['home'], 'Logs', 'Logs', 'manage_options', $aeplugin_slugs['logs'], 'aeplugin_pages_logs' );
    
}

#} Page preheader
function aeplugin__preheader(){
    
    #} this should be implemented on every Ae page, pre outputting the header.
    #} it is a space to do global pre-header works, e.g. toggling on off service
    $outputMsgs = array();
    global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
    
	if ($_GET['edit'] != "1"){if (!empty($aeplugin_t['f'])){ wp_die( __($aeplugin_t['f']) ); }}
    
    #} toggles Ae automation on/off
    if ($_GET['toggle'] == "1"){
        $aeNewStatus = get_option('aeplugin_status');
        $aeUpdateStatusTo = "0";
        if ($aeNewStatus == "1"){ 
            $aeUpdateStatusTo = "0";
            array_push($outputMsgs,"Automated Editor automation service paused.");
        } else { 
			if (get_option('aeplugin_caution') == "0"){
				$aeUpdateStatusTo = "1";
				array_push($outputMsgs,"Automated Editor automation service switched on.");
			} else { 
				$aeUpdateStatusTo = "0";
				array_push($outputMsgs,"Automated Editor automation service cannot be switched on until you accept the disclaimer!");
			}
        }
        update_option('aeplugin_status',$aeUpdateStatusTo);
    }
    
    #} toggles individual schedules on/off
    if (!empty($_GET['toggleid'])){
        $aeschedule = aeplugin_db_GetSchedule($_GET['toggleid']);
        
        if ($aeschedule){
                $aeUpdateStatusTo = "0";
                if ($aeschedule->AE_Status == "1"){ 
                    $aeUpdateStatusTo = "0";
                    array_push($outputMsgs,"Schedule id ".$aeschedule->AE_ScheduleID." paused.");
                } else { 
                    $aeUpdateStatusTo = "1";
                    array_push($outputMsgs,"Schedule id ".$aeschedule->AE_ScheduleID." enabled.");    
                }
                
                #} save it
                $wpdb->update( $aeplugin_t['schedules'], array( 'AE_Status' => $aeUpdateStatusTo), array('AE_ScheduleID' => $aeschedule->AE_ScheduleID));
                
        } else  {
            
            #} id sent that doesnt exist ?!?!
            
        }
        
    }
    
    #} outputs standard msg
    foreach ($outputMsgs as $aemsg){
    
            aeplugin_html_msg(0,$aemsg,false);    
        
    }
}







##--##} Page functions


#} Homepage
function aeplugin_pages_home() {
	
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
    #} Set any passed options
    if ($_GET['hidewelcome'] == "1") {update_option("aeplugin_welcome", "0");}
    if ($_GET['hidenorule'] == "1") {update_option("aeplugin_norules", "0");}
    if ($_GET['hidecaution'] == "1") {update_option("aeplugin_caution", "0");}
    
	
?>
<?php aeplugin_html_header(); ?>
<?php aeplugin__preheader(); ?>
<?php aeplugin_html_ads(); ?>
<hr id="spacer" />
<div id="aebody">
    <div class="wrap"> 
    <div id="icon-ae" class="icon32"><br /></div><h2>Schedules<?php if (aeplugin_db_GetRules()){ ?><a href="?page=<?php echo $aeplugin_slugs['schedules']; ?>&new=1" class="add-new-h2">Add New</a><?php } ?></h2> 
    </div>
<?php aeplugin_html_schedules(3); ?>
    <div class="wrap"> 
    <div id="icon-ae" class="icon32"><br /></div><h2>Rules<a href="?page=<?php echo $aeplugin_slugs['rules']; ?>&new=1" class="add-new-h2">Add New</a></h2> 
    </div>
<?php aeplugin_html_rules(3); ?>
</div>
<div align="center"><img src="http://a.automatededitor.com/ae-comp-logo329.png" alt="Automated Editor is an Infinity Progressive production" border="0" /></div>
<?php }


#} Schedules page
function aeplugin_pages_schedules() {
	
    global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
    
    if (!current_user_can('manage_options'))  {
    	wp_die( __('You do not have sufficient permissions to access this page.') );
    }
    

?>
<?php aeplugin_html_header(); ?>
<?php aeplugin__preheader(); ?>
<?php aeplugin_html_ads(); ?>
<div id="aebody">
    <div class="wrap"> 
    <div id="icon-ae" class="icon32"><br /></div><h2><?php
    
	#} is it new?
	if ($_GET['new'] == "1"){

	 	?>New Schedule<?php
		
	} else { 
	
	 	?>Schedules<a href="?page=<?php echo $aeplugin_slugs['schedules']; ?>&new=1" class="add-new-h2">Add New</a><?php
		
	}	
	
	?></h2> 
    </div>
<?php 
 #} MAIN BODY CONTENT
		#} is it new?
			if ($_GET['new'] == "1" || $_GET['edit'] == "1" || $_GET['ecopy'] == "1"){
		
					aeplugin_html_schedule_new();
				
			}
			else if ($_GET['savenew'] == "1"){
			
					aeplugin_html_save_schedule();
					
				
			}
			else if ($_GET['del'] == "1"){
			
					aeplugin_html_delete_schedule();
				
			}
			else { 
			
					aeplugin_html_schedules(); 
				
			}	

?>
</div>

<?php 
}


#} Rules page
function aeplugin_pages_rules() {
	
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
 ?>
 
<?php aeplugin_html_header(); ?>
<?php aeplugin__preheader(); ?>
<?php aeplugin_html_ads(); ?>
<div id="aebody">
    <div class="wrap"> 
    <div id="icon-ae" class="icon32"><br /></div><h2><?php
    
	#} is it new?
	if ($_GET['new'] == "1"){

	 	?>New rule<?php
		
	} else { 
	
	 	?>Rules<a href="?page=<?php echo $aeplugin_slugs['rules']; ?>&new=1" class="add-new-h2">Add New</a><?php
		
	}	
	
	?></h2> 
    </div>
<?php 
 #} MAIN BODY CONTENT
		#} is it new?
			if ($_GET['new'] == "1" || $_GET['edit'] == "1" || $_GET['ecopy'] == "1"){
		
					aeplugin_html_rule_new();
				
			}
			else if ($_GET['savenew'] == "1"){
			
					aeplugin_html_save_rule();
				
			}
			else if ($_GET['del'] == "1"){
			
					aeplugin_html_delete_rule();
				
			}
			else { 
			
					aeplugin_html_rules(); 
				
			}	

?>
</div>
<?php 
}


#} Import Export page (datadesk)
function aeplugin_pages_datadesk() {
	
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
     

?>
<?php aeplugin_html_header(); ?>
<?php aeplugin__preheader(); ?>
<?php aeplugin_html_ads(); ?>
<div id="aebody">
    <div class="wrap"> 
    <div id="icon-ae" class="icon32"><br /></div><h2>Import/Export</h2> 
    </div>
<?php 
		#} Main body content
		#} is it new?
			 if ($_GET['run'] == "1"){
			 
                    aeplugin_html_save_import(); 
             
			} else { 
			
					aeplugin_html_datadesk(); 
				
			}	

?>
</div>
<?php 
}


#} Options page
function aeplugin_pages_options() {
	
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
    

?>
<?php aeplugin_html_header(); ?>
<?php aeplugin__preheader(); ?>
<?php aeplugin_html_ads(); ?>
<div id="aebody">
    <div class="wrap"> 
    <div id="icon-ae" class="icon32"><br /></div><h2>Options</h2> 
    </div>
<?php 
		#} Main body content
		#} is it new?
			 if ($_GET['save'] == "1"){
			 
                    aeplugin_html_save_options(); 
             
			} else { 
			
					aeplugin_html_options(); 
				
			}	

?>
</div>
<?php 
}


#} Logs page
function aeplugin_pages_logs() {
	
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

?>
<?php aeplugin_html_header(); ?>
<?php aeplugin__preheader(); ?>
<?php aeplugin_html_ads(); ?>
<div id="aebody">
    <div class="wrap"> 
    <div id="icon-ae" class="icon32"><br /></div><h2>Logs</h2> 
    </div>
<?php 

	#} Main body content
	aeplugin_html_logs();

?>
</div>
<?php 
}


##--##} HTML Output functions (Chunks - not pages)

#} Standard header
function aeplugin_html_header(){
    
    global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
    
?><h2>Automated Editor Plugin Options</h2>
<div id="aemenubar">
    <a href="<?php echo $aeplugin_urls['members']; ?>" target="_blank">Members Area</a> |
    <a href="<?php echo $aeplugin_urls['videos']; ?>" target="_blank">Video Guides</a> |
    <a href="<?php echo $aeplugin_urls['faq']; ?>" target="_blank">FAQ</a> |
    <a href="<?php echo $aeplugin_urls['forum']; ?>" target="_blank">Support</a> |
    <a href="<?php echo $aeplugin_urls['ultrapro']; ?>" target="_blank">Get Ultra-Pro</a>
</div><?php

aeplugin_html_firstloadmsg(); #} check if first load:
    
}


#} Email subscription box HTML
function aeplugin_html_subscribebox(){
	
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
    
    ?><!-- AWeber Web Form Generator 3.0 -->
<style type="text/css">
#af-form-866895720 .af-body .af-textWrap{width:98%;display:block;float:none;}
#af-form-866895720 .af-body a{color:#0779D1;text-decoration:none;font-style:normal;font-weight:normal;}
#af-form-866895720 .af-body input.text, #af-form-866895720 .af-body textarea{background-color:#FFFFFF;border-color:#919191;border-width:1px;border-style:solid;color:#000000;text-decoration:none;font-style:normal;font-weight:normal;font-size:12px;font-family:Verdana, sans-serif;}
#af-form-866895720 .af-body input.text:focus, #af-form-866895720 .af-body textarea:focus{background-color:#FFFAD6;border-color:#030303;border-width:1px;border-style:solid;}
#af-form-866895720 .af-body label.previewLabel{display:block;float:none;text-align:left;width:auto;color:#45370E;text-decoration:none;font-style:normal;font-weight:normal;font-size:16px;font-family:Verdana, sans-serif;}
#af-form-866895720 .af-body{padding-bottom:1px;padding-top:1px;background-repeat:repeat;background-position:center;background-image:url("http://forms.aweber.com/images/forms/bends/citrus/body.png");color:#45370E;font-size:11px;font-family:Verdana, sans-serif;}
#af-form-866895720 .af-footer{padding-bottom:25px;padding-top:1px;padding-right:15px;padding-left:15px;background-color:transparent;background-repeat:no-repeat;background-position:bottom center;background-image:url("http://forms.aweber.com/images/forms/bends/citrus/footer.png");border-width:1px;border-bottom-style:none;border-left-style:none;border-right-style:none;border-top-style:none;color:#45370E;font-size:12px;font-family:Verdana, sans-serif;}
#af-form-866895720 .af-quirksMode .bodyText{padding-top:2px;padding-bottom:2px;}
#af-form-866895720 .af-quirksMode{padding-right:15px;padding-left:15px;}
#af-form-866895720 .af-standards .af-element{padding-right:15px;padding-left:15px;}
#af-form-866895720 .bodyText p{margin:1em 0;}
#af-form-866895720 .buttonContainer input.submit{background-color:#transparent;background-image:url("http://forms.aweber.com/images/forms/bends/citrus/submit.png");color:#FFFFFF;text-decoration:none;font-style:normal;font-weight:normal;font-size:14px;font-family:Verdana, sans-serif;}
#af-form-866895720 .buttonContainer input.submit{width:auto;}
#af-form-866895720 .buttonContainer{text-align:center;}
#af-form-866895720 body,#af-form-866895720 dl,#af-form-866895720 dt,#af-form-866895720 dd,#af-form-866895720 h1,#af-form-866895720 h2,#af-form-866895720 h3,#af-form-866895720 h4,#af-form-866895720 h5,#af-form-866895720 h6,#af-form-866895720 pre,#af-form-866895720 code,#af-form-866895720 fieldset,#af-form-866895720 legend,#af-form-866895720 blockquote,#af-form-866895720 th,#af-form-866895720 td{float:none;color:inherit;position:static;margin:0;padding:0;}
#af-form-866895720 button,#af-form-866895720 input,#af-form-866895720 submit,#af-form-866895720 textarea,#af-form-866895720 select,#af-form-866895720 label,#af-form-866895720 optgroup,#af-form-866895720 option{float:none;position:static;margin:0;}
#af-form-866895720 div{margin:0;}
#af-form-866895720 fieldset{border:0;}
#af-form-866895720 form,#af-form-866895720 textarea,.af-form-wrapper,.af-form-close-button,#af-form-866895720 img{float:none;color:inherit;position:static;background-color:none;border:none;margin:0;padding:0;}
#af-form-866895720 input,#af-form-866895720 button,#af-form-866895720 textarea,#af-form-866895720 select{font-size:100%;}
#af-form-866895720 p{color:inherit;}
#af-form-866895720 select,#af-form-866895720 label,#af-form-866895720 optgroup,#af-form-866895720 option{padding:0;}
#af-form-866895720 table{border-collapse:collapse;border-spacing:0;}
#af-form-866895720 ul,#af-form-866895720 ol{list-style-image:none;list-style-position:outside;list-style-type:disc;padding-left:40px;}
#af-form-866895720,#af-form-866895720 .quirksMode{width:279px;}
#af-form-866895720.af-quirksMode{overflow-x:hidden;}
#af-form-866895720{background-color:transparent;border-color:transparent;border-width:1px;border-style:solid;}
#af-form-866895720{overflow:hidden;}
.af-body .af-textWrap{text-align:left;}
.af-body input.image{border:none!important;}
.af-body input.submit,.af-body input.image,.af-form .af-element input.button{float:none!important;}
.af-body input.text{width:100%;float:none;padding:2px!important;}
.af-body.af-standards input.submit{padding:4px 12px;}
.af-clear{clear:both;}
.af-element label{text-align:left;display:block;float:left;}
.af-element{padding:5px 0;}
.af-footer{margin-bottom:0;margin-top:0;padding:10px;}
.af-form-wrapper{text-indent:0;}
.af-form{text-align:left;margin:auto;}
.af-quirksMode .af-element{padding-left:0!important;padding-right:0!important;}
.lbl-right .af-element label{text-align:right;}
body {
}
</style>
<form method="post" class="af-form-wrapper" action="http://www.aweber.com/scripts/addlead.pl" target="_new" >
<div style="display: none;">
<input type="hidden" name="meta_web_form_id" value="866895720" />
<input type="hidden" name="meta_split_id" value="" />
<input type="hidden" name="listname" value="automatededitor" />
<input type="hidden" name="redirect" value="http://www.automatededitor.com/thanks" id="redirect_a29e5a1d3c1af6076045e45ed8b2e870" />

<input type="hidden" name="meta_adtracking" value="inplugin" />
<input type="hidden" name="meta_message" value="1" />
<input type="hidden" name="meta_required" value="email" />

<input type="hidden" name="meta_tooltip" value="" />
</div>
<div id="af-form-866895720" class="af-form"><div id="af-body-866895720" class="af-body af-standards">
<div class="af-element">
<div id="aesubhd">Automated Editor secret subscription!</div>
<label class="previewLabel" for="awf_field-22959234">Email: </label>
<div class="af-textWrap"><input class="text" id="awf_field-22959234" type="text" name="email" value="<?php echo get_option('admin_email'); ?>" tabindex="500"  />
</div><div class="af-clear"></div>
</div>
<div class="af-element buttonContainer">
<input name="submit" id="af-submit-image-866895720" type="image" class="image" style="background: none;" alt="Submit Form" src="http://www.aweber.com/images/forms/bends/citrus/submit.png" tabindex="501" />
<div class="af-clear"></div>
</div>
</div>
<div id="af-footer-866895720" class="af-footer"><div class="bodyText"><p>&nbsp;</p></div></div>
</div>
<div style="display: none;"><img src="http://forms.aweber.com/form/displays.htm?id=HGxsHJys7EwM" alt="" /></div>
</form>
<script type="text/javascript">
    <!--
    (function() {
        var IE = /*@cc_on!@*/false;
        if (!IE) { return; }
        if (document.compatMode && document.compatMode == 'BackCompat') {
            if (document.getElementById("af-form-866895720")) {
                document.getElementById("af-form-866895720").className = 'af-form af-quirksMode';
            }
            if (document.getElementById("af-body-866895720")) {
                document.getElementById("af-body-866895720").className = "af-body inline af-quirksMode";
            }
            if (document.getElementById("af-header-866895720")) {
                document.getElementById("af-header-866895720").className = "af-header af-quirksMode";
            }
            if (document.getElementById("af-footer-866895720")) {
                document.getElementById("af-footer-866895720").className = "af-footer af-quirksMode";
            }
        }
    })();
    -->
</script>

<!-- /AWeber Web Form Generator 3.0 --><?php
}


#} Ads HTML
function aeplugin_html_ads(){
	
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
    
    # PLEASE READ:
    
    #} Removing the ad's here would be pretty lame, the full, ultra pro version is a lot slicker, unlimited and has a pretty
    #} sweet members only area. Think about getting that instead! http://www.automatededitor.com/compare-versions/
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
?><div id="aedyna" class="clearfix"><div id="aesub"><div id="aesuba">
    <div id="aesubl">
    	<table class="widefat" style="height:172px;"><tr><td>
    	<div class="aesubhd">Most Epic Hosting</div>
        <div class="aesubp">Biggest in world, cheapest, epic for wordpress. We run hundreds of sites on one shared hosting account - costs us less than $0.40 a year per site. You won't find a better shared host. Don't overpay, use 1and1:<div align="center"><a href="http://a.automatededitor.com/1and1.php" target="_blank"><img src="http://banner.1and1.co.uk/xml/banner?size=3&number=2" width="120" height="60" style="margin:3px;"  border="0"/></a></div></div>
        </td></tr></table>
    </div>
    <div id="aesubr">
	    <table class="widefat" style="height:172px;"><tr><td>
        <div class="aesubhd">Go Ultra-Pro</div>
        <div class="aesubp">Seen how powerful Automated Editor is? Want to use it on another blog? With <strong><a href="<?php echo $aeplugin_urls['ultrapro']; ?>">Ultra-Pro</a></strong> you can use it on unlimited blogs at no additional cost, with a huge bundle of extra's. Don't waste time fudging solutions, get the proper kit and get the job done. <strong><a href="<?php echo $aeplugin_urls['ultrapro']; ?>">Ultra-Pro</a></strong> has a bunch of benifits, unlimited use and only costs you once.<br /><a href="<?php echo $aeplugin_urls['ultrapro']; ?>">Ultra-Pro</a> also <strong>hides these pesky ads</strong>!</div>
        </td></tr></table>
    </div>
</div>
    <div id="aesubs">
        <?php aeplugin_html_subscribebox(); ?>
    </div>
</div></div><?php
    
}


#} Welcome message HTML
function aeplugin_html_firstloadmsg(){
    
		global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
        #} should be on each page init
        if (get_option('aeplugin_welcome') != "0"){
        
        #} show welcome msg
        $aehello = '<img src="'.aeplugin_file_url('i/icon-hello-48.png').'" border="0" align="left" style="padding-right:5px" />';
        $aehello .= 'Hello there! Welcome to Automated Editor, if you need some ideas on how to use this plugin check out our ';
        $aehello .= '<a href="'. $aeplugin_urls['videos'] .'" target="_blank">video guides</a>';
        $aehello .= ' or hit the <a href="'. $aeplugin_urls['forum'] .'" target="_blank">forums</a>. The best place to start is to ';
        $aehello .= '<a href="?page='.$aeplugin_slugs['rules'].'&new=1">Make a new rule</a> or <a href="?page='.$aeplugin_slugs['importexport'].'">Import a rules file</a>.';
        $aehello .= '<br /><div class="aeside"><strong><a href="?page='.$aeplugin_slugs['home'].'&hidewelcome=1">[x] Hide this</a></strong></div>';
        
        aeplugin_html_msg(2,$aehello);
        
    } else { 
        #} if welcome screen is hidden but still no rules, prompt
        if (!(aeplugin_db_GetRules()) && get_option('aeplugin_norules') != "0"){
            
            #} Hi, y you no rules?
            $aehello = '<img src="'.aeplugin_file_url('i/icon-help-48.png').'" border="0" align="left" style="padding-right:5px" />';
            $aehello .= 'You haven\'t made any rules yet, need some inspiration? check out our <a href="'. $aeplugin_urls['videos'] .'" target="_blank">video guides</a>';
            $aehello .= ' or hit the <a href="'. $aeplugin_urls['forum'] .'" target="_blank">forums</a>, if your ready, why not <a href="?page='. $aeplugin_slugs['rules'] .'&new=1">Make a new rule</a> or <a href="?page='.$aeplugin_slugs['importexport'].'">Import a rules file</a>?';
            $aehello .= '<br /><div class="aeside" style="margin-top:16px"><strong><a href="?page='.$aeplugin_slugs['home'].'&hidenorule=1">[x] Hide this</a></strong></div>';
            aeplugin_html_msg(2,$aehello);
            
        }
    }
	
	#} additional warning about rules added 1.3
	if (get_option('aeplugin_caution') != "0"){
		$aecaut = '<strong>Note:</strong><br />You must read this ';
		$aecaut .= '<a href="'.$aeplugin_urls['disclaimer'].'" target="_blank">disclaimer</a> before using this plugin, using its powerful features without';
		$aecaut .= 'care can result in messed up content.';
        $aecaut .= '<br /><div class="aeside"><strong><a href="?page='.$aeplugin_slugs['home'].'&hidecaution=1">[x] Hide this (Accept)</a></strong></div>';
        aeplugin_html_msg(-1,$aecaut);
	}
    
}


#} Schedules HTML
function aeplugin_html_schedules($limit=-1){

	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req

    #} check to see if there is schedules but no rules (and welcome message hidden)
    if ((aeplugin_db_GetSchedules()) && !(aeplugin_db_GetRules())){
        aeplugin_html_msg(-1,"You have schedules setup but no rules! Without rules to run schedules won't do anything! <a href=\"?page=".$aeplugin_slugs['rules']."&new=1\">create a rule</a> and assign it to a schedule!<br/>Check out our <a href=\"". $aeplugin_urls['videos'] ."\" target=\"_blank\">video guides</a> if you are stuck.",true);
    }
	
	#} Header
	
    aeplugin_html_schedules_currentstatus();
    
    #} Get for shared use 
    $aestatus = get_option('aeplugin_status');
    
    ?>
    <table class="wp-list-table widefat aetopmar" cellspacing="0"> 
    <thead> 
        <tr> 
            <th id='cb' class='manage-column column-cb check-column'><!--<input type="checkbox" />--></th>
            <th class='manage-column column-name'>Schedule Name</th>
            <th class='manage-column'>Rules</th>
            <th class='manage-column'>Target</th>
            <th class='manage-column'>Target detail</th>
            <th class='manage-column'>Point of action</th>
            <th class='manage-column'>Next Scheduled to Run</th>
            <th class='manage-column'>Last Ran</th>
        </tr> 
    </thead> 
    
    <tfoot> 
	    <tr>
            <th class='manage-column column-cb check-column'  style=""><!--<input type="checkbox" />--></th>
            <th class='manage-column column-name'>Schedule Name</th>
            <th class='manage-column'>Rules</th>
            <th class='manage-column'>Target</th>
            <th class='manage-column'>Target detail</th>
            <th class='manage-column'>Point of action</th>
            <th class='manage-column'>Next Scheduled to Run</th>
            <th class='manage-column'>Last Ran</th>
        </tr> 
    </tfoot>
     
	<?php 
	#} End of header
	
	#} Body
	
		#} get schedules
        if ($limit != -1){ $ae_limit = " limit 0,".$limit;}  
		$ae_schedules = $wpdb->get_results("SELECT *, (select count(AE_ScheduleID) from ".$aeplugin_t['schedules'].") sno, (select count(AE_RelationID) from ".$aeplugin_t['relationships']." r WHERE r.ae_scheduleid = s.ae_scheduleid) rn FROM ".$aeplugin_t['schedules']." s ORDER BY AE_ScheduleID DESC".$ae_limit);
		if ($ae_schedules) {
		  
          $totalScheduleCount = -1; #} this should always be set in the loop if at least one exits
          
          ?>
	<tbody id="the-list">
    	<?php
            $aeLineCount = 0;
            $aproxFlag = false;
  			foreach ($ae_schedules as $ae_schedule) { 
  			   
                $totalScheduleCount = $ae_schedule->sno; #} will always be the same just a dumb total count
                
                if ($aestatus == "1") { #} everythings on!
                    
            			$activeClass = 'aeinactive';
                        $aeplaypause = aeplugin_file_url('i/icon-play-32.png');
                        $aeplaypausetext = "Enable";
                        $aeplaypauseUrl = "?page=".$_GET['page']."&toggleid=".$ae_schedule->AE_ScheduleID;
                        
                        if ($ae_schedule->AE_Status == '1'){ 
                            $activeClass = 'aeactive'; 
                            $aeplaypause = aeplugin_file_url('i/icon-pause-32.png'); 
                            $aeplaypausetext = "Pause";
                        }
                       
                } else {
                    
                        $activeClass = 'aeinactive'; 
                }
    			
    			$aeid = "schedule_" . $ae_schedule->AE_ScheduleID;
    		?>
            <tr id='<?php echo $aeid; ?>' class='<?php echo $activeClass; ?>'>
            	<th scope='row' class='check-column'>
                    <?php if ($aestatus == "1"){ ?><a href="<?php echo $aeplaypauseUrl; ?>" title="<?php echo $aeplaypausetext; ?> this schedule"><img style="padding-left:4px;" src="<?php echo $aeplaypause; ?>" border="0" /></a><?php } ?>
                </th>
                <td class='plugin-title'>
                	<strong>Schedule <?php echo $ae_schedule->AE_ScheduleID; ?></strong>
                    <div class="row-actions-visible">
                    	<?php if ($aestatus == "1"){ ?><span class='activate'>
                        	<a href="<?php echo $aeplaypauseUrl; ?>" class="edit" title="<?php echo $aeplaypausetext; ?> this schedule"><?php echo $aeplaypausetext; ?></a> | 
                        </span><?php } ?>
                        <span class='edit'>
                        	<a href="?page=<?php echo $aeplugin_slugs['schedules']; ?>&aesid=<?php echo $ae_schedule->AE_ScheduleID; ?>&edit=1" title="Open this schedule to edit" class="edit">Edit</a> | 
                        </span>
                        <span class='delete'>
                        	<a href="?page=<?php echo $aeplugin_slugs['schedules']; ?>&aesid=<?php echo $ae_schedule->AE_ScheduleID; ?>&del=1" title="Delete this schedule" class="delete">Delete</a>
                        </span>
                    </div>
                </td>
                <td class='plugin-title'><?php
    			
                #} Last-wall check that rules even exist
                if (aeplugin_db_GetRules()){            
    			     echo $ae_schedule->rn . ' rules';
    			} else { echo '0 rules'; }
                
    			?></td>
                <td class='plugin-title'><?php 
    			
    			
    			switch($ae_schedule->AE_Target){
    				case "0": #all (no options)
    					echo "All posts";
    					break;
    				case "1": #containing string
    					echo "All posts containing string";
    					break;
    				case "2": #not containing string
    					echo "All posts not containing string";
    					break;
    				case "3": #title containing string
    					echo "All posts with title containing string";
    					break;
    				case "4": #title not containing string
    					echo "All posts with title not containing string";
    					break;
    				case "5": #in cat
    					echo "All posts in category";
    					break;
    				case "6": #not containing string
    					echo "All posts not in category";
    					break;
    				case "7": #not containing string
    					echo "All posts tagged";
    					break;
    				case "8": #not containing string
    					echo "All posts not tagged";
    					break;
    				case "9": #of a certain age
    					echo "No older than";
    					break;
    				case "10": #most recent post (no options)
    					echo "Most recent post";
    					break;
    			}
    			
    			?></td> 
                <td class='plugin-title'><?php 
    			
    			if ($ae_schedule->AE_Target == 5 || $ae_schedule->AE_Target == 6) {
    				echo get_cat_name($ae_schedule->AE_TargetVal);
    			} else if ($ae_schedule->AE_Target == 7 || $ae_schedule->AE_Target == 8){
    				echo aeplugin_db_GetTagName($ae_schedule->AE_TargetVal);	  
      		    } else if ($ae_schedule->AE_Target == 9){
    				echo $ae_schedule->AE_TargetVal . ' ';
    				if ($ae_schedule->AE_TargetValMultiplier == "1440"){ echo 'Days'; }
    				if ($ae_schedule->AE_TargetValMultiplier == "60"){ echo 'Hours'; }
    				if ($ae_schedule->AE_TargetValMultiplier == "1"){ echo 'Minutes'; }
      		    }
    			else { echo aeplugin_str_ShortenHTML($ae_schedule->AE_TargetVal); }
    			
    			?></td>
                <td class='plugin-title'><?php 
                
    			switch($ae_schedule->AE_PointOfAction){
    				case "1":
    					echo "After a post is published";
    					break;
    				case "2": 
    					echo "Every hour*"; $aproxFlag = true;
    					break;
    				case "3": 
    					echo "Every day*"; $aproxFlag = true;
    					break;
    			} ?></td>
                <td class='plugin-title'><?php 
    			if ($aestatus == "1" && $ae_schedule->AE_Status == "1"){ 
                    if ($ae_schedule->AE_PointOfAction == "1"){ 
        			
                    	echo "After publishing";
        			
                    } else {
        			
                    	#} Will run on next hourly/daily firing
        				$noHours = 1;
        				if ($ae_schedule->AE_PointOfAction == "2") { $noHours = 1; }
        				if ($ae_schedule->AE_PointOfAction == "3") { $noHours = 24; }
                        echo aeplugin_datetime_UntilNextWPFire($noHours);
                         
        			}
                    
                } else if ($aestatus == "1" && $ae_schedule->AE_Status == "0") { 
        		
                    echo 'This schedule is currently paused';                    
                        
    			} else { 
                    
                    echo 'All schedules currently paused';
                    
                }
    			?></td>
                <td class='plugin-title'><?php 

    			if ($ae_schedule->AE_LastRun != "2000-01-01 00:00:00"){
    						echo aeplugin_datetime_PrettyTimeSince($ae_schedule->AE_LastRun,0);				
    			} else { 
    						echo 'Not yet ran';
    			}?></td>           
    		</tr>          
       		<?php $aeLineCount++; } 
			
           if ($limit != -1 && $totalScheduleCount > $limit){ 
             ?><td colspan="8">
             	<div class="aemidmsg">Showing your most recent <?php echo $limit; ?> schedules, <a href="?page=<?php echo $aeplugin_slugs['schedules']; ?>">see all</a></div>
             </td><?php
           }
           
           ?>        
	</tbody>
    <?php } 
	
		else {
		  $aeNoScheds = true;
          
			#} NO schedules ...new 1? only if theres rules dude!!
            if (aeplugin_db_GetRules()){
            ?><td colspan="8"><div id="aeohno">No Schedules, do you want to <a href="?page=<?php echo $aeplugin_slugs['schedules']; ?>&new=1">create a schedule</a>?</div></td><?php
            } else { 
            ?><td colspan="8"><div id="aeohno">No Schedules or rules, first you should <a href="?page=<?php echo $aeplugin_slugs['rules']; ?>&new=1">create a rule</a>.</div></td><?php    
            }
		}
	
	#} End of body

	?></table><?php

?><br /><?php if (!$aeNoScheds && $aproxFlag) { ?>* Times are approximate. Read more about this <a href="<?php echo $aeplugin_urls['timesapprox']; ?>" target="_blank">here</a>.<?php }


}






#} Import Export page HTML
function aeplugin_html_datadesk(){
    
    global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
    ?><table cellpadding="0" cellspacing="0" border="0" width="715">
    <tr>
        <td class="aeFieldLabelHD topHD" colspan="2">Import</td>
    </tr>
    <tr>
        <td class="aeFieldLabel" width="180">File (.csv)</td>
        <td class="aeField">
            <form  method="post" enctype="multipart/form-data" action="?page=<?php echo $aeplugin_slugs['importexport']; ?>&run=1">
                <input type="file" name="aecsv" id="aecsv" /><input type="submit" value="Import" />
            </form>
        </td>
    </tr>
    <tr>
        <td class="aeFieldLabelHD" colspan="2">Export</td>
    </tr>
    <tr>
        <td class="aeFieldLabel">Export Rules</td>
        <td class="aeField">
            <a href="?page=<?php echo $aeplugin_slugs['importexport']; ?>&export=1">Export</a>
        </td>
    </tr>
     <tr>
        <td class="aeFieldLabelHD" colspan="2">Help</td>
    </tr>
    <tr>
        <td class="aeFieldLabel"></td>
        <td>
            This page allows you to import or export a file containing all your rules in the Automated Editor plugin. This functionality makes it easy to copy a set of rules between different blogs. For more information see our guide on importing and exporting rules <a href="<?php echo $aeplugin_urls['guide_importexport']; ?>" target="_blank">here</a>, on that page you will also find a default template if you want to hand craft a rules import file!
        </td>
    </tr>
    </table><?php
        
}


#} Options HTML
function aeplugin_html_options(){
    	
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
    
    $runruleson = get_option('aeplugin_runruleson');
    $hideWelcomeMsg = get_option('aeplugin_welcome');
    
    ?><form action="?page=<?php echo $aeplugin_slugs['options']; ?>&save=1" method="post">
        <table width="715" border="0" cellpadding="0" cellspacing="0">
           <tr>
            <td class="aeFieldLabelHD topHD" colspan="2">General options</td>
           </tr>
           <tr>
            <td class="aeFieldLabel" width="180" valign="top">Run rules on</td>
            <td class="aeField">
            <select name="aeplugin_runruleson" id="aeplugin_runruleson">
            	<option value="-1"<?php if ($runruleson == "-1"){ echo ' selected="selected"'; } ?>>All posts (irrelevant of status)*</option>
            	<option value="1"<?php if ($runruleson == "1"){ echo ' selected="selected"'; } ?>>Posts with published status</option>
            	<option value="2"<?php if ($runruleson == "2"){ echo ' selected="selected"'; } ?>>Posts with draft status*</option>
            </select>
            <div class="aesmallnote">*Note: Running rules on posts other than published status may result in some oddities for multi-users editing posts (e.g. clashes). This feature is not recommended unless for specific use, be careful!<br />(<strong>Recommended default: Posts with published status</strong>)</div>
            </td>
          </tr>
           <tr>
            <td class="aeFieldLabel">Hide welcome message</td>
            <td class="aeField">
                <input type="checkbox" name="aeplugin_welcome" id="aeplugin_welcome" value="0" <?php if ($hideWelcomeMsg == "0"){ echo ' checked="checked"'; } ?> />
            </td>
          </tr>
          <tr>
            <td class="aeFieldLabel">Max number of logs to keep</td>
            <td class="aeField">
                <input type="text" name="aeplugin_loglimit" style="width: 40px;" id="aeplugin_loglimit" value="<?php echo get_option('aeplugin_loglimit'); ?>" />
            </td>
          </tr>
          <tr>
            <td class="aeFieldLabel">&nbsp;</td>
            <td class="aeField"><input type="submit" value="Save options" /></td>
          </tr>          
          <tr>
            <td class="aeFieldLabelHD" colspan="2">About this Automated Editor plugin</td>
           </tr>
          <tr><td colspan="2">
              <table width="680" border="0" cellpadding="0" cellspacing="2">
              <tr>
                <td class="aeFieldLabel" width="180" valign="top">AE Version</td>
                <td class="aeFieldOption"><?php 
                #} REGISTRATION
                if (get_option("aeplugin_reg") == "0"){
                    echo 'Unregistered Plugin';                
                } else { 
                    echo 'Pro';
                } #} REGISTRATION
                
                echo ' Version '.get_option("aeplugin_version");
                
                 ?></td>
              </tr>
              <tr>
                <td class="aeFieldLabel">AE Database Version</td>
                <td class="aeFieldOption"><?php echo get_option('aeplugin_db_version'); ?></td>
              </tr>
              <tr>
                <td class="aeFieldLabel">Current server time</td>
                <td class="aeFieldOption"><?php echo current_time('mysql'); ?></td>
              </tr>
              <tr>
                <td class="aeFieldLabel">Next scheduled hourly check</td>
                <td class="aeFieldOption"><?php echo aeplugin_datetime_UnixToPrettyDate(wp_next_scheduled('ae_runHourly')); ?> (<?php echo aeplugin_datetime_UntilNextWPFire(1); ?>)</td>
              </tr>
              <tr>
                <td class="aeFieldLabel">Next scheduled daily check</td>
                <td class="aeFieldOption"><?php echo aeplugin_datetime_UnixToPrettyDate(wp_next_scheduled('ae_runDaily')); ?> (<?php echo aeplugin_datetime_UntilNextWPFire(24); ?>)</td>
              </tr>
            </table>
        </td></tr></table></form> 
        <?php
    
}


#} Logs HTML
function aeplugin_html_logs(){
    	
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req

	$ae_logs = $wpdb->get_results("SELECT * FROM ".$aeplugin_t['logs']." ORDER BY AE_LogID DESC");
	if ($ae_logs) {
	
    ?><table cellpadding="0" cellspacing="0" border="0" class="widefat">
    <thead>
    	<tr>
            <th>ID</th>
            <th>Rule</th>
            <th>Schedule</th>
            <th>Log</th>
            <th>Date</th>
        </tr>
    </thead>
    <tfoot>
    	<tr>
            <th>ID</th>
            <th>Rule</th>
            <th>Schedule</th>
            <th>Log</th>
            <th>Date</th>
        </tr>
    </tfoot>
    <tbody>
    <?php
    
        foreach ($ae_logs as $ae_log){
            
                    ?><tr>
                            <td><?php echo $ae_log->AE_LogID; ?></td>
                            <td><?php echo aeplugin_db_GetRuleName($ae_log->AE_RuleID).' ('.aeplugin_db_GetRuleType($ae_log->AE_RuleID).')'; ?></td>
                            <td><?php echo $ae_log->AE_ScheduleID; ?></td>
                            <td><?php echo $ae_log->AE_Msg; ?></td>
                            <td><?php echo $ae_log->AE_LogTime; ?></td>
                    </tr><?php
                        
        }   
        
    ?>
    </tbody>
    </table><?php
    
    }
    
}


#} Process an Import (outputting HTML too)
function aeplugin_html_save_import(){
    
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
    ?>Importing file....<br /><?php
    
    if ($_FILES["aecsv"]["error"] > 0){
    
        aeplugin_html_msg(-1,"Error: " . $_FILES["aecsv"]["error"]);
          
    } else {
        
        if ($_FILES["aecsv"]["size"] < 20000)
        {
            echo 'Recieved import file ('.$_FILES["aecsv"]["name"].' @ '.($_FILES["aecsv"]["size"] / 1024).'Kb)...analysing...<br />';
            #} Check line count, number of cols
            $lineCount = count(file($_FILES["aecsv"]["tmp_name"]));
            #} Take first line to check col numbers (could check more but this is just prelims)
            $fh = fopen($_FILES["aecsv"]["tmp_name"], 'r'); $topLine = fgets($fh); fclose($fh);
            $chunks = explode(",",$topLine);
            
            $aego = true;
            if (count($chunks) != 5){
                aeplugin_html_msg(-1,'The number of columns present in this CSV file doesnt match the expected count! Please see support forum.');
                $aego = false;
            }
            if ($lineCount == 0){
                aeplugin_html_msg(-1,'There are no lines present in this file, that simply cant work!');
                $aego = false;
            }
            #} Otherwise continue
            if ($aego){
                
                echo 'File checks out ok!...proceeding to import rules...<br />';
                echo '---------------------------------------------------<br />';
                
                #} Proceed!
                $aeImportLines = file($_FILES["aecsv"]["tmp_name"]);
    			$aeImportLines = aecr($aeImportLines);
				
                $aeSuccess = 0;
                $aeFailure = 0;
    
                // Loop through our array, show HTML source as HTML source; and line numbers too.
                foreach ($aeImportLines as $aeImportLine) {

                    #} Check its integrity
                    $linechunks = explode(",",$aeImportLine);
                    $trimmedline = trim($aeImportLine);
                    if (!empty($trimmedline)){ #} Sometimes double splits empties
                        if (count($linechunks) == 5){
                        
                                #} Save it
                                $wpdb->insert( $aeplugin_t['rules'], array( 
                									  'AE_RuleName' => aeplugin_str_CSVCleanBiDirection($linechunks[0],"in"), 
                									  'AE_RuleType' => aeplugin_str_CSVCleanBiDirection($linechunks[1],"in"), 
                									  'AE_Str' => aeplugin_str_CSVCleanBiDirection($linechunks[2],"in"), 
                									  'AE_RepStr' => aeplugin_str_CSVCleanBiDirection($linechunks[3],"in"), 
                									  'AE_RepInstance' => aeplugin_str_CSVCleanBiDirection($linechunks[4],"in"), 
                									  'AE_HitCount' => 0
                									  )
                					  );
                		
                    			$savedID = $wpdb->insert_id;
               		        
                    			if (!$savedID){
                    				#} Failed	
                    				aeplugin_html_msg(-1,"Failed importing rule line, something seems to have been up with this line:<br />".$aeImportLine);
                    				$aeFailure++;
                    			} else {
                    				#} Success 
                    				echo "Rule (".aeplugin_str_CSVCleanBiDirection($linechunks[0],"in").") imported successfully (ID ".$savedID.")<br />";
                                    $aeSuccess++;
                    			}
                                
                        } else { 
                            
                            aeplugin_html_msg(-1,"This line didn't check out:<br />".$aeImportLine);
                            $aeFailure++;
                            
                        }
                    }
                    
                }
                
                echo '---------------------------------------------------<br />';
                echo 'Finished importing file...<br />';
                echo $aeSuccess.' rules successfully imported...<br />';
                echo $aeFailure.' rules failed to import...<br />';
                echo '---------------------------------------------------<br />';
                echo '<a href="?page='.$aeplugin_slugs['rules'].'">Go to Rules page</a>';
            }
        }
    }
        
}


#} Generates HTML for current status play/pause button
function aeplugin_html_schedules_currentstatus(){
    
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
    #} outputs current status html with button to pause/play schedules overall
    #} note redirs to WHATEVER page it was fired from.
    $aestatus = get_option('aeplugin_status');
    	
		?><div align="center"><table cellpadding="2" cellspacing="0" border="0" class="widefat" style="width:170px"><tr><td><?php
    if ($aestatus == "1"){
        #} its on
        ?><img src="<?php echo aeplugin_file_url('i/ae-online.png'); ?>" alt="Currently Online - Automated editor is running in live mode!" border="0" id="aecurrentstatusimg"></td><td width="70"><div align="center" style="margin-right:6px;margin-top:4px;"><a href="?page=<?php echo $_GET['page']; ?>&toggle=1"><img src="<?php echo aeplugin_file_url('i/icon-pause.png'); ?>" border="0"></a><br/>[Pause]</div><?php
    } else { 
        #} its off
        ?><img src="<?php echo aeplugin_file_url('i/ae-offline.png'); ?>" alt="Currently Paused - Automated editor is not running and is in paused mode!"  border="0" id="aecurrentstatusimg"></td><td width="70"><div align="center" style="margin-right:6px;margin-top:4px;"><a href="?page=<?php echo $_GET['page']; ?>&toggle=1"><img src="<?php echo aeplugin_file_url('i/icon-play.png'); ?>" border="0"></a><br/>[Enable]</div><?php
    }
	
	?></td></tr></table></div><?php
    
}


#} Generates HTML for Rules table
function aeplugin_html_rules($limit=-1){

	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req

	#} Header
	?>
    
    <table class="wp-list-table widefat aetopmar" cellspacing="0"> 
    <thead> 
        <tr> 
            <th id='cb' class='manage-column column-cb check-column'><!--<input type="checkbox" />--></th>
            <th class='manage-column column-name'>Name</th>
            <th class='manage-column'>Type</th>
            <th class='manage-column' width="220">First option</th>
            <th class='manage-column' width="220">Second option</th>
            <th class='manage-column'>Replace/Remove instance</th>
            <th class='manage-column'>Run count</th>
        </tr> 
    </thead> 
    
    <tfoot> 
	    <tr>
            <th class='manage-column column-cb check-column'  style=""><!--<input type="checkbox" />--></th>
            <th class='manage-column column-name'>Name</th>
            <th class='manage-column'>Type</th>
            <th class='manage-column'>First option</th>
            <th class='manage-column'>Second option</th>
            <th class='manage-column'>Replace/Remove instance</th>
            <th class='manage-column'>Run count</th>
        </tr> 
    </tfoot>
     
	<?php 
	#} End of header
	
	#} Body
	
		#} Get rules
        if ($limit != -1){ $ae_limit = " limit 0,".$limit;}        
		$ae_rules = $wpdb->get_results("SELECT *, (select count(AE_RuleID) from ".$aeplugin_t['rules'].") rno from ".$aeplugin_t['rules']." ORDER BY AE_RuleID DESC".$ae_limit);
		if ($ae_rules) { ?>
	<tbody id="the-list">
    	<?php
        
            $totalRuleCount = -1; #} this should always be set in the loop if at least one exits
        
            $aeLineCount = 0;
  			foreach ($ae_rules as $ae_rule) { 
			
			$totalRuleCount = $ae_rule->rno; #} will always be same is count
            
			$aeid = "rule_" . $ae_rule->AE_RuleID;
		?>
        <tr id='<?php echo $aeid; ?>'>
        	<th scope='row' class='check-column'>
            	<!--<input type='checkbox' name='<?php echo $aeid; ?>_cb' value='0' id='<?php echo $aeid; ?>_cb' />
            	<label class='screen-reader-text' for='<?php echo $aeid; ?>_cb' ><?php echo $ae_rule->AE_RuleName; ?></label>-->
            </th>
            <td class='plugin-title'>
            	<strong><?php echo $ae_rule->AE_RuleName; ?></strong>
                <div class="row-actions-visible">
                	<span class='activate'>
                    	<a href="?page=<?php echo $aeplugin_slugs['rules']; ?>&aerid=<?php echo $ae_rule->AE_RuleID; ?>&ecopy=1" title="Make a new rule using this one as a template" class="edit">Copy</a> | 
                    </span>
                    <span class='edit'>
                    	<a href="?page=<?php echo $aeplugin_slugs['rules']; ?>&aerid=<?php echo $ae_rule->AE_RuleID; ?>&edit=1" title="Open this rule to edit" class="edit">Edit</a> | 
                    </span>
                    <span class='delete'>
                    	<a href="?page=<?php echo $aeplugin_slugs['rules']; ?>&aerid=<?php echo $ae_rule->AE_RuleID; ?>&del=1" title="Delete this rule" class="delete">Delete</a>
                    </span>
                </div>
            </td>
            <td class='plugin-title'><?php 
			echo aeplugin_str_PrettyRuleType($ae_rule->AE_RuleType);
			
			
			?></td>    
            <td class='plugin-title'><?php 
	
			#} Grab tags/cats
			if ($ae_rule->AE_RuleType == 7){
				
				echo get_cat_name($ae_rule->AE_Str);
				
			} else if ($ae_rule->AE_RuleType == 8){
				
				echo aeplugin_db_GetTagName($ae_rule->AE_Str);
				
			} else { 
				echo aeplugin_str_ShortenHTML($ae_rule->AE_Str); 
			}
			
			
			?></td>
            <td class='plugin-title'><?php echo aeplugin_str_ShortenHTML($ae_rule->AE_RepStr); ?></td>
            <td class='plugin-title'><?php 
			if ((int)$ae_rule->AE_RuleType < 5){ #} Needent show above
				if ($ae_rule->AE_RepInstance == -1){
					echo "All";	
				} else { 
					echo aeplugin_str_AddOrdinalNumberSuffix($ae_rule->AE_RepInstance);
				}
			} else {
				echo '-';	
			}
			
			?></td>
            <td class='plugin-title'><?php echo $ae_rule->AE_HitCount; ?></td>
		</tr>          
   		<?php $aeLineCount++; } 
           if ($limit != -1 && $totalRuleCount > $limit){ 
             ?><td colspan="8">
             	<div class="aemidmsg">Showing your most recent <?php echo $limit; ?> rules, <a href="?page=<?php echo $aeplugin_slugs['rules']; ?>">see all</a></div>
             </td><?php
           }
           ?>
        
	</tbody>
    <?php } 
	
		else {
			#NO rules ...new 1?	
            ?><td colspan="7"><div id="aeohno">No Rules, do you want to <a href="?page=<?php echo $aeplugin_slugs['rules']; ?>&new=1">create a rule</a>?</div></td><?php
		}
	
	#} End of body

	?></table><?php
	
}


#} Outputs HTML message
function aeplugin_html_msg($flag,$msg,$includeExclaim=false){
	
    if ($includeExclaim){ $msg = '<div id="aeExclaim">!</div>'.$msg.''; }
    
    if ($flag == -1){
		echo '<div class="aefail wrap">'.$msg.'</div>';
	} 
	if ($flag == 0){
		echo '<div class="aesuccess wrap">'.$msg.'</div>';	
	}
	if ($flag == 1){
		echo '<div class="aewarn wrap">'.$msg.'</div>';	
	}
    if ($flag == 2){
        echo '<div class="aeinfo wrap">'.$msg.'</div>';
    }
}


#} New Schedule HTML
function aeplugin_html_schedule_new(){
	
	#} Note: if this is fired with edit=1 flag itll autofill
	
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req  
    
    if ($_GET['edit'] == "1"){ $vr = true; } else { $vr = (count(aeplugin_db_GetSchedules()) < $aeplugin_t['ark']-2) ? true : false; }
    
    if (!$vr){
       wp_die( __($aeplugin_t['gs']) ); 
    } else { 
	
	if ($_GET['edit'] == "1" || $_GET['ecopy'] == "1"){ 
		
		#} Get tags
		$aeScheduleRow = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$aeplugin_t['schedules']." WHERE AE_ScheduleID = %s",$_GET['aesid']));
		
		if ($aeScheduleRow){
			
				#} Get vals
				$AE_ScheduleID = $aeScheduleRow->AE_ScheduleID;
				$AE_PointOfAction = $aeScheduleRow->AE_PointOfAction;
				$AE_RuleID =  $aeScheduleRow->AE_RuleID;
				$AE_Target =  $aeScheduleRow->AE_Target;
				$AE_TargetVal =  $aeScheduleRow->AE_TargetVal;
				$AE_TargetValMultiplier = $aeScheduleRow->AE_TargetValMultiplier;
				$AE_Status =  $aeScheduleRow->AE_Status;
				$AE_LastRun =  $aeScheduleRow->AE_LastRun;
				$ae_str = array($AE_TargetVal,$AE_TargetValMultiplier);
				
				$AE_PresentRuleIDS = aeplugin_db_GetRuleIDsForSchedule($AE_ScheduleID);
		}
	
	}
	
    #} Used to verify existence of rules
    $ae_rules = aeplugin_db_GetRules();
    
    
?>
<form id="aenewschedule" name="aenewschedule" method="post" action="?page=<?php echo $aeplugin_slugs['schedules']; ?>&savenew=1">
<?php #FOR EDITS (copies miss this which makes them insert as a new
if (!empty($AE_ScheduleID) && $_GET['edit'] == "1"){ ?><input type="hidden" id="aesid" name="aesid" value="<?php echo $AE_ScheduleID; ?>" /><?php }
?>
    <table width="715" border="1">
    <tr>
        <td class="aeFieldLabelHD topHD" colspan="2">Run Rules</td>
  	</tr>
     <tr id="rule">
        <td valign="top" width="120" class="aeFieldLabel">Rules to run</td>
        <td><?php if ($usingJustDropDown){ ?>
        <select name="ae_rule" id="ae_rule">
           <?php
                $ae_rules = aeplugin_db_GetRules();
                if ($ae_rules) {
                    foreach ($ae_rules as $ae_rule){ 
                        ?><option value="<?php echo $ae_rule->AE_RuleID; ?>"<?php if ($AE_RuleID == $ae_rule->AE_RuleID){ echo ' selected="selected"'; } ?>><?php echo $ae_rule->AE_RuleName; ?></option><?php
                    }
                } else { 
                    ?><option value="-1">You haven't created any rules yet!</option><?php
                }	
            ?>
        </select><?php
		} else { 
				$ruleCount = 0;
                if ($ae_rules) {
                    
                    ?><div id="aeRulesList"><?php
                    
                    foreach ($ae_rules as $ae_rule){ 
					
						if ($ruleCount > 0){ $ruleStr .= ","; } $ruleStr .= 'ruleid_'.$ae_rule->AE_RuleID; 
						?><input type="checkbox" id="ruleid_<?php echo $ae_rule->AE_RuleID; ?>" name="ruleid_<?php echo $ae_rule->AE_RuleID; ?>" <?php if (aeplugin_compare_IsRuleIDInList($ae_rule->AE_RuleID,$AE_PresentRuleIDS)){ echo ' checked="checked"'; } ?> />
						<?php echo $ae_rule->AE_RuleName . ' ('.aeplugin_str_PrettyRuleType($ae_rule->AE_RuleType).')'; ?><br /><?php
						$ruleCount++;
                    }
                    
					?></div><?php
                    
					if ($ruleCount > 1){
						
						?>Select 
                        <a href="#" onclick="javascript:setChecks('<?php echo $ruleStr; ?>');">All</a> / 
                        <a href="#" onclick="javascript:clearChecks('<?php echo $ruleStr; ?>');">None</a><?php
						
					}
                    ?><br /><a href="?page=<?php echo $aeplugin_slugs['rules']; ?>&new=1&hop=1">Create new rule</a><?php
					
                } else { 
                    ?>You haven't created any rules yet! <a href="?page=<?php echo $aeplugin_slugs['rules']; ?>&new=1&hop=1">Create a rule now</a><?php
                }	
				
		}		
		?>
        </td>
    </tr>
   	<tr>
        <td class="aeFieldLabelHD" colspan="2">..on Target</td>
  	</tr>
    <tr id="target">
    <td class="aeFieldLabel">Run rule against</td>
    <td>
    	<select name="ae_target" id="ae_target" onchange="javascript:showLines();">
    		<option value="0"<?php if ($AE_Target == "0"){ echo ' selected="selected"'; } ?>>All posts</option>
            <option value="1"<?php if ($AE_Target == "1"){ echo ' selected="selected"'; } ?>>Posts containing string</option>
            <option value="2"<?php if ($AE_Target == "2"){ echo ' selected="selected"'; } ?>>Posts not containing string</option>
            <option value="3"<?php if ($AE_Target == "3"){ echo ' selected="selected"'; } ?>>Posts title containing string</option>
            <option value="4"<?php if ($AE_Target == "4"){ echo ' selected="selected"'; } ?>>Posts title not containing string</option>
            <option value="5"<?php if ($AE_Target == "5"){ echo ' selected="selected"'; } ?>>Posts in specific category</option>
            <option value="6"<?php if ($AE_Target == "6"){ echo ' selected="selected"'; } ?>>Posts not in specific category</option>
            <option value="7"<?php if ($AE_Target == "7"){ echo ' selected="selected"'; } ?>>Posts tagged</option>
            <option value="8"<?php if ($AE_Target == "8"){ echo ' selected="selected"'; } ?>>Posts without tag</option>
            <option value="9"<?php if ($AE_Target == "9"){ echo ' selected="selected"'; } ?>>Posts no older than</option>
            <option value="10"<?php if ($AE_Target == "10"){ echo ' selected="selected"'; } ?>>Most recent post</option>
        </select>
    </td>
  </tr>
  <tr id="constring">
    <td class="aeFieldLabel">Posts containing string:</td>
    <td><textarea name="ae_constring" id="ae_constring" cols="45" rows="5"><?php if ($AE_Target == "1"){aeplugin_str_FormsIfVal($ae_str[0]);} ?></textarea></td>
  </tr>
  <tr id="nonconstring">
    <td class="aeFieldLabel">Posts not containing string:</td>
    <td><textarea name="ae_nonconstring" id="ae_nonconstring" cols="45" rows="5"><?php if ($AE_Target == "2"){aeplugin_str_FormsIfVal($ae_str[0]);} ?></textarea></td>
  </tr>
  <tr id="titconstring">
    <td class="aeFieldLabel">Posts title containing string:</td>
    <td><textarea name="ae_titconstring" id="ae_titconstring" cols="45" rows="5"><?php if ($AE_Target == "3"){aeplugin_str_FormsIfVal($ae_str[0]);} ?></textarea></td>
  </tr>
  <tr id="titnonconstring">
    <td class="aeFieldLabel">Posts title not containing string:</td>
    <td><textarea name="ae_titnonconstring" id="ae_titnonconstring" cols="45" rows="5"><?php if ($AE_Target == "4"){aeplugin_str_FormsIfVal($ae_str[0]);} ?></textarea></td>
  </tr>
  <tr id="incat">
    <td class="aeFieldLabel">Posts in category</td>
    <td> 
        <select name="ae_incat" id="ae_incat">
            <?php
				$ae_cats = aeplugin_db_GetCats();
				if ($ae_cats) {
					foreach ($ae_cats as $ae_cat){ 
						?><option value="<?php echo $ae_cat->term_id; ?>"<?php if ($AE_Target == "5"){if ($ae_str[0] == $ae_cat->term_id){ echo ' selected="selected"'; }} ?>><?php echo $ae_cat->name; ?></option><?php
					}
				} else { 
					?><option value="-1">No categories present!</option><?php
				}	
			?>
            </select>
    </td>
  </tr>
  <tr id="notincat">
    <td class="aeFieldLabel">Posts not in category</td>
    <td> 
            <select name="ae_notincat" id="ae_notincat">
            <?php
				$ae_cats = aeplugin_db_GetCats();
				if ($ae_cats) {
					foreach ($ae_cats as $ae_cat){ 
						?><option value="<?php echo $ae_cat->term_id; ?>"<?php if ($AE_Target == "6"){if ($ae_str[0] == $ae_cat->term_id){ echo ' selected="selected"'; }} ?>><?php echo $ae_cat->name; ?></option><?php
					}
				} else { 
					?><option value="-1">No categories present!</option><?php
				}	
			?>
            </select>
    </td>
  </tr>
  <tr id="tagged">
    <td class="aeFieldLabel">Posts tagged</td>
    <td> 
         <select name="ae_tagged" id="ae_tagged">
            <?php
				$ae_tags = aeplugin_db_GetTags();
				if ($ae_tags) {
					foreach ($ae_tags as $ae_tag){ 
						?><option value="<?php echo $ae_tag->term_id; ?>"<?php if ($AE_Target == "7"){ if ($ae_str[0] == $ae_tag->term_id){ echo ' selected="selected"'; }} ?>><?php echo $ae_tag->name; ?></option><?php
					}
				} else { 
					?><option value="-1">No tags present!</option><?php
				}	
			?>
            </select>
    </td>
  </tr>
  <tr id="nottagged">
    <td class="aeFieldLabel">Posts not tagged</td>
    <td> 
         <select name="ae_nottagged" id="ae_nottagged">
            <?php
				$ae_tags = aeplugin_db_GetTags();
				if ($ae_tags) {
					foreach ($ae_tags as $ae_tag){ 
						?><option value="<?php echo $ae_tag->term_id; ?>"<?php if ($AE_Target == "8"){if ($ae_str[0] == $ae_tag->term_id){ echo ' selected="selected"'; }} ?>><?php echo $ae_tag->name; ?></option><?php
					}
				} else { 
					?><option value="-1">No tags present!</option><?php
				}	
			?>
            </select>
    </td>
  </tr>
  <tr id="noolder">
    <td class="aeFieldLabel">Posts no older than</td>
    <td>
    <select name="ae_noolder" id="ae_noolder">
    	<?php
        	foreach(range(0, 31) as $number){
				?><option<?php if ($AE_Target == "9"){ if ($number == $ae_str[0]){ echo ' selected="selected"';}} ?>><?php echo $number; ?></option><?php
			}
		?>
    </select>
    <select name="ae_noolder_multiplier" id="ae_noolder_multiplier">
    	<option value="1440"<?php if ($AE_Target == "9"){ if ($ae_str[1] == "1440"){ echo ' selected="selected"';}} ?>>Days</option>
    	<option value="60"<?php if ($AE_Target == "9"){ if ($ae_str[1] == "60"){ echo ' selected="selected"';}} ?>>Hours</option>
    	<option value="1"<?php if ($AE_Target == "9"){ if ($ae_str[1] == "1"){ echo ' selected="selected"';}} ?>>Minutes</option>
    </select>
    </td>
  </tr>
  <tr><td></td><td></td></tr>
    <tr>
        <td class="aeFieldLabelHD" colspan="2">When</td>
  	</tr>
    <tr>
    <td class="aeFieldLabel">Run</td>
    <td>
    <select name="ae_pointofaction" id="ae_pointofaction">
    	<option value="1"<?php if ($AE_PointOfAction == "1"){ echo ' selected="selected"'; } ?>>After a post is published</option>
    	<option value="2"<?php if ($AE_PointOfAction == "2"){ echo ' selected="selected"'; } ?>>Every hour*</option>
    	<option value="3"<?php if ($AE_PointOfAction == "3"){ echo ' selected="selected"'; } ?>>Every day*</option>        
    </select>
    </td>
  </tr>
      <tr>
        <td>&nbsp;</td>
        <td><input type="submit" name="button" id="button" value="Save Schedule" style="margin-top:24px;" /></td>
      </tr>
    </table>
</form>
<?php
if (!empty($AE_Target)){  
	?>
    <script type="text/javascript">document.aenewschedule.ae_target[<?php echo int($AE_Target)-1; ?>].selected = "1";showLines();</script>
    <?php
}
?>

<?php
    } #vs
}


#} New Rule HTML
function aeplugin_html_rule_new(){
	
	#} Note: if this is fired with edit=1 flag itll autofill
    
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
    if ($_GET['edit'] == "1"){ $vr = true; } else { $vr = (count(aeplugin_db_GetRules()) < $aeplugin_t['ark']) ? true : false; }
	
	if (!$vr){
       wp_die( __($aeplugin_t['gr']) ); 
    } else { 
    #} For redirect after save
    $aehop = $_GET['hop'];
	
	if ($_GET['edit'] == "1" || $_GET['ecopy'] == "1"){ 
		
		#} Get tags
		$aeRuleRow = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$aeplugin_t['rules']." WHERE AE_RuleID = %s",$_GET['aerid']));
		
		if ($aeRuleRow){
			
				#} Get vals
				$AE_RuleID = $aeRuleRow->AE_RuleID;
				$AE_RuleName = $aeRuleRow->AE_RuleName;
				$AE_RuleType =  $aeRuleRow->AE_RuleType;
				$AE_RepInstance =  $aeRuleRow->AE_RepInstance;
								
				switch ($AE_RuleType){
						case "1": #STRING REPLACE
							$ae_str = $aeRuleRow->AE_Str;
							$ae_str2 = $aeRuleRow->AE_RepStr;
							$ae_opts_instances = $aeRuleRow->AE_RepInstance;
							break;
						case "2": #STRING REMOVE
							$ae_str = $aeRuleRow->AE_Str;
							$ae_opts_instances = $aeRuleRow->AE_RepInstance;
							break;
						case "3": #REGEX REPLACE
							$ae_str = $aeRuleRow->AE_Str;
							$ae_str2 = $aeRuleRow->AE_RepStr;
							$ae_opts_instances = "-1"; 
							break;
						case "4": #REGEX REMOVE
							$ae_str = $aeRuleRow->AE_Str;
							$ae_opts_instances = "-1"; 
							break;
						case "5": #PREPEND
							$ae_str5 = $aeRuleRow->AE_Str;
							break;
						case "6": #APPEND
							$ae_str4 = $aeRuleRow->AE_Str;
							break;
						case "7": #CATEGORY
							$ae_catq = $aeRuleRow->AE_Str;
							break;
						case "8": #TAG
							$ae_tagq = $aeRuleRow->AE_Str;
							break;
						case "9": #DATE
							$ae_date = $aeRuleRow->AE_Str;
							break;
						case "10": #STATUS
							$ae_opts_10 = $aeRuleRow->AE_Str;
							break;
				}
		}
	
	}
	
	
?>
<form id="aenewrule" name="aenewrule" method="post" action="?page=<?php echo $aeplugin_slugs['rules']; ?>&savenew=1">
<?php if (!empty($aehop)) { ?><input type="hidden" id="hop" name="hop" value="<?php echo $aehop; ?>" /><?php } ?>
<?php #FOR EDITS (copies miss this which makes them insert as a new
if (!empty($AE_RuleID) && $_GET['edit'] == "1"){ ?><input type="hidden" id="aerid" name="aerid" value="<?php echo $AE_RuleID; ?>" /><?php }
?>
    <table width="715" border="1">
      <tr>
        <td width="100" class="aeFieldLabel">Rule name</td>
        <td><input type="text" name="ae_name" id="ae_name"<?php aeplugin_str_FormsIfVal($AE_RuleName,1); ?> /></td>
      </tr>
      <tr>
        <td class="aeFieldLabel">Rule action</td>
        <td>
            <select name="ae_type" id="ae_type" onchange="javascript:showLines();">
                <option value="1"<?php if ($AE_RuleType == "1"){ echo ' selected="selected"'; } ?>>String replace</option>
                <option value="2"<?php if ($AE_RuleType == "2"){ echo ' selected="selected"'; } ?>>String remove</option>
                <option value="3"<?php if ($AE_RuleType == "3"){ echo ' selected="selected"'; } ?>>Regex replace</option>
                <option value="4"<?php if ($AE_RuleType == "4"){ echo ' selected="selected"'; } ?>>Regex remove</option>
                <option value="5"<?php if ($AE_RuleType == "5"){ echo ' selected="selected"'; } ?>>Prepend</option>
                <option value="6"<?php if ($AE_RuleType == "6"){ echo ' selected="selected"'; } ?>>Append</option>
                <option value="7"<?php if ($AE_RuleType == "7"){ echo ' selected="selected"'; } ?>>Add category</option>
                <option value="8"<?php if ($AE_RuleType == "8"){ echo ' selected="selected"'; } ?>>Add tag</option>
                <option value="9"<?php if ($AE_RuleType == "9"){ echo ' selected="selected"'; } ?>>Change post date</option>
                <option value="10"<?php if ($AE_RuleType == "10"){ echo ' selected="selected"'; } ?>>Change post status</option>
            </select>
        </td>
      </tr>
      <tr id="strrep">
        <td class="aeFieldLabel">Replace</td>
        <td><textarea name="ae_str" id="ae_str" cols="45" rows="5"><?php aeplugin_str_FormsIfVal($ae_str); ?></textarea></td>
      </tr>
      <tr id="strrep2">
        <td class="aeFieldLabel">With</td>
        <td><textarea name="ae_str2" id="ae_str2" cols="45" rows="5"><?php aeplugin_str_FormsIfVal($ae_str2); ?></textarea></td>
      </tr>
      <tr id="strrem">
        <td class="aeFieldLabel">Remove</td>
        <td><textarea name="ae_strrem" id="ae_strrem" cols="45" rows="5"><?php aeplugin_str_FormsIfVal($ae_str); ?></textarea></td>
      </tr>
      <tr id="prepend">
        <td class="aeFieldLabel">Prepend (add to start of post content)</td>
        <td><textarea name="ae_str5" id="ae_str5" cols="45" rows="5"><?php aeplugin_str_FormsIfVal($ae_str5); ?></textarea></td>
      </tr>
      <tr id="append">
        <td class="aeFieldLabel">Append (add to end of post content)</td>
        <td>
          <textarea name="ae_str4" id="ae_str4" cols="45" rows="5"><?php aeplugin_str_FormsIfVal($ae_str4); ?></textarea> 
        </td>
      </tr>
      <tr id="addcat">
        <td class="aeFieldLabel">Add category</td>
        <td> 
            <select name="ae_cat" id="ae_cat">
            <?php
				$ae_cats = aeplugin_db_GetCats();
				if ($ae_cats) {
					foreach ($ae_cats as $ae_cat){ 
						?><option value="<?php echo $ae_cat->term_id; ?>"<?php if ($ae_catq == $ae_cat->term_id){ echo ' selected="selected"'; } ?>><?php echo $ae_cat->name; ?></option><?php
					}
				} else { 
					?><option value="-1">No categories present!</option><?php
				}	
			?>
            </select>
        </td>
      </tr>
      <tr id="addtag">
        <td class="aeFieldLabel">Add tag</td>
        <td> 
            <select name="ae_tag" id="ae_tag">
            <?php
				$ae_tags = aeplugin_db_GetTags();
				if ($ae_tags) {
					foreach ($ae_tags as $ae_tag){ 
						?><option value="<?php echo $ae_tag->term_id; ?>"<?php if ($ae_tagq == $ae_tag->term_id){ echo ' selected="selected"'; } ?>><?php echo $ae_tag->name; ?></option><?php
					}
				} else { 
					?><option value="-1">No tags present!</option><?php
				}	
			?>
            </select>
        </td>
      </tr>
      <tr id="changedate">
        <td class="aeFieldLabel">Change post date too</td>
        <td>
            <input type="text" name="ae_date" id="ae_date"<?php aeplugin_str_FormsIfVal($ae_date); ?> />
        </td>
      </tr>
      
      <tr id="changestatus">
        <td class="aeFieldLabel">Change post status too</td>
        <td>
            <select name="ae_opts_10" id="ae_opts_10">
                <option<?php if ($ae_opts_10 == "Draft"){ echo ' selected="selected"'; } ?>>Draft</option>
                <option<?php if ($ae_opts_10 == "Published"){ echo ' selected="selected"'; } ?>>Published</option>
                <option<?php if ($ae_opts_10 == "Trash"){ echo ' selected="selected"'; } ?>>Trash</option>
            </select>
        </td>
      </tr>
      
      <tr id="instances">
        <td class="aeFieldLabel">Replace/Remove instances</td>
        <td>
            <select name="ae_opts_instances" id="ae_opts_instances">
                <option value="-1"<?php if ($AE_RepInstance == -1){ echo ' selected="selected"'; } ?>>All</option>
                <option value="1"<?php if ($AE_RepInstance == 1){ echo ' selected="selected"'; } ?>>1st</option>
                <option value="2"<?php if ($AE_RepInstance == 2){ echo ' selected="selected"'; } ?>>2nd</option>
                <option value="3"<?php if ($AE_RepInstance == 3){ echo ' selected="selected"'; } ?>>3rd</option>
                <option value="4"<?php if ($AE_RepInstance == 4){ echo ' selected="selected"'; } ?>>4th</option>
                <option value="5"<?php if ($AE_RepInstance == 5){ echo ' selected="selected"'; } ?>>5th</option>
                <option value="6"<?php if ($AE_RepInstance == 6){ echo ' selected="selected"'; } ?>>6th</option>
                <option value="7"<?php if ($AE_RepInstance == 7){ echo ' selected="selected"'; } ?>>7th</option>
                <option value="8"<?php if ($AE_RepInstance == 8){ echo ' selected="selected"'; } ?>>8th</option>
                <option value="9"<?php if ($AE_RepInstance == 9){ echo ' selected="selected"'; } ?>>9th</option>
                <option value="10"<?php if ($AE_RepInstance == 10){ echo ' selected="selected"'; } ?>>10th</option>
            </select>
        </td>
      </tr>
      
      <tr>
        <td>&nbsp;</td>
        <td><input type="submit" name="button" id="button" value="Save Rule" /></td>
      </tr>
    </table>
</form>
<?php
if (!empty($AE_RuleType)){  
	?>
    <script type="text/javascript">document.aenewrule.ae_type[<?php echo int($AE_RuleType)-1; ?>].selected = "1";showLines();</script>
    <?php
}
?>

<?php
    } #vr
}






##--##} Page Save downs (some generate HTML)


#} Save Schedule
function aeplugin_html_save_schedule(){

	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
	#} If this is set its an edit not save
	$AE_EditID = $_POST['aesid'];
	
	#} Comprehend saving
	$AE_RuleID = $_POST['ae_rule'];
	
	#} Replaced ruleid get with multiple pull
	$AE_RulesList = aeplugin_db_GetRules();
	$AE_RulesRelationships = array(); #} THIS is the real array of selected boxes
	foreach ($AE_RulesList as $AE_Rule){
	
		if (!empty($_POST['ruleid_'.$AE_Rule->AE_RuleID])){ 
				
				array_push($AE_RulesRelationships,$AE_Rule->AE_RuleID);
		}
		
	}
	
	$AE_Target = $_POST['ae_target'];
	$AE_PointOfAction =  $_POST['ae_pointofaction'];
	
	$AE_Str = "";
	$AE_StrVal = "";
		
	switch ($AE_Target){
				case "0": #all (no options)
					break;
				case "1": #containing string
					$AE_Str =  $_POST['ae_constring'];
					break;
				case "2": #not containing string
					$AE_Str =  $_POST['ae_nonconstring'];
					break;
				case "3": #title containing string
					$AE_Str =  $_POST['ae_titconstring'];
					break;
				case "4": #title not containing string
					$AE_Str =  $_POST['ae_titnonconstring'];
					break;
				case "5": #in cat
					$AE_Str =  $_POST['ae_incat'];
					break;
				case "6": #not containing string
					$AE_Str =  $_POST['ae_notincat'];
					break;
				case "7": #not containing string
					$AE_Str =  $_POST['ae_tagged'];
					break;
				case "8": #not containing string
					$AE_Str =  $_POST['ae_nottagged'];
					break;
				case "9": #not containing string
					$AE_Str =  $_POST['ae_noolder'];
					$AE_StrVal =  $_POST['ae_noolder_multiplier'];
					break;
				case "10": #most recent post (no options)
					break;
	}
	
		
		if (empty($AE_EditID)){ #} its new just save
		
		#} Save
		$wpdb->insert( $aeplugin_t['schedules'], array( 
									  'AE_PointOfAction' => $AE_PointOfAction, 
									  'AE_RuleID' => 'superceded',  
									  'AE_Target' => $AE_Target, 
									  'AE_TargetVal' => $AE_Str, 
									  'AE_TargetValMultiplier' => $AE_StrVal, 
									  'AE_LastRun' => '2000-01-01 00:00:00',
									  'AE_Status' => 1 #} default = on
									  )
					  );
		
			$savedID = $wpdb->insert_id;
			$savedScheduleID = $savedID;
			
			#} Save rule relationships
			$ruleSaveCount = 0;
			foreach ($AE_RulesRelationships as $AE_RuleRelID){
						
						$wpdb->insert( $aeplugin_t['relationships'], array(
									  'AE_ScheduleID' => $savedScheduleID, 
									  'AE_RuleID' => $AE_RuleRelID
									  )
					  	);
						if ($wpdb->insert_id){ $ruleSaveCount++; }
				
			}
			
			if (!$savedScheduleID){
				#} Failed	
				aeplugin_html_msg(-1,"Failed saving schedule, please contact support.");
				
			} else {
				#} Success 
				aeplugin_html_msg(0,"Schedule saved (ID ".$savedID.')');
			}
		
		} else { #} Its an edit, just update
		
			#} Update
			$aeup = $wpdb->update( $aeplugin_t['schedules'], 
								array( 
									  'AE_PointOfAction' => $AE_PointOfAction, 
									  'AE_RuleID' => $AE_RuleID,  
									  'AE_Target' => $AE_Target, 
									  'AE_TargetVal' => $AE_Str, 
									  'AE_TargetValMultiplier' => $AE_StrVal,
									  'AE_LastRun' => '2000-01-01 00:00:00'
									  ),
								 array( 'AE_ScheduleID' => $AE_EditID )
					  );
			
			#} Clear rule relationships
			$wpdb->query($wpdb->prepare("DELETE FROM ".$aeplugin_t['relationships']." WHERE AE_ScheduleID = %s",$AE_EditID));

			
			#} Save rule relationships - no checking?
			$ruleSaveCount = 0;
			foreach ($AE_RulesRelationships as $AE_RuleRelID){
						
						$wpdb->insert( $aeplugin_t['relationships'], array(
									  'AE_ScheduleID' => $AE_EditID, 
									  'AE_RuleID' => $AE_RuleRelID
									  )
						);

						if ($wpdb->insert_id){ $ruleSaveCount++; }
				
			} 
			
			if (!$aeup){
				#} Failed	
				#} missfires on non-updates (nochanges) aeplugin_html_msg(-1,"Failed updating rule, please contact support.");
				
			} else {
				#} Success 
				aeplugin_html_msg(0,"Schedule updated");//  ".$ruleSaveCount . " rules!");
			}
			
		}
			
	#} Load the rest
	aeplugin_html_Schedules();
}


#} Save a rule
function aeplugin_html_save_rule(){

	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
    #} For redirect after save
    $aehop = $_POST['hop'];
    switch ($aehop){ 
        
        case "1":
            $ae_redir = "?page=".$aeplugin_slugs['schedules']."&new=1";
            break;
        case "2":
            $ae_redir = "?page=".$aeplugin_slugs['schedules'];
            break;
        case "3":
            $ae_redir = "?page=".$aeplugin_slugs['home'];
            break;        
        
    }
	
	#} If this is set its an edit not save
	$AE_EditID = $_POST['aerid'];

	#} Comprehend saving
	$AE_RuleName = $_POST['ae_name']; #} standards
	$AE_RuleType =  $_POST['ae_type']; #} standards
	
	#} Defaults
	$AE_Str = "";
	$AE_RepStr = "";
	$AE_RepInstance = -1;
	
	switch ($AE_RuleType){
				case "1": #STRING REPLACE
					$AE_Str =  $_POST['ae_str'];
					$AE_RepStr =  $_POST['ae_str2'];
					$AE_RepInstance = $_POST['ae_opts_instances'];
					break;
				case "2": #STRING REMOVE
					$AE_Str =  $_POST['ae_strrem'];
					$AE_RepInstance = $_POST['ae_opts_instances'];
					break;
				case "3": #REGEX REPLACE
					$AE_Str =  $_POST['ae_str'];
					$AE_RepStr =  $_POST['ae_str2'];
					$AE_RepInstance = $_POST['ae_opts_instances'];
					break;
				case "4": #REGEX REMOVE
					$AE_Str =  $_POST['ae_strrem'];
					$AE_RepInstance = $_POST['ae_opts_instances'];
					break;
				case "5": #PREPEND
					$AE_Str =  $_POST['ae_str5'];
					break;
				case "6": #APPEND
					$AE_Str =  $_POST['ae_str4'];
					break;
				case "7": #CATEGORY
					$AE_Str =  $_POST['ae_cat'];
					break;
				case "8": #TAG
					$AE_Str =  $_POST['ae_tag'];
					break;
				case "9": #DATE
					$AE_Str =  $_POST['ae_date']; #} no checking?
					break;
				case "10": #STATUS
					$AE_Str =  $_POST['ae_opts_10'];
					break;
	}
		
		if (empty($AE_EditID)){ #} Its new, just save
		
		#} save
		$wpdb->insert( $aeplugin_t['rules'], array( 
									  'AE_RuleName' => $AE_RuleName, 
									  'AE_RuleType' => $AE_RuleType, 
									  'AE_Str' => $AE_Str, 
									  'AE_RepStr' => $AE_RepStr, 
									  'AE_RepInstance' => $AE_RepInstance, 
									  'AE_HitCount' => 0
									  )
					  );
		
			$savedID = $wpdb->insert_id;
		
			if (!$savedID){
				#} Failed	
				aeplugin_html_msg(-1,"Failed saving rule, please contact support.");
				
			} else {
				#} Success 
				aeplugin_html_msg(0,"Rule saved (ID ".$savedID.')');
			}
		
		} else { #} Its an edit, just update
		
		#} Update
			$aeup = $wpdb->update( $aeplugin_t['rules'], 
								array( 
									  'AE_RuleName' => $AE_RuleName, 
									  'AE_RuleType' => $AE_RuleType, 
									  'AE_Str' => $AE_Str, 
									  'AE_RepStr' => $AE_RepStr, 
									  'AE_RepInstance' => $AE_RepInstance, 
									  'AE_HitCount' => 0
									  ),
								 array( 'AE_RuleID' => $AE_EditID )
					  );
			
			if (!$aeup){
				#} Failed	
				#} missfires on non-updates (nochanges) aeplugin_html_msg(-1,"Failed updating rule, please contact support.");
				
			} else {
				#} Success 
				aeplugin_html_msg(0,"Rule updated");
			}
			
		}  
		
	
	if (empty($ae_redir)){
        	#} Load the rules page as normal
        	aeplugin_html_rules();
    } else { 
            #} Redirect some place
            aeplugin_html_msg(2,"Redirecting you back to where you were...");
            ?><script type="text/javascript">window.location = "<?php echo $ae_redir; ?>"</script><?php        
    }
}


#} Save options changes 
function aeplugin_html_save_options(){
    
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	    
    #} Save down  
	update_option("aeplugin_runruleson", $_POST['aeplugin_runruleson']);
    update_option("aeplugin_welcome",$_POST['aeplugin_welcome']);
    if (Is_Numeric($_POST['aeplugin_loglimit'])) update_option("aeplugin_loglimit",$_POST['aeplugin_loglimit']);
    
    #} Msg
    aeplugin_html_msg(0,"Saved options");
    
    #} Run standard
    aeplugin_html_options();
    
}



##--##} DELETE STUFF 


#} Deletes a schedule
function aeplugin_html_delete_schedule(){

	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
	#} Get vars
	$AE_ScheduleID = $_GET['aesid'];
	$AE_ScheduleIDWarned = $_GET['warned'];

	if ($AE_ScheduleIDWarned == "1"){
		# Delete schedule		
		if ($wpdb->query($wpdb->prepare("DELETE FROM ".$aeplugin_t['schedules']." WHERE AE_ScheduleID = %s",$AE_ScheduleID)) != false){
			
			#} SUCCESS
			aeplugin_html_msg(0,"Schedule deleted");	
			#} load the rest
			aeplugin_html_schedules();	
			
		} else { 

			#} FAIL
			aeplugin_html_msg(-1,"There was an error when trying to delete schedule ID " . $AE_ScheduleID . ", please contact support.");
			
		}
		
		
	} else { 
		#warn first
		$aewarnHTML = 'Are you sure you want to delete this schedule?<br /><br /><a href="?page='.$aeplugin_slugs['schedules'].'&aesid='.$AE_ScheduleID.'&warned=1&del=1" class="add-new-h2">Yes</a> <a href="?page='.$aeplugin_slugs['schedules'].'" class="add-new-h2">No</a>';
		aeplugin_html_msg(1,$aewarnHTML,true);
	}
	
	
}


#} Delete a rule
function aeplugin_html_delete_rule(){

	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
	#} Get vars
	$AE_RuleID = $_GET['aerid'];
	$AE_RuleIDWarned = $_GET['warned']; 

	if ($AE_RuleIDWarned == "1"){
		# Delete rule	
		if ($wpdb->query($wpdb->prepare("DELETE FROM ".$aeplugin_t['rules']." WHERE AE_RuleID = %s",$AE_RuleID)) != false){
			
			#} SUCCESS
			aeplugin_html_msg(0,"Rule deleted");	
			#} load the rest
			aeplugin_html_rules();	
			
		} else { 

			#} FAIL
			aeplugin_html_msg(-1,"There was an error when trying to delete rule ID " . $AE_RuleID . ", please contact support.");
			
		}
		
	} else { 
		#warn first
		$aewarnHTML = 'Are you sure you want to delete this rule?<br /><br /><a href="?page='.$aeplugin_slugs['rules'].'&aerid='.$AE_RuleID.'&warned=1&del=1" class="add-new-h2">Yes</a> <a href="?page='.$aeplugin_slugs['rules'].'" class="add-new-h2">No</a>';
		aeplugin_html_msg(1,$aewarnHTML,true);
	}
	
}




##--##} Operational functions - save, update, process - No HTML generation ------------


#} Runs rules @ publish post - acts as middleman. Is fired on "publish" event and will find which schedules need to be run and run them
function aeplugin_ops_RunRulesPublishPost($post_id=0){
		
    aeplugin_ops_RunSchedule("1");
	
}


#} Runs rules @ wp hourly schedule - acts as middleman. will be scheduled to run as near as possible each hour (wp does scheduling <3)
function aeplugin_ops_RunRulesScheduleHour($post_id){
		
    aeplugin_ops_RunSchedule("2");
	
}


#} Runs rules @ wp hourly schedule - acts as middleman. will be scheduled to run as near as possible each day (wp does scheduling <3)
function aeplugin_ops_RunRulesScheduleDay($post_id){
 
    aeplugin_ops_RunSchedule("3");
	
}


#} Runs actual schedule - this is the mother function which is fired at all points of action (on publish, hourly, daily.)
function aeplugin_ops_RunSchedule($pointOfAction){
		
    #} If $pointOfAction = 1 it will run "on publish" schedules, 2 it will run "every hour" schedules, 3 "every day" schedules
    #} Note: as of 19/07/2011 1.51pm this also includes a global on/off switch in the wp option aeplugin_status 
     
    if (get_option('aeplugin_status') == "1"){
            
        	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
        	
        		#} Get schedules that need to be run
        		$aesql = "SELECT * FROM ".$aeplugin_t['schedules']." WHERE AE_PointOfAction = ".$pointOfAction;

        		$ae_schedules = $wpdb->get_results($aesql);
        		
				if ($ae_schedules){
					
						#} Cycle through schedules
						foreach ($ae_schedules as $ae_schedule){
								
								if ($ae_schedule->AE_Status == "1"){ #} Only run if schedule is turned on
								
										#} Cycle through rules
										$AErules = aeplugin_db_GetRulesForSchedule($ae_schedule->AE_ScheduleID); 
										
										foreach ($AErules as $AE_Rule){
											  aeplugin_ops_RunRule($AE_Rule,
														$ae_schedule->AE_Target,
														$ae_schedule->AE_TargetVal,
														$ae_schedule->AE_TargetValMultiplier,
														$ae_schedule->AE_ScheduleID);   
										}
								
								}
								
						}
				}
	} else {
	   
       #} do nothing man its switched off!
       
	}
}

#} Logs rules being run
function aeplugin_ops_log($scheduleID,$ruleID,$flag,$msg,$updateScheduleIDLastRun = true,$hitcount = -1){
    
    
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
    
    #} Checks how many logs exist, clean up if already at max
    $logLimit = (int)get_option('aeplugin_loglimit');
    $logLimit--;
    $unwantedLogs = $wpdb->get_results("SELECT AE_LogID FROM ".$aeplugin_t['logs']." ORDER BY AE_LogID DESC LIMIT ".$logLimit.",100000000");
    if ($unwantedLogs){
		foreach ($unwantedLogs as $uw){
        
			#} trash em
			$wpdb->query($wpdb->prepare("DELETE FROM ".$aeplugin_t['logs']." WHERE AE_LogID = %s",$uw->AE_LogID));
        
    	}
	}
    
	#} Add log
    $wpdb->insert($aeplugin_t['logs'],
							  array(	'AE_ScheduleID' => $scheduleID,
										'AE_RuleID' => $ruleID, 
										'AE_Flag' => $flag,
										'AE_Msg' => $msg,
										'AE_LogTime' => date("Y-m-d H:i:s",
										time())));

    if ($wpdb->insert_id){ 
        
        #} Logged ok?
        
    } else { 
        
        aeplugin_html_msg(-1,'Trouble logging!!!');
        
    }
    
    if ($updateScheduleIDLastRun){
        
        $wpdb->update($aeplugin_t['schedules'],array('AE_LastRun' => date("Y-m-d H:i:s", time())),array('AE_ScheduleID' => $scheduleID));
		
    }
    
    if ($hitcount != -1){
        #} Update hit counter
        $wpdb->update($aeplugin_t['rules'],array('AE_HitCount' => $hitcount+1),array('AE_RuleID' => $ruleID));
    }
	
}

#} ACTUAL running rule function, does all the hard labour here
function aeplugin_ops_RunRule($AE_RuleRow,$AE_Target,$AE_TargetVal,$AE_TargetValMultipler=0,$scheduleID=-1){
	
    #} Note:
    #} targetvalmultiplier is present for time based targets
    #} scheduleid is for logging - if ignored (-1 passed) then it will be logged as "test run"
    
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
    
    #} Implements options page option to run on all posts, draft or published
    $runOn = get_option('aeplugin_runruleson');
    $whereAdd = "";
    switch ($runOn) {
        
            case "-1":
                $whereAdd = "";
                break;
            case "1":
                $whereAdd = " and post_status = 'publish'";
                break;
            case "2":
                $whereAdd = " and post_status = 'draft'";
                break;
    }
    
    #} Get list of target posts
        $aesql = "";
    	switch ($AE_Target){
				case "0": #all (no options)
                    $aesql = "SELECT * FROM " . $wpdb->prefix . "posts WHERE post_type = 'post'".$whereAdd." ORDER BY id";
					break;
				case "1": #containing string
                    $aesql = "SELECT * FROM " . $wpdb->prefix . "posts WHERE post_type = 'post'".$whereAdd." AND post_content LIKE  '%".$AE_TargetVal."%' ORDER BY id"; #} sanatation?
					break;
				case "2": #not containing string
					$aesql = "SELECT * FROM " . $wpdb->prefix . "posts WHERE post_type = 'post'".$whereAdd." AND post_content NOT LIKE  '%".$AE_TargetVal."%' ORDER BY id"; #} sanatation?
					break;
				case "3": #title containing string
					$aesql = "SELECT * FROM " . $wpdb->prefix . "posts WHERE post_type = 'post'".$whereAdd." AND post_title LIKE  '%".$AE_TargetVal."%' ORDER BY id"; #} sanatation?
					break;
				case "4": #title not containing string
					$aesql = "SELECT * FROM " . $wpdb->prefix . "posts WHERE post_type = 'post'".$whereAdd." AND post_title NOT LIKE  '%".$AE_TargetVal."%' ORDER BY id"; #} sanatation?
					break;
				case "5": #in cat
					$aesql = "SELECT * FROM " . $wpdb->prefix . "posts WHERE post_type = 'post'".$whereAdd." AND ID IN (select object_id from " . $wpdb->prefix . "term_relationships WHERE term_taxonomy_id = ".$AE_TargetVal.")";
					break;
				case "6": #not in cat
					$aesql = "SELECT * FROM " . $wpdb->prefix . "posts WHERE post_type = 'post'".$whereAdd." AND ID NOT IN (select object_id from " . $wpdb->prefix . "term_relationships WHERE term_taxonomy_id = ".$AE_TargetVal.")";
					break;
				case "7": #tagged
					$aesql = "SELECT * FROM " . $wpdb->prefix . "posts WHERE post_type = 'post'".$whereAdd." AND ID IN (select object_id from " . $wpdb->prefix . "term_relationships WHERE term_taxonomy_id = ".$AE_TargetVal.")";
					break;
				case "8": #not tagged
					$aesql = "SELECT * FROM " . $wpdb->prefix . "posts WHERE post_type = 'post'".$whereAdd." AND ID NOT IN (select object_id from " . $wpdb->prefix . "term_relationships WHERE term_taxonomy_id = ".$AE_TargetVal.")";
					break;
				case "9": #no older than
                    $olderThanMins = $AE_TargetVal * $AE_TargetValMultipler;
                    $olderThanDateTime = strtotime("-".$olderThanMins." minutes");
                    $aesql = "SELECT * FROM " . $wpdb->prefix . "posts WHERE post_type = 'post'".$whereAdd." AND post_date > '".date("Y-m-d H:i:s", $olderThanDateTime)."'";
					break;
				case "10": #most recent post (no options) //Note id > 0 is a bullshit non condition, always will be.
                    $aesql = "SELECT * FROM " . $wpdb->prefix . "posts WHERE id > 0".$whereAdd." ORDER BY ID DESC LIMIT 0,1";
					break;
	}
	
    #} Get target posts
	$ae_targets = $wpdb->get_results($aesql);
    
	if ($ae_targets){
				
			#} For logs - count targets
			$ae_targetCount = count($ae_targets);
			
			#} Cycle through posts
			foreach ($ae_targets as $targetPost){
				
						switch ($AE_RuleRow->AE_RuleType){
							
							case "1": #STRING REPLACE
										$postHTML = '';
										$postHTML = $targetPost->post_content;
										$postHTML = aeplugin_str_ReplaceInstances($AE_RuleRow->AE_Str,$postHTML,$AE_RuleRow->AE_RepStr,$AE_RuleRow->AE_RepInstance);
										aeplugin_ops_UpdatePostFieldRough($targetPost->ID,"post_content",$postHTML);
										break;
									case "2": #STRING REMOVE
										$postHTML = '';
										$postHTML = $targetPost->post_content;
										$postHTML = aeplugin_str_ReplaceInstances($AE_RuleRow->AE_Str,$postHTML,"",$AE_RuleRow->AE_RepInstance);
										aeplugin_ops_UpdatePostFieldRough($targetPost->ID,"post_content",$postHTML);
										break;
									case "3": #REGEX REPLACE
										$postHTML = '';
										$postHTML = $targetPost->post_content;
										$postHTML = preg_replace($AE_RuleRow->AE_Str, $AE_RuleRow->AE_RepStr, $postHTML);
										aeplugin_ops_UpdatePostFieldRough($targetPost->ID,"post_content",$postHTML);
										break;
									case "4": #REGEX REMOVE
										$postHTML = '';
										$postHTML = $targetPost->post_content;
										$postHTML = preg_replace($AE_RuleRow->AE_Str, "", $postHTML);
										aeplugin_ops_UpdatePostFieldRough($targetPost->ID,"post_content",$postHTML);
										break;
									case "5": #PREPEND
										$postHTML = '';
										$postHTML = $targetPost->post_content;
										$postHTML = $AE_RuleRow->AE_Str . $postHTML;
										aeplugin_ops_UpdatePostFieldRough($targetPost->ID,"post_content",$postHTML);
										break;
									case "6": #APPEND
										$postHTML = '';
										$postHTML = $targetPost->post_content;
										$postHTML = $postHTML . $AE_RuleRow->AE_Str;
										aeplugin_ops_UpdatePostFieldRough($targetPost->ID,"post_content",$postHTML);
										break;
									case "7": #CATEGORY
										#} check if present
										if (!has_term((int)$AE_RuleRow->AE_Str,'category',$targetPost->ID)){
											#} insert
											$wpdb->insert( $wpdb->prefix."term_relationships", array( 'object_id' => $targetPost->ID, 'term_taxonomy_id' => $AE_RuleRow->AE_Str, 'term_order' => 1));
										}
										break;
									case "8": #TAG
										#} check if present
										if (!has_term((int)$AE_RuleRow->AE_Str,'post_tag',$targetPost->ID)){
											#} insert
											$wpdb->insert( $wpdb->prefix."term_relationships", array( 'object_id' => $targetPost->ID, 'term_taxonomy_id' => $AE_RuleRow->AE_Str, 'term_order' => 1));
										}
										break;
									case "9": #DATE 
										aeplugin_ops_UpdatePostFieldRough($targetPost->ID,"post_date",$AE_RuleRow->AE_Str);
										break;
									case "10": #STATUS 
										if ($AE_RuleRow->AE_Str == "Draft"){
											aeplugin_ops_UpdatePostFieldRough($targetPost->ID,"post_status",'draft');
										}
										if ($AE_RuleRow->AE_Str == "Published"){
											aeplugin_ops_UpdatePostFieldRough($targetPost->ID,"post_status",'publish');
										}
										if ($AE_RuleRow->AE_Str == "Trash"){
											aeplugin_ops_UpdatePostFieldRough($targetPost->ID,"post_status",'trash');
										}
										break;
						}
			}
	}
	
	#} And finally...log
    aeplugin_ops_log($scheduleID,$AE_RuleRow->AE_RuleID,1,"Rule successfully run on ".$ae_targetCount." target posts",true,$AE_RuleRow->AE_HitCount);
}




#} Fires actual export if rights check out
function aeplugin_ops_doExportIfShould(){

	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req

    #} export for options
	if (current_user_can('manage_options'))  {
		if ($_GET['export'] == "1" && $_GET['page'] == $aeplugin_slugs['importexport']){ aeplugin_ops_ExportRules(); exit(); }
	}
    
    #} export .csv!
}

#} Creates export .csv of current rules, providing as dl, must be fired above other header info
function aeplugin_ops_ExportRules(){
    
    #} Set headers
    header("Content-type: application/csv");
    header("Content-Disposition: attachment; filename=AE_Rules_Export.csv");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    #} Get rules lines 
    $ae_rules = aeplugin_db_GetRules();
    
    if ($ae_rules){
    
            foreach ($ae_rules as $ae_rule){
                
                $aeExportLine = '';
                $aeExportLine .= aeplugin_str_CSVCleanBiDirection(esc_html($ae_rule->AE_RuleName)) . ',';
                $aeExportLine .= $ae_rule->AE_RuleType . ',';
                $aeExportLine .= aeplugin_str_CSVCleanBiDirection(esc_html($ae_rule->AE_Str)) . ',';
                $aeExportLine .= aeplugin_str_CSVCleanBiDirection(esc_html($ae_rule->AE_RepStr)) . ',';
                $aeExportLine .= $ae_rule->AE_RepInstance . "\n\r";
                echo $aeExportLine . '
                ';
        
            }
    
    }
    
    
}











##--##} Below this line is helper functions ---------------


#} Returns wordpress plugins_url('') response for a file in plugin dir.
function aeplugin_file_url($f){
    global $aeplugin_slugs;
    return plugins_url('/'.$aeplugin_slugs['plugindir'].'/'.$f,dirname(__FILE__));
    
}


#} Cleans CSV sensitive charecters from strings for import/export
function aeplugin_str_CSVCleanBiDirection($str,$inOrOut='out'){
    
    if ($inOrOut == 'out'){
		$str2 = str_replace("\r","*$$*$$*$$*",$str);
		$str2 = str_replace("\n","*$$$*$$*$$$*",$str2);
        return str_replace(',','**$*$*$**',$str2);
    } else { 
		$str2 = str_replace("*$$*$$*$$*","\r",$str);
		$str2 = str_replace("*$$$*$$*$$$*","\n",$str2);
        return str_replace('**$*$*$**',',',$str);
    }
    
}


#} Gives pretty output time until next wordpress hourly/daily firing (e.g. 'In 2 hours 12 minutes')
function aeplugin_datetime_UntilNextWPFire($hourOrDay=1){
    
    #} 1 = hourly, 24 = daily
    if ($hourOrDay == 24) {
        $aeFireFuncName = 'ae_runDaily';
    } else { 
        $aeFireFuncName = 'ae_runHourly';
    }
    
    $diff = abs(wp_next_scheduled($aeFireFuncName) - strtotime(current_time('mysql')));
    $years = floor($diff / (365*60*60*24));
    $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
    $days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
    $hours = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24)/ (60*60));
    $mins = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24 - $days*60*60*24 - $hours*60*60)/ (60));
    
    $outStr = "In ";
    if ($years != 0){ $outStr .= $years." years"; }
    if ($months != 0){ if ($outStr != "In "){ $outStr .= ", "; } $outStr .= $months." months"; }
    if ($days != 0){ if ($outStr != "In "){ $outStr .= ", "; } $outStr .= $days." days"; }
    if ($hours != 0){ if ($outStr != "In "){ $outStr .= ", "; } $outStr .= $hours." hours"; }
    if ($mins != 0){ if ($outStr != "In "){ $outStr .= ", "; } $outStr .= $mins." mintues"; }
    unset($diff,$years,$months,$days,$hours,$mins);
    if ($outStr != "In ") { return $outStr;} else { return "Running";}
	
}


#} Replaces instance no $instances of $needle in $haystack (-1 = all)
function aeplugin_str_ReplaceInstances($needle,$haystack,$replacewith,$instanceno){
    
	#} unescape html
	$needleU = stripslashes($needle);
	$replacewithU = stripslashes($replacewith);
	
    #} Just replace instances of a string in a haystack
    $returnHay = $haystack;
    
    #} Only use this for instance specifics, for all just use str_replace
    if ((int)$instanceno == -1){
        
        $returnHay = str_replace($needleU,$replacewithU,$haystack);
        
    } else { 
        
    #} Get positions array
    $posArr = aeplugin_str_GetPositionArr($needleU,$haystack);
    
        #} If valid, replace
        if (count($posArr) >= $instanceno){
            
            #} Chops pre needle point, adds middle and then chops post needle, merging all
            $returnHay = substr($haystack,0,$posArr[($instanceno-1)]) . $replacewithU . substr($haystack,($posArr[($instanceno-1)]+strlen($needleU)));
            
        }
    
    
    }
    
    
    return $returnHay;
    
}


#} Gets an array of starting positions for needle in haystack
function aeplugin_str_GetPositionArr($needle,$haystack){

    $maxCycles = 50; #} upper limit
    $cursor = 0;
    $out = array();
    
    while ($maxCycles > 0 && strpos($haystack,$needle,$cursor) !== false){
        
        $matchpos = strpos($haystack,$needle,$cursor);    #} Find pos
        
        array_push($out,$matchpos); #} Save pos
        
        $cursor = $matchpos + strlen($needle); $maxCycles--; #} Switch it for next
    }
    
    return $out;
    
}


#} updates a post roughly, outside wordpress, cant use wp_update_post (it fires publish_post)
function aeplugin_ops_UpdatePostFieldRough($postID,$fieldName,$val){
    
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
    
    $wpdb->update( $wpdb->prefix."posts", array( $fieldName => $val), array('ID' => $postID));
	
}


#} Adds a suffix to numbers
function aeplugin_str_AddOrdinalNumberSuffix($num) {
    if (!in_array(($num % 100),array(11,12,13))){
      switch ($num % 10) {
        // Handle 1st, 2nd, 3rd
        case 1:  return $num.'st';
        case 2:  return $num.'nd';
        case 3:  return $num.'rd';
      }
    }
    return $num.'th';
  }
  
  
#} Purely filter display func to dump out shortened/html stripped code into tables (mostly)
function aeplugin_str_ShortenHTML($t,$striptags=true){
   
	$re = $t;
    if ($striptags){ $re = strip_tags($re); }
	if (strlen($t) > 100){ $re = substr($t,0,100) . "...";  } //could use css
	$re = addslashes($re);
	return $re;
}


#} dumb arse function, no one knows
function aeplugin_str_FormsIfVal($v,$x=0){
	if (!empty($v) && $x==1){ echo ' value="'.stripslashes($v).'"'; }
	if (!empty($v) && $x!=1){ echo stripslashes($v); }
}


#} dumb short function to return pretty string for a ruletype (int) 1-11
function aeplugin_str_PrettyRuleType($ruleType){
	
	$AE_Str = "Unknown";
	switch ($ruleType){
					case "1": #STRING REPLACE
						$AE_Str = "String replace";
						break;
					case "2": #STRING REMOVE
						$AE_Str = "String remove";
						break;
					case "3": #REGEX REPLACE
						$AE_Str = "Regex replace";
						break;
					case "4": #REGEX REMOVE
						$AE_Str = "Regex remove";
						break;
					case "5": #PREPEND
						$AE_Str = "Prepend";
						break;
					case "6": #APPEND
						$AE_Str = "Append";
						break;
					case "7": #CATEGORY
						$AE_Str = "Add category";
						break;
					case "8": #TAG
						$AE_Str = "Add tag";
						break;
					case "9": #DATE
						$AE_Str = "Date change";
						break;
					case "10": #STATUS
						$AE_Str = "Status change";
						break;
					case "11": #TRASH
						$AE_Str = "Trash";
						break;
		}	
		
		return $AE_Str;
	
}


#} AEDB - Get a tags name based on id
function aeplugin_db_GetTagName( $tag_id ) {

	$tag_id = (int) $tag_id;
	$tag = get_term( $tag_id, 'post_tag' );

	if ( ! $tag || is_wp_error( $tag ) )

		return '';

	return $tag->name;

}


#} AEDB - Gets results array of tags of blog
function aeplugin_db_GetTags(){
	
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
	$sql = "SELECT ".$wpdb->prefix."terms.term_id, ".$wpdb->prefix."terms.name FROM `".$wpdb->prefix."terms` INNER JOIN ".$wpdb->prefix."term_taxonomy tax "
		 . "ON tax.term_id = ".$wpdb->prefix."terms.term_id WHERE taxonomy = 'post_tag' GROUP BY ".$wpdb->prefix."terms.`term_id` ORDER BY name";
	$ae_tags = $wpdb->get_results($sql);
	
	return $ae_tags;
}


#} AEDB - Gets results array of categories of blog
function aeplugin_db_GetCats(){
	
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
	$sql = "SELECT ".$wpdb->prefix."terms.term_id, ".$wpdb->prefix."terms.name FROM `".$wpdb->prefix."terms` INNER JOIN ".$wpdb->prefix."term_taxonomy tax "
		 . "ON tax.term_id = ".$wpdb->prefix."terms.term_id WHERE taxonomy = 'category' GROUP BY ".$wpdb->prefix."terms.`term_id` ORDER BY name";
	$ae_tags = $wpdb->get_results($sql);
	
	return $ae_tags;
}


#} Makes a date $x pretty
function aeplugin_datetime_PrettyDate($x){
	return Date_Difference::getStringResolved($x);	
}


#} AEDB - Gets results array of all schedules
function aeplugin_db_GetSchedules(){
	
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
	#} get schedules
	$sql = "SELECT * FROM ".$aeplugin_t['schedules'];
	$ae_schedules = $wpdb->get_results($sql);
	
	return $ae_schedules;
}


#} AEDB - Gets results array of a schedule by ID
function aeplugin_db_GetSchedule($id){
	
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
	$ae_schedule = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$aeplugin_t['schedules']." WHERE AE_ScheduleID = %s",$id));
	
	return $ae_schedule;
}


#} AEDB - Gets results array of all rules
function aeplugin_db_GetRules(){
	
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
	$sql = "SELECT * FROM ".$aeplugin_t['rules'];
	$ae_rules = $wpdb->get_results($sql);
	
	return $ae_rules;
}


#} AEDB - Gets name of a rule
function aeplugin_db_GetRuleName($ruleID){
	
   global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
	$ruleName = $wpdb->get_var($wpdb->prepare("SELECT AE_RuleName FROM ".$aeplugin_t['rules']." WHERE AE_RuleID = %s", $ruleID));
	
	return $ruleName;
}


#} AEDB - Gets pretty var of a rules type
function aeplugin_db_GetRuleType($ruleID){
	
    global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
	#} get rule type
	$ruleName = $wpdb->get_var($wpdb->prepare("SELECT AE_RuleType FROM ".$aeplugin_t['rules']." WHERE AE_RuleID = %s", $ruleID));
	
	return aeplugin_str_PrettyRuleType($ruleName);
}


#} AEDB - Gets array of IDs of rules for a schedule
function aeplugin_db_GetRuleIDsForSchedule($scheduleid){
	
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
	$sql = "SELECT AE_RuleID FROM ".$aeplugin_t['relationships']." WHERE AE_ScheduleID = ".$scheduleid;
	$ae_ruleids = $wpdb->get_results($sql);
	
		$ruleids = array();
		if ($ae_ruleids){
			foreach ($ae_ruleids as $ruleIDs){
				array_push($ruleids,$ruleIDs->AE_RuleID);
			}
		}
		
	
	
	return $ruleids;
}


#} AEDB - Gets results array of rules for a schedule
function aeplugin_db_GetRulesForSchedule($scheduleid){
	
	global $wpdb, $aeplugin_db_version, $aeplugin_t, $aeplugin_urls, $aeplugin_slugs;	#} Req
	
	#} get rules
	$sql = "SELECT * FROM ".$aeplugin_t['rules']." WHERE AE_RuleID in (select AE_RuleID from ";
	$sql .= $aeplugin_t['relationships']." WHERE AE_ScheduleID = ".$scheduleid.")"; //needs sanitising/order
	$ae_rules = $wpdb->get_results($sql);
	
	
	return $ae_rules;
}

#} A function returning true or false to see if an id is in a list
function aeplugin_compare_IsRuleIDInList($ruleID,$ruleIDList){
	
	$p = false;
	if (count($ruleIDList) > 0){ 
		foreach ($ruleIDList as $ruleIDs){
		
			if ($ruleID == $ruleIDs){
				$p = true;	
			}
			
		}
	}
	
	return $p;
	
}

#} Gets next official run date based on last run ( pretty date return for when the next time a schedule will run)
function aeplugin_datetime_GetNextRunDate($pointOfAction,$lastRun){
	
	if ($pointOfAction == "2"){ 
		$minsSince = aeplugin_datetime_MinutesSince($lastRun);
		$minsSince = 60 - $minsSince;
		return 'In '.$minsSince.' minutes';#
	} else if ($pointOfAction == "3") { 
		$minsSince = aeplugin_datetime_MinutesSince($lastRun);
		$minsSince = 1440 + $minsSince;
		$wholeHourCount = $minsSince % 60;
		$minsSince = $minsSince - ($wholeHourCount*60);
		return 'In '.$wholeHourCount.'H and '.$minsSince.' minutes';#
	} else {
		return '';	
	}
	
}


#} Minutes into hours and days
function aeplugin_datetime_MinutesIntoHoursDays($intMins){

	$minCount = $intMins;
	$hourminCount = $intMins % 60;
		
	$hourCount = floor($minCount / 60);
	
	if ($hourCount < 0){ 
		#in past
		$hourCount = $hourCount * -1;
		$hourminCount = $hourminCount * -1;
        $hourCount--; //some floor maths minusage
		if ($hourCount > 0 && $hourminCount > 0){
			return $hourCount.' hours and '.$hourminCount.' minutes ago';
		} else if ($hourCount > 0 && $hourminCount == 0) { 
			return $hourCount.' hours ago';
		} else if ($hourCount == 0 && $hourminCount > 0) { 
			return $hourminCount.' minutes ago';
		} else {
			return 'Unknown';	
		}
		
	} else {
		#in future
		if ($hourCount > 0 && $hourminCount > 0){
			return 'In '.$hourCount.' hours and '.$hourminCount.' minutes';
		} else if ($hourCount > 0 && $hourminCount == 0) { 
			return 'In '.$hourCount.' hours';
		} else if ($hourCount == 0 && $hourminCount > 0) { 
			return 'In '.$hourminCount.' minutes';
		} else {
			return 'Unknown';	
		}
	}

}


#} Makes unix timestamp a pretty date
function aeplugin_datetime_UnixToPrettyDate($uts){
    
    return date('l jS \of F Y h:i:s A',$uts);
    
}
function aecr($a){ return array_slice($a,3); }

#} Minutes since timestamp
function aeplugin_datetime_MinutesSince($timeStamp){
	
	
	$x = new DateTime($timeStamp);
	$y = new DateTime('now'); 
	$diff = $x->format('U') - $y->format('U'); 
    $minDiff = floor($diff / 60); 
	return $minDiff;

}


#} Adds hours $t to returned value
function aeplugin_datetime_AddHours($t,$hours){
	
	$x = new DateTime($t); 
	$seconds = ($hours * 3600); 
	$y = $x->format('U');
	$y += $seconds;
	$z = new DateTime(date("r", $y));
	
	return $z;
	
}


#} Privdes a pretty "time since x" 
function aeplugin_datetime_PrettyTimeSince($t,$s=0){ 

	if ($s == 0){ #full "in x hours x mins"
		return aeplugin_datetime_MinutesIntoHoursDays(aeplugin_datetime_MinutesSince($t));	
	} else if ($s == 1) { #short
		return str_replace("hours","H",str_replace("minutes","M",aeplugin_datetime_MinutesIntoHoursDays(aeplugin_datetime_MinutesSince($t))));
	}

}

?>