/**
 * Moteur de scoring GEO Analyzer
 *
 * Analyse les blocs Gutenberg et retourne un score GEO sur 100 avec
 * des détails par critère et des recommandations actionnables.
 *
 * Critères et poids :
 *  Clarté         → 15 pts
 *  Structure      → 15 pts
 *  FAQ / Q&R      → 10 pts
 *  Entités        → 15 pts
 *  Crédibilité    → 15 pts
 *  Résumabilité   → 10 pts
 *  Données struct → 10 pts
 *  Cohérence      → 10 pts
 *               = 100 pts
 */

/** Poids de chaque critère (total = 100) */
export const CRITERIA_WEIGHTS = {
	clarity:     15,
	structure:   15,
	faq:         10,
	entities:    15,
	credibility: 15,
	summary:     10,
	schema:      10,
	consistency: 10,
};

/** Labels affichés dans le panneau */
export const CRITERIA_LABELS = {
	clarity:     'Clarté de réponse',
	structure:   'Structure',
	faq:         'FAQ / Q&R',
	entities:    'Entités',
	credibility: 'Crédibilité',
	summary:     'Résumabilité',
	schema:      'Données structurées',
	consistency: 'Cohérence',
};

// ── Utilitaires ────────────────────────────────────────────────────────────

/** Aplatit les blocs imbriqués récursivement */
function flattenBlocks( blocks ) {
	return blocks.reduce( ( acc, block ) => {
		acc.push( block );
		if ( block.innerBlocks?.length ) {
			acc.push( ...flattenBlocks( block.innerBlocks ) );
		}
		return acc;
	}, [] );
}

/** Extrait le texte brut de tous les blocs */
function extractText( blocks ) {
	return blocks
		.map( ( block ) => {
			const content = block.attributes?.content ?? block.attributes?.value ?? '';
			return typeof content === 'string' ? content.replace( /<[^>]+>/g, ' ' ) : '';
		} )
		.join( ' ' )
		.replace( /\s+/g, ' ' )
		.trim();
}

/** Compte les mots dans une chaîne */
function wordCount( text ) {
	return text.split( /\s+/ ).filter( Boolean ).length;
}

// ── Critère 1 : Clarté de réponse (15 pts) ────────────────────────────────

function analyzeClarity( blocks ) {
	let score = 0;
	const details = [];

	const firstPara = blocks.find( ( b ) => b.name === 'core/paragraph' );

	if ( ! firstPara ) {
		details.push( { status: 'error', text: 'Aucun paragraphe d\'introduction trouvé' } );
		return { score: 0, max: CRITERIA_WEIGHTS.clarity, details };
	}

	const raw  = ( firstPara.attributes?.content ?? '' ).replace( /<[^>]+>/g, '' );
	const wc   = wordCount( raw );

	// Longueur du premier paragraphe (idéal : 30–120 mots)
	if ( wc >= 30 && wc <= 120 ) {
		score += 6;
		details.push( { status: 'ok', text: `Introduction bien dimensionnée (${ wc } mots)` } );
	} else if ( wc > 0 ) {
		score += 2;
		const msg = wc < 30
			? `Introduction trop courte (${ wc } mots — visez 30 min.)`
			: `Introduction trop longue (${ wc } mots — visez 120 max.)`;
		details.push( { status: 'warning', text: msg } );
	}

	// Phrase expliquant le service (verbe d'action)
	if ( /\b(permet|aide|offre|propose|fournit|assure|garantit|réalise|accompagne|spécialisé|expert)\b/i.test( raw ) ) {
		score += 5;
		details.push( { status: 'ok', text: 'Phrase explicative du service présente' } );
	} else {
		details.push( { status: 'warning', text: 'Ajoutez une phrase qui explique clairement votre service' } );
	}

	// Longueur moyenne des phrases (< 25 mots = lisible)
	const sentences = raw.split( /[.!?]+/ ).filter( ( s ) => s.trim().length > 10 );
	if ( sentences.length > 0 ) {
		const avgW = sentences.reduce( ( s, sent ) => s + wordCount( sent ), 0 ) / sentences.length;
		if ( avgW <= 25 ) {
			score += 4;
			details.push( { status: 'ok', text: 'Phrases courtes et lisibles (moy. ' + Math.round( avgW ) + ' mots)' } );
		} else {
			details.push( { status: 'warning', text: 'Raccourcissez vos phrases (moy. ' + Math.round( avgW ) + ' mots, max. 25)' } );
		}
	}

	return { score: Math.min( score, CRITERIA_WEIGHTS.clarity ), max: CRITERIA_WEIGHTS.clarity, details };
}

