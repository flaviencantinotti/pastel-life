<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pl_manifeste() {
	static $manifest = null;
	if ( null === $manifest ) {
		$file     = get_theme_file_path( 'content/manifest.json' );
		$manifest = file_exists( $file ) ? json_decode( file_get_contents( $file ), true ) : array();
	}
	return $manifest;
}

function pl_remplacer_profond( $value ) {
	if ( is_array( $value ) ) {
		return array_map( 'pl_remplacer_profond', $value );
	}
	if ( ! is_string( $value ) ) {
		return $value;
	}
	$value = str_replace( '{{home}}', home_url( '/' ), $value );
	return preg_replace_callback(
		'/\{\{media:([a-z0-9-]+)\}\}/',
		function ( $m ) {
			return pl_media_html( $m[1] );
		},
		$value
	);
}

function pl_resoudre_media( $noeud ) {
	if ( ! is_array( $noeud ) ) {
		return $noeud;
	}
	if ( isset( $noeud['__media'] ) ) {
		$id = pl_media_id( $noeud['__media'] );
		return array(
			'url'    => $id ? (string) wp_get_attachment_url( $id ) : '',
			'id'     => $id,
			'size'   => '',
			'alt'    => '',
			'source' => 'library',
		);
	}
	return array_map( 'pl_resoudre_media', $noeud );
}

function pl_installer_css( $force, &$report ) {
	$fichier = get_theme_file_path( 'content/css-additionnel.css' );
	if ( ! file_exists( $fichier ) ) {
		return;
	}
	$css    = trim( file_get_contents( $fichier ) );
	$actuel = trim( (string) wp_get_custom_css() );
	if ( $actuel === $css ) {
		update_option( 'pastel_life_css_hash', md5( $css ) );
		return;
	}
	if ( ! $force && '' !== $actuel && md5( $actuel ) !== get_option( 'pastel_life_css_hash' ) ) {
		$report['skipped'][] = 'CSS additionnel : modifié par vous, laissé tel quel.';
		return;
	}
	$resultat = wp_update_custom_css_post( $css, array( 'stylesheet' => get_stylesheet() ) );
	if ( is_wp_error( $resultat ) ) {
		$report['notes'][] = 'CSS additionnel : échec (' . $resultat->get_error_message() . ').';
		return;
	}
	update_option( 'pastel_life_css_hash', md5( $css ) );
	$report['notes'][] = 'CSS additionnel installé (Apparence > Personnaliser > CSS additionnel).';
}

function pl_trouver_par_cle( $key, $post_type ) {
	$found = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'meta_key'       => '_pl_key',
			'meta_value'     => $key,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	return $found ? (int) $found[0] : 0;
}

function pl_installer_media( $key, $def ) {
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'meta_key'       => '_pl_media',
			'meta_value'     => $key,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	$src = get_theme_file_path( $def['file'] );

	if ( $existing ) {
		$existing_id = (int) $existing[0];
		pl_media_id( $key, $existing_id );
		update_post_meta( $existing_id, '_wp_attachment_image_alt', $def['alt'] );

		if ( file_exists( $src ) && md5_file( $src ) !== get_post_meta( $existing_id, '_pl_media_hash', true ) ) {
			$path = get_attached_file( $existing_id );
			if ( $path && file_exists( dirname( $path ) ) && copy( $src, $path ) ) {
				wp_update_attachment_metadata( $existing_id, wp_generate_attachment_metadata( $existing_id, $path ) );
				update_post_meta( $existing_id, '_pl_media_hash', md5_file( $src ) );
			}
		}
		return $existing_id;
	}

	if ( ! file_exists( $src ) ) {
		return 0;
	}
	$bits = wp_upload_bits( basename( $src ), null, file_get_contents( $src ) );
	if ( ! empty( $bits['error'] ) ) {
		return 0;
	}
	$type = wp_check_filetype( $bits['file'] );
	$id   = wp_insert_attachment(
		array(
			'post_mime_type' => $type['type'],
			'post_title'     => $def['title'],
			'post_status'    => 'inherit',
		),
		$bits['file']
	);
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $bits['file'] ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $def['alt'] );
	update_post_meta( $id, '_pl_media', $key );
	update_post_meta( $id, '_pl_media_hash', md5_file( $src ) );
	pl_media_id( $key, $id );
	return (int) $id;
}

