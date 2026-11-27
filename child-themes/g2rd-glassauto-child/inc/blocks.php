<?php
/**
 * Blocs dynamiques du thème enfant (rendu PHP, aucun build).
 *
 * Tous lisent les informations du centre (inc/centre.php) ou les données
 * embarquées dans assets/data/ : aucune valeur n'est écrite dans les pages.
 *
 *   glassauto/horaires     Tableau des horaires, complet ou résumé
 *   glassauto/coordonnees  Adresse, téléphone, e-mail, itinéraire
 *   glassauto/diagnostic   Schéma interactif « Réparer ou remplacer ? »
 *   glassauto/communes     Communes de la zone : bandes de temps, secteurs ou tableau
 *   glassauto/carte-zone   Carte statique des communes autour du centre
 *   glassauto/plan-acces   Plan du quartier + bouton pour charger le plan interactif
 *
 * Côté éditeur, assets/js/editor-blocks.js les déclare avec un aperçu serveur.
 *
 * @package G2RD_GlassAuto
 */

declare( strict_types = 1 );

namespace G2RD_GlassAuto;

\defined( 'ABSPATH' ) || exit;

// Icônes (24 × 24, trait 1,8) — mêmes dessins que la maquette.

/**
 * Tracés des icônes.
 *
 * @return array<string,string>
 */
function icones(): array {
	return array(
		'tel'        => '<path d="M5 4.5h3l1.5 4-2 1.3a10.5 10.5 0 0 0 6.7 6.7l1.3-2 4 1.5v3a1.5 1.5 0 0 1-1.6 1.5C10.6 20 4 13.4 3.5 6.1A1.5 1.5 0 0 1 5 4.5z"/>',
		'pin'        => '<path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.4"/>',
		'horloge'    => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
		'mail'       => '<rect x="3" y="5.5" width="18" height="13" rx="1.5"/><path d="m3.5 6.5 8.5 6.5 8.5-6.5"/>',
		'itineraire' => '<path d="M12 2.5 21.5 12 12 21.5 2.5 12z"/><path d="M9 14v-2.5a1.5 1.5 0 0 1 1.5-1.5H15M13 8l2 2-2 2"/>',
		'calendrier' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/><path d="M7.5 13h.01M12 13h.01M16.5 13h.01M7.5 16.5h.01M12 16.5h.01" stroke-width="2.6"/>',
		'check'      => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
		'info'       => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5.5M12 7.8h.01" stroke-width="2.2"/>',
		'alerte'     => '<path d="M12 3.8 21 19.5H3z"/><path d="M12 10v4.5M12 17h.01" stroke-width="2.2"/>',
		'fleche'     => '<path d="M4 12h15M13.5 6.5 19 12l-5.5 5.5"/>',
		'cadenas'    => '<rect x="5" y="10.5" width="14" height="10" rx="2"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5M12 14.5v2.5"/>',
		'externe'    => '<path d="M14 4.5h5.5V10M19.5 4.5 11 13M17 14v4.5A1.5 1.5 0 0 1 15.5 20h-10A1.5 1.5 0 0 1 4 18.5v-10A1.5 1.5 0 0 1 5.5 7H10"/>',
		'bouclier'   => '<path d="M12 3l7.5 3v5.5c0 4.6-3.2 8.3-7.5 9.5-4.3-1.2-7.5-4.9-7.5-9.5V6z"/><path d="m8.8 12 2.2 2.2 4.4-4.4"/>',
	);
}

/**
 * Icône SVG inline.
 *
 * @param string $nom   Nom de l'icône.
 * @param string $class_name Classe CSS.
 * @param string $label Intitulé accessible ; vide = décorative.
 * @return string
 */
function icone( string $nom, string $class_name = 'ga-ico', string $label = '' ): string {
	$traces = icones()[ $nom ] ?? '';
	if ( '' === $traces ) {
		return '';
	}
	$a11y = '' !== $label
		? 'role="img" aria-label="' . \esc_attr( $label ) . '"'
		: 'aria-hidden="true" focusable="false"';
	// Tracés internes au thème (constante ci-dessus), jamais issus d'une saisie.
	return \sprintf(
		'<svg class="%1$s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" %2$s>%3$s</svg>',
		\esc_attr( $class_name ),
		$a11y,
		$traces
	);
}

/**
 * Enveloppe un rendu dans les attributs du bloc.
 *
 * @param string               $class_name Classe du bloc.
 * @param string               $inner Contenu.
 * @param string               $tag   Balise.
 * @param array<string,string> $extra Attributs supplémentaires.
 * @return string
 */
function block_wrap( string $class_name, string $inner, string $tag = 'div', array $extra = array() ): string {
	if ( '' === $inner ) {
		return '';
	}
	$attrs = \get_block_wrapper_attributes( \array_merge( array( 'class' => $class_name ), $extra ) );
	return \sprintf( '<%1$s %2$s>%3$s</%1$s>', $tag, $attrs, $inner );
}

// Données : communes.

/**
 * Communes de la zone, hors communes exclues dans les réglages.
 *
 * Distances et temps : calcul routier OSRM (données OpenStreetMap) depuis les
 * coordonnées du centre, hors trafic. Fichier assets/data/communes.json.
 *
 * @param bool $avec_exclues Inclure les communes exclues (page d'arbitrage).
 * @return array<int,array<string,mixed>>
 */
