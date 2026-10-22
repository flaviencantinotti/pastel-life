<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<main id="content" class="pl-principal pl-archive">

	<header class="pl-entete-page pl-entete-page--petit pl-formes pl-a-rond-peche pl-a-m pl-a-hd">
		<div class="pl-conteneur">
			<?php echo pl_fil_ariane_html(); ?>
			<h1 class="pl-entete-page__titre">Recherche : <?php echo esc_html( get_search_query() ); ?></h1>
			<div class="pl-recherche"><?php get_search_form(); ?></div>
		</div>
	</header>

	<div class="pl-conteneur pl-section">
		<?php if ( have_posts() ) : ?>
			<div class="pl-grille pl-grille--articles">
				<?php
				while ( have_posts() ) {
					the_post();
					if ( 'produit' === get_post_type() ) {
						echo pl_carte_produit_html( get_post() );
					} elseif ( 'post' === get_post_type() ) {
						echo pl_carte_article_html( get_post() );
					} else {
						printf( '<article class="pl-carte-article pl-apparition"><div class="pl-carte-article__corps"><h3 class="pl-carte-article__titre"><a href="%s">%s</a></h3><p class="pl-carte-article__texte">%s</p></div></article>', esc_url( get_permalink() ), esc_html( get_the_title() ), esc_html( wp_trim_words( get_the_excerpt(), 24 ) ) );
					}
				}
				?>
			</div>
		<?php else : ?>
			<p>Aucun résultat pour cette recherche. Essayez « coffret », « pastel sec » ou « dégradé », ou parcourez la <a href="<?php echo esc_url( pl_url_page( 'boutique' ) ); ?>">boutique</a> et le <a href="<?php echo esc_url( pl_url_page( 'journal' ) ); ?>">journal</a>.</p>
		<?php endif; ?>
	</div>

</main>