function pl_empreinte( $id ) {
	$post  = get_post( $id );
	$parts = array( $post->post_title, $post->post_content, $post->post_excerpt, get_post_meta( $id, '_elementor_data', true ) );
	foreach ( array( 'pl_price', 'pl_sku', 'pl_count', 'pl_unit', 'pl_badge', 'pl_stock', 'pl_featured', 'pl_features', '_pl_seo_title', '_pl_seo_desc', '_pl_faq' ) as $meta ) {
		$parts[] = get_post_meta( $id, $meta, true );
	}
	return md5( wp_json_encode( $parts ) );
}

function pl_marquer( $id ) {
	update_post_meta( $id, '_pl_hash', pl_empreinte( $id ) );
}

function pl_est_modifie( $id ) {
	$hash = get_post_meta( $id, '_pl_hash', true );
	return $hash && pl_empreinte( $id ) !== $hash;
}

function pl_installer_contenu( $postarr, $key, $label, &$report ) {
	$type = $postarr['post_type'];
	$id   = pl_trouver_par_cle( $key, $type );

	if ( $id && empty( $report['force'] ) && pl_est_modifie( $id ) ) {
		$report['locked'][ $id ] = true;
		$report['skipped'][]     = $label . ' : modifié par vous, laissé tel quel.';
		return (int) $id;
	}

	if ( ! $id ) {
		$existing = get_page_by_path( $postarr['post_name'], OBJECT, $type );
		if ( $existing ) {
			$report['skipped'][] = $label . ' : « ' . $existing->post_title . ' » existe déjà, laissé tel quel.';
			return (int) $existing->ID;
		}
	}

	if ( $id ) {
		$postarr['ID']            = $id;
		$postarr['page_template'] = '';
		$result                  = wp_update_post( wp_slash( $postarr ), true );
	} else {
		$result = wp_insert_post( wp_slash( $postarr ), true );
	}
	if ( is_wp_error( $result ) || ! $result ) {
		$report['notes'][] = $label . ' : échec (' . ( is_wp_error( $result ) ? $result->get_error_message() : 'inconnu' ) . ').';
		return 0;
	}

	update_post_meta( $result, '_pl_key', $key );
	$report[ $id ? 'updated' : 'created' ][] = $label;
	return (int) $result;
}

function pl_installer_seo( $id, $def ) {
	update_post_meta( $id, '_pl_seo_title', isset( $def['seo_title'] ) ? $def['seo_title'] : '' );
	update_post_meta( $id, '_pl_seo_desc', isset( $def['seo_desc'] ) ? $def['seo_desc'] : '' );
	if ( ! empty( $def['schema'] ) ) {
		update_post_meta( $id, '_pl_schema', $def['schema'] );
	}
}