function communes( bool $avec_exclues = false ): array {
	static $toutes = null;
	if ( null === $toutes ) {
		$toutes = array();
		$path   = \get_stylesheet_directory() . '/assets/data/communes.json';
		if ( \is_readable( $path ) ) {
			$data   = \wp_json_file_decode( $path, array( 'associative' => true ) );
			$toutes = \is_array( $data ) ? $data : array();
		}
		\usort( $toutes, static fn( $a, $b ) => ( $a['route_km'] <=> $b['route_km'] ) );
	}
	if ( $avec_exclues ) {
		return $toutes;
	}
	$lignes  = \preg_split( '/\r\n|\r|\n/', (string) centre_get( 'communes_exclues' ) );
	$exclues = \array_map( 'trim', \is_array( $lignes ) ? $lignes : array() );
	$liste   = \array_values( \array_filter( $toutes, static fn( $c ) => ! \in_array( $c['nom'], $exclues, true ) ) );
	return (array) \apply_filters( 'glassauto_communes', $liste );
}

/**
 * Communes déléguées à mentionner (communes nouvelles).
 *
 * @return array<string,string>
 */
function communes_deleguees(): array {
	return (array) \apply_filters(
		'glassauto_communes_deleguees',
		array(
			'La Chapelle-Longueville' => 'Saint-Just, Saint-Pierre-d’Autils, La Chapelle-Réanville',
			"Le Val d'Hazey"          => 'Aubevoye, Sainte-Barbe-sur-Gaillon, Vieux-Villez',
		)
	);
}

/**
 * Secteurs géographiques de la page Zone d'intervention.
 *
 * @return array<int,array{titre:string,texte:string,noms:string[]}>
 */
function secteurs(): array {
	return (array) \apply_filters(
		'glassauto_secteurs',
		array(
			array(
				'titre' => 'Vernon et ses environs',
				'texte' => 'Les communes voisines du centre, de part et d’autre de la Seine.',
				'noms'  => array( 'Vernon', 'Saint-Marcel', 'La Chapelle-Longueville', 'Giverny', 'Houlbec-Cocherel' ),
			),
			array(
				'titre' => 'Vallée de la Seine, vers Gaillon et Les Andelys',
				'texte' => 'En aval de Vernon, sur la rive gauche comme sur la rive droite.',
				'noms'  => array( 'Gaillon', "Le Val d'Hazey", 'Courcelles-sur-Seine', 'Les Andelys' ),
			),
			array(
				'titre' => "Vallée de l'Eure et plaine de Saint-André",
				'texte' => "Au sud-ouest, de Pacy-sur-Eure jusqu'à la limite de l'Eure-et-Loir.",
				'noms'  => array( 'Pacy-sur-Eure', 'Ivry-la-Bataille', 'Ézy-sur-Eure', "Saint-André-de-l'Eure" ),
			),
			array(
				'titre' => 'Vexin normand',
				'texte' => "Au nord-est, vers la vallée de l'Epte et Gisors.",
				'noms'  => array( 'Gasny', 'Vexin-sur-Epte', 'Gisors' ),
			),
			array(
				'titre' => 'Yvelines',
				'texte' => 'En amont de Vernon le long de la Seine, et au sud jusqu’à Bréval.',
				'noms'  => array( 'Limetz-Villez', 'Gommecourt', 'Bonnières-sur-Seine', 'Freneuse', 'Rolleboise', 'Rosny-sur-Seine', 'Bréval', 'Mantes-la-Jolie', 'Mantes-la-Ville', 'Limay', 'Buchelay', 'Magnanville' ),
			),
		)
	);
}

/**
 * Formate une distance en kilomètres (virgule décimale).
 *
 * @param float $km Distance.
 * @return string
 */
function km( float $km ): string {
	return \number_format_i18n( $km, 1 ) . "\u{202F}km";
}

// Rendus.

/**
 * Rendu : horaires.
 *
 * @param array<string,mixed> $attributes Attributs.
 * @return string
 */
function render_horaires( array $attributes ): string {
	$c      = centre();
	$resume = ! empty( $attributes['resume'] );
	$lignes = array();
	$jours  = jours();

	if ( $resume ) {
		$semaine = \array_slice( $c['horaires'], 0, 5, true );
		if ( 1 === \count( \array_unique( $semaine ) ) ) {
			$lignes[] = array( \__( 'Lundi – vendredi', 'g2rd-glassauto-child' ), \reset( $semaine ) );
		} else {
			foreach ( $semaine as $jour => $h ) {
				$lignes[] = array( $jours[ $jour ], $h );
			}
		}
		$lignes[] = array( $jours['samedi'], $c['horaires']['samedi'] );
		$lignes[] = array( $jours['dimanche'], $c['horaires']['dimanche'] );
	} else {
		foreach ( $c['horaires'] as $jour => $h ) {
			$lignes[] = array( $jours[ $jour ] ?? $jour, $h );
		}
	}

	$html = '';
	foreach ( $lignes as [ $jour, $h ] ) {
		$html .= \sprintf( '<div><dt>%s</dt><dd>%s</dd></div>', \esc_html( $jour ), \esc_html( (string) $h ) );
	}
	return block_wrap( 'ga-hours', $html, 'dl' );
}

/**
 * Rendu : coordonnées.
 *
 * @param array<string,mixed> $attributes Attributs.
 * @return string
 */
