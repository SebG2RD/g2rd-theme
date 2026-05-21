<?php
/**
 * Bibliothèque de prompts IA G2RD
 *
 * Centralise tous les prompts structurés du module IA. Chaque méthode accepte
 * un contexte ($ctx) et retourne un prompt prêt à envoyer au connecteur IA.
 *
 * Convention de réponse : toujours du texte brut ou du JSON valide selon l'action.
 * Le prompt demande explicitement le format pour chaque action.
 *
 * @package    G2RD\AI
 * @since      1.14.0
 * @license    EUPL-1.2
 * @copyright  (c) 2025 Sebastien GERARD
 */

namespace G2RD\AI;

/**
 * Classe AiPrompts
 */
class AiPrompts {

	// ──────────────────────────────────────────────────────────────────────
	// BLOCS GUTENBERG
	// ──────────────────────────────────────────────────────────────────────

	/**
	 * Génère un titre H1 pour le bloc Hero.
	 *
	 * @param array<string, string> $ctx Contexte.
	 * @return string
	 */
	public static function hero_heading( array $ctx ): string {
		$lang     = self::lang_instruction( $ctx );
		$activity = self::ctx( $ctx, 'activity', 'agence web' );
		$city     = self::ctx( $ctx, 'city', '' );
		$tone     = self::tone_instruction( $ctx );
		$loc      = $city ? " à {$city}" : '';

		return "Tu es expert en copywriting de conversion. {$lang}
Rédige UN SEUL titre H1 percutant pour le héro d'un site web d'{$activity}{$loc}.
Ton : {$tone}. Maximum 12 mots. Commence directement par le titre, sans guillemets ni explication.";
	}

	/**
	 * Génère un sous-titre pour le bloc Hero.
	 *
	 * @param array<string, string> $ctx Contexte.
	 * @return string
	 */
	public static function hero_subheading( array $ctx ): string {
		$lang     = self::lang_instruction( $ctx );
		$activity = self::ctx( $ctx, 'activity', 'agence web' );
		$heading  = self::ctx( $ctx, 'existing_content', '' );
		$tone     = self::tone_instruction( $ctx );
		$ref      = $heading ? " Le titre principal est : « {$heading} »." : '';

		return "Tu es expert en copywriting de conversion. {$lang}{$ref}
Rédige UN SEUL sous-titre accrocheur pour le héro d'un site web d'{$activity}.
Ton : {$tone}. Maximum 25 mots. 1 à 2 phrases. Commence directement par le texte.";
	}

