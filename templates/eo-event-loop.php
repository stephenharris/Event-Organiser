<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> itemscope itemtype="http://data-vocabulary.org/Event">
					
	<header class="entry-header">

		<h1 class="entry-title" style="display: inline;" >
			<a href="<?php the_permalink(); ?>" itemprop="url">
				<?php 
				//If it has one, display the thumbnail
				if( has_post_thumbnail() ){
					the_post_thumbnail( 'thumbnail', array( 'style' => 'float:left;margin-right:20px;' ) );
				}
					
				//Display the title
				?>
				<span itemprop="summary"><?php the_title() ?></span>
			</a>
		</h1>

		<div class="event-entry-meta">

			<!-- Output the date of the occurrence-->
			<?php
			//Formats the start & end date of the event
			echo eo_format_event_occurrence();
			
			//A list of event details: venue, categories, tags.
			echo eo_get_event_meta_list(); 
			?>
			
		</div><!-- .event-entry-meta -->

	</header><!-- .entry-header -->
	
	<!-- Show Event text as 'the_excerpt' or 'the_content' -->
	<div class="entry-content" itemprop="description"><?php the_excerpt(); ?></div>
			
	<div style="clear:both;"></div>

</article><!-- #post-<?php the_ID(); ?> -->