function render_coordonnees( array $attributes ): string {
	$c        = centre();
	$elements = \array_map( 'trim', \explode( ',', (string) ( $attributes['elements'] ?? 'adresse,telephone,email' ) ) );
	$style    = 'cartes' === ( $attributes['style'] ?? '' ) ? 'cartes' : 'liste';
	$niveau   = \in_array( $attributes['niveauTitre'] ?? 'h3', array( 'h2', 'h3', 'h4', 'p' ), true ) ? $attributes['niveauTitre'] : 'h3';
	$items    = array();

	if ( \in_array( 'adresse', $elements, true ) ) {
		$items[] = array( 'pin', \__( 'Adresse', 'g2rd-glassauto-child' ), \sprintf( '<strong>%s</strong><br>%s<br>%s %s', \esc_html( $c['nom'] ), \esc_html( $c['rue'] ), \esc_html( $c['cp'] ), \esc_html( $c['ville'] ) ), itineraire_url() );
	}
	if ( \in_array( 'telephone', $elements, true ) && '' !== $c['tel'] ) {
		$items[] = array( 'tel', \__( 'Téléphone', 'g2rd-glassauto-child' ), \sprintf( '<a class="ga-coord__big" href="%s">%s</a>', \esc_url( tel_href() ), \esc_html( $c['tel'] ) ), '' );
	}
	if ( \in_array( 'email', $elements, true ) && '' !== $c['email'] ) {
		$items[] = array( 'mail', \__( 'E-mail', 'g2rd-glassauto-child' ), \sprintf( '<a href="mailto:%1$s">%1$s</a>', \esc_attr( $c['email'] ) ), '' );
	}
	if ( \in_array( 'horaires', $elements, true ) ) {
		$items[] = array( 'horloge', \__( 'Horaires', 'g2rd-glassauto-child' ), render_horaires( array() ), '' );
	}
	if ( \in_array( 'itineraire', $elements, true ) ) {
		$items[] = array( 'itineraire', \__( 'Itinéraire', 'g2rd-glassauto-child' ), \sprintf( '<a class="ga-link-u" href="%s">%s %s</a>', \esc_url( itineraire_url() ), \esc_html__( 'Calculer mon itinéraire', 'g2rd-glassauto-child' ), icone( 'fleche' ) ), '' );
	}

	$html = '';
	foreach ( $items as [ $ico, $titre, $contenu, $lien ] ) {
		$html .= \sprintf(
			'<li class="ga-coord">%1$s<div><%2$s class="ga-coord__title">%3$s</%2$s><div class="ga-coord__body">%4$s</div></div></li>',
			icone( $ico ),
			\esc_html( $niveau ),
			\esc_html( $titre ),
			$contenu // Contenu construit ci-dessus, échappé champ par champ.
		);
	}
	return block_wrap( 'ga-coords ga-coords--' . $style, $html, 'ul' );
}

/**
 * Verdicts du diagnostic « Réparer ou remplacer ? ».
 *
 * Indications générales ; le technicien confirme au centre. Critères à
 * valider par le centre (filtre glassauto_diagnostic_verdicts).
 *
 * @return array<string,array<string,string|int>>
 */
function diagnostic_verdicts(): array {
	return (array) \apply_filters(
		'glassauto_diagnostic_verdicts',
		array(
			'ok'     => array(
				'x'      => 430,
				'y'      => 186,
				'chip'   => 'menthe',
				'label'  => \__( 'Réparation généralement possible', 'g2rd-glassauto-child' ),
				'titre'  => \__( 'Votre impact peut sans doute être réparé', 'g2rd-glassauto-child' ),
				'texte'  => \__( "Hors du champ de vision et plus petit qu'une pièce de 2 €, un impact se répare en général par injection de résine, sans changer le pare-brise. Plus vous venez tôt, plus la réparation a de chances de réussir.", 'g2rd-glassauto-child' ),
				'option' => \__( "Hors du champ de vision, plus petit qu'une pièce de 2 €", 'g2rd-glassauto-child' ),
			),
			'vision' => array(
				'x'      => 203,
				'y'      => 182,
				'chip'   => 'fuchsia',
				'label'  => \__( 'Remplacement généralement nécessaire', 'g2rd-glassauto-child' ),
				'titre'  => \__( 'Un impact dans votre champ de vision', 'g2rd-glassauto-child' ),
				'texte'  => \__( 'Une réparation laisserait une trace dans la zone que le conducteur doit voir parfaitement. Le remplacement du pare-brise est alors en principe nécessaire.', 'g2rd-glassauto-child' ),
				'option' => \__( 'Dans le champ de vision du conducteur', 'g2rd-glassauto-child' ),
			),
			'bord'   => array(
				'x'      => 581,
				'y'      => 186,
				'chip'   => 'ambre',
				'label'  => \__( 'Remplacement souvent conseillé', 'g2rd-glassauto-child' ),
				'titre'  => \__( 'Un impact proche du bord', 'g2rd-glassauto-child' ),
				'texte'  => \__( 'Près du cadre, le verre est plus sollicité et un impact se transforme facilement en fissure. Le technicien vérifie si une réparation reste possible ; sinon, il remplace le pare-brise.', 'g2rd-glassauto-child' ),
				'option' => \__( 'Près du bord du pare-brise', 'g2rd-glassauto-child' ),
			),
			'grand'  => array(
				'x'      => 400,
				'y'      => 224,
				'chip'   => 'fuchsia',
				'label'  => \__( 'Remplacement nécessaire', 'g2rd-glassauto-child' ),
				'titre'  => \__( "Une fissure ou un impact plus large qu'une pièce de 2 €", 'g2rd-glassauto-child' ),
				'texte'  => \__( "Une fissure ne se répare pas : le pare-brise doit être remplacé pour retrouver la solidité et la visibilité d'origine.", 'g2rd-glassauto-child' ),
				'option' => \__( "Fissure, ou impact plus grand qu'une pièce de 2 €", 'g2rd-glassauto-child' ),
			),
		)
	);
}

/**
 * Rendu : diagnostic interactif.
 *
 * Sans JavaScript, le premier verdict s'affiche et les options restent des
 * boutons radio accessibles ; le script (glassauto.js) anime le schéma.
 *
 * @return string
 */
