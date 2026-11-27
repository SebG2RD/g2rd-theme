<?php
/**
 * Boîte à outils opérationnels : registre, blocs rescue18/outils et rescue18/outil.
 *
 * Chaque outil a sa propre page (/outils/<outil>/) pour le référencement :
 * titre, description, méthode de calcul, sources officielles, questions
 * fréquentes et données structurées viennent de ce registre unique.
 * Le calcul tourne dans le navigateur (assets/js/outils.js), en direct,
 * sans envoi de données. Les valeurs saisies se retrouvent dans l'URL :
 * un résultat se partage par simple lien.
 *
 * Sources vérifiées le 30 octobre 2026 :
 * - DPS : référentiel national des missions de sécurité civile « DPS »
 *   (arrêté du 7 novembre 2006), grille RIS, seuils, effectifs.
 * - ARI : guide de l'apprenant ARI, SDIS de l'Ain (T = P × V / Q, 90 L/min,
 *   sifflet 55 bar, retour à mi-pression) et fiche INC-FTO-07.3
 *   « Techniques de gestion de l'air » (fourchettes de consommation).
 * - Messages : ENSOSP, mémento chef de groupe (ambiance, compte rendu,
 *   ordre initial) et GDO Exercice du commandement, 2e édition juin 2020
 *   (ordre préparatoire PATRACDR, ordre de mouvement DPIF).
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18;

\defined( 'ABSPATH' ) || exit;

/**
 * Missions (filtres du catalogue).
 *
 * @return array<string,string>
 */
function outil_missions(): array {
	$labels = (array) ( outils_reglages()['_global']['missions'] ?? array() );
	$out    = array();
	foreach ( outil_missions_defaut() as $key => $label ) {
		$out[ $key ] = '' !== \trim( (string) ( $labels[ $key ] ?? '' ) ) ? (string) $labels[ $key ] : $label;
	}
	return $out;
}

/**
 * Missions par défaut.
 *
 * @return array<string,string>
 */
function outil_missions_defaut(): array {
	return array(
		'secours-personne' => 'Secours à personne',
		'incendie'         => 'Incendie',
		'commandement'     => 'Commandement',
		'statut'           => 'Statut et carrière',
	);
}

/**
 * Réglages enregistrés dans Boîte à outils (back-office).
 *
 * @return array<string,mixed>
 */
function outils_reglages(): array {
	$r = \get_option( 'rescue18_outils', array() );
	return \is_array( $r ) ? $r : array();
}

/**
 * Texte d'avertissement commun aux outils.
 *
 * @return string
 */
function outil_avertissement(): string {
	$t = (string) ( outils_reglages()['_global']['avertissement'] ?? '' );
	return '' !== \trim( $t ) ? $t : outil_avertissement_defaut();
}

/**
 * Avertissement par défaut.
 *
 * @return string
 */
function outil_avertissement_defaut(): string {
	return 'Outil d\'aide à la décision. Il ne remplace ni le jugement du commandant des opérations ni la doctrine de ton département.';
}

/**
 * Registre fusionné : valeurs par défaut du thème + réglages du back-office.
 *
 * Les valeurs d'options marquées « lock » (coefficients réglementaires,
 * types de message lus par le script) restent figées : seuls leurs
 * libellés changent.
 *
 * @param bool $actifs_seulement Ne garder que les outils activés.
 * @return array<string,array<string,mixed>>
 */
function outils( bool $actifs_seulement = false ): array {
	$reg    = outils_reglages();
	$all    = array();
	$ordre  = 0;
	$scalar = array( 'title', 'short', 'seo_title', 'description', 'lead', 'gives', 'ref', 'mission', 'icon' );
	foreach ( outils_defaut() as $slug => $o ) {
		$r          = (array) ( $reg[ $slug ] ?? array() );
		$ordre     += 10;
		$o['actif'] = ! isset( $r['actif'] ) || (bool) $r['actif'];
		$o['ordre'] = isset( $r['ordre'] ) && '' !== (string) $r['ordre'] ? (int) $r['ordre'] : $ordre;
		foreach ( $scalar as $k ) {
			if ( isset( $r[ $k ] ) && '' !== \trim( (string) $r[ $k ] ) ) {
				$o[ $k ] = (string) $r[ $k ];
			}
		}
		foreach ( $o['fields'] as $i => $f ) {
			$rf = (array) ( $r['fields'][ $f['name'] ] ?? array() );
			foreach ( array( 'label', 'help', 'unit' ) as $k ) {
				if ( isset( $rf[ $k ] ) && '' !== \trim( (string) $rf[ $k ] ) ) {
					$o['fields'][ $i ][ $k ] = (string) $rf[ $k ];
				}
			}
			foreach ( array( 'default', 'min', 'max', 'step' ) as $k ) {
				if ( isset( $rf[ $k ] ) && '' !== (string) $rf[ $k ] ) {
					$o['fields'][ $i ][ $k ] = 'number' === $f['type'] ? 0 + (float) $rf[ $k ] : (string) $rf[ $k ];
				}
			}
			if ( 'select' === $f['type'] && ! empty( $rf['options'] ) && \is_array( $rf['options'] ) ) {
				if ( ! empty( $f['lock'] ) ) {
					foreach ( $f['options'] as $val => $label ) {
						if ( '' !== \trim( (string) ( $rf['options'][ $val ] ?? '' ) ) ) {
							$o['fields'][ $i ]['options'][ $val ] = (string) $rf['options'][ $val ];
						}
					}
				} else {
					$o['fields'][ $i ]['options'] = \array_map( 'strval', $rf['options'] );
				}
			}
			if ( 'select' === $f['type'] && ! isset( $o['fields'][ $i ]['options'][ (string) $o['fields'][ $i ]['default'] ] ) ) {
				$o['fields'][ $i ]['default'] = (string) \array_key_first( $o['fields'][ $i ]['options'] );
			}
		}
		foreach ( array( 'method', 'sources', 'faq' ) as $k ) {
			if ( isset( $r[ $k ] ) && \is_array( $r[ $k ] ) ) {
				$o[ $k ] = $r[ $k ];
			}
		}
		if ( ! isset( outil_missions_defaut()[ $o['mission'] ] ) ) {
			$o['mission'] = (string) outils_defaut()[ $slug ]['mission'];
		}
		$all[ $slug ] = $o;
	}
	\uasort( $all, static fn( array $a, array $b ): int => $a['ordre'] <=> $b['ordre'] );
	return $actifs_seulement ? \array_filter( $all, static fn( array $o ): bool => $o['actif'] ) : $all;
}

/**
 * Registre des outils, valeurs par défaut du thème.
 *
 * Champ : name, label, type (number|select|time|text|textarea), default,
 * unit, min, max, step, help, options, lock (valeurs d'options figées),
 * when (affichage conditionnel).
 *
 * @return array<string,array<string,mixed>>
 */
