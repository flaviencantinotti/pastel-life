<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

while ( have_posts() ) :
	the_post();
	if ( is_singular( 'produit' ) ) {
		get_template_part( 'template-parts/content', 'produit' );
	} elseif ( is_singular( 'post' ) ) {
		get_template_part( 'template-parts/content', 'post' );
	} else {
		get_template_part( 'template-parts/content', 'page' );
	}
endwhile;