function render_diagnostic(): string {
	$verdicts = diagnostic_verdicts();
	$premier  = \reset( $verdicts );
	$cle_1    = (string) \key( $verdicts );
	$rdv      = jetons()['{{reservation}}'];
	$uid      = \wp_unique_id( 'ga-diag-' );

	$options = '';
	foreach ( $verdicts as $cle => $v ) {
		$options .= \sprintf(
			'<label class="ga-diag-opt"><input type="radio" name="%1$s" value="%2$s"%3$s> %4$s</label>',
			\esc_attr( $uid ),
			\esc_attr( (string) $cle ),
			$cle === $cle_1 ? ' checked' : '',
			\esc_html( (string) $v['option'] )
		);
	}

	$svg = '<svg class="ga-diag__svg" viewBox="0 0 640 380" role="img" aria-labelledby="' . \esc_attr( $uid ) . '-t ' . \esc_attr( $uid ) . '-d">'
		. '<title id="' . \esc_attr( $uid ) . '-t">' . \esc_html__( "Schéma d'un pare-brise vu de l'intérieur", 'g2rd-glassauto-child' ) . '</title>'
		. '<desc id="' . \esc_attr( $uid ) . '-d">' . \esc_html__( 'Le champ de vision du conducteur est à gauche, devant le volant. Les bords forment une bande tout autour du vitrage. Le reste de la surface est généralement réparable.', 'g2rd-glassauto-child' ) . '</desc>'
		. '<defs><pattern id="' . \esc_attr( $uid ) . '-h" width="10" height="10" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><rect width="10" height="10" fill="rgba(44,174,196,.08)"/><line x1="0" y1="0" x2="0" y2="10" stroke="rgba(44,174,196,.55)" stroke-width="2"/></pattern></defs>'
		. '<path class="ga-diag__glass" d="M70 64 Q320 8 570 64 L612 296 Q320 340 28 296 Z"/>'
		. '<path data-zone="bord" d="M70 64 Q320 8 570 64 L612 296 Q320 340 28 296 Z M92 86 Q320 36 548 86 L584 278 Q320 316 56 278 Z" fill="rgba(242,183,5,.08)" fill-rule="evenodd"/>'
		. '<path class="ga-diag__edge" d="M92 86 Q320 36 548 86 L584 278 Q320 316 56 278 Z" pointer-events="none"/>'
		. '<rect data-zone="ok" class="ga-diag__ok" x="300" y="104" width="236" height="160" rx="14"/>'
		. '<rect data-zone="vision" class="ga-diag__vision" x="128" y="100" width="150" height="166" rx="14" fill="url(#' . \esc_attr( $uid ) . '-h)"/>'
		. '<text class="ga-diag__lbl ga-diag__lbl--e" x="320" y="58" text-anchor="middle">' . \esc_html__( 'BORDS', 'g2rd-glassauto-child' ) . '</text>'
		. '<text class="ga-diag__lbl ga-diag__lbl--v" x="203" y="128" text-anchor="middle">' . \esc_html__( 'CHAMP DE VISION', 'g2rd-glassauto-child' ) . '</text>'
		. '<text class="ga-diag__lbl ga-diag__lbl--ok" x="418" y="132" text-anchor="middle">' . \esc_html__( 'ZONE RÉPARABLE', 'g2rd-glassauto-child' ) . '</text>'
		. '<ellipse class="ga-diag__wheel" cx="203" cy="360" rx="74" ry="16"/>'
		. '<text class="ga-diag__lbl ga-diag__lbl--s" x="203" y="345" text-anchor="middle">' . \esc_html__( 'VOLANT', 'g2rd-glassauto-child' ) . '</text>'
		. '<g class="ga-diag__impact" transform="translate(' . (int) $premier['x'] . ' ' . (int) $premier['y'] . ')" pointer-events="none"><circle r="9"/><circle r="17" opacity=".5"/></g>'
		. '</svg>';

	$data = array();
	foreach ( $verdicts as $cle => $v ) {
		$data[ $cle ] = array(
			'x'     => (int) $v['x'],
			'y'     => (int) $v['y'],
			'chip'  => (string) $v['chip'],
			'label' => (string) $v['label'],
			'titre' => (string) $v['titre'],
			'texte' => (string) $v['texte'],
		);
	}

	$inner = '<div class="ga-diag__visual">' . $svg . '</div>'
		. '<div class="ga-diag__panel">'
		. '<fieldset class="ga-diag__options"><legend>' . \esc_html__( 'Où se situe le dommage ?', 'g2rd-glassauto-child' ) . '</legend>' . $options . '</fieldset>'
		. '<div class="ga-verdict ga-corner ga-corner--on-dark" data-verdict aria-live="polite">'
		. '<p class="ga-chip ga-chip--' . \esc_attr( (string) $premier['chip'] ) . ' ga-verdict__label">' . \esc_html( (string) $premier['label'] ) . '</p>'
		. '<h3 class="ga-verdict__title">' . \esc_html( (string) $premier['titre'] ) . '</h3>'
		. '<p class="ga-verdict__text" data-verdict-text>' . \esc_html( (string) $premier['texte'] ) . '</p>'
		. '<a class="wp-block-button__link wp-element-button ga-btn--rdv" href="' . \esc_url( $rdv ) . '">' . icone( 'calendrier' ) . ' ' . \esc_html__( 'Faire vérifier mon pare-brise', 'g2rd-glassauto-child' ) . '</a>'
		. '</div>'
		. '<p class="ga-diag__note">' . \esc_html__( 'Indication générale : le technicien confirme le diagnostic au centre, après examen du vitrage.', 'g2rd-glassauto-child' ) . '</p>'
		. '</div>';

	return block_wrap( 'ga-diag', $inner, 'div', array( 'data-diag' => (string) \wp_json_encode( $data ) ) );
}