// ── Critère 2 : Structure (15 pts) ────────────────────────────────────────

function analyzeStructure( blocks ) {
	let score = 0;
	const details = [];

	const headings = blocks.filter( ( b ) => b.name === 'core/heading' );
	const h2 = headings.filter( ( b ) => b.attributes?.level === 2 );
	const h3 = headings.filter( ( b ) => b.attributes?.level === 3 );

	if ( h2.length >= 2 ) {
		score += 6;
		details.push( { status: 'ok', text: `${ h2.length } titres H2 — structure claire` } );
	} else if ( h2.length === 1 ) {
		score += 3;
		details.push( { status: 'warning', text: '1 seul H2 — ajoutez-en au moins un autre' } );
	} else {
		details.push( { status: 'error', text: 'Aucun H2 — la structure est absente' } );
	}

	if ( h3.length >= 2 ) {
		score += 5;
		details.push( { status: 'ok', text: `${ h3.length } sous-titres H3 présents` } );
	} else if ( h3.length === 1 ) {
		score += 2;
		details.push( { status: 'warning', text: 'Ajoutez des H3 pour détailler les sections' } );
	} else {
		details.push( { status: 'warning', text: 'Aucun H3 — subdivisez vos sections' } );
	}

	// Paragraphes courts (< 80 mots)
	const paragraphs = blocks.filter( ( b ) => b.name === 'core/paragraph' );
	const tooLong = paragraphs.filter( ( b ) => {
		const txt = ( b.attributes?.content ?? '' ).replace( /<[^>]+>/g, '' );
		return wordCount( txt ) > 80;
	} );

	if ( paragraphs.length > 0 && tooLong.length === 0 ) {
		score += 4;
		details.push( { status: 'ok', text: 'Tous les paragraphes sont bien dimensionnés' } );
	} else if ( tooLong.length > 0 ) {
		details.push( { status: 'warning', text: `${ tooLong.length } paragraphe(s) trop long(s) à découper (> 80 mots)` } );
	}

	return { score: Math.min( score, CRITERIA_WEIGHTS.structure ), max: CRITERIA_WEIGHTS.structure, details };
}

// ── Critère 3 : FAQ / Q&R (10 pts) ────────────────────────────────────────

function analyzeFAQ( blocks, fullText ) {
	let score = 0;
	const details = [];

	// Bloc FAQ avec mode GEO actif (schema FAQPage + JSON-LD)
	const hasGeoFaq = blocks.some(
		( b ) => b.name === 'g2rd/geo-faq' ||
		         ( b.name === 'g2rd/faq' && b.attributes?.optimizeForGEO )
	);

	// Bloc FAQ quelconque (standard, sans GEO)
	const hasAnyFaq = hasGeoFaq || blocks.some(
		( b ) => [ 'yoast/faq-block', 'g2rd/faq', 'core/faq' ].includes( b.name )
	);

	if ( hasGeoFaq ) {
		score += 6;
		details.push( { status: 'ok', text: 'Bloc FAQ en mode GEO — schema FAQPage actif' } );
	} else if ( hasAnyFaq ) {
		score += 4;
		details.push( { status: 'warning', text: 'Bloc FAQ présent — activez le mode GEO pour le schema FAQPage' } );
	} else {
		details.push( { status: 'error', text: 'Ajoutez un bloc FAQ et activez le mode GEO (≥ 3 questions)' } );
	}

	// Phrases interrogatives dans le texte
	const qCount = ( fullText.match( /\?/g ) ?? [] ).length;
	if ( qCount >= 3 ) {
		score += 4;
		details.push( { status: 'ok', text: `${ qCount } questions détectées` } );
	} else if ( qCount >= 1 ) {
		score += 2;
		details.push( { status: 'warning', text: `${ qCount } question(s) — visez au moins 3` } );
	} else {
		details.push( { status: 'warning', text: 'Aucune question — adoptez un format Q&R' } );
	}

	return { score: Math.min( score, CRITERIA_WEIGHTS.faq ), max: CRITERIA_WEIGHTS.faq, details };
}