function outils_defaut(): array {
	$e1 = array(
		'0.25' => 'Bâtiment permanent, voies dégagées, accès facile',
		'0.30' => 'Structure non permanente, espace naturel jusqu\'à 2 ha, brancardage de 150 à 300 m',
		'0.35' => 'Espace naturel de 2 à 5 ha, brancardage de 300 à 600 m, accès difficile',
		'0.40' => 'Espace naturel de plus de 5 ha, brancardage de plus de 600 m, escaliers, foule dense',
	);
	return array(
		'dimensionnement-dps'    => array(
			'title'       => 'Calcul du RIS et dimensionnement d\'un DPS',
			'short'       => 'Dimensionnement DPS',
			'seo_title'   => 'Calcul du RIS : dimensionner un DPS (grille officielle)',
			'description' => 'Calcule le ratio d\'intervenants secouristes (RIS) d\'un événement avec la grille du référentiel national DPS : type de dispositif, nombre de secouristes, postes.',
			'lead'        => 'Saisis l\'effectif du public et les trois indicateurs de risque. Le RIS, le type de dispositif et le nombre d\'intervenants secouristes s\'affichent aussitôt.',
			'gives'       => 'RIS, type de DPS, nombre de secouristes',
			'mission'     => 'secours-personne',
			'icon'        => 'users',
			'ref'         => 'Référentiel national DPS, arrêté du 7 novembre 2006',
			'fields'      => array(
				array(
					'name'    => 'p1',
					'label'   => 'Public attendu',
					'type'    => 'number',
					'default' => 1000,
					'unit'    => 'personnes',
					'min'     => 0,
					'step'    => 1,
					'help'    => 'Effectif maximal présent en même temps. Les acteurs et participants se calculent à part.',
				),
				array(
					'name'    => 'p2',
					'lock'    => true,
					'label'   => 'Comportement du public (P2)',
					'type'    => 'select',
					'default' => '0.30',
					'options' => array(
						'0.25' => 'Public assis : spectacle, cérémonie, sport en tribune',
						'0.30' => 'Public debout, peu dynamique : foire, salon, exposition',
						'0.35' => 'Public debout, statique : fête foraine, sport protégé',
						'0.40' => 'Public dynamique : danse, défilé, sport non protégé, hébergement sur place',
					),
					'help'    => 'Plusieurs activités sur le site : prends la plus élevée.',
				),
				array(
					'name'    => 'e1',
					'lock'    => true,
					'label'   => 'Environnement et accès du site (E1)',
					'type'    => 'select',
					'default' => '0.25',
					'options' => $e1,
					'help'    => 'Plusieurs critères : retiens le plus aggravant.',
				),
				array(
					'name'    => 'e2',
					'lock'    => true,
					'label'   => 'Délai d\'arrivée des secours publics (E2)',
					'type'    => 'select',
					'default' => '0.25',
					'options' => array(
						'0.25' => '10 minutes ou moins',
						'0.30' => 'De 10 à 20 minutes',
						'0.35' => 'De 20 à 30 minutes',
						'0.40' => 'Plus de 30 minutes',
					),
					'help'    => 'Depuis le centre de secours le plus proche jusqu\'au point d\'accès le plus défavorisé du site, mobilisation comprise.',
				),
			),
			'method'      => array(
				array( 'Effectif pris en compte (P)', 'Jusqu\'à 100 000 personnes, P = P1. Au-delà, P = 100 000 + (P1 − 100 000) / 2.' ),
				array( 'Indicateur de risque (i)', 'i = P2 + E1 + E2. Il varie de 0,75 à 1,20.' ),
				array( 'Ratio d\'intervenants secouristes', 'RIS = i × P / 1 000.' ),
				array( 'Type de dispositif', 'RIS ≤ 0,25 : à l\'appréciation de l\'autorité de police. Jusqu\'à 1,125 : point d\'alerte et de premiers secours (PAPS, 2 secouristes). Jusqu\'à 12 : DPS de petite envergure. Jusqu\'à 36 : moyenne envergure. Au-delà : grande envergure.' ),
				array( 'Nombre d\'intervenants', 'Minimum 4 dès le DPS de petite envergure. Au-delà, le RIS est arrondi au nombre pair supérieur : 9 donne 10, 12,4 donne 14. Un chef de poste encadre 12 intervenants au plus.' ),
			),
			'sources'     => array(
				array( 'Référentiel national des missions de sécurité civile, DPS (texte diffusé par secourisme.net)', 'https://www.secourisme.net/IMG/pdf/RNMSC-DPS.pdf' ),
				array( 'Arrêté du 7 novembre 2006 fixant le référentiel national DPS (AIDA, INERIS)', 'https://aida.ineris.fr/reglementation/arrete-071106-fixant-referentiel-national-relatif-dispositifs-previsionnels-secours' ),
			),
			'faq'         => array(
				array( 'À partir de quel RIS un DPS est-il obligatoire ?', 'Au-delà d\'un RIS de 0,25, la grille impose un dispositif tenu par une association agréée de sécurité civile. En dessous, la décision revient à l\'autorité de police.' ),
				array( 'Les participants comptent-ils dans le public ?', 'Non. Le dispositif pour les acteurs et participants se calcule indépendamment de celui du public, avec une analyse propre à l\'activité.' ),
				array( 'Pourquoi un nombre pair de secouristes ?', 'Les intervenants travaillent en binômes et en équipes. Le référentiel retient le nombre pair immédiatement supérieur au RIS, avec un minimum de 4 pour un DPS de petite envergure.' ),
			),
		),
		'autonomie-ari'          => array(
			'title'       => 'Calcul d\'autonomie ARI',
			'short'       => 'Autonomie ARI',
			'seo_title'   => 'Calcul d\'autonomie ARI : temps, pression de retour, sifflet',
			'description' => 'Calcule l\'autonomie théorique d\'un appareil respiratoire isolant : temps total, pression de retour, temps avant sifflet et heures clés à partir de l\'heure d\'engagement.',
			'lead'        => 'Saisis la pression lue au manomètre, le volume de la bouteille et la consommation. L\'outil donne le temps disponible, la pression de retour et les heures à surveiller.',
			'gives'       => 'Autonomie, pression de retour, heure du sifflet',
			'mission'     => 'incendie',
			'icon'        => 'clock',
			'ref'         => 'Formation ARI · INC-FTO-07.3 Gestion de l\'air',
			'fields'      => array(
				array(
					'name'    => 'p',
					'label'   => 'Pression au manomètre',
					'type'    => 'number',
					'default' => 300,
					'unit'    => 'bar',
					'min'     => 0,
					'max'     => 350,
					'step'    => 5,
					'help'    => 'Contrôle avant engagement : 280 bar au minimum.',
				),
				array(
					'name'    => 'v',
					'label'   => 'Volume de la bouteille',
					'type'    => 'select',
					'default' => '6.8',
					'options' => array(
						'6'   => '6 litres',
						'6.8' => '6,8 litres',
						'9'   => '9 litres',
					),
				),
				array(
					'name'    => 'q',
					'label'   => 'Consommation',
					'type'    => 'number',
					'default' => 90,
					'unit'    => 'L/min',
					'min'     => 10,
					'max'     => 150,
					'step'    => 5,
					'help'    => 'Repères : 90 à 100 en effort intense, 40 à 70 pour un porteur moyen, 10 à 12 en mode économie.',
				),
				array(
					'name'    => 'sifflet',
					'label'   => 'Déclenchement du sifflet',
					'type'    => 'number',
					'default' => 55,
					'unit'    => 'bar',
					'min'     => 0,
					'max'     => 100,
					'step'    => 5,
				),
				array(
					'name'    => 'heure',
					'label'   => 'Heure d\'engagement',
					'type'    => 'time',
					'default' => '',
					'help'    => 'Facultatif. Donne les heures de retour, de sifflet et de fin.',
				),
			),
			'method'      => array(
				array( 'Formule', 'T = P × V / Q. T en minutes, P la pression en bar, V le volume de la bouteille en litres, Q la consommation en litres par minute.' ),
				array( 'Exemple', 'Bouteille de 6 litres à 300 bar, consommation de 90 L/min : 300 × 6 / 90 = 20 minutes.' ),
				array( 'Pression de retour', 'Le binôme se retire sur ordre du contrôleur ou quand l\'air atteint la moitié de la pression de départ.' ),
				array( 'Limite du calcul', 'Le calcul suppose un air parfait et une consommation constante. Sous l\'effort, la chaleur ou le stress, la consommation varie. Le manomètre fait foi.' ),
			),
			'sources'     => array(
				array( 'Guide de l\'apprenant ARI, SDIS de l\'Ain (formule, 90 L/min, sifflet à 55 bar, retour à mi-pression)', 'https://enasis.fr/pluginfile.php/6253/mod_folder/content/0/Pr%C3%A9-modules%20ARI%20JSP/Cours_ARI_2019.pdf' ),
				array( 'INC-FTO-07.3 Techniques de gestion de l\'air (fourchettes de consommation)', 'https://enasis.fr/pluginfile.php/14463/mod_folder/content/0/INC-FTO-7-3-Techniques%20de%20gestion%20de%20lair.pdf' ),
			),
			'faq'         => array(
				array( 'Quelle consommation retenir pour un calcul d\'autonomie ?', 'Les formations retiennent 90 L/min en intervention. La fiche de gestion de l\'air donne 90 à 100 L/min en effort intense et 40 à 70 L/min pour un porteur moyen.' ),
				array( 'Quand faire demi-tour ?', 'Sur ordre du contrôleur, ou quand la pression atteint la moitié de la pression de départ. Le sifflet de fin de charge se déclenche vers 55 bar.' ),
				array( 'L\'autonomie calculée est-elle garantie ?', 'Non. C\'est une valeur théorique. La consommation réelle dépend de l\'effort, de la chaleur et du stress. Surveille le manomètre en permanence.' ),
			),
		),
		'noria'                  => array(
			'title'       => 'Dimensionnement d\'une noria',
			'short'       => 'Noria',
			'seo_title'   => 'Calcul de noria : débit assuré et nombre de camions-citernes',
			'description' => 'Calcule le débit moyen d\'une noria de camions-citernes et le nombre d\'engins nécessaires pour tenir un débit, selon la distance, la capacité et les débits de remplissage et de vidange.',
			'lead'        => 'Renseigne la capacité des engins, la distance du point d\'eau et les débits. L\'outil donne la durée d\'une rotation, le débit tenu et le nombre d\'engins pour atteindre ton besoin.',
			'gives'       => 'Durée de rotation, débit tenu, engins nécessaires',
			'mission'     => 'incendie',
			'icon'        => 'truck',
			'ref'         => 'Alimentation en eau · calcul de rotation',
			'fields'      => array(
				array(
					'name'    => 'cap',
					'label'   => 'Capacité utile par engin',
					'type'    => 'number',
					'default' => 10000,
					'unit'    => 'litres',
					'min'     => 500,
					'step'    => 500,
				),
				array(
					'name'    => 'n',
					'label'   => 'Engins engagés',
					'type'    => 'number',
					'default' => 3,
					'unit'    => 'engins',
					'min'     => 1,
					'step'    => 1,
				),
				array(
					'name'    => 'besoin',
					'label'   => 'Débit à tenir',
					'type'    => 'number',
					'default' => 1000,
					'unit'    => 'L/min',
					'min'     => 0,
					'step'    => 50,
				),
				array(
					'name'    => 'dist',
					'label'   => 'Distance du point d\'eau',
					'type'    => 'number',
					'default' => 5,
					'unit'    => 'km, aller',
					'min'     => 0,
					'step'    => 0.5,
				),
				array(
					'name'    => 'vit',
					'label'   => 'Vitesse moyenne',
					'type'    => 'number',
					'default' => 40,
					'unit'    => 'km/h',
					'min'     => 5,
					'step'    => 5,
				),
				array(
					'name'    => 'remp',
					'label'   => 'Débit de remplissage',
					'type'    => 'number',
					'default' => 1000,
					'unit'    => 'L/min',
					'min'     => 50,
					'step'    => 50,
				),
				array(
					'name'    => 'vid',
					'label'   => 'Débit de vidange',
					'type'    => 'number',
					'default' => 2000,
					'unit'    => 'L/min',
					'min'     => 50,
					'step'    => 50,
				),
				array(
					'name'    => 'man',
					'label'   => 'Manœuvres par rotation',
					'type'    => 'number',
					'default' => 6,
					'unit'    => 'min',
					'min'     => 0,
					'step'    => 1,
					'help'    => 'Raccordements, mise en place et dégagement, aux deux extrémités.',
				),
			),
			'method'      => array(
				array( 'Durée d\'une rotation', 'Remplissage (capacité / débit de remplissage) + vidange (capacité / débit de vidange) + aller-retour (2 × distance / vitesse) + manœuvres.' ),
				array( 'Débit tenu', 'Chaque engin apporte sa capacité une fois par rotation. Débit moyen = nombre d\'engins × capacité / durée de rotation.' ),
				array( 'Engins nécessaires', 'Débit à tenir × durée de rotation / capacité, arrondi à l\'engin supérieur.' ),
				array( 'Limite du calcul', 'Le débit est une moyenne. Un bac tampon ou une citerne souple sur le lieu de l\'intervention lisse les arrivées. Les points de remplissage et de vidange limitent aussi le débit : un seul engin s\'y présente à la fois.' ),
			),
			'sources'     => array(),
			'faq'         => array(
				array( 'Comment réduire le nombre d\'engins d\'une noria ?', 'Rapproche le point d\'eau, augmente le débit de remplissage avec une aspiration bien dimensionnée, et vide dans une réserve tampon pour libérer l\'engin plus vite.' ),
				array( 'Le débit calculé est-il continu ?', 'Non. C\'est un débit moyen. Sans réserve tampon, l\'alimentation arrive par à-coups à chaque vidange.' ),
			),
		),
		'messages-operationnels' => array(
			'title'       => 'Générateur de messages opérationnels',
			'short'       => 'Messages opérationnels',
			'seo_title'   => 'Message d\'ambiance, compte rendu, ordre initial SOIEC : générateur',
			'description' => 'Rédige un message d\'ambiance, un compte rendu, un ordre initial (SOIEC), un ordre préparatoire (PATRACDR) ou un ordre de mouvement (DPIF) dans le bon ordre, prêt à copier.',
			'lead'        => 'Choisis le type de message, remplis les rubriques dans l\'ordre réglementaire, copie le texte. Rien ne quitte ton appareil.',
			'gives'       => 'Message structuré prêt à transmettre',
			'mission'     => 'commandement',
			'icon'        => 'radio',
			'ref'         => 'GDO Exercice du commandement · mémento ENSOSP',
			'fields'      => array(
				array(
					'name'    => 'type',
					'lock'    => true,
					'label'   => 'Type de message',
					'type'    => 'select',
					'default' => 'ambiance',
					'options' => array(
						'ambiance'     => 'Message d\'ambiance',
						'compte-rendu' => 'Message de compte rendu',
						'soiec'        => 'Ordre initial (SOIEC)',
						'patracdr'     => 'Ordre préparatoire (PATRACDR)',
						'dpif'         => 'Ordre de mouvement (DPIF)',
					),
				),
				array( 'name' => 'de', 'label' => 'De', 'type' => 'text', 'default' => '', 'help' => 'Indicatif de l\'émetteur.' ),
				array( 'name' => 'a', 'label' => 'À', 'type' => 'text', 'default' => 'CODIS' ),
				array( 'name' => 'gh', 'label' => 'Groupe horaire', 'type' => 'time', 'default' => '' ),
				array( 'name' => 'suis', 'label' => 'Je suis', 'type' => 'textarea', 'default' => '', 'when' => 'ambiance,compte-rendu', 'help' => 'Adresse précise, point de transit.' ),
				array( 'name' => 'vois', 'label' => 'Je vois', 'type' => 'textarea', 'default' => '', 'when' => 'ambiance,compte-rendu', 'help' => 'Nature, ampleur, victimes, risques.' ),
				array( 'name' => 'fais', 'label' => 'Je fais', 'type' => 'textarea', 'default' => '', 'when' => 'ambiance', 'help' => 'Actions engagées, moyens.' ),
				array( 'name' => 'demande', 'label' => 'Je demande', 'type' => 'textarea', 'default' => '', 'when' => 'ambiance,compte-rendu', 'help' => 'Renforts, moyens spécialisés, services.' ),
				array( 'name' => 's', 'label' => 'Situation', 'type' => 'textarea', 'default' => '', 'when' => 'soiec', 'help' => 'Nous sommes confrontés à… Quoi, où, par où.' ),
				array( 'name' => 'o', 'label' => 'Objectif', 'type' => 'textarea', 'default' => '', 'when' => 'soiec', 'help' => 'Nous devons… Personnes, biens, environnement.' ),
				array( 'name' => 'im', 'label' => 'Idée de manœuvre', 'type' => 'textarea', 'default' => '', 'when' => 'soiec', 'help' => 'Je veux que… Quoi faire, comment faire.' ),
				array( 'name' => 'ex', 'label' => 'Exécution', 'type' => 'textarea', 'default' => '', 'when' => 'soiec', 'help' => 'Vous faites… Missions, sécurité, sectorisation.' ),
				array( 'name' => 'c', 'label' => 'Commandement', 'type' => 'textarea', 'default' => '', 'when' => 'soiec', 'help' => 'Un chef, une mission, des moyens. Adjoint, transmissions.' ),
				array( 'name' => 'pers', 'label' => 'Personnel', 'type' => 'textarea', 'default' => '', 'when' => 'patracdr', 'help' => 'Effectif par grade, affectation nominative par engin.' ),
				array( 'name' => 'arm', 'label' => 'Armement', 'type' => 'textarea', 'default' => '', 'when' => 'patracdr', 'help' => 'Engins et matériels.' ),
				array( 'name' => 'ten', 'label' => 'Tenue', 'type' => 'textarea', 'default' => '', 'when' => 'patracdr' ),
				array( 'name' => 'rad', 'label' => 'Radio', 'type' => 'textarea', 'default' => '', 'when' => 'patracdr', 'help' => 'Postes, batteries, canaux.' ),
				array( 'name' => 'ali', 'label' => 'Alimentation', 'type' => 'textarea', 'default' => '', 'when' => 'patracdr', 'help' => 'Vivres, boissons, carburant. Objectif : 24 h d\'autonomie.' ),
				array( 'name' => 'cdt', 'label' => 'Commandement', 'type' => 'textarea', 'default' => '', 'when' => 'patracdr', 'help' => 'Chef de détachement, adjoint, chefs d\'agrès.' ),
				array( 'name' => 'der', 'label' => 'Déroulement prévu', 'type' => 'textarea', 'default' => '', 'when' => 'patracdr', 'help' => 'Grandes phases et durée prévue.' ),
				array( 'name' => 'rdv', 'label' => 'Rendez-vous', 'type' => 'textarea', 'default' => '', 'when' => 'patracdr', 'help' => 'Heure et lieu de rassemblement des engins.' ),
				array( 'name' => 'dir', 'label' => 'Direction générale', 'type' => 'textarea', 'default' => '', 'when' => 'dpif' ),
				array( 'name' => 'pt', 'label' => 'Point à atteindre', 'type' => 'textarea', 'default' => '', 'when' => 'dpif' ),
				array( 'name' => 'iti', 'label' => 'Itinéraire', 'type' => 'textarea', 'default' => '', 'when' => 'dpif' ),
				array( 'name' => 'form', 'label' => 'Formation du groupe', 'type' => 'textarea', 'default' => '', 'when' => 'dpif', 'help' => 'Ordre de marche, intervalles, vitesse, signalisation, écoute radio.' ),
			),
			'method'      => array(
				array( 'Message d\'ambiance', 'Je suis, je vois, je fais, je demande. Premier message du chef d\'agrès ou du chef de groupe à son arrivée.' ),
				array( 'Message de compte rendu', 'Je suis, je vois, je demande. Il fait le point de situation au fil de l\'intervention.' ),
				array( 'Ordre initial', 'Situation, objectif, idée de manœuvre, exécution, commandement (SOIEC).' ),
				array( 'Ordre préparatoire', 'Personnel, armement, tenue, radio, alimentation, commandement, déroulement, rendez-vous (PATRACDR). Il prépare le déplacement d\'un groupe ou d\'une colonne.' ),
				array( 'Ordre de mouvement', 'Direction, point à atteindre, itinéraire, formation (DPIF). Il passe les consignes à tous les chefs d\'agrès avant le départ.' ),
			),
			'sources'     => array(
				array( 'GDO Exercice du commandement et conduite des opérations, 2e édition, juin 2020', 'https://interieur.gouv.fr/content/download/133613/1059035/file/GDO-Exercice-Commandement-Conduite-Operations-V2.pdf' ),
				array( 'ENSOSP, mémento chef de groupe', 'https://enasis.fr/pluginfile.php/6539/mod_resource/content/1/memento%20CDG.pdf' ),
			),
			'faq'         => array(
				array( 'Quelle différence entre message d\'ambiance et compte rendu ?', 'Le message d\'ambiance part à l\'arrivée sur les lieux et décrit aussi les actions engagées (je fais). Le compte rendu fait le point de situation et les besoins au cours de l\'intervention.' ),
				array( 'Que signifie SOIEC ?', 'Situation, objectif, idée de manœuvre, exécution, commandement. C\'est la trame de l\'ordre initial donné aux chefs d\'agrès.' ),
				array( 'Mes messages sont-ils enregistrés ?', 'Non. Le texte reste dans ton navigateur. Rien n\'est envoyé ni conservé par Rescue 18.' ),
			),
		),
		'mousse'              => array(
			'title'       => 'Calcul mousse et émulseur',
			'short'       => 'Mousse et émulseur',
			'seo_title'   => 'Calcul de mousse : débit de solution moussante, quantité d\'émulseur',
			'description' => 'Débit de solution moussante, quantités d\'émulseur et d\'eau, volume de mousse et autonomie de la réserve, à partir de la surface en feu.',
			'lead'        => 'Saisis la surface, le taux d\'application et le taux de concentration de ton émulseur. Le calcul donne les débits, les quantités pour toute la durée d\'attaque et l\'autonomie de ta réserve.',
			'gives'       => 'Débits, quantité d\'émulseur, volume de mousse',
			'mission'     => 'incendie',
			'icon'        => 'layers',
			'ref'         => 'Circulaire du 6 mai 1999 · feux de liquides inflammables',
			'fields'      => array(
				array( 'name' => 'surf', 'label' => 'Surface en feu', 'type' => 'number', 'default' => 100, 'unit' => 'm²', 'min' => 1, 'step' => 1 ),
				array(
					'name'    => 'taux',
					'label'   => 'Taux d\'application',
					'type'    => 'select',
					'default' => '2.5',
					'options' => array(
						'2.5' => 'Émulseur filmogène classe I : 2,5 L/min/m²',
						'3'   => 'Émulseur non filmogène classe I : 3 L/min/m²',
						'3.5' => 'Émulseur classe II : 3,5 L/min/m²',
						'5'   => 'Taux majoré : 5 L/min/m²',
					),
					'help'    => 'Taux minimaux de la circulaire du 6 mai 1999 pour les hydrocarbures. Ton SDIS ou l\'étude du site fixent parfois un taux plus élevé.',
				),
				array(
					'name'    => 'conc',
					'label'   => 'Taux de concentration de l\'émulseur',
					'type'    => 'select',
					'default' => '3',
					'options' => array(
						'1' => '1 %',
						'3' => '3 %',
						'6' => '6 %',
					),
					'help'    => 'Indiqué sur le bidon ou la fiche technique de l\'émulseur.',
				),
				array( 'name' => 'duree', 'label' => 'Durée d\'application', 'type' => 'number', 'default' => 20, 'unit' => 'min', 'min' => 1, 'step' => 1, 'help' => 'Objectif d\'extinction de 20 minutes retenu par la circulaire.' ),
				array(
					'name'    => 'fois',
					'label'   => 'Foisonnement',
					'type'    => 'select',
					'default' => '10',
					'options' => array(
						'10'  => 'Bas foisonnement (×10)',
						'75'  => 'Moyen foisonnement (×75)',
						'500' => 'Haut foisonnement (×500)',
					),
					'help'    => 'Volume de mousse produit par litre de solution. Valeur type, propre à chaque générateur.',
				),
				array( 'name' => 'lance', 'label' => 'Débit d\'une lance ou d\'un générateur', 'type' => 'number', 'default' => 250, 'unit' => 'L/min', 'min' => 10, 'step' => 10 ),
				array( 'name' => 'stock', 'label' => 'Émulseur disponible', 'type' => 'number', 'default' => 0, 'unit' => 'litres', 'min' => 0, 'step' => 20, 'help' => 'Laisse 0 pour ne pas calculer l\'autonomie.' ),
			),
			'method'      => array(
				array( 'Débit de solution', 'Surface × taux d\'application. C\'est le débit de solution moussante à fournir en permanence.' ),
				array( 'Débit d\'émulseur', 'Débit de solution × taux de concentration. Le reste est de l\'eau.' ),
				array( 'Quantités', 'Chaque débit × durée d\'application.' ),
				array( 'Mousse produite', 'Quantité de solution × foisonnement.' ),
				array( 'Autonomie', 'Émulseur disponible ÷ débit d\'émulseur.' ),
				array( 'Coefficient opérationnel', 'La circulaire applique un coefficient K de 1 à 3 selon les conditions de l\'attaque. Saisis directement le taux réel si ton plan d\'intervention le donne.' ),
			),
			'sources'     => array(
				array( 'Circulaire du 6 mai 1999 relative à l\'extinction des feux de liquides inflammables (AIDA, Ineris)', 'https://aida.ineris.fr/reglementation/circulaire-060599-relative-a-lextinction-feux-liquides-inflammables-installations' ),
			),
			'faq'         => array(
				array( 'Que signifie un émulseur 3 % ?', 'Il faut 3 litres d\'émulseur pour 97 litres d\'eau, soit 100 litres de solution moussante.' ),
				array( 'Pourquoi ma réserve tient moins longtemps que prévu ?', 'Le taux réel dépend du proportionneur et de la pression. Un réglage à 6 % avec un émulseur 3 % double la consommation.' ),
			),
		),
		'hydraulique'         => array(
			'title'       => 'Calcul hydraulique : pression de refoulement',
			'short'       => 'Hydraulique incendie',
			'seo_title'   => 'Pertes de charge et pression de refoulement : calcul hydraulique incendie',
			'description' => 'Pertes de charge dans les tuyaux de 45, 70 et 110, effet du dénivelé et pression à afficher au refoulement de la pompe pour obtenir la bonne pression à la lance.',
			'lead'        => 'Choisis le tuyau, la longueur de l\'établissement, le débit et la pression de la lance. Le calcul donne la pression à afficher au refoulement.',
			'gives'       => 'Pertes de charge, pression de refoulement',
			'mission'     => 'incendie',
			'icon'        => 'tool',
			'ref'         => 'Formation conducteur engin pompe · règle du carré du débit',
			'fields'      => array(
				array(
					'name'    => 'tuyau',
					'label'   => 'Tuyau',
					'type'    => 'select',
					'default' => '1.5/250',
					'options' => array(
						'1.5/250' => 'DN 45 : 1,5 bar/100 m à 250 L/min',
						'0.6/500' => 'DN 70 : 0,6 bar/100 m à 500 L/min',
						'0.3/1000' => 'DN 110 : 0,3 bar/100 m à 1 000 L/min',
					),
					'help'    => 'Pertes de référence pour 100 m au débit nominal du tuyau.',
				),
				array( 'name' => 'long', 'label' => 'Longueur de l\'établissement', 'type' => 'number', 'default' => 120, 'unit' => 'm', 'min' => 0, 'step' => 20 ),
				array( 'name' => 'debit', 'label' => 'Débit', 'type' => 'number', 'default' => 250, 'unit' => 'L/min', 'min' => 1, 'step' => 10, 'help' => 'Lance 40/14 : 250 L/min. Lance 65/18 : 500 L/min. LDV : débit affiché sur la bague.' ),
				array( 'name' => 'plance', 'label' => 'Pression à la lance', 'type' => 'number', 'default' => 6, 'unit' => 'bar', 'min' => 0, 'step' => 0.5, 'help' => 'LDV : 6 bar. Lance 40/14 : 4 bar. Lance 20/7 : 3,5 bar.' ),
				array( 'name' => 'deniv', 'label' => 'Dénivelé', 'type' => 'number', 'default' => 0, 'unit' => 'm', 'min' => -200, 'max' => 200, 'step' => 1, 'help' => 'Positif si la lance est plus haute que la pompe, négatif si elle est plus basse.' ),
				array( 'name' => 'pmax', 'label' => 'Pression maximale de la pompe', 'type' => 'number', 'default' => 15, 'unit' => 'bar', 'min' => 1, 'step' => 1 ),
			),
			'method'      => array(
				array( 'Pertes dans les tuyaux', 'Pertes de référence × (débit réel ÷ débit nominal)² × longueur ÷ 100. Doubler le débit multiplie les pertes par 4.' ),
				array( 'Dénivelé', '1 bar pour 10 m. Il s\'ajoute en montée et se retranche en descente.' ),
				array( 'Pression de refoulement', 'Pression à la lance + pertes dans les tuyaux + dénivelé.' ),
				array( 'Limite du calcul', 'Les divisions, retenues et coudes ajoutent des pertes non comptées ici. Les valeurs de référence varient selon le fabricant et l\'état des tuyaux.' ),
			),
			'sources'     => array(
				array( 'Formation de spécialité conducteur engin pompe (GMFOR, version 3, septembre 2013)', 'https://ressourcedocumentairesp.webador.fr/_downloads/f41d30f94ed2bd0ce9d4418aad5afc78' ),
				array( 'Hydraulique, notions de base (INC.06, ENASIS)', 'https://enasis.fr/pluginfile.php/70009/mod_resource/content/1/01-INC.06%20Hydraulique-Notions%20de%20base.pdf' ),
			),
			'faq'         => array(
				array( 'Pourquoi les pertes montent si vite avec le débit ?', 'Elles suivent le carré du débit. Un DN 45 perd 1,5 bar aux 100 m à 250 L/min, et 6 bar à 500 L/min.' ),
				array( 'Comment compter un étage ?', 'Compte 3 m par étage environ, soit 0,3 bar par étage en montée.' ),
			),
		),
		'indemnites-spv'      => array(
			'title'       => 'Simulateur d\'indemnités SPV',
			'short'       => 'Indemnités SPV',
			'seo_title'   => 'Indemnités horaires des sapeurs-pompiers volontaires : simulateur 2026',
			'description' => 'Estime tes indemnités de sapeur-pompier volontaire selon ton grade et tes heures d\'intervention, de formation, de garde et d\'astreinte.',
			'lead'        => 'Choisis ton grade, saisis tes heures du mois. Les taux de garde et d\'astreinte dépendent de ton SDIS : règle-les selon sa délibération.',
			'gives'       => 'Montant estimé des indemnités du mois',
			'mission'     => 'statut',
			'icon'        => 'briefcase',
			'ref'         => 'Arrêté du 29 septembre 2026 · décret n° 2012-492',
			'fields'      => array(
				array(
					'name'    => 'grade',
					'label'   => 'Grade',
					'type'    => 'select',
					'default' => '8.77',
					'options' => array(
						'8.77'  => 'Sapeur : 8,77 €/h',
						'9.42'  => 'Caporal : 9,42 €/h',
						'10.63' => 'Sous-officier : 10,63 €/h',
						'13.21' => 'Officier : 13,21 €/h',
					),
					'help'    => 'Taux horaires de base en vigueur au 1er novembre 2026.',
				),
				array( 'name' => 'hinter', 'label' => 'Heures d\'intervention', 'type' => 'number', 'default' => 10, 'unit' => 'h', 'min' => 0, 'step' => 0.5 ),
				array( 'name' => 'hform', 'label' => 'Heures de formation', 'type' => 'number', 'default' => 0, 'unit' => 'h', 'min' => 0, 'step' => 0.5 ),
				array( 'name' => 'hgarde', 'label' => 'Heures de garde en caserne', 'type' => 'number', 'default' => 0, 'unit' => 'h', 'min' => 0, 'step' => 1 ),
				array( 'name' => 'tgarde', 'label' => 'Taux appliqué aux gardes', 'type' => 'number', 'default' => 100, 'unit' => '%', 'min' => 0, 'max' => 100, 'step' => 1, 'help' => 'Fixé par ton SDIS.' ),
				array( 'name' => 'hastr', 'label' => 'Heures d\'astreinte', 'type' => 'number', 'default' => 0, 'unit' => 'h', 'min' => 0, 'step' => 1 ),
				array( 'name' => 'tastr', 'label' => 'Taux appliqué aux astreintes', 'type' => 'number', 'default' => 9, 'unit' => '%', 'min' => 0, 'max' => 9, 'step' => 0.5, 'help' => 'Plafond de 9 % fixé par le décret. Ton SDIS choisit d\'indemniser ou non, et à quel taux.' ),
			),
			'method'      => array(
				array( 'Interventions et formation', 'Heures × taux horaire de base du grade.' ),
				array( 'Gardes', 'Heures × taux de base × taux de garde de ton SDIS.' ),
				array( 'Astreintes', 'Heures × taux de base × taux d\'astreinte, 9 % au plus.' ),
				array( 'Non comptés', 'Les majorations de nuit, de dimanche et de jour férié décidées par ton SDIS, la prestation de fidélisation et de reconnaissance.' ),
				array( 'Fiscalité', 'Les indemnités des SPV sont exonérées d\'impôt sur le revenu et de cotisations sociales.' ),
			),
			'sources'     => array(
				array( 'Banque des Territoires : nouveaux montants des indemnités horaires (arrêté du 29 septembre 2026)', 'https://www.banquedesterritoires.fr/sapeurs-pompiers-volontaires-de-nouveaux-montants-dindemnites-horaires' ),
				array( 'Régimes indemnitaires et statutaires des sapeurs-pompiers (PNRS, ENSOSP)', 'https://pnrs.ensosp.fr/content/download/375444/5684049/file/R%C3%A9gimes%20indemnitaires%20et%20statutaires%20des%20sp.pdf' ),
			),
			'faq'         => array(
				array( 'Mes indemnités sont-elles imposables ?', 'Non. Elles sont exonérées d\'impôt sur le revenu et de cotisations sociales.' ),
				array( 'Pourquoi mon relevé diffère du simulateur ?', 'Ton SDIS applique ses propres taux de garde et d\'astreinte et parfois des majorations. Règle les taux selon sa délibération.' ),
			),
		),
		'aide-memoire-ssuap'  => array(
			'title'       => 'Aide-mémoire SSUAP : Glasgow et constantes',
			'short'       => 'Aide-mémoire SSUAP',
			'seo_title'   => 'Score de Glasgow et valeurs normales par âge : aide-mémoire SSUAP',
			'description' => 'Calcule le score de Glasgow et compare la fréquence respiratoire et la fréquence cardiaque aux valeurs normales de l\'âge de la victime.',
			'lead'        => 'Choisis l\'âge de la victime, saisis ses fréquences, coche les réponses yeux, verbale et motrice. Le bilan reste à transmettre au médecin régulateur.',
			'gives'       => 'Score de Glasgow, constantes hors normes',
			'mission'     => 'secours-personne',
			'icon'        => 'med',
			'ref'         => 'Référentiel technique SUAP · échelle de Glasgow',
			'fields'      => array(
				array(
					'name'    => 'age',
					'label'   => 'Âge de la victime',
					'type'    => 'select',
					'default' => '12-20/60-100',
					'options' => array(
						'12-20/60-100'  => 'Adulte, dès la puberté',
						'20-30/70-140'  => 'Enfant, de 1 an à la puberté',
						'30-40/100-160' => 'Nourrisson, de 1 mois à 1 an',
						'40-60/120-160' => 'Nouveau-né, moins de 1 mois',
					),
					'help'    => 'Les fréquences saisies sont comparées aux valeurs normales de cette tranche d\'âge.',
				),
				array( 'name' => 'fr', 'label' => 'Fréquence respiratoire', 'type' => 'number', 'default' => 16, 'unit' => '/min', 'min' => 0, 'max' => 120, 'step' => 1 ),
				array( 'name' => 'fc', 'label' => 'Fréquence cardiaque', 'type' => 'number', 'default' => 80, 'unit' => '/min', 'min' => 0, 'max' => 300, 'step' => 1 ),
				array(
					'name'    => 'y',
					'lock'    => true,
					'label'   => 'Ouverture des yeux',
					'type'    => 'select',
					'default' => '4',
					'options' => array(
						'4' => '4 : spontanée',
						'3' => '3 : à la demande',
						'2' => '2 : à la douleur',
						'1' => '1 : aucune',
					),
				),
				array(
					'name'    => 'v',
					'lock'    => true,
					'label'   => 'Réponse verbale',
					'type'    => 'select',
					'default' => '5',
					'options' => array(
						'5' => '5 : orientée',
						'4' => '4 : confuse',
						'3' => '3 : mots inappropriés',
						'2' => '2 : sons incompréhensibles',
						'1' => '1 : aucune',
					),
				),
				array(
					'name'    => 'm',
					'lock'    => true,
					'label'   => 'Réponse motrice',
					'type'    => 'select',
					'default' => '6',
					'options' => array(
						'6' => '6 : obéit aux ordres',
						'5' => '5 : orientée à la douleur',
						'4' => '4 : évitement',
						'3' => '3 : flexion anormale',
						'2' => '2 : extension',
						'1' => '1 : aucune',
					),
				),
			),
			'method'      => array(
				array( 'Score de Glasgow', 'Yeux (1 à 4) + verbal (1 à 5) + moteur (1 à 6). Total de 3 à 15. Un score de 8 ou moins signe un coma grave.' ),
				array( 'Constantes', 'Chaque fréquence est comparée aux valeurs normales de la tranche d\'âge du référentiel SUAP.' ),
				array( 'Ordre du bilan', 'Bilan circonstanciel, bilan d\'urgence vitale, bilan complémentaire, puis surveillance.' ),
				array( 'Limite', 'Aide-mémoire de formation. Il ne pose aucun diagnostic et ne remplace pas le bilan transmis au médecin régulateur.' ),
			),
			'sources'     => array(
				array( 'Référentiel technique SUAP, tableau récapitulatif des valeurs normales (ENASIS)', 'https://enasis.fr/pluginfile.php/10300/mod_folder/content/0/0-Tableau%20r%C3%A9capitulatif.pdf' ),
			),
			'faq'         => array(
				array( 'Comment coter une victime intubée ou aphasique ?', 'La réponse verbale n\'est pas évaluable. Note-le dans le bilan et transmets les scores yeux et moteur séparément.' ),
				array( 'Faut-il transmettre le total ou le détail ?', 'Transmets les deux, par exemple Y3 V4 M5, soit 12.' ),
			),
		),
	);
}

