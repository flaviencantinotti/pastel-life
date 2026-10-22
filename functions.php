<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PASTEL_LIFE_VERSION', '2.8.0' );

require_once get_stylesheet_directory() . '/inc/helpers.php';
require_once get_stylesheet_directory() . '/inc/cpt.php';
require_once get_stylesheet_directory() . '/inc/seo.php';
require_once get_stylesheet_directory() . '/inc/shortcodes.php';
require_once get_stylesheet_directory() . '/inc/kit.php';
require_once get_stylesheet_directory() . '/inc/installer.php';

add_action(
	'wp_enqueue_scripts',
	function () {
		$v   = PASTEL_LIFE_VERSION;
		$uri = get_stylesheet_directory_uri();
		wp_enqueue_style( 'pastel-life', get_stylesheet_uri(), array(), $v );
		wp_enqueue_style( 'pastel-life-shapes', $uri . '/assets/css/shapes.css', array( 'pastel-life' ), $v );
		wp_enqueue_style( 'pastel-life-components', $uri . '/assets/css/components.css', array( 'pastel-life' ), $v );
		wp_enqueue_style( 'pastel-life-motion', $uri . '/assets/css/motion.css', array( 'pastel-life' ), $v );
		wp_enqueue_script( 'pastel-life', $uri . '/assets/js/theme.js', array(), $v, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	},
	20
);

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'post-thumbnails' );
		add_image_size( 'pl-carte', 640, 640, false );
	}
);

add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );

add_filter(
	'excerpt_length',
	function () {
		return 28;
	},
	999
);
add_filter(
	'excerpt_more',
	function () {
		return '…';
	}
);

add_filter(
	'get_search_form',
	function () {
		$q = get_search_query();
		return '<form role="search" method="get" class="pl-formulaire-recherche" action="' . esc_url( home_url( '/' ) ) . '">'
			. '<label class="screen-reader-text" for="pl-champ-recherche">Rechercher</label>'
			. '<input type="search" id="pl-champ-recherche" name="s" value="' . esc_attr( $q ) . '" placeholder="Rechercher un coffret, un conseil…">'
			. '<button type="submit" class="pl-bouton">Rechercher</button></form>';
	}
);

add_filter(
	'wp_date',
	function ( $date ) {
		static $map = array(
			'January'   => 'janvier',
			'February'  => 'février',
			'March'     => 'mars',
			'April'     => 'avril',
			'May'       => 'mai',
			'June'      => 'juin',
			'July'      => 'juillet',
			'August'    => 'août',
			'September' => 'septembre',
			'October'   => 'octobre',
			'November'  => 'novembre',
			'December'  => 'décembre',
		);
		return is_string( $date ) ? strtr( $date, $map ) : $date;
	}
);

add_filter(
	'gettext',
	function ( $translation, $text, $domain ) {
		if ( 'hello-elementor' === $domain && 'Skip to content' === $text ) {
			return 'Aller au contenu';
		}
		return $translation;
	},
	10,
	3
);
