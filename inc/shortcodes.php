<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pl_carte_produit_html( $post ) {
	$post = get_post( $post );
	$d    = pl_donnees_produit( $post->ID );
	$url  = get_permalink( $post );
	$name = get_the_title( $post );

	ob_start();
	?>
	<article class="pl-carte-produit pl-apparition">
		<a class="pl-carte-produit__visuel" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo get_the_post_thumbnail( $post, 'large', array( 'alt' => $name, 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
		</a>
		<div class="pl-carte-produit__corps">
			<?php if ( $d['badge'] ) : ?>
				<span class="pl-badge"><?php echo esc_html( $d['badge'] ); ?></span>
			<?php endif; ?>
			<h3 class="pl-carte-produit__titre"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $name ); ?></a></h3>
			<p class="pl-carte-produit__texte"><?php echo esc_html( get_the_excerpt( $post ) ); ?></p>
			<div class="pl-carte-produit__pied">
				<span class="pl-prix"><?php echo esc_html( pl_prix_formate( $d['price'] ) ); ?></span>
				<a class="pl-bouton pl-bouton--petit" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( 'Découvrir : ' . $name ); ?>">Découvrir</a>
			</div>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

function pl_carte_article_html( $post ) {
	$post = get_post( $post );
	$url  = get_permalink( $post );
	$cats = get_the_category( $post->ID );

	ob_start();
	?>
	<article class="pl-carte-article pl-apparition">
		<a class="pl-carte-article__visuel" href="<?php echo esc_url( $url ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo get_the_post_thumbnail( $post, 'large', array( 'alt' => get_the_title( $post ), 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
		</a>
		<div class="pl-carte-article__corps">
			<p class="pl-carte-article__infos">
				<?php if ( $cats ) : ?>
					<span class="pl-pastille"><?php echo esc_html( $cats[0]->name ); ?></span>
				<?php endif; ?>
				<time datetime="<?php echo esc_attr( get_the_date( 'c', $post ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y', $post ) ); ?></time>
				<span><?php echo esc_html( pl_temps_lecture( $post ) ); ?> min de lecture</span>
			</p>
			<h3 class="pl-carte-article__titre"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></h3>
			<p class="pl-carte-article__texte"><?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 28 ) ); ?></p>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

add_shortcode(
	'pl_products',
	function ( $atts ) {
		$a    = shortcode_atts(
			array(
				'limit'    => -1,
				'gamme'    => '',
				'featured' => '',
				'exclude'  => '',
			),
			$atts
		);
		$args = array(
			'post_type'      => 'produit',
			'posts_per_page' => (int) $a['limit'],
			'no_found_rows'  => true,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
		);
		if ( $a['gamme'] ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'gamme',
					'field'    => 'slug',
					'terms'    => array_map( 'trim', explode( ',', $a['gamme'] ) ),
				),
			);
		}
		if ( $a['featured'] ) {
			$args['meta_query'] = array(
				array(
					'key'   => 'pl_featured',
					'value' => '1',
				),
			);
		}
		if ( $a['exclude'] ) {
			$args['post__not_in'] = array_map( 'intval', explode( ',', $a['exclude'] ) );
		}

		$query = new WP_Query( $args );
		if ( ! $query->have_posts() ) {
			return '';
		}
		$html = '<div class="pl-grille pl-grille--produits">';
		foreach ( $query->posts as $p ) {
			$html .= pl_carte_produit_html( $p );
		}
		wp_reset_postdata();
		return $html . '</div>';
	}
);

add_shortcode(
	'pl_posts',
	function ( $atts ) {
		$a     = shortcode_atts(
			array(
				'limit'   => 3,
				'exclude' => '',
			),
			$atts
		);
		$query = new WP_Query(
			array(
				'post_type'           => 'post',
				'posts_per_page'      => (int) $a['limit'],
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
				'post__not_in'        => $a['exclude'] ? array_map( 'intval', explode( ',', $a['exclude'] ) ) : array(),
			)
		);
		if ( ! $query->have_posts() ) {
			return '';
		}
		$html = '<div class="pl-grille pl-grille--articles">';
		foreach ( $query->posts as $p ) {
			$html .= pl_carte_article_html( $p );
		}
		wp_reset_postdata();
		return $html . '</div>';
	}
);

add_shortcode(
	'pl_faq',
	function ( $atts ) {
		$a    = shortcode_atts(
			array(
				'page'  => '',
				'limit' => 0,
			),
			$atts
		);
		$page = $a['page'] ? get_page_by_path( $a['page'] ) : get_post();
		if ( ! $page ) {
			return '';
		}
		$items = json_decode( (string) get_post_meta( $page->ID, '_pl_faq', true ), true );
		if ( ! is_array( $items ) || ! $items ) {
			return '';
		}
		if ( (int) $a['limit'] > 0 ) {
			$items = array_slice( $items, 0, (int) $a['limit'] );
		}

		$html     = '<div class="pl-faq">';
		$entities = array();
		foreach ( $items as $i => $item ) {
			$html      .= '<details class="pl-faq__element pl-apparition"' . ( 0 === $i ? ' open' : '' ) . '><summary>' . esc_html( $item['q'] ) . '</summary><div class="pl-faq__reponse">' . wp_kses_post( $item['a'] ) . '</div></details>';
			$entities[] = array(
				'@type'          => 'Question',
				'name'           => $item['q'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $item['a'] ),
				),
			);
		}
		$html .= '</div>';
		$html .= '<script type="application/ld+json">' . wp_json_encode(
			array(
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => $entities,
			),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		) . '</script>';
		return $html;
	}
);

add_shortcode(
	'pl_breadcrumbs',
	function () {
		return pl_fil_ariane_html();
	}
);