/**
 * Slug de l'outil affiché sur la page courante, ou vide.
 *
 * @return string
 */
function current_outil(): string {
	if ( ! \is_singular( 'page' ) ) {
		return '';
	}
	$post = \get_queried_object();
	if ( ! $post instanceof \WP_Post || ( ! \has_block( 'rescue18/outil', $post ) && ! \has_block( 'rescue18/outil-entete', $post ) ) ) {
		return '';
	}
	foreach ( \parse_blocks( $post->post_content ) as $b ) {
		$found = find_outil_block( $b );
		if ( '' !== $found ) {
			return $found;
		}
	}
	return '';
}

/**
 * Recherche récursive du bloc outil.
 *
 * @param array<string,mixed> $block Bloc analysé.
 * @return string
 */
function find_outil_block( array $block ): string {
	if ( \in_array( $block['blockName'] ?? '', array( 'rescue18/outil', 'rescue18/outil-entete' ), true ) ) {
		$slug = (string) ( $block['attrs']['outil'] ?? 'dimensionnement-dps' );
		return isset( outils()[ $slug ] ) ? $slug : '';
	}
	foreach ( (array) ( $block['innerBlocks'] ?? array() ) as $inner ) {
		$found = find_outil_block( $inner );
		if ( '' !== $found ) {
			return $found;
		}
	}
	return '';
}

