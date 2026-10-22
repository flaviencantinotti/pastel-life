<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'add_meta_boxes',
	function () {
		foreach ( array( 'page', 'post', 'produit' ) as $type ) {
			add_meta_box( 'pl_seo', 'SEO · Pastel Life', 'pl_encart_seo', $type, 'normal', 'default' );
		}
	}
);

function pl_encart_seo( $post ) {
	wp_nonce_field( 'pl_seo_save', 'pl_seo_nonce' );
	$title = get_post_meta( $post->ID, '_pl_seo_title', true );
	$desc  = get_post_meta( $post->ID, '_pl_seo_desc', true );
	$noidx = get_post_meta( $post->ID, '_pl_noindex', true );
	echo '<p><label for="pl_seo_title"><strong>Titre SEO</strong> (idéalement 50 à 60 caractères)</label><br>';
	echo '<input type="text" class="large-text" id="pl_seo_title" name="pl_seo_title" value="' . esc_attr( $title ) . '"></p>';
	echo '<p><label for="pl_seo_desc"><strong>Meta description</strong> (idéalement 120 à 155 caractères)</label><br>';
	echo '<textarea class="large-text" rows="3" id="pl_seo_desc" name="pl_seo_desc">' . esc_textarea( $desc ) . '</textarea></p>';
	echo '<p><label><input type="checkbox" name="pl_noindex" value="1" ' . checked( $noidx, '1', false ) . '> Ne pas indexer cette page (noindex)</label></p>';
}

add_action(
	'save_post',
	function ( $post_id ) {
		if ( ! isset( $_POST['pl_seo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pl_seo_nonce'] ) ), 'pl_seo_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, '_pl_seo_title', isset( $_POST['pl_seo_title'] ) ? sanitize_text_field( wp_unslash( $_POST['pl_seo_title'] ) ) : '' );
		update_post_meta( $post_id, '_pl_seo_desc', isset( $_POST['pl_seo_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['pl_seo_desc'] ) ) : '' );
		update_post_meta( $post_id, '_pl_noindex', isset( $_POST['pl_noindex'] ) ? '1' : '' );
	}
);

function pl_id_contenu_seo() {
	if ( is_singular() ) {
		return (int) get_queried_object_id();
	}
	if ( is_home() && ! is_front_page() ) {
		return (int) get_option( 'page_for_posts' );
	}
	return 0;
}

add_filter(
	'pre_get_document_title',
	function ( $title ) {
		$id = pl_id_contenu_seo();
		if ( $id ) {
			$seo = get_post_meta( $id, '_pl_seo_title', true );
			if ( $seo ) {
				return $seo;
			}
		}
		if ( is_search() ) {
			return sprintf( 'Recherche : %s | %s', get_search_query(), get_bloginfo( 'name' ) );
		}
		if ( is_404() ) {
			return 'Page introuvable | ' . get_bloginfo( 'name' );
		}
		return $title;
	},
	20
);

add_filter(
	'document_title_separator',
	function () {
		return '|';
	}
);

function pl_couper_texte( $text, $max = 155 ) {
	$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $text ) ) );
	if ( mb_strlen( $text ) <= $max ) {
		return $text;
	}
	$cut = mb_substr( $text, 0, $max - 1 );
	$pos = mb_strrpos( $cut, ' ' );
	return rtrim( $pos ? mb_substr( $cut, 0, $pos ) : $cut, ' ,;:.-' ) . '…';
}

function pl_description_meta() {
	$id = pl_id_contenu_seo();
	if ( $id ) {
		$desc = get_post_meta( $id, '_pl_seo_desc', true );
		if ( $desc ) {
			return $desc;
		}
		$post = get_post( $id );
		if ( $post && $post->post_excerpt ) {
			return pl_couper_texte( $post->post_excerpt );
		}
	}
	if ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();
		if ( $term && $term->description ) {
			return pl_couper_texte( $term->description );
		}
	}
	return get_bloginfo( 'description' );
}

