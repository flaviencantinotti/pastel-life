<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cats = get_the_category();
?>
<div class="pl-progression" aria-hidden="true"><span></span></div>

<main id="content" <?php post_class( 'pl-principal pl-article' ); ?>>

	<header class="pl-entete-page pl-entete-page--article pl-formes pl-a-gribouillis-peche pl-a-g pl-a-hd pl-b-rond-menthe pl-b-tp pl-b-bg">
		<div class="pl-conteneur">
			<?php echo pl_fil_ariane_html(); ?>
			<?php if ( $cats ) : ?>
				<p class="pl-entete-page__surtitre"><a class="pl-pastille" href="<?php echo esc_url( get_category_link( $cats[0] ) ); ?>"><?php echo esc_html( $cats[0]->name ); ?></a></p>
			<?php endif; ?>
			<h1 class="pl-entete-page__titre"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="pl-entete-page__chapo"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
			<p class="pl-article__infos">
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">Publié le <?php echo esc_html( get_the_date( 'j F Y' ) ); ?></time>
				<?php if ( get_the_modified_date( 'Y-m-d' ) !== get_the_date( 'Y-m-d' ) ) : ?>
					<span>· Mis à jour le <time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php echo esc_html( get_the_modified_date( 'j F Y' ) ); ?></time></span>
				<?php endif; ?>
				<span>· <?php echo esc_html( pl_temps_lecture() ); ?> min de lecture</span>
			</p>
		</div>
	</header>

	<div class="pl-conteneur pl-conteneur--etroit">
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="pl-article__figure">
				<?php the_post_thumbnail( 'large', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ) ); ?>
			</figure>
		<?php endif; ?>

		<div class="pl-prose entry-content">
			<?php the_content(); ?>
		</div>

		<aside class="pl-article__appel pl-formes pl-a-rond-peche pl-a-p pl-a-hd pl-b-gribouillis-menthe pl-b-p pl-b-bg" aria-labelledby="pl-appel-titre">
			<h2 id="pl-appel-titre">Passez à la pratique</h2>
			<p>Nos coffrets de crayons pastel sont pensés pour commencer en douceur. Découvrez la sélection de l'atelier.</p>
			<p><a class="pl-bouton" href="<?php echo esc_url( pl_url_page( 'boutique' ) ); ?>">Voir les coffrets de crayons pastel</a></p>
		</aside>
	</div>

	<section class="pl-section pl-section--douce" aria-labelledby="pl-suite-titre">
		<div class="pl-conteneur">
			<h2 id="pl-suite-titre" class="pl-section__titre">À lire ensuite</h2>
			<?php echo do_shortcode( '[pl_posts limit="3" exclude="' . (int) get_the_ID() . '"]' ); ?>
		</div>
	</section>

</main>
