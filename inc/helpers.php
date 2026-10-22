<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pl_prix_formate( $amount ) {
	return number_format( (float) $amount, 2, ',', "\u{202F}" ) . "\u{00A0}€";
}

function pl_url_page( $slug, $anchor = '' ) {
	static $cache = array();
	if ( ! isset( $cache[ $slug ] ) ) {
		$page           = get_page_by_path( $slug );
		$cache[ $slug ] = $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
	}
	return $cache[ $slug ] . $anchor;
}

function pl_media_id( $cle, $definir = null ) {
	static $ids = array();
	if ( null !== $definir ) {
		$ids[ $cle ] = (int) $definir;
	}
	if ( ! isset( $ids[ $cle ] ) ) {
		$trouve      = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'meta_key'       => '_pl_media',
				'meta_value'     => $cle,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		$ids[ $cle ] = $trouve ? (int) $trouve[0] : 0;
	}
	return $ids[ $cle ];
}

function pl_media_url( $cle ) {
	$id = pl_media_id( $cle );
	return $id ? (string) wp_get_attachment_url( $id ) : '';
}

function pl_media_html( $cle ) {
	$id = pl_media_id( $cle );
	return $id ? wp_get_attachment_image( $id, 'large', false, array( 'loading' => 'lazy', 'decoding' => 'async' ) ) : '';
}

function pl_donnees_produit( $post_id ) {
	$features = (string) get_post_meta( $post_id, 'pl_features', true );
	return array(
		'price'    => (float) get_post_meta( $post_id, 'pl_price', true ),
		'sku'      => (string) get_post_meta( $post_id, 'pl_sku', true ),
		'count'    => (string) get_post_meta( $post_id, 'pl_count', true ),
		'unit'     => (string) get_post_meta( $post_id, 'pl_unit', true ),
		'badge'    => (string) get_post_meta( $post_id, 'pl_badge', true ),
		'stock'    => get_post_meta( $post_id, 'pl_stock', true ) ? get_post_meta( $post_id, 'pl_stock', true ) : 'InStock',
		'features' => array_values( array_filter( array_map( 'trim', explode( "\n", $features ) ) ) ),
	);
}

function pl_temps_lecture( $post = null ) {
	$post  = get_post( $post );
	$words = str_word_count( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 0, 'àâçéèêëîïôûùüÿœÀÂÇÉÈÊËÎÏÔÛÙÜŸŒ' );
	return max( 1, (int) ceil( $words / 200 ) );
}

function pl_elements_fil_ariane() {
	if ( is_front_page() ) {
		return array();
	}

	$items = array(
		array(
			'name' => 'Accueil',
			'url'  => home_url( '/' ),
		),
	);

	if ( is_singular( 'produit' ) ) {
		$items[] = array(
			'name' => 'Boutique',
			'url'  => pl_url_page( 'boutique' ),
		);
		$terms = get_the_terms( get_the_ID(), 'gamme' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$items[] = array(
				'name' => $terms[0]->name,
				'url'  => get_term_link( $terms[0] ),
			);
		}
		$items[] = array(
			'name' => get_the_title(),
			'url'  => get_permalink(),
		);
	} elseif ( is_singular( 'post' ) ) {
		$items[] = array(
			'name' => 'Journal',
			'url'  => pl_url_page( 'journal' ),
		);
		$items[] = array(
			'name' => get_the_title(),
			'url'  => get_permalink(),
		);
	} elseif ( is_page() ) {
		$ancestors = array_reverse( get_post_ancestors( get_the_ID() ) );
		foreach ( $ancestors as $ancestor ) {
			$items[] = array(
				'name' => get_the_title( $ancestor ),
				'url'  => get_permalink( $ancestor ),
			);
		}
		$items[] = array(
			'name' => get_the_title(),
			'url'  => get_permalink(),
		);
	} elseif ( is_home() ) {
		$items[] = array(
			'name' => 'Journal',
			'url'  => pl_url_page( 'journal' ),
		);
	} elseif ( is_tax( 'gamme' ) ) {
		$items[] = array(
			'name' => 'Boutique',
			'url'  => pl_url_page( 'boutique' ),
		);
		$term    = get_queried_object();
		$items[] = array(
			'name' => $term->name,
			'url'  => get_term_link( $term ),
		);
	} elseif ( is_category() || is_tag() ) {
		$items[] = array(
			'name' => 'Journal',
			'url'  => pl_url_page( 'journal' ),
		);
		$term    = get_queried_object();
		$items[] = array(
			'name' => $term->name,
			'url'  => get_term_link( $term ),
		);
	} elseif ( is_search() ) {
		$items[] = array(
			'name' => 'Recherche',
			'url'  => home_url( '/?s=' . rawurlencode( get_search_query() ) ),
		);
	}

	return $items;
}

function pl_fil_ariane_html() {
	$items = pl_elements_fil_ariane();
	if ( count( $items ) < 2 ) {
		return '';
	}

	$html = '<nav class="pl-fil-ariane" aria-label="Fil d\'Ariane"><ol>';
	$last = count( $items ) - 1;
	foreach ( $items as $i => $item ) {
		if ( $i === $last ) {
			$html .= '<li aria-current="page">' . esc_html( $item['name'] ) . '</li>';
		} else {
			$html .= '<li><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['name'] ) . '</a></li>';
		}
	}
	return $html . '</ol></nav>';
}

function pl_courriel_contact() {
	return apply_filters( 'pl_courriel_contact', 'bonjour@pastel-life.example' );
}