// ── Critère 4 : Entités (15 pts) ──────────────────────────────────────────

function analyzeEntities( fullText, title ) {
	let score = 0;
	const details = [];
	const text = ( title + ' ' + fullText ).toLowerCase();

	// Entreprise / agence
	const hasCompany = /\b(agence|cabinet|studio|société|entreprise|sarl|sas|sasu|eurl|freelance)\b/i.test( fullText )
		|| /(?:chez|par|avec|l'équipe|notre agence)\s+[A-ZÀÉÈ]/u.test( fullText );
	if ( hasCompany ) {
		score += 4;
		details.push( { status: 'ok', text: 'Entité entreprise détectée' } );
	} else {
		details.push( { status: 'warning', text: 'Mentionnez le nom ou le type de votre structure' } );
	}

	// Ville / localisation
	const hasLocation = /\b(paris|lyon|marseille|bordeaux|toulouse|nantes|lille|strasbourg|rennes|nice|grenoble|montpellier|région|département|france|\bà\s+[A-ZÀÉÈ][a-zàéè]+)\b/iu.test( fullText );
	if ( hasLocation ) {
		score += 4;
		details.push( { status: 'ok', text: 'Localisation géographique présente' } );
	} else {
		details.push( { status: 'error', text: 'Ajoutez votre ville dans l\'introduction' } );
	}

	// Service principal
	const hasService = /\b(création|développement|conception|design|marketing|référencement|seo|audit|conseil|formation|accompagnement|maintenance|hébergement|site web|application|e-commerce|vitrine|wordpress)\b/i.test( text );
	if ( hasService ) {
		score += 4;
		details.push( { status: 'ok', text: 'Service principal identifiable' } );
	} else {
		details.push( { status: 'warning', text: 'Nommez explicitement votre service principal' } );
	}

	// Vocabulaire métier (mots capitalisés ≥ 5 lettres — heuristique)
	const metierWords = new Set( ( fullText.match( /\b[A-ZÀÉÈ][a-zàéè]{4,}\b/gu ) ?? [] ) );
	if ( metierWords.size >= 5 ) {
		score += 3;
		details.push( { status: 'ok', text: `Vocabulaire métier présent (${ metierWords.size } termes)` } );
	} else {
		details.push( { status: 'warning', text: 'Enrichissez le vocabulaire métier de votre domaine' } );
	}

	return { score: Math.min( score, CRITERIA_WEIGHTS.entities ), max: CRITERIA_WEIGHTS.entities, details };
}

// ── Critère 5 : Crédibilité (15 pts) ──────────────────────────────────────

function analyzeCredibility( fullText ) {
	let score = 0;
	const details = [];

	// Chiffres clés
	const numbersMatch = fullText.match( /\d+\s*(%|ans?|projets?|clients?|années?|mois|jours?|€|\$|k€)/gi ) ?? [];
	if ( numbersMatch.length >= 2 ) {
		score += 4;
		details.push( { status: 'ok', text: `${ numbersMatch.length } chiffre(s) clé(s) trouvé(s)` } );
	} else {
		details.push( { status: 'warning', text: 'Ajoutez des chiffres concrets (ex. : 150 clients, 10 ans)' } );
	}

	// Avis / témoignages
	if ( /\b(avis|témoignage|recommande|satisfaction|clients? satisfaits?|ils nous font confiance|ce qu[''']ils disent|★|⭐|\d[\.,]\d\s*\/\s*5)\b/i.test( fullText ) ) {
		score += 4;
		details.push( { status: 'ok', text: 'Témoignage ou preuve sociale détecté' } );
	} else {
		details.push( { status: 'warning', text: 'Ajoutez un témoignage client ou des avis' } );
	}

	// Contact (email, téléphone, mot "contact")
	if ( /(\b0[1-9](\s?\d{2}){4}\b|[\w.+-]+@[\w-]+\.[a-z]{2,}|\b(contactez|appelez|formulaire de contact)\b)/i.test( fullText ) ) {
		score += 4;
		details.push( { status: 'ok', text: 'Informations de contact présentes' } );
	} else {
		details.push( { status: 'error', text: 'Ajoutez vos coordonnées (email, téléphone)' } );
	}

	// Prix / délais
	if ( /\b(tarif|prix|devis|à partir de|\d+\s*€|gratuit|dès\s+\d|délai|sous\s+\d+|en\s+\d+\s*jours?|semaines?)\b/i.test( fullText ) ) {
		score += 3;
		details.push( { status: 'ok', text: 'Tarification ou délai mentionné' } );
	} else {
		details.push( { status: 'warning', text: 'Indiquez un tarif indicatif ou un délai de livraison' } );
	}

	return { score: Math.min( score, CRITERIA_WEIGHTS.credibility ), max: CRITERIA_WEIGHTS.credibility, details };
}

// ── Critère 6 : Résumabilité (10 pts) ─────────────────────────────────────

function analyzeSummary( blocks ) {
	let score = 0;
	const details = [];

	// Bloc Résumé GEO
	if ( blocks.some( ( b ) => b.name === 'g2rd/geo-summary' ) ) {
		score += 6;
		details.push( { status: 'ok', text: 'Bloc Résumé GEO présent — parfait pour les IA' } );
	} else {
		details.push( { status: 'error', text: 'Ajoutez un bloc "Résumé GEO" en haut de page' } );
	}

	// Listes à puces (facilite la scannabilité)
	const lists = blocks.filter( ( b ) => b.name === 'core/list' );
	if ( lists.length >= 1 ) {
		score += 4;
		details.push( { status: 'ok', text: `${ lists.length } liste(s) à puces — contenu scannable` } );
	} else {
		details.push( { status: 'warning', text: 'Ajoutez au moins une liste de points clés' } );
	}

	return { score: Math.min( score, CRITERIA_WEIGHTS.summary ), max: CRITERIA_WEIGHTS.summary, details };
}

// ── Critère 7 : Données structurées (10 pts) ──────────────────────────────

function analyzeSchema( blocks, fullText ) {
	let score = 0;
	const details = [];

	// FAQ schema (bloc FAQ GEO, FAQ G2RD en mode GEO, ou Yoast)
	const hasFaqSchema = blocks.some(
		( b ) => b.name === 'g2rd/geo-faq' ||
		         b.name === 'yoast/faq-block' ||
		         ( b.name === 'g2rd/faq' && b.attributes?.optimizeForGEO )
	);
	if ( hasFaqSchema ) {
		score += 4;
		details.push( { status: 'ok', text: 'Schema FAQPage prêt à être indexé' } );
	} else {
		details.push( { status: 'warning', text: 'Utilisez le bloc FAQ (mode GEO activé) pour le schema FAQPage' } );
	}

	// LocalBusiness (heuristique : présence d'adresse ou horaires)
	if ( /\b(adresse|horaires?|ouvert|fermé|lundi|mardi|siret|siren|code postal)\b/i.test( fullText ) ) {
		score += 3;
		details.push( { status: 'ok', text: 'Données LocalBusiness probables détectées' } );
	} else {
		details.push( { status: 'warning', text: 'Ajoutez adresse et horaires pour le schema LocalBusiness' } );
	}

	// Service schema
	if ( /\b(nos\s+services?|nos\s+prestations?|ce\s+que\s+nous\s+faisons|ce\s+que\s+nous\s+proposons)\b/i.test( fullText ) ) {
		score += 3;
		details.push( { status: 'ok', text: 'Section services détectée (schema Service)' } );
	} else {
		details.push( { status: 'warning', text: 'Créez une section "Nos services" pour le schema Service' } );
	}

	return { score: Math.min( score, CRITERIA_WEIGHTS.schema ), max: CRITERIA_WEIGHTS.schema, details };
}

// ── Critère 8 : Cohérence titre / contenu (10 pts) ───────────────────────

function analyzeConsistency( blocks, title, fullText ) {
	let score = 0;
	const details = [];

	if ( ! title ) {
		details.push( { status: 'error', text: 'Aucun titre de page défini' } );
		return { score: 0, max: CRITERIA_WEIGHTS.consistency, details };
	}

	// Mots du titre (> 4 lettres) retrouvés dans le contenu
	const titleWords    = title.toLowerCase().split( /\s+/ ).filter( ( w ) => w.length > 4 );
	const matchedWords  = titleWords.filter( ( w ) => fullText.toLowerCase().includes( w ) );
	const ratio         = titleWords.length > 0 ? matchedWords.length / titleWords.length : 0;

	if ( ratio >= 0.6 ) {
		score += 4;
		details.push( { status: 'ok', text: 'Bonne cohérence titre ↔ contenu' } );
	} else if ( ratio >= 0.3 ) {
		score += 2;
		details.push( { status: 'warning', text: 'Répétez davantage les termes du titre dans le texte' } );
	} else {
		details.push( { status: 'error', text: 'Contenu peu cohérent avec le titre de la page' } );
	}

	// Terme service répété au moins 3 fois
	const serviceHits = ( fullText.toLowerCase().match( /\b(site web|application|design|développement|marketing|référencement|création|conseil|formation)\b/gi ) ?? [] ).length;
	if ( serviceHits >= 3 ) {
		score += 3;
		details.push( { status: 'ok', text: `Service répété ${ serviceHits } fois — cohérence forte` } );
	} else {
		details.push( { status: 'warning', text: 'Répétez votre service principal au moins 3 fois dans le texte' } );
	}

	// Titres H2/H3 bien renseignés (au moins 2 headings avec du texte)
	const headingTexts = blocks
		.filter( ( b ) => b.name === 'core/heading' )
		.map( ( b ) => ( b.attributes?.content ?? '' ).replace( /<[^>]+>/g, '' ).trim() )
		.filter( Boolean );

	if ( headingTexts.length >= 2 ) {
		score += 3;
		details.push( { status: 'ok', text: 'Structure de titres cohérente et renseignée' } );
	} else {
		details.push( { status: 'warning', text: 'Ajoutez plus de titres pour une structure cohérente' } );
	}

	return { score: Math.min( score, CRITERIA_WEIGHTS.consistency ), max: CRITERIA_WEIGHTS.consistency, details };
}

// ── Fonction principale d'analyse ─────────────────────────────────────────

/**
 * Analyse le contenu Gutenberg et retourne le score GEO complet.
 *
 * @param {Array}  blocks - Blocs Gutenberg (depuis wp.data select core/block-editor)
 * @param {string} title  - Titre de la page / article
 * @returns {{ score: number, criteria: Object }}
 */
export function analyzeContent( blocks, title = '' ) {
	const flat     = flattenBlocks( blocks );
	const fullText = extractText( flat );

	const criteria = {
		clarity:     analyzeClarity( flat ),
		structure:   analyzeStructure( flat ),
		faq:         analyzeFAQ( flat, fullText ),
		entities:    analyzeEntities( fullText, title ),
		credibility: analyzeCredibility( fullText ),
		summary:     analyzeSummary( flat ),
		schema:      analyzeSchema( flat, fullText ),
		consistency: analyzeConsistency( flat, title, fullText ),
	};

	const score = Object.values( criteria ).reduce( ( sum, c ) => sum + c.score, 0 );

	return { score: Math.min( score, 100 ), criteria };
}

/**
 * Retourne la couleur HEX associée à un ratio score/max.
 *
 * @param {number} score
 * @param {number} max
 * @returns {string} Couleur hex
 */
export function getScoreColor( score, max ) {
	if ( max === 0 ) return '#94a3b8';
	const r = score / max;
	if ( r >= 0.8 ) return '#22c55e';
	if ( r >= 0.5 ) return '#f59e0b';
	return '#ef4444';
}

/**
 * Retourne la couleur globale pour un score sur 100.
 *
 * @param {number} score Score sur 100
 * @returns {string}
 */
export function getGlobalColor( score ) {
	if ( score >= 75 ) return '#22c55e';
	if ( score >= 50 ) return '#f59e0b';
	return '#ef4444';
}

/**
 * Retourne un label textuel pour un ratio score/max.
 *
 * @param {number} score
 * @param {number} max
 * @returns {string}
 */
export function getStatusLabel( score, max ) {
	if ( max === 0 ) return 'N/A';
	const r = score / max;
	if ( r >= 0.8 ) return 'Excellent';
	if ( r >= 0.5 ) return 'Correct';
	if ( r > 0   ) return 'À améliorer';
	return 'Absent';
}
