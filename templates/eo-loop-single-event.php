<?php
/**
 * A single event in a events loop. Used by eo-loop-single-event.php
 *
 ***************** NOTICE: *****************
 *  Do not make changes to this file. Any changes made to this file
 * will be overwritten if the plug-in is updated.
 *
 * To overwrite this template with your own, make a copy of it (with the same name)
 * in your theme directory.
 *
 * WordPress will automatically prioritise the template in your theme directory.
 ***************** NOTICE: *****************
 *
 * @package Event Organiser (plug-in)
 * @since 3.0.0
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> itemscope itemtype="http://data-vocabulary.org/Event">
					
	<header class="eo-event-header entry-header">

		<h2 class="eo-event-title entry-title">
			<a href="<?php echo eo_get_permalink(); ?>" itemprop="url">
				<span itemprop="summary"><?php the_title() ?></span>
			</a>
		</h2>

		<div class="eo-event-date"> 
			<?php
				//Formats the start & end date of the event
				echo eo_format_event_occurrence();
			?>
		</div>
			
	</header><!-- .entry-header -->
	
	<div class="eo-event-details event-entry-meta">
			
		<?php
		//If it has one, display the thumbnail
		if ( has_post_thumbnail() ) {
			the_post_thumbnail( 'thumbnail', array( 'class' => 'attachment-thumbnail eo-event-thumbnail' ) );
		}

		//A list of event details: venue, categories, tags.
		echo eo_get_event_meta_list();
		?>
			
	</div><!-- .event-entry-meta -->

	<!-- Show Event text as 'the_excerpt' or 'the_content' -->
	<div class="eo-event-content" itemprop="description"><?php the_excerpt(); ?></div>
			
	<div style="clear:both;"></div>

</article>
