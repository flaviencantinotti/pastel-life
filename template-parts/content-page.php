<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<main id="content" <?php post_class( 'pl-principal pl-legal' ); ?>>

	<header class="pl-entete-page pl-entete-page--petit pl-formes pl-a-gribouillis-peche pl-a-m pl-a-hd">
		<div class="pl-conteneur">
			<?php echo pl_fil_ariane_html(); ?>
			<h1 class="pl-entete-page__titre"><?php the_title(); ?></h1>
		</div>
	</header>

	<div class="pl-conteneur pl-conteneur--etroit">
		<div class="pl-prose entry-content">
			<?php the_content(); ?>
			<?php wp_link_pages(); ?>
		</div>
	</div>

</main>