/**
 * Pastille de commune.
 *
 * @param array<string,mixed> $c       Commune.
 * @param bool                $avec_km Afficher la distance.
 * @return string
 */
function pastille_commune( array $c, bool $avec_km ): string {
	$deleguees = communes_deleguees()[ $c['nom'] ] ?? '';
	$temps     = ( $avec_km ? km( (float) $c['route_km'] ) . ' · ' : '' ) . (int) $c['route_min'] . "\u{202F}min";
	return \sprintf(
		'<li class="ga-town"%1$s>%2$s<span class="ga-town__dep ga-town__dep--%3$s">%3$s</span><span class="ga-town__t">%4$s</span></li>',
		'' !== $deleguees ? ' title="' . \esc_attr( \__( 'Communes déléguées : ', 'g2rd-glassauto-child' ) . $deleguees ) . '"' : '',
		\esc_html( (string) $c['nom'] ),
		\esc_attr( (string) $c['dep'] ),
		\esc_html( $temps )
	);
}

/**
 * Rendu : communes.
 *
 * @param array<string,mixed> $attributes Attributs.
 * @return string
 */
function render_communes( array $attributes ): string {
	$affichage = (string) ( $attributes['affichage'] ?? 'bandes' );
	$avec_km   = ! empty( $attributes['avecKm'] );
	$liste     = communes();
	if ( empty( $liste ) ) {
		return '';
	}

	if ( 'tableau' === $affichage ) {
		$rows = '';
		foreach ( $liste as $c ) {
			$deleguees = communes_deleguees()[ $c['nom'] ] ?? '';
			$rows     .= \sprintf(
				'<tr><th scope="row">%1$s%2$s</th><td>%3$s</td><td class="ga-num">%4$s</td><td class="ga-num">%5$s</td></tr>',
				\esc_html( (string) $c['nom'] ),
				'' !== $deleguees ? '<br><span class="ga-note">' . \esc_html( \__( 'Dont ', 'g2rd-glassauto-child' ) . $deleguees ) . '</span>' : '',
				'27' === (string) $c['dep'] ? \esc_html__( 'Eure (27)', 'g2rd-glassauto-child' ) : \esc_html__( 'Yvelines (78)', 'g2rd-glassauto-child' ),
				\esc_html( km( (float) $c['route_km'] ) ),
				\esc_html( (int) $c['route_min'] . "\u{202F}min" )
			);
		}
		$table = '<table class="ga-table"><caption class="screen-reader-text">' . \esc_html__( 'Distances et temps de trajet indicatifs entre chaque commune et le centre GlassAuto Vernon', 'g2rd-glassauto-child' ) . '</caption>'
			. '<thead><tr><th scope="col">' . \esc_html__( 'Commune', 'g2rd-glassauto-child' ) . '</th><th scope="col">' . \esc_html__( 'Département', 'g2rd-glassauto-child' ) . '</th><th scope="col" class="ga-num">' . \esc_html__( 'Distance par la route', 'g2rd-glassauto-child' ) . '</th><th scope="col" class="ga-num">' . \esc_html__( 'Temps indicatif', 'g2rd-glassauto-child' ) . '</th></tr></thead>'
			. '<tbody>' . $rows . '</tbody></table>';
		return block_wrap(
			'ga-table-wrap',
			$table,
			'div',
			array(
				'tabindex'   => '0',
				'role'       => 'region',
				'aria-label' => \__( 'Temps de trajet jusqu’au centre', 'g2rd-glassauto-child' ),
			)
		);
	}

	if ( 'secteurs' === $affichage ) {
		$html = '';
		foreach ( secteurs() as $s ) {
			$cs = \array_values( \array_filter( $liste, static fn( $c ) => \in_array( $c['nom'], $s['noms'], true ) ) );
			if ( empty( $cs ) ) {
				continue;
			}
			$temps = \array_map( static fn( $c ) => (int) $c['route_min'], $cs );
			$html .= \sprintf(
				'<article class="ga-sector"><p class="ga-chip ga-chip--turquoise ga-chip--plain">%1$s</p><h3>%2$s</h3><p>%3$s</p><ul>%4$s</ul></article>',
				/* translators: 1 : temps minimal, 2 : temps maximal (minutes). */
				\esc_html( \sprintf( \__( '%1$d à %2$d min', 'g2rd-glassauto-child' ), \min( $temps ), \max( $temps ) ) ),
				\esc_html( $s['titre'] ),
				\esc_html( $s['texte'] ),
				\implode( '', \array_map( static fn( $c ) => '<li>' . \esc_html( (string) $c['nom'] ) . '</li>', $cs ) )
			);
		}
		return block_wrap( 'ga-sectors', $html );
	}

	// Bandes de temps de trajet.
	$bandes = array(
		array( \__( 'Moins de 15 minutes', 'g2rd-glassauto-child' ), 0, 15 ),
		array( \__( '15 à 20 minutes', 'g2rd-glassauto-child' ), 15, 20 ),
		array( \__( '20 à 30 minutes', 'g2rd-glassauto-child' ), 20, 30 ),
		array( \__( '30 à 40 minutes', 'g2rd-glassauto-child' ), 30, 40 ),
		array( \__( 'Plus de 40 minutes', 'g2rd-glassauto-child' ), 40, 999 ),
	);
	$html   = '';
	foreach ( $bandes as [ $titre, $min, $max ] ) {
		$cs = \array_filter( $liste, static fn( $c ) => $c['route_min'] > $min && $c['route_min'] <= $max );
		if ( empty( $cs ) ) {
			continue;
		}
		$html .= '<div class="ga-band"><p class="ga-band__title">' . \esc_html( $titre ) . '</p><ul class="ga-towns">';
		foreach ( $cs as $c ) {
			$html .= pastille_commune( $c, $avec_km );
		}
		$html .= '</ul></div>';
	}
	return block_wrap( 'ga-bands', $html );
}