/**
 * URL de la page d'un outil.
 *
 * @param string $slug Outil.
 * @return string
 */
function outil_url( string $slug ): string {
	return page_url( 'outils/' . $slug );
}

/**
 * Bloc rescue18/outils : catalogue filtrable.
 *
 * @return string
 */
function render_outils(): string {
	if ( ! \is_admin() ) {
		\wp_enqueue_script( 'rescue18-outils' );
	}
	$chips = '<button type="button" class="r18-tk-chip" aria-pressed="true" data-mission="">Tous</button>';
	$count = \array_count_values( \array_column( outils( true ), 'mission' ) );
	foreach ( outil_missions() as $key => $label ) {
		if ( empty( $count[ $key ] ) ) {
			continue;
		}
		$chips .= '<button type="button" class="r18-tk-chip" aria-pressed="false" data-mission="' . \esc_attr( $key ) . '">' . \esc_html( $label ) . '</button>';
	}
	$rows = '';
	foreach ( outils( true ) as $slug => $o ) {
		$hay   = \strtolower( \remove_accents( $o['title'] . ' ' . $o['gives'] . ' ' . $o['ref'] . ' ' . $o['description'] ) );
		$rows .= '<li class="r18-tk-row" data-mission="' . \esc_attr( $o['mission'] ) . '" data-search="' . \esc_attr( $hay ) . '">'
			. '<span class="r18-tk-row__ico r18-i--' . \esc_attr( $o['icon'] ) . '" aria-hidden="true"></span>'
			. '<div class="r18-tk-row__txt"><h2 class="r18-tk-row__title"><a href="' . \esc_url( outil_url( $slug ) ) . '">' . \esc_html( $o['title'] ) . '</a></h2>'
			. '<p>' . \esc_html( $o['lead'] ) . '</p></div>'
			. '<dl class="r18-tk-row__meta"><dt>Résultat</dt><dd>' . \esc_html( $o['gives'] ) . '</dd><dt>Référence</dt><dd>' . \esc_html( $o['ref'] ) . '</dd></dl>'
			. '</li>';
	}
	$html  = '<div class="r18-tk-bar">'
		. '<label class="r18-tk-search"><span class="screen-reader-text">Rechercher un outil</span>'
		. icone( 'search' ) . '<input type="search" data-tk-search placeholder="Rechercher un outil : DPS, ARI, noria…" autocomplete="off"></label>'
		. '<div class="r18-tk-chips" role="group" aria-label="Filtrer par mission">' . $chips . '</div></div>'
		. '<p class="r18-tk-count" aria-live="polite" data-tk-count>' . \count( outils( true ) ) . ' outil' . ( \count( outils( true ) ) > 1 ? 's' : '' ) . '</p>'
		. '<ul class="r18-tk-list" role="list">' . $rows . '</ul>'
		. '<p class="r18-tk-empty" data-tk-empty hidden>Aucun outil ne correspond. Efface la recherche ou choisis « Tous ».</p>';
	return wrap( 'r18-tk', $html, 'section', array( 'aria-label' => 'Catalogue des outils' ) );
}