	/**
	 * Génère 3 variantes de CTA pour le bloc Hero.
	 *
	 * @param array<string, string> $ctx Contexte.
	 * @return string Prompt demandant un JSON array.
	 */
	public static function hero_cta( array $ctx ): string {
		$lang     = self::lang_instruction( $ctx );
		$activity = self::ctx( $ctx, 'activity', 'agence web' );
		$goal     = self::ctx( $ctx, 'objective', 'prise de contact' );

		return "Tu es expert en copywriting de conversion. {$lang}
Génère exactement 3 textes de bouton CTA pour un site web d'{$activity}, objectif : {$goal}.
Chaque texte : 2 à 5 mots, orienté action.
Réponds UNIQUEMENT avec un tableau JSON valide, sans explication ni markdown :
[\"CTA 1\", \"CTA 2\", \"CTA 3\"]";
	}

	/**
	 * Réécrit un texte existant du bloc Hero.
	 *
	 * @param array<string, string> $ctx Contexte.
	 * @return string
	 */
	public static function hero_rewrite( array $ctx ): string {
		$lang    = self::lang_instruction( $ctx );
		$text    = self::ctx( $ctx, 'existing_content', '' );
		$tone    = self::tone_instruction( $ctx );
		$length  = self::length_instruction( $ctx );

		return "Tu es expert en copywriting. {$lang}
Réécris le texte suivant en version plus claire et percutante.
Ton : {$tone}. Longueur : {$length}.
Texte original : « {$text} »
Commence directement par le texte réécrit, sans explication.";
	}

	/**
	 * Adapte le texte Hero au SEO local.
	 *
	 * @param array<string, string> $ctx Contexte.
	 * @return string
	 */
	public static function hero_seo_local( array $ctx ): string {
		$lang     = self::lang_instruction( $ctx );
		$activity = self::ctx( $ctx, 'activity', 'agence web' );
		$city     = self::ctx( $ctx, 'city', 'Paris' );
		$text     = self::ctx( $ctx, 'existing_content', '' );

		return "Tu es expert en SEO local. {$lang}
Réécris ce texte pour optimiser le référencement local d'un{$activity} à {$city}.
Intègre naturellement la ville et l'activité sans sur-optimiser.
Texte original : « {$text} »
Commence directement par le texte optimisé.";
	}

	/**
	 * Génère une FAQ complète pour le bloc FAQ.
	 *
	 * @param array<string, string> $ctx Contexte.
	 * @return string Prompt demandant un JSON array.
	 */
	public static function faq_generate( array $ctx ): string {
		$lang     = self::lang_instruction( $ctx );
		$activity = self::ctx( $ctx, 'activity', 'agence web' );
		$city     = self::ctx( $ctx, 'city', '' );
		$service  = self::ctx( $ctx, 'service', '' );
		$keywords = self::ctx( $ctx, 'keywords', '' );
		$loc      = $city ? " à {$city}" : '';
		$svc      = $service ? " pour le service : {$service}" : '';
		$kw       = $keywords ? " Mots-clés SEO à intégrer : {$keywords}." : '';

		return "Tu es expert en SEO et copywriting. {$lang}
Génère une FAQ SEO de 6 questions-réponses pour un{$activity}{$loc}{$svc}.{$kw}
Les questions doivent correspondre à de vraies interrogations des internautes.
Réponds UNIQUEMENT avec un tableau JSON valide :
[{\"question\":\"...\",\"answer\":\"...\"}, ...]";
	}

	/**
	 * Génère des textes de CTA pour le bloc CTA Band.
	 *
	 * @param array<string, string> $ctx Contexte.
	 * @return string Prompt demandant un JSON.
	 */
	public static function cta_band_texts( array $ctx ): string {
		$lang      = self::lang_instruction( $ctx );
		$activity  = self::ctx( $ctx, 'activity', 'agence web' );
		$goal      = self::ctx( $ctx, 'objective', 'devis' );

		return "Tu es expert en copywriting. {$lang}
Pour un bandeau CTA d'{$activity}, objectif : {$goal}.
Génère :
- 5 textes de bouton (2-5 mots chacun, orientés action)
- 3 phrases d'accroche avant le bouton (max 20 mots chacune)
Réponds UNIQUEMENT avec un JSON valide :
{\"buttons\":[\"...\",\"...\",\"...\",\"...\",\"...\"],\"preTexts\":[\"...\",\"...\",\"...\"]}";
	}

	/**
	 * Génère le contenu d'une offre tarifaire.
	 *
	 * @param array<string, string> $ctx Contexte.
	 * @return string Prompt demandant un JSON.
	 */
	public static function pricing_benefits( array $ctx ): string {
		$lang     = self::lang_instruction( $ctx );
		$activity = self::ctx( $ctx, 'activity', 'agence web' );
		$service  = self::ctx( $ctx, 'service', '' );
		$svc      = $service ? " pour {$service}" : '';

		return "Tu es expert en marketing et pricing. {$lang}
Pour une offre{$svc} d'{$activity}, génère :
- 3 noms d'offres (Essentiel, Pro, Premium ou équivalents adaptés)
- Pour chaque offre : 4 bénéfices clients concis (max 10 mots chacun)
- Une phrase de recommandation vers l'offre du milieu
Réponds UNIQUEMENT avec un JSON valide :
{\"offers\":[{\"name\":\"...\",\"benefits\":[\"...\",\"...\",\"...\",\"...\"]},{\"name\":\"...\",\"benefits\":[\"..\",\"...\",\"...\",\"...\"]},{\"name\":\"...\",\"benefits\":[\"...\",\"...\",\"...\",\"...\"]}],\"recommendation\":\"...\"}";
	}

	/**
	 * Améliore un témoignage client.
	 *
	 * @param array<string, string> $ctx Contexte.
	 * @return string Prompt demandant un JSON.
	 */
	public static function testimonial_improve( array $ctx ): string {
		$lang = self::lang_instruction( $ctx );
		$text = self::ctx( $ctx, 'existing_content', '' );

		return "Tu es expert en copywriting. {$lang}
Pour ce témoignage client : « {$text} »
Génère :
- Version corrigée (orthographe, style, sans modifier le fond)
- Version raccourcie (max 2 phrases, garde l'essentiel)
- Une phrase forte à mettre en exergue
- Un titre court (max 6 mots)
Réponds UNIQUEMENT avec un JSON valide :
{\"corrected\":\"...\",\"shortened\":\"...\",\"highlight\":\"...\",\"title\":\"...\"}";
	}

	/**
	 * Génère les textes alt pour la galerie/carte.
	 *
	 * @param array<string, string> $ctx Contexte.
	 * @return string Prompt demandant un JSON.
	 */
	public static function image_alt_texts( array $ctx ): string {
		$lang     = self::lang_instruction( $ctx );
		$activity = self::ctx( $ctx, 'activity', 'agence web' );
		$service  = self::ctx( $ctx, 'service', '' );
		$city     = self::ctx( $ctx, 'city', '' );
		$svc      = $service ? " — {$service}" : '';
		$loc      = $city ? " à {$city}" : '';

		return "Tu es expert en SEO et accessibilité. {$lang}
Pour une image d'{$activity}{$svc}{$loc}, génère :
- Un texte alternatif descriptif et SEO (max 125 caractères)
- Une légende courte (max 15 mots)
- Un nom de fichier SEO (format : activite-service-ville.jpg, pas d'accents)
Réponds UNIQUEMENT avec un JSON valide :
{\"altText\":\"...\",\"caption\":\"...\",\"filename\":\"...\"}";
	}

	// ──────────────────────────────────────────────────────────────────────
	// GÉNÉRATION DE PAGES
	// ──────────────────────────────────────────────────────────────────────

	/**
	 * Génère le contenu d'une page de service.
	 *
	 * @param array<string, string> $ctx Contexte.
	 * @return string Prompt demandant un JSON structuré.
	 */
	public static function page_service( array $ctx ): string {
		$lang     = self::lang_instruction( $ctx );
		$activity = self::ctx( $ctx, 'activity', 'agence web' );
		$service  = self::ctx( $ctx, 'service', 'création de site web' );
		$city     = self::ctx( $ctx, 'city', '' );
		$target   = self::ctx( $ctx, 'target', 'PME et indépendants' );
		$tone     = self::tone_instruction( $ctx );
		$loc      = $city ? " à {$city}" : '';

		return "Tu es expert en rédaction web SEO. {$lang}
Rédige une page de service complète pour {$service} proposé par une {$activity}{$loc}.
Cible : {$target}. Ton : {$tone}.
Génère :
- H1 (max 10 mots, intégrant le service et éventuellement la ville)
- Introduction (2 paragraphes, 80 mots total)
- Section bénéfices : 4 bénéfices clients avec titre et description
- Section méthode : 3 étapes numérotées
- Section FAQ : 4 questions-réponses
- CTA final (1 phrase + texte bouton)
- Meta title (max 60 caractères)
- Meta description (max 155 caractères)
- Slug conseillé (kebab-case, sans accents)
Réponds UNIQUEMENT avec un JSON valide selon cette structure :
{\"h1\":\"...\",\"intro\":\"...\",\"benefits\":[{\"title\":\"...\",\"desc\":\"...\"}],\"steps\":[{\"num\":1,\"title\":\"...\",\"desc\":\"...\"}],\"faq\":[{\"q\":\"...\",\"a\":\"...\"}],\"cta\":{\"text\":\"...\",\"button\":\"...\"},\"seo\":{\"metaTitle\":\"...\",\"metaDesc\":\"...\",\"slug\":\"...\"}}";
	}

	/**
	 * Génère une page locale (SEO géographique).
	 *
	 * @param array<string, string> $ctx Contexte.
	 * @return string
	 */
	public static function page_local( array $ctx ): string {
		$lang     = self::lang_instruction( $ctx );
		$service  = self::ctx( $ctx, 'service', 'création de site web' );
		$city     = self::ctx( $ctx, 'city', 'Paris' );
		$activity = self::ctx( $ctx, 'activity', 'agence web' );
		$zone     = self::ctx( $ctx, 'zone', '' );
		$target   = self::ctx( $ctx, 'target', '' );
		$zonestr  = $zone ? " Zone d'intervention : {$zone}." : '';
		$tgt      = $target ? " Cible : {$target}." : '';

		return "Tu es expert en SEO local. {$lang}
Rédige une page locale pour {$service} à {$city} par une {$activity}.{$zonestr}{$tgt}
Génère :
- H1 local (service + ville)
- Introduction locale (50 mots)
- Services disponibles localement (3 items)
- Zones d'intervention (liste courte)
- 2 éléments de réassurance locaux
- FAQ locale (4 questions)
- CTA local
- Meta title SEO local (max 60 chars)
- Meta description locale (max 155 chars)
Réponds UNIQUEMENT avec un JSON valide :
{\"h1\":\"...\",\"intro\":\"...\",\"services\":[\"...\"],\"zones\":[\"...\"],\"trust\":[\"...\"],\"faq\":[{\"q\":\"...\",\"a\":\"...\"}],\"cta\":{\"text\":\"...\",\"button\":\"...\"},\"seo\":{\"metaTitle\":\"...\",\"metaDesc\":\"...\"}}";
	}

	// ──────────────────────────────────────────────────────────────────────
	// GÉNÉRATION D'ARTICLES
	// ──────────────────────────────────────────────────────────────────────

	/**
	 * Génère un plan d'article.
	 *
	 * @param array<string, string> $ctx Contexte.
	 * @return string
	 */
	public static function post_outline( array $ctx ): string {
		$lang     = self::lang_instruction( $ctx );
		$subject  = self::ctx( $ctx, 'service', 'référencement naturel' );
		$keywords = self::ctx( $ctx, 'keywords', '' );
		$kw       = $keywords ? " Mots-clés SEO : {$keywords}." : '';

		return "Tu es expert en rédaction SEO. {$lang}
Génère un plan d'article complet sur : « {$subject} ».{$kw}
Inclure :
- Titre principal (H1)
- Introduction (2-3 phrases)
- 4-6 sections H2 avec pour chaque section : 1-2 sous-sections H3
- Section FAQ (4 questions)
- Conclusion + CTA
- 5 mots-clés secondaires
Réponds UNIQUEMENT avec un JSON valide :
{\"title\":\"...\",\"intro\":\"...\",\"sections\":[{\"h2\":\"...\",\"h3s\":[\"...\",\"...\"]}],\"faq\":[{\"q\":\"...\",\"a\":\"...\"}],\"conclusion\":\"...\",\"cta\":\"...\",\"keywords\":[\"...\"]}";
	}

	/**
	 * Génère le contenu SEO complet d'un article.
	 *
	 * @param array<string, string> $ctx Contexte.
	 * @return string
	 */
	public static function post_generate( array $ctx ): string {
		$lang     = self::lang_instruction( $ctx );
		$subject  = self::ctx( $ctx, 'service', '' );
		$outline  = self::ctx( $ctx, 'existing_content', '' );
		$tone     = self::tone_instruction( $ctx );
		$length   = self::length_instruction( $ctx );
		$outline_str = $outline ? " Plan à suivre : {$outline}." : '';

		return "Tu es expert en rédaction SEO. {$lang}
Rédige un article complet sur : « {$subject} ».{$outline_str}
Ton : {$tone}. Longueur : {$length}.
Génère :
- Titre SEO
- Introduction (accrocheur, 80 mots)
- Corps de l'article structuré (H2/H3 inclus dans le texte)
- FAQ (4 questions)
- Conclusion courte + CTA
- Extrait WordPress (max 155 caractères)
- Meta title (max 60 chars)
- Meta description (max 155 chars)
- Slug conseillé
Réponds UNIQUEMENT avec un JSON valide :
{\"title\":\"...\",\"intro\":\"...\",\"body\":\"...\",\"faq\":[{\"q\":\"...\",\"a\":\"...\"}],\"conclusion\":\"...\",\"cta\":\"...\",\"excerpt\":\"...\",\"seo\":{\"metaTitle\":\"...\",\"metaDesc\":\"...\",\"slug\":\"...\"}}";
	}

	/**
	 * Génère du contenu pour les réseaux sociaux.
	 *
	 * @param array<string, string> $ctx Contexte.
	 * @return string
	 */
	public static function social_generate( array $ctx ): string {
		$lang    = self::lang_instruction( $ctx );
		$content = self::ctx( $ctx, 'existing_content', '' );
		$subject = self::ctx( $ctx, 'service', 'article' );

		return "Tu es expert en social media. {$lang}
À partir de ce contenu : « {$content} »
Ou de ce sujet si vide : {$subject}.
Génère :
- Post Facebook (2-3 paragraphes, émojis appropriés)
- Post LinkedIn (ton professionnel, 3 paragraphes)
- Texte Google Business Profile (court, max 1500 chars)
- Email newsletter court (objet + corps 3 paragraphes)
Réponds UNIQUEMENT avec un JSON valide :
{\"facebook\":\"...\",\"linkedin\":\"...\",\"google\":\"...\",\"email\":{\"subject\":\"...\",\"body\":\"...\"}}";
	}

	/**
	 * Génère les méta SEO (title, description, slug).
	 *
	 * @param array<string, string> $ctx Contexte.
	 * @return string
	 */
	public static function seo_generate( array $ctx ): string {
		$lang     = self::lang_instruction( $ctx );
		$title    = self::ctx( $ctx, 'service', '' );
		$content  = self::ctx( $ctx, 'existing_content', '' );
		$keywords = self::ctx( $ctx, 'keywords', '' );
		$kw       = $keywords ? " Mot-clé principal : {$keywords}." : '';

		return "Tu es expert en SEO on-page. {$lang}
Titre/sujet : « {$title} ».{$kw}
Contenu disponible : « " . \mb_substr( $content, 0, 500 ) . ' »
Génère :
- Meta title SEO (max 60 caractères, inclut le mot-clé)
- Meta description (max 155 caractères, incitative)
- Slug URL (kebab-case, sans accents, max 5 mots)
- Mot-clé principal
- 5 mots-clés secondaires
- 4 suggestions de H2
Réponds UNIQUEMENT avec un JSON valide :
{"metaTitle":"...","metaDesc":"...","slug":"...","primaryKeyword":"...","secondaryKeywords":["..."],"h2Suggestions":["..."]}';
	}

	/**
	 * Suggère des liens internes depuis le contenu.
	 *
	 * @param array<string, string> $ctx Contexte.
	 * @return string
	 */
	public static function suggest_links( array $ctx ): string {
		$lang    = self::lang_instruction( $ctx );
		$content = \mb_substr( self::ctx( $ctx, 'existing_content', '' ), 0, 1000 );
		$pages   = self::ctx( $ctx, 'pages_list', '' );
		$pages_str = $pages ? " Pages disponibles sur le site : {$pages}." : '';

		return "Tu es expert en SEO et maillage interne. {$lang}
Contenu de la page à analyser : « {$content} ».{$pages_str}
Propose 5 suggestions de liens internes pertinents.
Pour chaque lien : ancre naturelle, emplacement dans le texte, priorité (haute/moyenne/basse).
Réponds UNIQUEMENT avec un JSON valide :
[{\"anchor\":\"...\",\"context\":\"...\",\"priority\":\"haute\"}]";
	}

	// ──────────────────────────────────────────────────────────────────────
	// HELPERS PRIVÉS
	// ──────────────────────────────────────────────────────────────────────

	/**
	 * Récupère une valeur du contexte avec un fallback.
	 *
	 * @param array<string, string> $ctx     Contexte.
	 * @param string                $key     Clé.
	 * @param string                $default Valeur par défaut.
	 * @return string
	 */
	private static function ctx( array $ctx, string $key, string $default ): string {
		$value = $ctx[ $key ] ?? $default;
		return \sanitize_textarea_field( (string) $value );
	}

	/**
	 * Retourne l'instruction de langue.
	 *
	 * @param array<string, string> $ctx Contexte.
	 * @return string
	 */
	private static function lang_instruction( array $ctx ): string {
		$lang = self::ctx( $ctx, 'language', 'fr' );
		return 'fr' === $lang
			? 'Réponds en français.'
			: 'Answer in English.';
	}

	/**
	 * Retourne l'instruction de ton.
	 *
	 * @param array<string, string> $ctx Contexte.
	 * @return string
	 */
	private static function tone_instruction( array $ctx ): string {
		$tones = [
			'professionnel' => 'professionnel et rassurant',
			'decontracte'   => 'décontracté et accessible',
			'technique'     => 'technique et précis',
			'humain'        => 'humain et empathique',
			'commercial'    => 'commercial et persuasif',
		];

		$tone = \sanitize_key( self::ctx( $ctx, 'tone', 'professionnel' ) );
		return $tones[ $tone ] ?? 'professionnel et rassurant';
	}

	/**
	 * Retourne l'instruction de longueur.
	 *
	 * @param array<string, string> $ctx Contexte.
	 * @return string
	 */
	private static function length_instruction( array $ctx ): string {
		$lengths = [
			'court'  => 'courte (150-250 mots)',
			'moyen'  => 'moyenne (400-600 mots)',
			'long'   => 'longue (800-1200 mots)',
		];

		$length = \sanitize_key( self::ctx( $ctx, 'length', 'moyen' ) );
		return $lengths[ $length ] ?? 'moyenne (400-600 mots)';
	}
}
