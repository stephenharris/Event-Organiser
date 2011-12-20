<?php
/**
* Event importer / exporter
 */
if ( ! function_exists( 'add_action' ) ) {
	echo "Hi there!  I'm just a part of plugin, not much I can do when called directly.";
	exit;
}

class Event_Organiser_Im_Export  {
	
	static private $classobj = NULL;

	/**
	 * Handler for the action 'init'. Instantiates this class.
	 */
	public function get_object() {
		
		if ( NULL === self :: $classobj ) {
			self :: $classobj = new self;
		}
		
		return self :: $classobj;
	}
	
	/**
	 * Constructor
	 */
	public function __construct() {
		global $pagenow;
		
		if ( isset( $_GET['addquicktag_download'] )&& check_admin_referer( 'eventorganiser_export' )
			&& current_user_can('manage_options')&& $pagenow=="options-general.php"){
			$this->get_export_file();
		}
						
	}
	

	/*
	 * Build export file, ics
	 * @since   1.0.0
	 */
	public function get_export_file() {
		$filename = urlencode( 'event-organiser_' . date('Y-m-d') . '.ics' );
		$this -> export_events( $filename, 'text/calendar' );
	}

	public function export_events( $filename, $filetype ){ 
	//Collect output 
	ob_start();

	// File header
	header( 'Content-Description: File Transfer' );
	header( 'Content-Disposition: attachment; filename=' . $filename );
	header('Content-type: text/calendar');
	header("Pragma: 0");
	header("Expires: 0");

	// Content header
?>
BEGIN:VCALENDAR
VERSION:2.0
CALSCALE:GREGORIAN
<?php
  
	// Query for events
	$events = eo_get_events(array('numberofposts'=>-1,'showoccurrences'=>false));
 
	// Loop through events
	if ($events):
		global $post;
		foreach ($events as $post):

			//Set up start and end date times
			if($post->event_allday){
				$start_date =eo_get_the_start('Ymd');
				$end_date =eo_get_the_end('Ymd');
				$schedule_end = eo_get_schedule_end('Ymd');
			}else{
				$start_date = eo_get_the_start('Ymd\THis\Z');
				$end_date = eo_get_the_end('Ymd\THis\Z');
				$schedule_end = eo_get_schedule_end('Ymd\THis\Z');
			}
	
			$schedule = eo_get_reoccurence();
	
			switch($schedule['reoccurrence']):
				case 'yearly':
					$reoccurrence_rule = "FREQ=YEARLY;INTERVAL=".$schedule['frequency'].";UNTIL=".$schedule_end;
					break;

				case 'monthly':
					$reoccurrence_rule = "FREQ=MONTHLY;INTERVAL=".$schedule['frequency'].";";
					if($schedule['meta']!='date'){
						$reoccurrence_rule.="BYDAY=".$schedule['meta'].";";
					}else{	
						$reoccurrence_rule.="BYMONTHDAY=". eo_get_start_datetime('d').";";
					}
					$reoccurrence_rule.= "UNTIL=".$schedule_end;
					break;
	
				case 'weekly':
					$reoccurrence_rule = "FREQ=WEEKLY;INTERVAL=".$schedule['frequency'].";BYDAY=".implode(',',$schedule['meta']).";UNTIL=".$schedule_end;
					break;

				case 'daily':
					$reoccurrence_rule = "FREQ=DAILY;INTERVAL=".$schedule['frequency'].";UNTIL=".$schedule_end;
					break;

			endswitch;
			//Output event
?>
BEGIN:VEVENT
DTSTART:<?php echo $start_date ; ?>

DTEND:<?php echo $end_date; ?>
<?php if ($schedule['reoccurrence']!='once'):?>

RRULE:<?php echo $reoccurrence_rule;?>
<?php endif;?>

SUMMARY:<?php echo the_title(); ?>

DESCRIPTION:<?php the_excerpt_rss('', TRUE, '', 50); ?>

END:VEVENT
<?php
		endforeach;
	endif;
?>
END:VCALENDAR
<?php

	//Collect output and echo 
	$eventsical = ob_get_contents();
	ob_end_clean();
	echo $eventsical;
	exit();
	}	
} // end class