/**
 * Champ de formulaire d'un outil.
 *
 * @param string              $slug Outil.
 * @param array<string,mixed> $f    Définition.
 * @return string
 */
function outil_field( string $slug, array $f ): string {
	$id    = 'r18-' . $slug . '-' . $f['name'];
	$help  = ! empty( $f['help'] ) ? '<p class="r18-tf__help" id="' . \esc_attr( $id ) . '-aide">' . \esc_html( $f['help'] ) . '</p>' : '';
	$desc  = '' !== $help ? ' aria-describedby="' . \esc_attr( $id ) . '-aide"' : '';
	$name  = ' name="' . \esc_attr( $f['name'] ) . '" id="' . \esc_attr( $id ) . '"' . $desc;
	$value = (string) $f['default'];
	switch ( $f['type'] ) {
		case 'select':
			$opts = '';
			foreach ( $f['options'] as $k => $l ) {
				$opts .= '<option value="' . \esc_attr( (string) $k ) . '"' . \selected( (string) $k, $value, false ) . '>' . \esc_html( $l ) . '</option>';
			}
			$control = '<select' . $name . '>' . $opts . '</select>';
			break;
		case 'textarea':
			$control = '<textarea' . $name . ' rows="2">' . \esc_textarea( $value ) . '</textarea>';
			break;
		case 'number':
			$attrs = '';
			foreach ( array( 'min', 'max', 'step' ) as $k ) {
				if ( isset( $f[ $k ] ) ) {
					$attrs .= ' ' . $k . '="' . \esc_attr( (string) $f[ $k ] ) . '"';
				}
			}
			$mode    = isset( $f['step'] ) && (float) $f['step'] !== \floor( (float) $f['step'] ) ? 'decimal' : 'numeric';
			$control = '<span class="r18-tf__num"><input type="number"' . $name . ' value="' . \esc_attr( $value ) . '" inputmode="' . $mode . '"' . $attrs . ' required>'
				. ( ! empty( $f['unit'] ) ? '<span class="r18-tf__unit" aria-hidden="true">' . \esc_html( $f['unit'] ) . '</span>' : '' ) . '</span>';
			break;
		default:
			$control = '<input type="' . \esc_attr( $f['type'] ) . '"' . $name . ' value="' . \esc_attr( $value ) . '"' . ( 'text' === $f['type'] ? ' autocomplete="off"' : '' ) . '>';
	}
	$unit_sr = 'number' === $f['type'] && ! empty( $f['unit'] ) ? '<span class="screen-reader-text"> (' . \esc_html( $f['unit'] ) . ')</span>' : '';
	$when    = ! empty( $f['when'] ) ? ' data-when="' . \esc_attr( $f['when'] ) . '"' : '';
	$wide    = 'textarea' === $f['type'] || 'select' === $f['type'] ? ' r18-tf--wide' : '';
	return '<div class="r18-tf' . $wide . '"' . $when . '><label for="' . \esc_attr( $id ) . '">' . \esc_html( $f['label'] ) . $unit_sr . '</label>' . $control . '<p class="r18-tf__err" role="alert" hidden></p>' . $help . '</div>';
}

