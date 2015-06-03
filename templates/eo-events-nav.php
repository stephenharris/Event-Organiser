<?php
global $wp_query;
if ( $wp_query->max_num_pages > 1 ) { ?>
<nav>
	<div class="nav-next eo-events-nav-later"><?php next_posts_link( __( 'Later events <span class="meta-nav">&rarr;</span>' , 'eventorganiser' ) ); ?></div>
	<div class="nav-previous eo-events-newer"><?php previous_posts_link( __( ' <span class="meta-nav">&larr;</span> Newer events', 'eventorganiser' ) ); ?></div>
</nav><!-- #nav-above -->
<?php }; ?>