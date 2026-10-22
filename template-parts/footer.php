<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_name = get_bloginfo( 'name' );
$home      = home_url( '/' );

$gammes = array();
foreach ( array( 'coffrets' => 'Coffrets de crayons pastel', 'accessoires' => 'Accessoires' ) as $slug => $label ) {
	$term = get_term_by( 'slug', $slug, 'gamme' );
	if ( $term && ! is_wp_error( $term ) ) {
		$gammes[ get_term_link( $term ) ] = $label;
	}
}

$cols = array(
	'Boutique' => array_merge(
		array( pl_url_page( 'boutique' ) => 'Toute la boutique' ),
		$gammes
	),
	'La marque' => array(
		pl_url_page( 'la-collection' ) => 'La collection de teintes',
		pl_url_page( 'savoir-faire' )  => 'Savoir-faire',
		pl_url_page( 'a-propos' )      => 'Notre histoire',
		pl_url_page( 'journal' )       => 'Journal',
	),
	'Aide' => array(
		pl_url_page( 'contact' )                      => 'Contact',
		pl_url_page( 'faq' )                          => 'Questions fréquentes',
		pl_url_page( 'cgv' )                          => 'Conditions de vente',
		pl_url_page( 'mentions-legales' )             => 'Mentions légales',
		pl_url_page( 'politique-de-confidentialite' ) => 'Confidentialité',
	),
);
?>

<footer id="site-footer" class="pl-pied pl-formes pl-a-gribouillis-rose pl-a-m pl-a-hd pl-b-rond-lavande pl-b-p pl-b-bg">
	<div class="pl-pied__interieur">
		<div class="pl-pied__grille">

			<div>
				<a class="pl-marque" href="<?php echo esc_url( $home ); ?>" rel="home">
					<svg viewBox="0 0 36 24" aria-hidden="true" focusable="false">
						<circle cx="6" cy="6" r="5.2" fill="#F16D82"/><circle cx="18" cy="6" r="5.2" fill="#F5A79E"/><circle cx="30" cy="6" r="5.2" fill="#FBCE9E"/>
						<circle cx="6" cy="18" r="5.2" fill="#C6DFA3"/><circle cx="18" cy="18" r="5.2" fill="#82DEC0"/><circle cx="30" cy="18" r="5.2" fill="#A7ACDE"/>
					</svg>
					<?php echo esc_html( $site_name ); ?>
				</a>
				<p class="pl-pied__presentation">Des crayons pastel doux et pigmentés, pensés pour colorer chaque jour un peu plus joyeusement.</p>
				<p class="pl-pied__courriel"><a href="mailto:<?php echo esc_attr( pl_courriel_contact() ); ?>"><?php echo esc_html( pl_courriel_contact() ); ?></a></p>
			</div>

			<?php foreach ( $cols as $heading => $links ) : ?>
				<nav aria-label="<?php echo esc_attr( $heading ); ?>">
					<h2 class="pl-pied__titre"><?php echo esc_html( $heading ); ?></h2>
					<ul>
						<?php foreach ( $links as $url => $label ) : ?>
							<li><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endforeach; ?>

		</div>

		<div class="pl-pied__bas">
			<span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $site_name ); ?> · Livraison offerte dès 35 €</span>
			<span>Site fictif créé à des fins de démonstration · Créé par <strong>Flavien C.</strong></span>
		</div>
	</div>
</footer>
