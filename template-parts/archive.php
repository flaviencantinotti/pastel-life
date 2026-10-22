<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_products = is_tax( 'gamme' );

if ( is_home() ) {
	$title = single_post_title( '', false );
	$intro = get_the_excerpt( get_option( 'page_for_posts' ) );
} elseif ( is_tax() || is_category() || is_tag() ) {
	$title = single_term_title( '', false );
	$intro = wp_strip_all_tags( term_description() );
} else {
	$title = wp_strip_all_tags( get_the_archive_title() );
	$intro = wp_strip_all_tags( get_the_archive_description() );
}
?>
<main id="content" class="pl-principal pl-archive">

	<header class="pl-entete-page pl-formes pl-a-gribouillis-<?php echo $is_products ? 'menthe' : 'peche'; ?> pl-a-g pl-a-hd pl-b-rond-<?php echo $is_products ? 'saumon' : 'lavande'; ?> pl-b-tp pl-b-bg">
		<div class="pl-conteneur">
			<?php echo pl_fil_ariane_html(); ?>
			<h1 class="pl-entete-page__titre"><?php echo esc_html( $title ); ?></h1>
			<?php if ( $intro ) : ?>
				<p class="pl-entete-page__chapo"><?php echo esc_html( $intro ); ?></p>
			<?php endif; ?>
		</div>
	</header>

	<div class="pl-conteneur pl-section">
		<?php if ( ! $is_products ) : ?>
			<?php $categories = get_categories( array( 'hide_empty' => true ) ); ?>
			<?php if ( count( $categories ) > 0 ) : ?>
				<nav class="pl-pastilles" aria-label="Catégories du journal">
					<a class="pl-pastille <?php echo is_home() ? 'est-actif' : ''; ?>" href="<?php echo esc_url( pl_url_page( 'journal' ) ); ?>">Tous les articles</a>
					<?php foreach ( $categories as $category ) : ?>
						<a class="pl-pastille <?php echo is_category( $category->term_id ) ? 'est-actif' : ''; ?>" href="<?php echo esc_url( get_category_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<div class="pl-grille <?php echo $is_products ? 'pl-grille--produits' : 'pl-grille--articles'; ?>">
				<?php
				while ( have_posts() ) {
					the_post();
					echo 'produit' === get_post_type() ? pl_carte_produit_html( get_post() ) : pl_carte_article_html( get_post() );
				}
				?>
			</div>
		<?php else : ?>
			<p>Rien à afficher pour le moment. <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Retour à l'accueil</a>.</p>
		<?php endif; ?>

		<?php
		$links = paginate_links(
			array(
				'type'      => 'list',
				'prev_text' => '← Précédent',
				'next_text' => 'Suivant →',
			)
		);
		if ( $links ) {
			echo '<nav class="pl-pagination" aria-label="Pagination">' . $links . '</nav>';
		}
		?>
	</div>

</main>