function pl_installer_site( $force = false ) {
	$m      = pl_manifeste();
	$report = array(
		'created' => array(),
		'updated' => array(),
		'skipped' => array(),
		'notes'   => array(),
		'locked'  => array(),
		'force'   => (bool) $force,
	);
	if ( empty( $m ) ) {
		$report['notes'][] = 'Manifeste introuvable (content/manifest.json).';
		return $report;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$first = $force || ! get_option( 'pastel_life_site_installed' );

	if ( $first ) {
		update_option( 'blogname', $m['options']['blogname'] );
		update_option( 'blogdescription', $m['options']['blogdescription'] );
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		update_option( 'timezone_string', 'Europe/Paris' );
		update_option( 'date_format', 'j F Y' );
		update_option( 'time_format', 'H:i' );
		update_option( 'start_of_week', 1 );
		update_option( 'posts_per_page', 9 );
		update_option( 'default_comment_status', 'closed' );
		update_option( 'default_ping_status', 'closed' );
		update_option( 'blog_public', 1 );
		update_option( 'WPLANG', 'fr_FR' );
	}

	$default_cat = (int) get_option( 'default_category' );
	if ( $first && $default_cat ) {
		wp_update_term(
			$default_cat,
			'category',
			array(
				'name'        => 'Conseils et techniques',
				'slug'        => 'conseils',
				'description' => 'Techniques, matériel et conseils pour dessiner aux crayons pastel.',
			)
		);
	}

	$media = array();
	foreach ( $m['media'] as $key => $def ) {
		$media[ $key ] = pl_installer_media( $key, $def );
	}
	if ( ! empty( $media['site-icon'] ) && ( $first || ! get_option( 'site_icon' ) ) ) {
		update_option( 'site_icon', $media['site-icon'] );
	}
	$report['notes'][] = count( array_filter( $media ) ) . ' image(s) dans la médiathèque.';
	$manquantes        = array_keys( array_filter( $media, function ( $id ) {
		return ! $id;
	} ) );
	if ( $manquantes ) {
		$report['notes'][] = count( $manquantes ) . ' image(s) introuvable(s) dans le dossier du thème (assets/photos et assets/img) et absente(s) de la médiathèque : ' . implode( ', ', $manquantes ) . '.';
	}

	foreach ( $m['gammes'] as $g ) {
		$term = get_term_by( 'slug', $g['slug'], 'gamme' );
		if ( $term ) {
			if ( $force ) {
				wp_update_term(
					$term->term_id,
					'gamme',
					array(
						'name'        => $g['name'],
						'description' => $g['description'],
					)
				);
			}
		} else {
			wp_insert_term(
				$g['name'],
				'gamme',
				array(
					'slug'        => $g['slug'],
					'description' => $g['description'],
				)
			);
		}
	}

	$page_ids = array();
	foreach ( $m['pages'] as $p ) {
		$key = 'page:' . $p['slug'];

		if ( 'accueil' === $p['slug'] && ! pl_trouver_par_cle( $key, 'page' ) ) {
			$old = (int) get_option( 'pastel_life_home_installed' );
			if ( $old && get_post( $old ) ) {
				update_post_meta( $old, '_pl_key', $key );
			}
		}

		$content = ! empty( $p['content'] ) ? pl_remplacer_profond( $p['content'] ) : '';
		$id      = pl_installer_contenu(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $p['title'],
				'post_name'    => $p['slug'],
				'post_content' => $content,
				'post_excerpt' => isset( $p['excerpt'] ) ? $p['excerpt'] : '',
				'menu_order'   => isset( $p['order'] ) ? (int) $p['order'] : 0,
			),
			$key,
			'Page ' . $p['title'],
			$report
		);
		if ( ! $id ) {
			continue;
		}
		$page_ids[ $p['slug'] ] = $id;

		if ( ! empty( $report['locked'][ $id ] ) || get_post_meta( $id, '_pl_key', true ) !== $key ) {
			continue;
		}

		pl_installer_seo( $id, $p );

		if ( ! empty( $p['faq'] ) ) {
			update_post_meta( $id, '_pl_faq', wp_slash( wp_json_encode( pl_remplacer_profond( $p['faq'] ), JSON_UNESCAPED_UNICODE ) ) );
		}

		if ( ! empty( $p['elementor'] ) ) {
			$file = get_theme_file_path( 'content/' . $p['elementor'] );
			$raw  = file_exists( $file ) ? str_replace( '{{home}}', home_url( '/' ), file_get_contents( $file ) ) : '';
			$data = json_decode( $raw, true );
			if ( ! empty( $data['content'] ) ) {
				update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( pl_resoudre_media( $data['content'] ) ) ) );
				update_post_meta( $id, '_elementor_edit_mode', 'builder' );
				update_post_meta( $id, '_elementor_template_type', 'wp-page' );
				update_post_meta( $id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0' );
				update_post_meta( $id, '_wp_page_template', 'elementor_header_footer' );
				delete_post_meta( $id, '_elementor_css' );
			}
		}
		pl_marquer( $id );

		if ( ! empty( $p['front'] ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $id );
			update_option( 'pastel_life_home_installed', $id );
		}
		if ( ! empty( $p['posts_page'] ) ) {
			update_option( 'page_for_posts', $id );
		}
		if ( ! empty( $p['privacy'] ) ) {
			update_option( 'wp_page_for_privacy_policy', $id );
		}
	}

	foreach ( $m['products'] as $p ) {
		$id = pl_installer_contenu(
			array(
				'post_type'    => 'produit',
				'post_status'  => 'publish',
				'post_title'   => $p['title'],
				'post_name'    => $p['slug'],
				'post_content' => pl_remplacer_profond( $p['content'] ),
				'post_excerpt' => $p['excerpt'],
				'menu_order'   => (int) $p['order'],
			),
			'produit:' . $p['slug'],
			'Produit ' . $p['title'],
			$report
		);
		if ( ! $id || ! empty( $report['locked'][ $id ] ) || get_post_meta( $id, '_pl_key', true ) !== 'produit:' . $p['slug'] ) {
			continue;
		}
		update_post_meta( $id, 'pl_price', (string) $p['price'] );
		update_post_meta( $id, 'pl_sku', $p['sku'] );
		update_post_meta( $id, 'pl_count', $p['count'] );
		update_post_meta( $id, 'pl_unit', $p['unit'] );
		update_post_meta( $id, 'pl_badge', $p['badge'] );
		update_post_meta( $id, 'pl_stock', 'InStock' );
		update_post_meta( $id, 'pl_featured', ! empty( $p['featured'] ) ? '1' : '' );
		update_post_meta( $id, 'pl_features', implode( "\n", $p['features'] ) );
		pl_installer_seo( $id, $p );
		wp_set_object_terms( $id, array( $p['gamme'] ), 'gamme' );

		$current = (int) get_post_thumbnail_id( $id );
		if ( ! empty( $media[ $p['image'] ] ) && ( ! $current || get_post_meta( $current, '_pl_media', true ) ) ) {
			set_post_thumbnail( $id, $media[ $p['image'] ] );
		}
		pl_marquer( $id );
	}

	foreach ( $m['posts'] as $p ) {
		$key     = 'post:' . $p['slug'];
		$postarr = array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'post_title'   => $p['title'],
			'post_name'    => $p['slug'],
			'post_content' => pl_remplacer_profond( $p['content'] ),
			'post_excerpt' => $p['excerpt'],
		);
		if ( ! pl_trouver_par_cle( $key, 'post' ) ) {
			$postarr['post_date'] = wp_date( 'Y-m-d H:i:s', time() - (int) $p['days'] * DAY_IN_SECONDS );
		}
		$id = pl_installer_contenu( $postarr, $key, 'Article ' . $p['title'], $report );
		if ( ! $id || ! empty( $report['locked'][ $id ] ) || get_post_meta( $id, '_pl_key', true ) !== $key ) {
			continue;
		}
		pl_installer_seo( $id, $p );
		if ( $default_cat ) {
			wp_set_post_categories( $id, array( $default_cat ) );
		}

		$current = (int) get_post_thumbnail_id( $id );
		if ( ! empty( $media[ $p['image'] ] ) && ( ! $current || get_post_meta( $current, '_pl_media', true ) ) ) {
			set_post_thumbnail( $id, $media[ $p['image'] ] );
		}
		pl_marquer( $id );
	}

	$menu    = wp_get_nav_menu_object( 'Menu principal' );
	$menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( 'Menu principal' );
	if ( $menu_id && ! wp_get_nav_menu_items( $menu_id ) ) {
		foreach ( $m['menu'] as $item ) {
			if ( empty( $page_ids[ $item['slug'] ] ) ) {
				continue;
			}
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $item['label'],
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $page_ids[ $item['slug'] ],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}
		$report['created'][] = 'Menu principal';
	}
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	if ( $menu_id && empty( $locations['menu-1'] ) ) {
		$locations['menu-1'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	foreach ( array(
		array( 'hello-world', 'post' ),
		array( 'sample-page', 'page' ),
		array( 'privacy-policy', 'page' ),
	) as $default ) {
		$sample = get_page_by_path( $default[0], OBJECT, $default[1] );
		if ( $sample && ! get_post_meta( $sample->ID, '_pl_key', true ) && $sample->post_modified_gmt === $sample->post_date_gmt ) {
			wp_trash_post( $sample->ID );
		}
	}

	pl_installer_css( $force, $report );
	if ( ( $force || ! get_option( 'pastel_life_kit_applied' ) ) && pastel_life_appliquer_kit() ) {
		update_option( 'pastel_life_kit_applied', PASTEL_LIFE_VERSION );
	}
	if ( did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Plugin' ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}

	flush_rewrite_rules( true );
	update_option( 'pastel_life_site_installed', $m['version'] );
	return $report;
}

add_action(
	'admin_init',
	function () {
		if ( get_option( 'pastel_life_stamped' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$ids = get_posts(
			array(
				'post_type'      => array( 'page', 'produit', 'post' ),
				'post_status'    => 'any',
				'meta_key'       => '_pl_key',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		foreach ( $ids as $id ) {
			if ( ! get_post_meta( $id, '_pl_hash', true ) ) {
				pl_marquer( $id );
			}
		}
		update_option( 'pastel_life_stamped', 1 );
	},
	40
);

add_action(
	'admin_post_pastel_life_install_site',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Action non autorisée.', 'pastel-life' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'pastel_life_install_site' );

		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 300 );
		}
		wp_raise_memory_limit( 'admin' );

		$force = ! empty( $_GET['force'] );
		set_transient( 'pl_install_report', pl_installer_site( $force ), 600 );
		wp_safe_redirect( admin_url( 'index.php?pl_installed=1' ) );
		exit;
	}
);

add_action(
	'admin_notices',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->base, array( 'dashboard', 'themes', 'plugins', 'edit' ), true ) ) {
			return;
		}

		$report = get_transient( 'pl_install_report' );
		if ( $report ) {
			delete_transient( 'pl_install_report' );
			$links = array(
				'Accueil'  => home_url( '/' ),
				'Boutique' => pl_url_page( 'boutique' ),
				'Journal'  => pl_url_page( 'journal' ),
			);
			echo '<div class="notice notice-success"><p><strong>Pastel Life :</strong> ' . ( ! empty( $report['force'] ) ? 'site réinstallé en entier. ' : 'site à jour. ' );
			echo esc_html( count( $report['created'] ) . ' élément(s) créé(s), ' . count( $report['updated'] ) . ' mis à jour, ' . count( isset( $report['locked'] ) ? $report['locked'] : array() ) . ' laissé(s) tel(s) quel(s) car modifié(s) par vous. ' );
			foreach ( $links as $label => $url ) {
				echo '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a> · ';
			}
			echo '<a href="' . esc_url( admin_url( 'edit.php?post_type=produit' ) ) . '">Produits</a></p>';
			foreach ( array_merge( $report['skipped'], $report['notes'] ) as $note ) {
				echo '<p>' . esc_html( $note ) . '</p>';
			}
			echo '</div>';
			return;
		}

		if ( ! did_action( 'elementor/loaded' ) ) {
			echo '<div class="notice notice-warning"><p><strong>Pastel Life :</strong> installez et activez le plugin <em>Elementor</em> pour utiliser le thème.</p></div>';
			return;
		}

		$manifest = pl_manifeste();
		if ( ! empty( $manifest['version'] ) && get_option( 'pastel_life_site_installed' ) !== $manifest['version'] ) {
			$url       = wp_nonce_url( admin_url( 'admin-post.php?action=pastel_life_install_site' ), 'pastel_life_install_site' );
			$installed = (bool) get_option( 'pastel_life_site_installed' );
			if ( $installed ) {
				printf(
					'<div class="notice notice-info"><p><strong>Pastel Life :</strong> une mise à jour du site est disponible. Ce que vous avez modifié (pages, produits, articles, réglages, palette) est conservé ; seul ce que vous n\'avez pas touché est mis à jour. <a class="button button-primary" href="%1$s">Mettre à jour</a> <a class="button" href="%2$s" onclick="return confirm(\'Tout remettre à l\\\'état d\\\'origine ? Vos modifications seront perdues.\');">Tout réinstaller (écrase mes modifications)</a></p></div>',
					esc_url( $url ),
					esc_url( add_query_arg( 'force', '1', $url ) )
				);
			} else {
				printf(
					'<div class="notice notice-info"><p><strong>Pastel Life :</strong> installer le site complet (pages, boutique, journal, menu, réglages SEO) ? Ce qui existe déjà et que vous avez créé n\'est pas modifié. <a class="button button-primary" href="%s">Installer le site complet</a></p></div>',
					esc_url( $url )
				);
			}
		}
	}
);
