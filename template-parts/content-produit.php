<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$d     = pl_donnees_produit( get_the_ID() );
$order = add_query_arg( 'produit', get_post_field( 'post_name' ), pl_url_page( 'contact' ) ) . '#formulaire-contact';
$terms = get_the_terms( get_the_ID(), 'gamme' );
?>
<main id="content" <?php post_class( 'pl-principal pl-produit' ); ?>>

	<div class="pl-conteneur">
		<?php echo pl_fil_ariane_html(); ?>

		<div class="pl-produit__grille">
			<div class="pl-produit__visuel">
				<?php if ( has_post_thumbnail() ) : ?>
					<?php the_post_thumbnail( 'large', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async', 'alt' => get_the_title() ) ); ?>
				<?php endif; ?>
			</div>

			<div class="pl-produit__infos">
				<?php if ( $d['badge'] ) : ?>
					<span class="pl-badge"><?php echo esc_html( $d['badge'] ); ?></span>
				<?php endif; ?>
				<h1 class="pl-produit__titre"><?php the_title(); ?></h1>
				<p class="pl-produit__prix"><span class="pl-prix"><?php echo esc_html( pl_prix_formate( $d['price'] ) ); ?></span> <small>TTC · livraison offerte dès 35 €</small></p>
				<?php if ( has_excerpt() ) : ?>
					<p class="pl-produit__chapo"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>

				<?php if ( $d['features'] ) : ?>
					<ul class="pl-points">
						<?php foreach ( $d['features'] as $feature ) : ?>
							<li><?php echo esc_html( $feature ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<p class="pl-produit__appel">
					<a class="pl-bouton" href="<?php echo esc_url( $order ); ?>">Commander ce produit</a>
					<a class="pl-bouton pl-bouton--contour" href="<?php echo esc_url( pl_url_page( 'contact' ) ); ?>">Poser une question</a>
				</p>

				<ul class="pl-reassurance">
					<li><strong>Livraison offerte</strong> dès 35 € d'achat</li>
					<li><strong>30 jours</strong> pour changer d'avis</li>
					<li><strong>Conseil gratuit</strong> par e-mail pour bien choisir</li>
				</ul>
			</div>
		</div>

		<section class="pl-produit__description pl-prose entry-content" aria-labelledby="pl-description-titre">
			<h2 id="pl-description-titre">Description : <?php the_title(); ?></h2>
			<?php the_content(); ?>
			<?php if ( $terms && ! is_wp_error( $terms ) ) : ?>
				<p class="pl-attenue">Gamme : <a href="<?php echo esc_url( get_term_link( $terms[0] ) ); ?>"><?php echo esc_html( $terms[0]->name ); ?></a></p>
			<?php endif; ?>
		</section>
	</div>

	<section class="pl-section pl-section--douce" aria-labelledby="pl-associes-titre">
		<div class="pl-conteneur">
			<h2 id="pl-associes-titre" class="pl-section__titre">Vous aimerez aussi</h2>
			<?php echo do_shortcode( '[pl_products limit="3" exclude="' . (int) get_the_ID() . '"]' ); ?>
		</div>
	</section>

</main>
