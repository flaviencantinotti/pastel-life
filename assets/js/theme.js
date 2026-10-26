( function () {
	'use strict';

	var doc = document;
	var corps = doc.body;
	var mouvementReduit = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var edition = corps.classList.contains( 'elementor-editor-active' ) || corps.classList.contains( 'elementor-editor-preview' );

	var boutonMenu = doc.querySelector( '.pl-menu-bouton' );
	var navigation = doc.getElementById( 'pl-navigation' );
	if ( boutonMenu && navigation ) {
		var definirOuvert = function ( ouvert ) {
			boutonMenu.setAttribute( 'aria-expanded', ouvert ? 'true' : 'false' );
			navigation.classList.toggle( 'est-ouvert', ouvert );
		};
		boutonMenu.addEventListener( 'click', function () {
			definirOuvert( boutonMenu.getAttribute( 'aria-expanded' ) !== 'true' );
		} );
		navigation.addEventListener( 'click', function ( evenement ) {
			if ( evenement.target.closest( 'a' ) ) {
				definirOuvert( false );
			}
		} );
		doc.addEventListener( 'keydown', function ( evenement ) {
			if ( evenement.key === 'Escape' ) {
				definirOuvert( false );
			}
		} );
	}

	var entete = doc.querySelector( '.pl-entete' );
	var enAttente = [];
	var compteurs = [];
	var formes = [];
	var progression = doc.querySelector( '.pl-progression span' );
	var article = doc.querySelector( '.pl-article .pl-prose' );
	var imageProgrammee = false;

	var proprietesForme = [
		{ nom: '--pl-ya', unite: 'px' },
		{ nom: '--pl-xa', unite: 'px' },
		{ nom: '--pl-ra', unite: 'deg' },
		{ nom: '--pl-yb', unite: 'px' },
		{ nom: '--pl-xb', unite: 'px' },
		{ nom: '--pl-rb', unite: 'deg' }
	];

	var selecteurApparition = [
		'.pl-carte',
		'.elementor-widget-heading',
		'.elementor-widget-text-editor',
		'.elementor-widget-button',
		'.elementor-widget-html',
		'.elementor-widget-image',
		'.elementor-widget-shortcode .pl-formulaire-bloc'
	].join( ',' );

	function reveler( element ) {
		element.classList.add( 'est-visible' );
		window.setTimeout( function () {
			element.classList.remove( 'pl-apparition', 'est-visible' );
			element.style.removeProperty( '--rd' );
		}, 1400 );
	}

	function preparerApparitions() {
		if ( edition ) {
			return;
		}
		doc.querySelectorAll( selecteurApparition ).forEach( function ( element ) {
			if ( element.closest( '.pl-banniere, .pl-entete, .pl-pied' ) || element.classList.contains( 'pl-entree' ) || element.querySelector( '.pl-defilement' ) ) {
				return;
			}
			element.classList.add( 'pl-apparition' );
		} );
		enAttente = Array.prototype.slice.call( doc.querySelectorAll( '.pl-apparition' ) );

		var rangs = new Map();
		enAttente.forEach( function ( element ) {
			var parent = element.parentElement;
			var rang = rangs.get( parent ) || 0;
			element.style.setProperty( '--rd', Math.min( rang, 6 ) * 0.08 + 's' );
			rangs.set( parent, rang + 1 );
		} );
	}

	function verifierApparitions( hauteurFenetre ) {
		if ( ! enAttente.length ) {
			return;
		}
		enAttente = enAttente.filter( function ( element ) {
			if ( element.getBoundingClientRect().top < hauteurFenetre * 0.92 ) {
				reveler( element );
				return false;
			}
			return true;
		} );
	}

	function preparerCompteurs() {
		if ( mouvementReduit ) {
			return;
		}
		compteurs = Array.prototype.slice.call( doc.querySelectorAll( '.pl-compteur[data-valeur]' ) );
	}

	function lancerCompteur( element ) {
		var valeur = parseFloat( element.getAttribute( 'data-valeur' ) );
		var debut = null;
		var duree = 1400;
		var etape = function ( instant ) {
			debut = debut || instant;
			var avancement = Math.min( ( instant - debut ) / duree, 1 );
			element.textContent = Math.round( valeur * ( 1 - Math.pow( 1 - avancement, 3 ) ) );
			if ( avancement < 1 ) {
				window.requestAnimationFrame( etape );
			} else {
				element.textContent = String( valeur );
			}
		};
		element.textContent = '0';
		window.requestAnimationFrame( etape );
	}

	function verifierCompteurs( hauteurFenetre ) {
		if ( ! compteurs.length ) {
			return;
		}
		compteurs = compteurs.filter( function ( element ) {
			var rectangle = element.getBoundingClientRect();
			if ( rectangle.top < hauteurFenetre * 0.85 && rectangle.bottom > 0 ) {
				lancerCompteur( element );
				return false;
			}
			return true;
		} );
	}

	function preparerCodesCouleur() {
		var notification = null;
		var minuterie = null;
		var afficher = function ( texte ) {
			if ( ! notification ) {
				notification = doc.createElement( 'div' );
				notification.className = 'pl-notification';
				notification.setAttribute( 'role', 'status' );
				corps.appendChild( notification );
			}
			notification.textContent = texte;
			notification.classList.add( 'est-affiche' );
			window.clearTimeout( minuterie );
			minuterie = window.setTimeout( function () {
				notification.classList.remove( 'est-affiche' );
			}, 1800 );
		};
		var copier = function ( element ) {
			var code = element.textContent.trim();
			var termine = function () {
				afficher( 'Code ' + code + ' copié !' );
			};
			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( code ).then( termine, termine );
			} else {
				termine();
			}
		};
		doc.querySelectorAll( '.pl-code-couleur' ).forEach( function ( element ) {
			element.setAttribute( 'role', 'button' );
			element.setAttribute( 'tabindex', '0' );
			element.setAttribute( 'aria-label', 'Copier le code couleur ' + element.textContent.trim() );
			element.addEventListener( 'click', function () {
				copier( element );
			} );
			element.addEventListener( 'keydown', function ( evenement ) {
				if ( evenement.key === 'Enter' || evenement.key === ' ' ) {
					evenement.preventDefault();
					copier( element );
				}
			} );
		} );
	}

	function mettreAJour() {
		imageProgrammee = false;
		var hauteurFenetre = window.innerHeight;

		if ( entete ) {
			entete.classList.toggle( 'est-fixe', window.scrollY > 8 );
		}

		verifierApparitions( hauteurFenetre );
		verifierCompteurs( hauteurFenetre );

		if ( progression && article ) {
			var rectangle = article.getBoundingClientRect();
			var total = rectangle.height - hauteurFenetre * 0.6;
			var avancement = total > 0 ? Math.min( Math.max( ( hauteurFenetre * 0.2 - rectangle.top ) / total, 0 ), 1 ) : 0;
			progression.style.setProperty( '--p', avancement.toFixed( 3 ) );
			progression.parentNode.style.setProperty( '--p', avancement.toFixed( 3 ) );
		}

		if ( mouvementReduit || edition ) {
			return;
		}

		var intensite = window.innerWidth < 768 ? 0.6 : 1;
		var borner = function ( valeur, limite ) {
			return Math.max( -limite, Math.min( limite, valeur ) );
		};
		formes.forEach( function ( forme ) {
			var rectangle = forme.element.getBoundingClientRect();
			if ( rectangle.bottom < -200 || rectangle.top > hauteurFenetre + 200 ) {
				return;
			}
			var ecart = rectangle.top + rectangle.height / 2 - hauteurFenetre / 2;
			forme.cible = [
				borner( ecart * -0.34 * intensite, 240 * intensite ),
				borner( ecart * 0.1 * intensite, 70 * intensite ),
				borner( ecart * 0.03, 22 ),
				borner( ecart * 0.26 * intensite, 190 * intensite ),
				borner( ecart * -0.08 * intensite, 55 * intensite ),
				borner( ecart * -0.025, 18 )
			];
		} );
		demarrerAdoucissement();
	}

	var adoucissementEnCours = false;
	function adoucir() {
		var enMouvement = false;
		formes.forEach( function ( forme ) {
			proprietesForme.forEach( function ( propriete, indice ) {
				var ecart = forme.cible[ indice ] - forme.courant[ indice ];
				if ( Math.abs( ecart ) > 0.1 ) {
					forme.courant[ indice ] += ecart * 0.1;
					forme.element.style.setProperty( propriete.nom, forme.courant[ indice ].toFixed( 2 ) + propriete.unite );
					enMouvement = true;
				}
			} );
		} );
		if ( enMouvement ) {
			window.requestAnimationFrame( adoucir );
		} else {
			adoucissementEnCours = false;
		}
	}
	function demarrerAdoucissement() {
		if ( ! adoucissementEnCours ) {
			adoucissementEnCours = true;
			window.requestAnimationFrame( adoucir );
		}
	}

	function demanderMiseAJour() {
		if ( ! imageProgrammee ) {
			imageProgrammee = true;
			window.requestAnimationFrame( mettreAJour );
		}
	}

	function initialiser() {
		formes = Array.prototype.slice.call( doc.querySelectorAll( '.pl-formes' ) ).map( function ( element ) {
			return { element: element, courant: [ 0, 0, 0, 0, 0, 0 ], cible: [ 0, 0, 0, 0, 0, 0 ] };
		} );
		preparerApparitions();
		preparerCompteurs();
		preparerCodesCouleur();
		mettreAJour();
		window.addEventListener( 'scroll', demanderMiseAJour, { passive: true } );
		window.addEventListener( 'resize', demanderMiseAJour );
		window.addEventListener( 'load', demanderMiseAJour );
		window.setTimeout( mettreAJour, 400 );
		window.setTimeout( mettreAJour, 1500 );
	}

	if ( doc.readyState === 'loading' ) {
		doc.addEventListener( 'DOMContentLoaded', initialiser );
	} else {
		initialiser();
	}
}() );