/**
 * Projection Web Mercator → pixels dans une carte assemblée.
 *
 * @param array<string,mixed> $m   Métadonnées de la carte (maps.json).
 * @param float               $lat Latitude.
 * @param float               $lon Longitude.
 * @return array{0:float,1:float}
 */
function projeter( array $m, float $lat, float $lon ): array {
	$n = 2 ** (int) $m['z'];
	$x = ( ( $lon + 180 ) / 360 ) * $n;
	$r = \deg2rad( $lat );
	$y = ( ( 1 - \log( \tan( $r ) + 1 / \cos( $r ) ) / M_PI ) / 2 ) * $n;
	return array( ( $x - (float) $m['x0'] ) * 256, ( $y - (float) $m['y0'] ) * 256 );
}

/**
 * Métadonnées des cartes assemblées.
 *
 * @return array<string,array<string,mixed>>
 */
function cartes(): array {
	static $maps = null;
	if ( null === $maps ) {
		$path = \get_stylesheet_directory() . '/assets/data/maps.json';
		$data = \is_readable( $path ) ? \wp_json_file_decode( $path, array( 'associative' => true ) ) : null;
		$maps = \is_array( $data ) ? $data : array();
	}
	return $maps;
}

/**
 * Rendu : carte statique de la zone.
 *
 * @param array<string,mixed> $attributes Attributs.
 * @return string
 */
function render_carte_zone( array $attributes ): string {
	$m = cartes()['region-vernon-z11'] ?? null;
	if ( ! $m ) {
		return '';
	}
	$c           = centre();
	$lat         = (float) $c['lat'];
	$lon         = (float) $c['lon'];
	[ $cx, $cy ] = projeter( $m, $lat, $lon );
	$mpp         = ( 156543.03392 * \cos( \deg2rad( $lat ) ) ) / 2 ** (int) $m['z'];
	$size        = 1180;
	$vx          = (int) \round( \min( \max( $cx - $size / 2, 0 ), $m['width'] - $size ) );
	$vy          = (int) \round( \min( \max( $cy - $size / 2 + 40, 0 ), $m['height'] - $size ) );
	$legend      = ! isset( $attributes['legende'] ) || ! empty( $attributes['legende'] );

	// Décalage des libellés pour éviter les chevauchements (dx, dy, ancre).
	$labels = (array) \apply_filters(
		'glassauto_carte_libelles',
		array(
			'Giverny'               => array( 16, -10, 'start' ),
			'Gasny'                 => array( 16, 8, 'start' ),
			'Gaillon'               => array( -14, 22, 'end' ),
			'Pacy-sur-Eure'         => array( -16, 8, 'end' ),
			'Les Andelys'           => array( 16, 8, 'start' ),
			'Gisors'                => array( 16, 8, 'start' ),
			'Bonnières-sur-Seine'   => array( 16, 20, 'start' ),
			'Rosny-sur-Seine'       => array( 16, 8, 'start' ),
			"Saint-André-de-l'Eure" => array( -16, 8, 'end' ),
			'Ivry-la-Bataille'      => array( 16, 8, 'start' ),
			'Bréval'                => array( 16, 8, 'start' ),
			'Vexin-sur-Epte'        => array( 16, -6, 'start' ),
			"Le Val d'Hazey"        => array( -16, -12, 'end' ),
			'Ézy-sur-Eure'          => array( -16, 8, 'end' ),
			'Mantes-la-Jolie'       => array( 16, -6, 'start' ),
			'Évreux'                => array( -16, 8, 'end' ),
			'Louviers'              => array( -16, 8, 'end' ),
		)
	);

	$svg = '';
	foreach ( array( 10, 20, 30 ) as $kmr ) {
		$r    = ( $kmr * 1000 ) / $mpp;
		$svg .= \sprintf( '<circle class="ga-map__ring" cx="%.1f" cy="%.1f" r="%.1f"/><text class="ga-map__ring-lbl" x="%.1f" y="%.1f" text-anchor="middle">%s</text>', $cx, $cy, $r, $cx + $r * 0.17, $cy + $r * 0.985 - 8, \esc_html( $kmr . "\u{202F}km" ) );
	}
	foreach ( communes() as $t ) {
		if ( \in_array( $t['nom'], array( 'Vernon', 'Saint-Marcel' ), true ) ) {
			continue;
		}
		[ $px, $py ] = projeter( $m, (float) $t['lat'], (float) $t['lon'] );
		$l           = $labels[ $t['nom'] ] ?? null;
		$txt         = $l ? \sprintf( '<text x="%.1f" y="%.1f" text-anchor="%s">%s</text>', $px + $l[0], $py + $l[1] + 7, \esc_attr( $l[2] ), \esc_html( (string) $t['nom'] ) ) : '';
		$svg        .= \sprintf(
			'<g class="ga-map__town ga-map__town--%1$s"><circle cx="%2$.1f" cy="%3$.1f" r="7"><title>%4$s</title></circle>%5$s</g>',
			\esc_attr( (string) $t['dep'] ),
			$px,
			$py,
			\esc_html( \sprintf( '%s (%s) · %s · %d min', $t['nom'], $t['dep'], km( (float) $t['route_km'] ), (int) $t['route_min'] ) ),
			$txt
		);
	}
	$svg .= \sprintf(
		'<g class="ga-map__centre"><circle class="r" cx="%1$.1f" cy="%2$.1f" r="26"/><circle class="c" cx="%1$.1f" cy="%2$.1f" r="11"/><text x="%3$.1f" y="%4$.1f" text-anchor="end">%5$s</text></g>',
		$cx,
		$cy,
		$cx - 24,
		$cy - 22,
		\esc_html( (string) $c['nom'] )
	);

	$img   = \get_stylesheet_directory_uri() . '/assets/img/maps/region-vernon-z11.webp';
	$inner = '<div class="ga-map__frame">'
		. '<svg viewBox="' . $vx . ' ' . $vy . ' ' . $size . ' ' . $size . '" role="img" aria-label="' . \esc_attr__( 'Carte des communes situées autour du centre GlassAuto de Vernon, avec cercles de 10, 20 et 30 km', 'g2rd-glassauto-child' ) . '">'
		. '<image class="ga-map__tiles" href="' . \esc_url( $img ) . '" x="0" y="0" width="' . (int) $m['width'] . '" height="' . (int) $m['height'] . '"/>'
		. '<rect class="ga-map__tint" x="0" y="0" width="' . (int) $m['width'] . '" height="' . (int) $m['height'] . '"/>'
		. $svg . '</svg>'
		. '<p class="ga-osm">© <a href="https://www.openstreetmap.org/copyright">' . \esc_html__( 'contributeurs OpenStreetMap', 'g2rd-glassauto-child' ) . '</a></p>'
		. '</div>';
	if ( $legend ) {
		$inner .= '<p class="ga-map__legend"><span><i class="lc"></i> ' . \esc_html( (string) $c['nom'] ) . '</span><span><i></i> ' . \esc_html__( 'Eure (27)', 'g2rd-glassauto-child' ) . '</span><span><i class="l78"></i> ' . \esc_html__( 'Yvelines (78)', 'g2rd-glassauto-child' ) . '</span><span><i class="lr"></i> ' . \esc_html__( "Distance à vol d'oiseau", 'g2rd-glassauto-child' ) . '</span></p>';
	}
	return block_wrap( 'ga-map-card', $inner, 'figure' );
}

