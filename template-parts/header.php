<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_name = get_bloginfo( 'name' );
$home      = home_url( '/' );

$menu = wp_nav_menu(
	array(
		'theme_location' => 'menu-1',
		'fallback_cb'    => false,
		'container'      => false,
		'menu_class'     => 'pl-menu',
		'echo'           => false,
	)
);

if ( ! $menu ) {
	$links = array(
		'la-collection' => 'La collection',
		'boutique'      => 'Boutique',
		'savoir-faire'  => 'Savoir-faire',
		'journal'       => 'Journal',
		'contact'       => 'Contact',
	);
	$menu  = '<ul class="pl-menu">';
	foreach ( $links as $slug => $label ) {
		$menu .= '<li><a href="' . esc_url( pl_url_page( $slug ) ) . '">' . esc_html( $label ) . '</a></li>';
	}
	$menu .= '</ul>';
}
?>

<header id="site-header" class="pl-entete">
	<div class="pl-entete__interieur">

		<?php if ( has_custom_logo() ) : ?>
			<?php the_custom_logo(); ?>
		<?php else : ?>
			<a class="pl-marque" href="<?php echo esc_url( $home ); ?>" rel="home">
				<svg viewBox="0 0 36 24" aria-hidden="true" focusable="false">
					<circle cx="6" cy="6" r="5.2" fill="#F16D82"/><circle cx="18" cy="6" r="5.2" fill="#F5A79E"/><circle cx="30" cy="6" r="5.2" fill="#FBCE9E"/>
					<circle cx="6" cy="18" r="5.2" fill="#C6DFA3"/><circle cx="18" cy="18" r="5.2" fill="#82DEC0"/><circle cx="30" cy="18" r="5.2" fill="#A7ACDE"/>
				</svg>
				<?php echo esc_html( $site_name ); ?>
			</a>
		<?php endif; ?>

		<button class="pl-menu-bouton" type="button" aria-expanded="false" aria-controls="pl-navigation" aria-label="<?php echo esc_attr__( 'Menu', 'pastel-life' ); ?>"><span></span></button>

		<nav id="pl-navigation" class="pl-navigation" aria-label="<?php echo esc_attr__( 'Menu principal', 'pastel-life' ); ?>">
			<?php echo $menu; ?>
			<a class="pl-bouton" href="<?php echo esc_url( pl_url_page( 'boutique' ) ); ?>"><?php echo esc_html__( 'Boutique', 'pastel-life' ); ?></a>
		</nav>

	</div>
</header>
