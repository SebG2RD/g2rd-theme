<?php
/**
 * Rendu du bloc g2rd/pricing-tiers.
 *
 * Le palier en cours est déduit de la date du jour, dans le fuseau du site.
 * Aucune intervention manuelle n'est nécessaire pour faire basculer un tarif.
 *
 * Règle de bascule : un palier court de 00h00 le jour de `debut` à 23h59:59 le
 * jour de `fin`, inclus. Le tarif « passe » donc à minuit, comme annoncé au
 * public — pas à midi ni à l'heure serveur.
 *
 * @package G2RD
 * @since   1.0.0
 *
 * @var array    $attributes Attributs du bloc.
 * @var string   $content    Contenu interne (inutilisé).
 * @var WP_Block $block      Instance du bloc.
 */

defined( 'ABSPATH' ) || exit;

$paliers = isset( $attributes['paliers'] ) && is_array( $attributes['paliers'] ) ? $attributes['paliers'] : [];

if ( empty( $paliers ) ) {
	return;
}

$devise          = (string) ( $attributes['devise'] ?? '€' );
$badge_actif     = (string) ( $attributes['badgeActif'] ?? __( 'Tarif actuel', 'g2rd' ) );
$afficher_passes = ! empty( $attributes['afficherPasses'] );
$afficher_bandeau = ! empty( $attributes['afficherBandeau'] );
$afficher_bouton = ! empty( $attributes['afficherBouton'] );
$url_inscription = (string) ( $attributes['urlInscription'] ?? '#inscription' );
$texte_bouton    = (string) ( $attributes['texteBouton'] ?? __( 'S’inscrire', 'g2rd' ) );
$titre_a11y      = trim( (string) ( $attributes['titreAccessible'] ?? '' ) );

/* Une ancre interne n'est pas une URL : esc_url() la vide dès qu'elle contient
   un « : » (il y voit un protocole inconnu). Un fragment ne peut de toute façon
   porter aucun protocole — le simple échappement d'attribut suffit et préserve
   les ancres existantes. Tout le reste passe par esc_url(). */
$href_inscription = str_starts_with( $url_inscription, '#' )
	? esc_attr( $url_inscription )
	: esc_url( $url_inscription );

$fuseau     = wp_timezone();
$maintenant = new DateTimeImmutable( 'now', $fuseau );
$aujourdhui = $maintenant->format( 'Y-m-d' );

/**
 * Qualifie un palier par rapport à la date du jour.
 *
 * @param array  $palier     Données du palier.
 * @param string $aujourdhui Date du jour au format Y-m-d.
 * @return string 'passe' | 'actif' | 'futur' | 'permanent'
 */
$statut = static function ( array $palier, string $aujourdhui ): string {
	$debut = trim( (string) ( $palier['debut'] ?? '' ) );
	$fin   = trim( (string) ( $palier['fin'] ?? '' ) );

	// Un palier sans dates est permanent : tarif étudiante, tarif groupe.
	if ( '' === $debut && '' === $fin ) {
		return 'permanent';
	}
	if ( '' !== $fin && $aujourdhui > $fin ) {
		return 'passe';
	}
	if ( '' !== $debut && $aujourdhui < $debut ) {
		return 'futur';
	}
	return 'actif';
};

// Qualification de chaque palier, puis repérage du prochain à entrer en vigueur.
$qualifies = [];
foreach ( $paliers as $palier ) {
	if ( ! is_array( $palier ) ) {
		continue;
	}
	$palier['_statut'] = $statut( $palier, $aujourdhui );
	$qualifies[]       = $palier;
}

if ( empty( $qualifies ) ) {
	return;
}

// Le prochain palier daté : celui dont la date de début est la plus proche
// dans le futur. C'est lui qui alimente le bandeau d'urgence.
$suivant = null;
foreach ( $qualifies as $palier ) {
	if ( 'futur' !== $palier['_statut'] || '' === trim( (string) ( $palier['debut'] ?? '' ) ) ) {
		continue;
	}
	if ( null === $suivant || $palier['debut'] < $suivant['debut'] ) {
		$suivant = $palier;
	}
}

$classes = [ 'g2rd-pricing-tiers' ];
$wrapper = get_block_wrapper_attributes( [ 'class' => implode( ' ', $classes ) ] );

/**
 * Met en forme une date au format long français.
 *
 * @param string $iso Date Y-m-d.
 * @return string
 */