add_shortcode(
	'pl_contact_form',
	function () {
		$sent  = isset( $_GET['pl_sent'] );
		$error = isset( $_GET['pl_error'] ) ? sanitize_key( wp_unslash( $_GET['pl_error'] ) ) : '';
		$pre   = isset( $_GET['produit'] ) ? sanitize_title( wp_unslash( $_GET['produit'] ) ) : '';

		$products = get_posts(
			array(
				'post_type'      => 'produit',
				'posts_per_page' => -1,
				'orderby'        => array( 'menu_order' => 'ASC' ),
			)
		);

		ob_start();
		?>
		<div class="pl-formulaire-bloc" id="formulaire-contact">
			<?php if ( $sent ) : ?>
				<p class="pl-avis pl-avis--ok" role="status">Merci, votre message est bien parti ! On vous répond sous 48 h ouvrées.</p>
			<?php elseif ( $error ) : ?>
				<p class="pl-avis pl-avis--erreur" role="alert">
					<?php echo 'missing' === $error ? 'Merci de remplir tous les champs obligatoires et d\'accepter le traitement de vos données.' : 'Une erreur est survenue, merci de réessayer dans un instant.'; ?>
				</p>
			<?php endif; ?>
			<form class="pl-formulaire" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="pl_contact">
				<?php wp_nonce_field( 'pl_contact', 'pl_contact_nonce' ); ?>
				<div class="pl-formulaire__piege" aria-hidden="true"><label>Ne pas remplir<input type="text" name="pl_website" tabindex="-1" autocomplete="off"></label></div>
				<div class="pl-formulaire__ligne">
					<p><label for="pl-nom">Votre nom *</label><input type="text" id="pl-nom" name="pl_name" required autocomplete="name"></p>
					<p><label for="pl-courriel">Votre e-mail *</label><input type="email" id="pl-courriel" name="pl_email" required autocomplete="email"></p>
				</div>
				<div class="pl-formulaire__ligne">
					<p><label for="pl-sujet">Votre demande</label>
						<select id="pl-sujet" name="pl_topic">
							<option>Question sur un produit</option>
							<option <?php selected( (bool) $pre ); ?>>Commande</option>
							<option>Conseil pour choisir un coffret</option>
							<option>Partenariat ou presse</option>
							<option>Autre</option>
						</select>
					</p>
					<p><label for="pl-produit">Produit concerné</label>
						<select id="pl-produit" name="pl_product">
							<option value="">Aucun en particulier</option>
							<?php foreach ( $products as $p ) : ?>
								<option value="<?php echo esc_attr( $p->post_title ); ?>" <?php selected( $pre, $p->post_name ); ?>><?php echo esc_html( $p->post_title ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
				</div>
				<p><label for="pl-message">Votre message *</label><textarea id="pl-message" name="pl_message" rows="6" required></textarea></p>
				<p class="pl-formulaire__consentement"><label><input type="checkbox" name="pl_consent" value="1" required> <span>J'accepte que mes données soient utilisées pour répondre à ma demande (voir la <a href="<?php echo esc_url( pl_url_page( 'politique-de-confidentialite' ) ); ?>">politique de confidentialité</a>). *</span></label></p>
				<p><button type="submit" class="pl-bouton">Envoyer le message</button></p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}
);

function pl_traiter_contact() {
	$back = wp_get_referer() ? remove_query_arg( array( 'pl_sent', 'pl_error' ), wp_get_referer() ) : pl_url_page( 'contact' );
	$go   = function ( $args ) use ( $back ) {
		wp_safe_redirect( add_query_arg( $args, $back ) . '#formulaire-contact' );
		exit;
	};

	if ( ! isset( $_POST['pl_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pl_contact_nonce'] ) ), 'pl_contact' ) ) {
		$go( array( 'pl_error' => 'failed' ) );
	}

	if ( ! empty( $_POST['pl_website'] ) ) {
		$go( array( 'pl_sent' => 1 ) );
	}

	$name    = isset( $_POST['pl_name'] ) ? sanitize_text_field( wp_unslash( $_POST['pl_name'] ) ) : '';
	$email   = isset( $_POST['pl_email'] ) ? sanitize_email( wp_unslash( $_POST['pl_email'] ) ) : '';
	$topic   = isset( $_POST['pl_topic'] ) ? sanitize_text_field( wp_unslash( $_POST['pl_topic'] ) ) : '';
	$product = isset( $_POST['pl_product'] ) ? sanitize_text_field( wp_unslash( $_POST['pl_product'] ) ) : '';
	$message = isset( $_POST['pl_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['pl_message'] ) ) : '';

	if ( ! $name || ! is_email( $email ) || ! $message || empty( $_POST['pl_consent'] ) ) {
		$go( array( 'pl_error' => 'missing' ) );
	}

	$key = 'pl_contact_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'x' );
	if ( get_transient( $key ) ) {
		$go( array( 'pl_error' => 'failed' ) );
	}
	set_transient( $key, 1, 30 );

	$body  = "Nom : $name\nE-mail : $email\nDemande : $topic\n";
	$body .= $product ? "Produit : $product\n" : '';
	$body .= "\n$message\n";

	$ok = wp_mail(
		get_option( 'admin_email' ),
		'[' . get_bloginfo( 'name' ) . '] ' . $topic . ' - ' . $name,
		$body,
		array( 'Reply-To: ' . $name . ' <' . $email . '>' )
	);
	$go( $ok ? array( 'pl_sent' => 1 ) : array( 'pl_error' => 'failed' ) );
}
add_action( 'admin_post_nopriv_pl_contact', 'pl_traiter_contact' );
add_action( 'admin_post_pl_contact', 'pl_traiter_contact' );