/**
 * Bloc rescue18/outil : formulaire, afficheur, méthode, sources, FAQ.
 *
 * @param array<string,mixed> $attrs Attributs.
 * @return string
 */
function render_outil( array $attrs ): string {
	$slug = (string) ( $attrs['outil'] ?? 'dimensionnement-dps' );
	$all  = outils();
	if ( ! isset( $all[ $slug ] ) ) {
		return '';
	}
	$o = $all[ $slug ];
	if ( ! $o['actif'] ) {
		return \current_user_can( 'manage_options' ) ? wrap( 'r18-outil r18-tool--off', '<p>Cet outil est désactivé dans Boîte à outils. Les visiteurs ne le voient pas.</p>' ) : '';
	}
	if ( ! \is_admin() ) {
		\wp_enqueue_script( 'rescue18-outils' );
	}

	$fields = '';
	foreach ( $o['fields'] as $f ) {
		$fields .= outil_field( $slug, $f );
	}

	$form = '<form class="r18-tool__form" data-tool-form novalidate aria-labelledby="r18-' . \esc_attr( $slug ) . '-donnees">'
		. '<h2 id="r18-' . \esc_attr( $slug ) . '-donnees" class="r18-tool__h">' . icone( 'list' ) . 'Données</h2>'
		. '<p class="r18-tool__jump"><a href="#r18-' . \esc_attr( $slug ) . '-resultat">Aller au résultat</a></p>'
		. '<div class="r18-tool__fields">' . $fields . '</div>'
		. '<p class="r18-tool__reset"><button type="reset" class="r18-btn-txt">Revenir aux valeurs par défaut</button></p>'
		. '</form>';

	$panel = '<div class="r18-tool__out" data-tool-out id="r18-' . \esc_attr( $slug ) . '-resultat" tabindex="-1">'
		. '<h2 class="r18-tool__h">' . icone( (string) $o['icon'] ) . 'Résultat</h2>'
		. '<div class="r18-tool__screen" data-tool-screen aria-live="polite" aria-atomic="true"><noscript><p>Active JavaScript pour lancer le calcul.</p></noscript></div>'
		. '<div class="r18-tool__actions">'
		. '<button type="button" class="r18-tool__btn" data-tool-copy>' . icone( 'clip' ) . '<span>Copier le résultat</span></button>'
		. '<button type="button" class="r18-tool__btn" data-tool-share>' . icone( 'link' ) . '<span>Copier le lien</span></button>'
		. '<button type="button" class="r18-tool__btn" data-tool-print>' . icone( 'download' ) . '<span>Imprimer</span></button>'
		. '</div>'
		. '<p class="r18-tool__status" data-tool-status role="status"></p>'
		. '</div>';

	$method = '';
	foreach ( $o['method'] as [ $t, $d ] ) {
		$method .= '<dt>' . \esc_html( $t ) . '</dt><dd>' . \esc_html( $d ) . '</dd>';
	}
	$sources = '';
	foreach ( $o['sources'] as [ $label, $url ] ) {
		$sources .= '<li><a href="' . \esc_url( $url ) . '" rel="noopener" target="_blank">' . \esc_html( $label ) . '<span class="screen-reader-text"> (nouvel onglet)</span></a></li>';
	}
	$faq = '';
	foreach ( $o['faq'] as [ $q, $a ] ) {
		$faq .= '<details class="r18-tool__qa"><summary>' . \esc_html( $q ) . '</summary><p>' . \esc_html( $a ) . '</p></details>';
	}
	$related = '';
	foreach ( outils( true ) as $other => $oo ) {
		if ( $other !== $slug ) {
			$related .= '<li><a href="' . \esc_url( outil_url( $other ) ) . '"><span class="r18-i--' . \esc_attr( $oo['icon'] ) . '" aria-hidden="true"></span>' . \esc_html( $oo['short'] ) . '</a></li>';
		}
	}

	$doc = '<div class="r18-tool__doc">'
		. '<section aria-labelledby="r18-' . \esc_attr( $slug ) . '-methode"><h2 id="r18-' . \esc_attr( $slug ) . '-methode">Comment le calcul est fait</h2><dl class="r18-tool__method">' . $method . '</dl>'
		. '<p class="r18-tool__warn">' . icone( 'info' ) . '<span>' . \esc_html( outil_avertissement() ) . '</span></p></section>'
		. ( '' !== $sources ? '<section aria-labelledby="r18-' . \esc_attr( $slug ) . '-sources"><h2 id="r18-' . \esc_attr( $slug ) . '-sources">Sources</h2><ul class="r18-tool__src">' . $sources . '</ul></section>' : '' )
		. '<section aria-labelledby="r18-' . \esc_attr( $slug ) . '-faq"><h2 id="r18-' . \esc_attr( $slug ) . '-faq">Questions fréquentes</h2>' . $faq . '</section>'
		. '<nav class="r18-tool__rel" aria-label="Autres outils"><h2>Autres outils</h2><ul>' . $related . '<li><a href="' . \esc_url( page_url( 'outils' ) ) . '"><span class="r18-i--grid" aria-hidden="true"></span>Toute la boîte à outils</a></li></ul></nav>'
		. '</div>';

	$html = '<div class="r18-tool__grid">' . $form . $panel . '</div>' . $doc;
	return wrap( 'r18-outil', $html, 'div', array( 'data-tool' => $slug ) );
}