/**
 * Rendu : plan d'accès (image statique + plan interactif après clic).
 *
 * Le plan OpenStreetMap interactif n'est chargé qu'après action de
 * l'utilisateur : aucune requête tierce sans consentement.
 *
 * @return string
 */
function render_plan_acces(): string {
	$m = cartes()['centre-vernon-z16'] ?? null;
	if ( ! $m ) {
		return '';
	}
	$c           = centre();
	$lat         = (float) $c['lat'];
	$lon         = (float) $c['lon'];
	[ $px, $py ] = projeter( $m, $lat, $lon );
	$pxp         = $px / (float) $m['width'] * 100;
	$pyp         = $py / (float) $m['height'] * 100;
	$uid         = \wp_unique_id( 'ga-plan-' );
	$box         = \sprintf( '%.4f,%.4f,%.4f,%.4f', $lon - 0.0131, $lat - 0.0061, $lon + 0.0131, $lat + 0.0061 );
	$src         = 'https://www.openstreetmap.org/export/embed.html?bbox=' . \rawurlencode( $box ) . '&layer=mapnik&marker=' . \rawurlencode( $lat . ',' . $lon );

	$inner = \sprintf(
		'<div class="ga-plan__map" id="%1$s" data-iframe-src="%2$s" data-iframe-title="%3$s"><img src="%4$s" alt="%5$s" loading="lazy" width="%6$d" height="%7$d" style="--map-pos:%8$.1f%% %9$.1f%%"><span class="ga-plan__pin" style="--pin-x:%8$.2f%%;--pin-y:%9$.2f%%"><span class="ga-impact"></span><span class="ga-plan__pin-label">%10$s</span></span><p class="ga-osm">© <a href="https://www.openstreetmap.org/copyright">%11$s</a></p></div>'
		. '<button class="ga-plan__consent" type="button" data-load-map="#%1$s">%12$s %13$s</button>',
		\esc_attr( $uid ),
		\esc_url( $src ),
		/* translators: %s : nom et adresse du centre. */
		\esc_attr( \sprintf( \__( 'Plan interactif : %s', 'g2rd-glassauto-child' ), $c['nom'] . ', ' . adresse_ligne() ) ),
		\esc_url( \get_stylesheet_directory_uri() . '/assets/img/maps/centre-vernon-z16.webp' ),
		/* translators: %s : adresse du centre. */
		\esc_attr( \sprintf( \__( 'Plan du quartier avec l’emplacement du centre, %s', 'g2rd-glassauto-child' ), adresse_ligne() ) ),
		(int) $m['width'],
		(int) $m['height'],
		$pxp,
		$pyp,
		\esc_html( (string) $c['nom'] ),
		\esc_html__( 'contributeurs OpenStreetMap', 'g2rd-glassauto-child' ),
		icone( 'pin' ),
		\esc_html__( 'Afficher le plan interactif', 'g2rd-glassauto-child' )
	);
	return block_wrap( 'ga-plan', $inner );
}

/**
 * Rendu : avis Google.
 *
 * Reproduit le markup du bloc g2rd/testimonial en mode Google (bloc statique
 * du parent) et charge son script et sa feuille : le rendu reste dynamique,
 * donc jamais invalidé dans l'éditeur, et le Place ID vient des réglages.
 *
 * @param array<string,mixed> $attributes Attributs.
 * @return string
 */
