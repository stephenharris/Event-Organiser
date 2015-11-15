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

		<h1 class="eo-event-title entry-title" style="display: inline;" >
			<a href="<?php the_permalink(); ?>" itemprop="url">
				<?php
				//If it has one, display the thumbnail
				if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'thumbnail', array( 'style' => 'float:left;margin-right:20px;' ) );
				}

				//Display the title
				?>
				<span itemprop="summary"><?php the_title() ?></span>
			</a>
		</h1>

		<div class="eo-event-details event-entry-meta">

			<div class="eo-event-date"> 
			<?php
				//Formats the start & end date of the event
				echo eo_format_event_occurrence();
				?>
			</div>
		
			<?php
			//A list of event details: venue, categories, tags.
			echo eo_get_event_meta_list();
			?>
			
		</div><!-- .event-entry-meta -->

	</header><!-- .entry-header -->
	
	<!-- Show Event text as 'the_excerpt' or 'the_content' -->
	<div class="eo-event-cntent entry-content" itemprop="description"><?php the_excerpt(); ?></div>
			
	<div style="clear:both;"></div>

</article>