function pl_image_sociale() {
	$id = pl_id_contenu_seo();
	if ( $id && has_post_thumbnail( $id ) ) {
		$src = wp_get_attachment_image_src( get_post_thumbnail_id( $id ), 'full' );
		if ( $src ) {
			return array(
				'url' => $src[0],
				'w'   => $src[1],
				'h'   => $src[2],
				'alt' => get_post_meta( get_post_thumbnail_id( $id ), '_wp_attachment_image_alt', true ),
			);
		}
	}
	return array(
		'url' => pl_media_url( 'og-default' ),
		'w'   => 1200,
		'h'   => 630,
		'alt' => 'Pastel Life, crayons pastel doux et pigmentés',
	);
}

function pl_url_courante() {
	if ( is_singular() ) {
		return get_permalink( get_queried_object_id() );
	}
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_home() ) {
		return get_permalink( get_option( 'page_for_posts' ) );
	}
	if ( is_tax() || is_category() || is_tag() ) {
		return get_term_link( get_queried_object() );
	}
	return home_url( add_query_arg( array(), '' ) );
}

add_action(
	'wp_head',
	function () {
		if ( is_404() ) {
			return;
		}

		$title = wp_get_document_title();
		$desc  = pl_description_meta();
		$url   = pl_url_courante();
		$img   = pl_image_sociale();
		$type  = 'website';
		if ( is_singular( 'post' ) ) {
			$type = 'article';
		} elseif ( is_singular( 'produit' ) ) {
			$type = 'product';
		}

		if ( $desc ) {
			echo "\n" . '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $desc ) ) . '">' . "\n";
		}
		if ( ! is_singular() && ! is_search() && is_string( $url ) ) {
			echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
		}
		echo '<link rel="alternate" hreflang="fr-FR" href="' . esc_url( is_string( $url ) ? $url : home_url( '/' ) ) . '">' . "\n";
		echo '<meta name="theme-color" content="#FFF9F3">' . "\n";

		$og = array(
			'og:locale'      => 'fr_FR',
			'og:type'        => $type,
			'og:site_name'   => get_bloginfo( 'name' ),
			'og:title'       => $title,
			'og:description' => wp_strip_all_tags( $desc ),
			'og:url'         => is_string( $url ) ? $url : home_url( '/' ),
			'og:image'       => $img['url'],
			'og:image:width' => $img['w'],
			'og:image:height' => $img['h'],
			'og:image:alt'   => $img['alt'],
		);
		foreach ( $og as $property => $content ) {
			if ( '' !== (string) $content ) {
				echo '<meta property="' . esc_attr( $property ) . '" content="' . esc_attr( $content ) . '">' . "\n";
			}
		}
		if ( 'article' === $type ) {
			echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c' ) ) . '">' . "\n";
			echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c' ) ) . '">' . "\n";
		}
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( wp_strip_all_tags( $desc ) ) . '">' . "\n";
		if ( $img['url'] ) {
			echo '<meta name="twitter:image" content="' . esc_url( $img['url'] ) . '">' . "\n";
		}
	},
	1
);

add_action(
	'wp_head',
	function () {
		foreach ( array( 'fredoka-latin-wght-normal.woff2', 'nunito-latin-wght-normal.woff2' ) as $font ) {
			echo '<link rel="preload" href="' . esc_url( get_theme_file_uri( 'assets/fonts/' . $font ) ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
		}
		echo '<script>document.documentElement.classList.add("pl-js");</script>' . "\n";
	},
	0
);

add_filter(
	'wp_robots',
	function ( $robots ) {
		$id = pl_id_contenu_seo();
		if ( is_search() || is_404() || ( $id && get_post_meta( $id, '_pl_noindex', true ) ) ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['index'] );
		}
		return $robots;
	}
);

add_filter(
	'robots_txt',
	function ( $output, $public ) {
		if ( $public ) {
			$output .= "Disallow: /?s=\nDisallow: /*?s=\nDisallow: /search/\n";
		}
		return $output;
	},
	20,
	2
);