$date_longue = static function ( string $iso ) use ( $fuseau ): string {
	// La date est interprétée dans le fuseau du site, pas en UTC : sinon un site
	// réglé sur un fuseau négatif afficherait la veille de la date saisie.
	$date = \DateTimeImmutable::createFromFormat( '!Y-m-d', $iso, $fuseau );
	return $date ? wp_date( 'j F Y', $date->getTimestamp() ) : '';
};
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<?php if ( '' !== $titre_a11y ) : ?>
		<h2 class="screen-reader-text"><?php echo esc_html( $titre_a11y ); ?></h2>
	<?php endif; ?>

	<ul class="g2rd-pricing-tiers__grille" role="list">
		<?php
		foreach ( $qualifies as $palier ) :
			$etat = $palier['_statut'];

			if ( 'passe' === $etat && ! $afficher_passes ) {
				continue;
			}

			$nom     = trim( (string) ( $palier['nom'] ?? '' ) );
			$prix    = trim( (string) ( $palier['prix'] ?? '' ) );
			$mention = trim( (string) ( $palier['mention'] ?? '' ) );
			$debut   = trim( (string) ( $palier['debut'] ?? '' ) );
			$fin     = trim( (string) ( $palier['fin'] ?? '' ) );

			if ( '' === $nom && '' === $prix ) {
				continue;
			}

			// Période lisible, adaptée aux paliers ouverts d'un seul côté.
			if ( '' !== $debut && '' !== $fin ) {
				$periode = sprintf(
					/* translators: 1: date de début, 2: date de fin. */
					__( 'Du %1$s au %2$s', 'g2rd' ),
					$date_longue( $debut ),
					$date_longue( $fin )
				);
			} elseif ( '' !== $debut ) {
				/* translators: %s: date de début. */
				$periode = sprintf( __( 'À partir du %s', 'g2rd' ), $date_longue( $debut ) );
			} elseif ( '' !== $fin ) {
				/* translators: %s: date de fin. */
				$periode = sprintf( __( 'Jusqu’au %s', 'g2rd' ), $date_longue( $fin ) );
			} else {
				$periode = __( 'Toute la période d’inscription', 'g2rd' );
			}
			?>
			<li class="g2rd-pricing-tiers__palier g2rd-pricing-tiers__palier--<?php echo esc_attr( $etat ); ?>">

				<?php if ( 'actif' === $etat && '' !== $badge_actif ) : ?>
					<span class="g2rd-pricing-tiers__badge"><?php echo esc_html( $badge_actif ); ?></span>
				<?php endif; ?>

				<p class="g2rd-pricing-tiers__nom"><?php echo esc_html( $nom ); ?></p>

				<p class="g2rd-pricing-tiers__prix">
					<?php if ( 'passe' === $etat ) : ?>
						<s><?php echo esc_html( $prix ); ?>&nbsp;<?php echo esc_html( $devise ); ?></s>
						<span class="screen-reader-text"><?php esc_html_e( '— tarif écoulé', 'g2rd' ); ?></span>
					<?php else : ?>
						<?php echo esc_html( $prix ); ?>&nbsp;<?php echo esc_html( $devise ); ?>
					<?php endif; ?>
				</p>

				<p class="g2rd-pricing-tiers__periode"><?php echo esc_html( $periode ); ?></p>

				<?php if ( '' !== $mention ) : ?>
					<p class="g2rd-pricing-tiers__mention"><?php echo esc_html( $mention ); ?></p>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if ( $afficher_bandeau && null !== $suivant ) : ?>
		<p class="g2rd-pricing-tiers__bascule">
			<?php
			printf(
				/* translators: 1: montant du prochain palier, 2: devise, 3: date de bascule. */
				esc_html__( 'Le tarif passe à %1$s %2$s le %3$s à minuit.', 'g2rd' ),
				'<strong>' . esc_html( trim( (string) $suivant['prix'] ) ) . '</strong>',
				esc_html( $devise ),
				'<strong>' . esc_html( $date_longue( (string) $suivant['debut'] ) ) . '</strong>'
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( $afficher_bouton && '' !== $texte_bouton ) : ?>
		<p class="g2rd-pricing-tiers__action">
			<a class="wp-element-button" href="<?php echo $href_inscription; // phpcs:ignore WordPress.Security.EscapeOutput — échappé plus haut selon la nature du lien. ?>" data-inscription>
				<?php echo esc_html( $texte_bouton ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
