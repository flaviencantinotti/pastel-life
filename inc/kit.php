<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pastel_life_reglages_kit() {
	$custom = array(
		'pl-rose'       => array( 'Rose Framboise', '#F16D82' ),
		'pl-salmon'     => array( 'Saumon Doux', '#F5A79E' ),
		'pl-peach'      => array( 'Pêche Sorbet', '#FBCE9E' ),
		'pl-pistachio'  => array( 'Pistache', '#C6DFA3' ),
		'pl-mint'       => array( 'Menthe Glacée', '#82DEC0' ),
		'pl-lavender'   => array( 'Lavande Brume', '#A7ACDE' ),
		'pl-cream'      => array( 'Crème', '#FFF9F3' ),
		'pl-white'      => array( 'Blanc', '#FFFFFF' ),
		'pl-rose-tint'  => array( 'Rose pâle', '#FDE8EC' ),
		'pl-peach-tint' => array( 'Pêche pâle', '#FEF1E2' ),
		'pl-pist-tint'  => array( 'Pistache pâle', '#F0F7E6' ),
		'pl-mint-tint'  => array( 'Menthe pâle', '#E1F7EF' ),
		'pl-lav-tint'   => array( 'Lavande pâle', '#ECEDF8' ),
	);

	$custom_colors = array();
	foreach ( $custom as $id => $def ) {
		$custom_colors[] = array(
			'_id'   => $id,
			'title' => $def[0],
			'color' => $def[1],
		);
	}

	$typo = function ( $id, $title, $family, $weight ) {
		return array(
			'_id'                    => $id,
			'title'                  => $title,
			'typography_typography'  => 'custom',
			'typography_font_family' => $family,
			'typography_font_weight' => $weight,
		);
	};

	return array(
		'system_colors'         => array(
			array( '_id' => 'primary', 'title' => 'Encre', 'color' => '#2B2A3A' ),
			array( '_id' => 'secondary', 'title' => 'Rose Framboise', 'color' => '#F16D82' ),
			array( '_id' => 'text', 'title' => 'Texte', 'color' => '#4A4960' ),
			array( '_id' => 'accent', 'title' => 'Lavande Brume', 'color' => '#A7ACDE' ),
		),
		'custom_colors'         => $custom_colors,
		'system_typography'     => array(
			$typo( 'primary', 'Titres', 'PL Fredoka', '600' ),
			$typo( 'secondary', 'Sous-titres', 'PL Fredoka', '500' ),
			$typo( 'text', 'Texte', 'PL Nunito', '400' ),
			$typo( 'accent', 'Accent', 'PL Nunito', '800' ),
		),
		'background_background' => 'classic',
		'background_color'      => '#FFF9F3',
	);
}

function pastel_life_appliquer_kit() {
	if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Plugin' ) ) {
		return false;
	}

	$manager = \Elementor\Plugin::$instance->kits_manager;

	if ( ! $manager->get_active_id() && method_exists( $manager, 'create_default' ) ) {
		$manager->create_default();
	}

	$kit = $manager->get_active_kit();
	if ( ! $kit || ! $kit->get_id() ) {
		return false;
	}

	try {
		$kit->update_settings( pastel_life_reglages_kit() );
	} catch ( \Throwable $e ) {
		return false;
	}
	return true;
}

add_action(
	'admin_init',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! get_option( 'pastel_life_kit_applied' ) && pastel_life_appliquer_kit() ) {
			update_option( 'pastel_life_kit_applied', PASTEL_LIFE_VERSION );
		}
	},
	30
);
