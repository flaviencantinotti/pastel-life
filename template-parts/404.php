<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<main id="content" class="pl-principal pl-404">

	<header class="pl-entete-page pl-formes pl-a-gribouillis-rose pl-a-m pl-a-hd pl-b-rond-menthe pl-b-tp pl-b-bg">
		<div class="pl-conteneur">
			<p class="pl-entete-page__surtitre"><span class="pl-pastille">Erreur 404</span></p>
			<h1 class="pl-entete-page__titre">Cette page s'est envolée</h1>
			<p class="pl-entete-page__chapo">Le crayon a dû glisser de la table. Cherchez ce que vous vouliez, ou repartez d'un des chemins ci-dessous.</p>
			<div class="pl-recherche"><?php get_search_form(); ?></div>
			<p class="pl-404__liens">
				<a class="pl-bouton" href="<?php echo esc_url( home_url( '/' ) ); ?>">Retour à l'accueil</a>
				<a class="pl-bouton pl-bouton--contour" href="<?php echo esc_url( pl_url_page( 'boutique' ) ); ?>">Voir la boutique</a>
				<a class="pl-bouton pl-bouton--contour" href="<?php echo esc_url( pl_url_page( 'journal' ) ); ?>">Lire le journal</a>
			</p>
		</div>
	</header>

</main>