/**
 * Bloc rescue18/outil-entete : en-tête sombre de la page d'un outil,
 * tiré des réglages (titre, référence, chapeau).
 *
 * @param array<string,mixed> $attrs Attributs.
 * @return string
 */
function render_outil_entete( array $attrs ): string {
	$slug = (string) ( $attrs['outil'] ?? 'dimensionnement-dps' );
	$o    = outils()[ $slug ] ?? null;
	if ( null === $o ) {
		return '';
	}
	$inner = '<div class="r18-dhero__in">' . render_fil_ariane()
		. '<span class="r18-outil__badge" aria-hidden="true">' . icone( (string) $o['icon'] ) . '</span>'
		. '<p class="r18-eyebrow is-vif">' . \esc_html( $o['ref'] ) . '</p>'
		. '<h1 class="wp-block-heading">' . \esc_html( $o['title'] ) . '</h1>'
		. '<p class="r18-lead">' . \esc_html( $o['lead'] ) . '</p></div>';
	return wrap( 'r18-dhero is-outil r18-on-dark alignfull', $inner, 'section' );
}

/**
 * Contenu de la page d'un outil (installateur).
 *
 * @param string $slug Outil.
 * @return string
 */
function outil_page_content( string $slug ): string {
	$o = outils()[ $slug ] ?? null;
	if ( null === $o ) {
		return '';
	}
	return '<!-- wp:rescue18/outil-entete {"outil":"' . \esc_attr( $slug ) . '","align":"full"} /-->'
		. "\n" . '<!-- wp:rescue18/outil {"outil":"' . \esc_attr( $slug ) . '","align":"wide"} /-->';
}

