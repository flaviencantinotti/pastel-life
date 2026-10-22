<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	function () {
		register_post_type(
			'produit',
			array(
				'labels'       => array(
					'name'          => 'Produits',
					'singular_name' => 'Produit',
					'menu_name'     => 'Boutique',
					'all_items'     => 'Tous les produits',
					'add_new_item'  => 'Ajouter un produit',
					'edit_item'     => 'Modifier le produit',
					'view_item'     => 'Voir le produit',
					'search_items'  => 'Rechercher un produit',
				),
				'public'       => true,
				'show_in_rest' => true,
				'has_archive'  => false,
				'menu_icon'    => 'dashicons-edit',
				'menu_position' => 20,
				'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
				'rewrite'      => array(
					'slug'       => 'boutique',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			'gamme',
			'produit',
			array(
				'labels'       => array(
					'name'          => 'Gammes',
					'singular_name' => 'Gamme',
				),
				'public'       => true,
				'hierarchical' => true,
				'show_in_rest' => true,
				'rewrite'      => array(
					'slug'       => 'gamme',
					'with_front' => false,
				),
			)
		);
	}
);

function pl_champs_produit() {
	return array(
		'pl_price'    => array( 'Prix TTC (ex. 12.90)', 'text' ),
		'pl_sku'      => array( 'Référence (SKU)', 'text' ),
		'pl_count'    => array( 'Nombre de pièces', 'text' ),
		'pl_unit'     => array( 'Unité (crayons, carnet…)', 'text' ),
		'pl_badge'    => array( 'Badge (Nouveau, Best-seller…)', 'text' ),
		'pl_stock'    => array( 'Disponibilité', 'select' ),
		'pl_featured' => array( 'Mis en avant sur l\'accueil', 'checkbox' ),
		'pl_features' => array( 'Points forts (un par ligne)', 'textarea' ),
	);
}

add_action(
	'add_meta_boxes',
	function () {
		add_meta_box( 'pl_product', 'Détails du produit', 'pl_encart_produit', 'produit', 'normal', 'high' );
	}
);

function pl_encart_produit( $post ) {
	wp_nonce_field( 'pl_product_save', 'pl_product_nonce' );
	echo '<table class="form-table" role="presentation">';
	foreach ( pl_champs_produit() as $key => $def ) {
		$value = get_post_meta( $post->ID, $key, true );
		echo '<tr><th><label for="' . esc_attr( $key ) . '">' . esc_html( $def[0] ) . '</label></th><td>';
		if ( 'textarea' === $def[1] ) {
			echo '<textarea class="large-text" rows="5" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">' . esc_textarea( $value ) . '</textarea>';
		} elseif ( 'checkbox' === $def[1] ) {
			echo '<input type="checkbox" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="1" ' . checked( $value, '1', false ) . '>';
		} elseif ( 'select' === $def[1] ) {
			$value = $value ? $value : 'InStock';
			echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">';
			foreach ( array(
				'InStock'    => 'En stock',
				'OutOfStock' => 'Rupture',
				'PreOrder'   => 'Précommande',
			) as $v => $label ) {
				echo '<option value="' . esc_attr( $v ) . '" ' . selected( $value, $v, false ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select>';
		} else {
			echo '<input type="text" class="regular-text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
		}
		echo '</td></tr>';
	}
	echo '</table>';
}

add_action(
	'save_post_produit',
	function ( $post_id ) {
		if ( ! isset( $_POST['pl_product_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pl_product_nonce'] ) ), 'pl_product_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		foreach ( pl_champs_produit() as $key => $def ) {
			$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
			if ( 'textarea' === $def[1] ) {
				$value = sanitize_textarea_field( $raw );
			} elseif ( 'pl_price' === $key ) {
				$value = (string) (float) str_replace( ',', '.', $raw );
			} else {
				$value = sanitize_text_field( $raw );
			}
			update_post_meta( $post_id, $key, $value );
		}
	}
);