function render_avis_google( array $attributes ): string {
	$place = (string) centre_get( 'place_id' );
	$max   = \max( 1, \min( 5, \absint( $attributes['max'] ?? 3 ) ) );
	$min   = \max( 1, \min( 5, \absint( $attributes['noteMin'] ?? 4 ) ) );

	if ( \wp_style_is( 'g2rd-testimonial-style', 'registered' ) ) {
		\wp_enqueue_style( 'g2rd-testimonial-style' );
	}
	if ( \wp_script_is( 'g2rd-testimonial-view-script', 'registered' ) ) {
		\wp_enqueue_script( 'g2rd-testimonial-view-script' );
	}

	$data  = array(
		'data-google-place-id'           => $place,
		'data-google-min-rating'         => (string) $min,
		'data-google-max-reviews'        => (string) $max,
		'data-google-layout'             => 'grid',
		'data-google-columns'            => '3',
		'data-google-card-style'         => 'shadow',
		'data-google-show-header'        => 'true',
		'data-google-show-avatar'        => 'true',
		'data-google-show-date'          => 'true',
		'data-google-show-author-link'   => 'false',
		'data-google-max-text'           => '220',
		'data-google-highlight-first'    => 'false',
		'data-google-marquee-speed'      => '40',
		'data-google-show-business-link' => 'true',
		'aria-busy'                      => 'true',
	);
	$inner = '<div class="g2rd-testimonial__google-header is-skeleton" aria-hidden="true"></div>' . \str_repeat( '<div class="g2rd-testimonial__card is-skeleton" aria-hidden="true"></div>', $max );
	return block_wrap( 'g2rd-testimonial g2rd-testimonial--google ga-avis-google', $inner, 'div', $data );
}

// Enregistrement.

/**
 * Déclaration des blocs : nom → attributs + rendu.
 *
 * @return array<string,array<string,mixed>>
 */
function blocs(): array {
	return array(
		'glassauto/horaires'    => array(
			'attributes' => array(
				'resume' => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
			'callback'   => __NAMESPACE__ . '\render_horaires',
		),
		'glassauto/coordonnees' => array(
			'attributes' => array(
				'elements'    => array(
					'type'    => 'string',
					'default' => 'adresse,telephone,email',
				),
				'style'       => array(
					'type'    => 'string',
					'default' => 'liste',
				),
				'niveauTitre' => array(
					'type'    => 'string',
					'default' => 'h3',
				),
			),
			'callback'   => __NAMESPACE__ . '\render_coordonnees',
		),
		'glassauto/diagnostic'  => array(
			'attributes' => array(),
			'callback'   => __NAMESPACE__ . '\render_diagnostic',
		),
		'glassauto/communes'    => array(
			'attributes' => array(
				'affichage' => array(
					'type'    => 'string',
					'default' => 'bandes',
				),
				'avecKm'    => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
			'callback'   => __NAMESPACE__ . '\render_communes',
		),
		'glassauto/carte-zone'  => array(
			'attributes' => array(
				'legende' => array(
					'type'    => 'boolean',
					'default' => true,
				),
			),
			'callback'   => __NAMESPACE__ . '\render_carte_zone',
		),
		'glassauto/plan-acces'  => array(
			'attributes' => array(),
			'callback'   => __NAMESPACE__ . '\render_plan_acces',
		),
		'glassauto/avis-google' => array(
			'attributes' => array(
				'max'     => array(
					'type'    => 'integer',
					'default' => 3,
				),
				'noteMin' => array(
					'type'    => 'integer',
					'default' => 4,
				),
			),
			'callback'   => __NAMESPACE__ . '\render_avis_google',
		),
	);
}

\add_action(
	'init',
	function (): void {
		foreach ( blocs() as $name => $block ) {
			\register_block_type(
				$name,
				array(
					'api_version'     => 3,
					'render_callback' => $block['callback'],
					'attributes'      => $block['attributes'],
					'supports'        => array(
						'html'    => false,
						'align'   => array( 'wide', 'full' ),
						'spacing' => array( 'margin' => true ),
					),
				)
			);
		}
	}
);

\add_filter(
	'block_categories_all',
	static function ( array $categories ): array {
		\array_unshift(
			$categories,
			array(
				'slug'  => 'glassauto',
				'title' => \__( 'GlassAuto Vernon', 'g2rd-glassauto-child' ),
				'icon'  => null,
			)
		);
		return $categories;
	}
);

\add_action(
	'enqueue_block_editor_assets',
	function (): void {
		$js = \get_stylesheet_directory() . '/assets/js/editor-blocks.js';
		if ( ! \is_readable( $js ) ) {
			return;
		}
		\wp_enqueue_script(
			'glassauto-editor-blocks',
			\get_stylesheet_directory_uri() . '/assets/js/editor-blocks.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
			(string) \filemtime( $js ),
			true
		);
		\wp_set_script_translations( 'glassauto-editor-blocks', TEXTDOMAIN, \get_stylesheet_directory() . '/languages' );
		// Le script du front anime aussi l'aperçu du diagnostic dans l'éditeur.
		$front = \get_stylesheet_directory() . '/assets/js/glassauto.js';
		if ( \is_readable( $front ) ) {
			\wp_enqueue_script( 'glassauto', \get_stylesheet_directory_uri() . '/assets/js/glassauto.js', array(), (string) \filemtime( $front ), true );
		}
	}
);

// Tableaux : la figure défile horizontalement sur petit écran (style du cœur) ;
// sans tabindex, cette zone n'est pas atteignable au clavier (axe
// « scrollable-region-focusable »). On l'ajoute au rendu pour ne pas toucher
// au markup enregistré du bloc.
\add_filter(
	'render_block_core/table',
	static function ( $html ): string {
		return (string) \preg_replace( '/<figure class="wp-block-table([^"]*)"(?![^>]*tabindex)/', '<figure class="wp-block-table$1" tabindex="0"', (string) $html, 1 );
	}
);