/**
 * Contenu de la page catalogue /outils/.
 *
 * @return string
 */
function outils_page_content(): string {
	return '<!-- wp:group {"tagName":"section","align":"full","className":"r18-dhero is-outil r18-on-dark","layout":{"type":"constrained","contentSize":"1254px"}} -->'
		. '<section class="wp-block-group alignfull r18-dhero is-outil r18-on-dark"><!-- wp:rescue18/fil-ariane /-->'
		. '<!-- wp:paragraph {"className":"r18-eyebrow is-vif"} --><p class="r18-eyebrow is-vif">Gratuits, sans compte, utilisables sur le terrain</p><!-- /wp:paragraph -->'
		. '<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Boîte à outils du sapeur-pompier</h1><!-- /wp:heading -->'
		. '<!-- wp:paragraph {"className":"r18-lead"} --><p class="r18-lead">Calculs et aides à la rédaction fondés sur les textes officiels. Chaque outil affiche sa méthode et ses sources. Le résultat se partage par un simple lien.</p><!-- /wp:paragraph --></section>'
		. '<!-- /wp:group -->'
		. "\n" . '<!-- wp:rescue18/outils {"align":"wide"} /-->';
}

/**
 * Feuille de style et script des outils, chargés seulement sur ces pages
 * (dans l'en-tête, pour éviter un flash sans style).
 */
\add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$post = \get_queried_object();
		if ( ! $post instanceof \WP_Post || ( '' === current_outil() && ! \has_block( 'rescue18/outils', $post ) ) ) {
			return;
		}
		$a = asset( 'assets/css/outils.css' );
		if ( null !== $a ) {
			\wp_enqueue_style( 'rescue18-outils', $a[0], array( 'rescue18-pages' ), $a[1] );
		}
		\wp_enqueue_script( 'rescue18-outils' );
	},
	30
);

/**
 * Enregistrement du script.
 */
\add_action(
	'init',
	static function (): void {
		$a = asset( 'assets/js/outils.js' );
		if ( null !== $a ) {
			\wp_register_script(
				'rescue18-outils',
				$a[0],
				array(),
				$a[1],
				array(
					'strategy'  => 'defer',
					'in_footer' => true,
				)
			);
		}
	}
);