add_filter(
	'wp_sitemaps_add_provider',
	function ( $provider, $name ) {
		return 'users' === $name ? false : $provider;
	},
	10,
	2
);

add_filter(
	'wp_sitemaps_posts_query_args',
	function ( $args ) {
		$args['meta_query'] = array(
			array(
				'key'     => '_pl_noindex',
				'compare' => 'NOT EXISTS',
			),
		);
		return $args;
	}
);

add_action(
	'init',
	function () {
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	}
);

add_action(
	'wp_head',
	function () {
		if ( is_404() || is_search() ) {
			return;
		}

		$home   = home_url( '/' );
		$org_id = $home . '#organization';
		$name   = get_bloginfo( 'name' );
		$img    = pl_image_sociale();
		$url    = pl_url_courante();
		$url    = is_string( $url ) ? $url : $home;

		$graph = array(
			array(
				'@type'        => 'Organization',
				'@id'          => $org_id,
				'name'         => $name,
				'url'          => $home,
				'description'  => get_bloginfo( 'description' ),
				'logo'         => array(
					'@type'  => 'ImageObject',
					'url'    => pl_media_url( 'site-icon' ),
					'width'  => 512,
					'height' => 512,
				),
				'image'        => pl_media_url( 'og-default' ),
				'contactPoint' => array(
					'@type'             => 'ContactPoint',
					'contactType'       => 'customer service',
					'email'             => pl_courriel_contact(),
					'availableLanguage' => 'fr',
				),
			),
			array(
				'@type'           => 'WebSite',
				'@id'             => $home . '#website',
				'url'             => $home,
				'name'            => $name,
				'inLanguage'      => 'fr-FR',
				'publisher'       => array( '@id' => $org_id ),
				'potentialAction' => array(
					'@type'       => 'SearchAction',
					'target'      => array(
						'@type'       => 'EntryPoint',
						'urlTemplate' => $home . '?s={search_term_string}',
					),
					'query-input' => 'required name=search_term_string',
				),
			),
		);

		$page_type = 'WebPage';
		$post_id   = pl_id_contenu_seo();
		if ( $post_id ) {
			$custom = get_post_meta( $post_id, '_pl_schema', true );
			if ( $custom ) {
				$page_type = $custom;
			}
		}
		if ( is_tax() || is_category() ) {
			$page_type = 'CollectionPage';
		}

		$webpage = array(
			'@type'      => $page_type,
			'@id'        => $url . '#webpage',
			'url'        => $url,
			'name'       => wp_get_document_title(),
			'description' => wp_strip_all_tags( pl_description_meta() ),
			'inLanguage' => 'fr-FR',
			'isPartOf'   => array( '@id' => $home . '#website' ),
			'about'      => array( '@id' => $org_id ),
			'primaryImageOfPage' => array(
				'@type'  => 'ImageObject',
				'url'    => $img['url'],
				'width'  => $img['w'],
				'height' => $img['h'],
			),
		);
		if ( $post_id ) {
			$webpage['datePublished'] = get_the_date( 'c', $post_id );
			$webpage['dateModified']  = get_the_modified_date( 'c', $post_id );
		}

		$crumbs = pl_elements_fil_ariane();
		if ( count( $crumbs ) > 1 ) {
			$list = array();
			foreach ( $crumbs as $i => $crumb ) {
				$list[] = array(
					'@type'    => 'ListItem',
					'position' => $i + 1,
					'name'     => $crumb['name'],
					'item'     => $crumb['url'],
				);
			}
			$graph[]                 = array(
				'@type'           => 'BreadcrumbList',
				'@id'             => $url . '#breadcrumb',
				'itemListElement' => $list,
			);
			$webpage['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
		}
		$graph[] = $webpage;

		if ( is_singular( 'produit' ) ) {
			$pid  = get_queried_object_id();
			$data = pl_donnees_produit( $pid );
			$prod = array(
				'@type'            => 'Product',
				'@id'              => $url . '#product',
				'name'             => get_the_title( $pid ),
				'description'      => wp_strip_all_tags( pl_description_meta() ),
				'url'              => $url,
				'image'            => array( $img['url'] ),
				'brand'            => array(
					'@type' => 'Brand',
					'name'  => $name,
				),
				'mainEntityOfPage' => array( '@id' => $url . '#webpage' ),
				'offers'           => array(
					'@type'           => 'Offer',
					'url'             => $url,
					'priceCurrency'   => 'EUR',
					'price'           => number_format( $data['price'], 2, '.', '' ),
					'priceValidUntil' => gmdate( 'Y-12-31' ),
					'availability'    => 'https://schema.org/' . $data['stock'],
					'itemCondition'   => 'https://schema.org/NewCondition',
					'seller'          => array( '@id' => $org_id ),
				),
			);
			if ( $data['sku'] ) {
				$prod['sku'] = $data['sku'];
			}
			$graph[] = $prod;
		}

		if ( is_singular( 'post' ) ) {
			$pid       = get_queried_object_id();
			$cats      = get_the_category( $pid );
			$graph[]   = array(
				'@type'            => 'BlogPosting',
				'@id'              => $url . '#article',
				'headline'         => get_the_title( $pid ),
				'description'      => wp_strip_all_tags( pl_description_meta() ),
				'image'            => array( $img['url'] ),
				'datePublished'    => get_the_date( 'c', $pid ),
				'dateModified'     => get_the_modified_date( 'c', $pid ),
				'author'           => array( '@id' => $org_id ),
				'publisher'        => array( '@id' => $org_id ),
				'mainEntityOfPage' => array( '@id' => $url . '#webpage' ),
				'inLanguage'       => 'fr-FR',
				'wordCount'        => str_word_count( wp_strip_all_tags( get_post_field( 'post_content', $pid ) ) ),
				'articleSection'   => $cats ? $cats[0]->name : 'Journal',
			);
		}

		if ( is_tax( 'gamme' ) || is_page( 'boutique' ) ) {
			$query = new WP_Query(
				array(
					'post_type'      => 'produit',
					'posts_per_page' => 24,
					'no_found_rows'  => true,
					'tax_query'      => is_tax( 'gamme' ) ? array(
						array(
							'taxonomy' => 'gamme',
							'terms'    => get_queried_object_id(),
						),
					) : array(),
				)
			);
			$items = array();
			foreach ( $query->posts as $i => $p ) {
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $i + 1,
					'url'      => get_permalink( $p ),
					'name'     => get_the_title( $p ),
				);
			}
			if ( $items ) {
				$graph[] = array(
					'@type'           => 'ItemList',
					'@id'             => $url . '#products',
					'itemListElement' => $items,
				);
			}
		}

		echo '<script type="application/ld+json">' . wp_json_encode(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => $graph,
			),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		) . "</script>\n";
	},
	6
);

add_filter(
	'the_content',
	function ( $content ) {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$toc = array();
		$content = preg_replace_callback(
			'#<h2([^>]*)>(.*?)</h2>#is',
			function ( $m ) use ( &$toc ) {
				$text = wp_strip_all_tags( $m[2] );
				$id   = sanitize_title( $text );
				$toc[] = array( $id, $text );
				return '<h2' . $m[1] . ' id="' . esc_attr( $id ) . '">' . $m[2] . '</h2>';
			},
			$content
		);

		if ( count( $toc ) >= 3 ) {
			$html = '<nav class="pl-sommaire" aria-label="Sommaire"><p class="pl-sommaire__titre">Dans cet article</p><ol>';
			foreach ( $toc as $item ) {
				$html .= '<li><a href="#' . esc_attr( $item[0] ) . '">' . esc_html( $item[1] ) . '</a></li>';
			}
			$html   .= '</ol></nav>';
			$content = preg_replace( '#(<h2)#i', $html . '$1', $content, 1 );
		}
		return $content;
	},
	9
);
