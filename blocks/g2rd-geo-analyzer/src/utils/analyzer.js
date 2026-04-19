/**
 * Moteur de scoring GEO Analyzer v2
 *
 * 9 critères adaptatifs, détection de domaine métier,
 * priorités sur les recommandations et suggestions IA.
 *
 * @package G2RD
 * @since   1.5.0
 */

// ── Types de pages ─────────────────────────────────────────────────────────

export const PAGE_TYPES = {
	SERVICE:   'service',
	HOME:      'home',
	BLOG_POST: 'blog_post',
	CONTACT:   'contact',
	PORTFOLIO: 'portfolio',
	GENERIC:   'generic',
};

export const PAGE_TYPE_LABELS = {
	[ PAGE_TYPES.SERVICE   ]: 'Page service / prestation',
	[ PAGE_TYPES.HOME      ]: 'Page d\'accueil',
	[ PAGE_TYPES.BLOG_POST ]: 'Article de blog',
	[ PAGE_TYPES.CONTACT   ]: 'Page contact',
	[ PAGE_TYPES.PORTFOLIO ]: 'Portfolio',
	[ PAGE_TYPES.GENERIC   ]: 'Page générique',
};

// ── Domaines métier ────────────────────────────────────────────────────────

export const DOMAIN_VOCABULARIES = {
	vtc:        [ 'chauffeur', 'transfert', 'aéroport', 'gare', 'navette', 'berline', 'limousine', 'vtc', 'taxi' ],
	restaurant: [ 'menu', 'plat', 'réservation', 'carte', 'cuisine', 'chef', 'gastronomique', 'traiteur', 'restaurant' ],
	avocat:     [ 'droit', 'juridique', 'cabinet', 'procédure', 'avocat', 'tribunal', 'litige', 'contrat', 'défense' ],
	artisan:    [ 'devis', 'chantier', 'travaux', 'rénovation', 'installation', 'plombier', 'électricien', 'maçon', 'artisan' ],
	sante:      [ 'patient', 'consultation', 'rendez-vous', 'médecin', 'kiné', 'thérapeute', 'soin', 'clinique', 'cabinet médical' ],
	immo:       [ 'appartement', 'maison', 'location', 'vente', 'loyer', 'bien immobilier', 'agence immobilière', 'propriété' ],
	ecommerce:  [ 'panier', 'commande', 'livraison', 'produit', 'boutique', 'acheter', 'stock', 'shop' ],
	coach:      [ 'coaching', 'accompagnement', 'bilan', 'séance', 'programme', 'objectif', 'transformation', 'coach' ],
};

export const DOMAIN_LABELS = {
	vtc:        'VTC / Transport',
	restaurant: 'Restauration',
	avocat:     'Juridique',
	artisan:    'Artisanat',
	sante:      'Santé',
	immo:       'Immobilier',
	ecommerce:  'E-commerce',
	coach:      'Coaching',
};

// ── Poids adaptatifs — 9 critères, somme = 100 par profil ─────────────────

export const ADAPTIVE_WEIGHTS = {
	[ PAGE_TYPES.SERVICE   ]: { clarity: 15, structure:  9, faq: 12, entities: 11, credibility: 16, summary:  7, schema:  9, consistency:  7, readability: 14 },
	[ PAGE_TYPES.HOME      ]: { clarity:  9, structure:  9, faq:  5, entities: 16, credibility: 18, summary: 11, schema:  7, consistency: 10, readability: 15 },
	[ PAGE_TYPES.BLOG_POST ]: { clarity: 14, structure: 15, faq:  7, entities:  9, credibility:  7, summary: 13, schema:  7, consistency:  9, readability: 19 },
	[ PAGE_TYPES.CONTACT   ]: { clarity:  8, structure:  7, faq:  7, entities: 12, credibility: 28, summary:  7, schema: 11, consistency:  7, readability: 13 },
	[ PAGE_TYPES.PORTFOLIO ]: { clarity: 11, structure: 11, faq:  5, entities: 14, credibility: 14, summary:  9, schema: 12, consistency: 10, readability: 14 },
	[ PAGE_TYPES.GENERIC   ]: { clarity: 12, structure: 12, faq:  9, entities: 12, credibility: 12, summary:  9, schema:  9, consistency:  9, readability: 16 },
};

export const CRITERIA_WEIGHTS = ADAPTIVE_WEIGHTS[ PAGE_TYPES.GENERIC ];

export const CRITERIA_LABELS = {
	clarity:     'Clarté de réponse',
	structure:   'Structure',
	faq:         'FAQ / Q&R',
	entities:    'Entités',
	credibility: 'Crédibilité',
	summary:     'Résumabilité',
	schema:      'Données structurées',
	consistency: 'Cohérence',
	readability: 'Lisibilité IA',
};

// ── Utilitaires ────────────────────────────────────────────────────────────

function flattenBlocks( blocks ) {
	return blocks.reduce( ( acc, block ) => {
		acc.push( block );
		if ( block.innerBlocks?.length ) {
			acc.push( ...flattenBlocks( block.innerBlocks ) );
		}
		return acc;
	}, [] );
}

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

function wordCount( text ) {
	return text.split( /\s+/ ).filter( Boolean ).length;
}

/**
 * Crée un objet détail avec priorité automatique.
 * error → high | warning → medium (par défaut) | ok → null
 * Passer priority explicitement pour forcer 'low' sur les warnings mineurs.
 */
function mkDetail( status, text, priority, extra = {} ) {
	const auto = status === 'error' ? 'high' : status === 'warning' ? 'medium' : null;
	return { status, text, priority: priority !== undefined ? priority : auto, ...extra };
}

// ── Détection du type de page ─────────────────────────────────────────────

export function detectPageType( postType = 'page', title = '', fullText = '' ) {
	const t = title.toLowerCase();
	const c = fullText.toLowerCase();

	if ( postType === 'post' ) return PAGE_TYPES.BLOG_POST;
	if ( [ 'portfolio', 'g2rd_portfolio' ].includes( postType ) ) return PAGE_TYPES.PORTFOLIO;
	if ( /\b(contact|nous contacter|contactez[- ]nous|joindre|prise de contact)\b/.test( t + ' ' + c.slice( 0, 200 ) ) ) {
		return PAGE_TYPES.CONTACT;
	}
	if ( /\b(accueil|bienvenue|homepage|home page)\b/.test( t ) ) return PAGE_TYPES.HOME;

	const serviceKeywords = /\b(service|prestation|offre|forfait|tarif|prix|devis|création|développement|conception|audit|conseil|formation|agence|studio)\b/gi;
	if ( ( ( t + ' ' + c.slice( 0, 400 ) ).match( serviceKeywords ) ?? [] ).length >= 2 ) {
		return PAGE_TYPES.SERVICE;
	}
	return PAGE_TYPES.GENERIC;
}

// ── Détection du domaine métier ───────────────────────────────────────────

export function detectDomain( fullText ) {
	const text = fullText.toLowerCase();
	let bestDomain = null;
	let bestScore  = 1;

	for ( const [ domain, words ] of Object.entries( DOMAIN_VOCABULARIES ) ) {
		const matches = words.filter( ( w ) => text.includes( w ) ).length;
		if ( matches > bestScore ) {
			bestScore  = matches;
			bestDomain = domain;
		}
	}
	return bestDomain;
}

// ── Détection des types de schéma ─────────────────────────────────────────

function detectSchemaTypes( blocks, fullText ) {
	const types = [];

	const hasFaqGeo = blocks.some( ( b ) =>
		b.name === 'g2rd/geo-faq' ||
		b.name === 'yoast/faq-block' ||
		( b.name === 'g2rd/faq' && b.attributes?.optimizeForGEO )
	);
	if ( hasFaqGeo ) types.push( 'FAQPage' );

	if ( /\b(adresse|horaires?|ouvert|fermé|lundi|mardi|siret|code postal)\b/i.test( fullText ) ) {
		types.push( 'LocalBusiness' );
	}
	if ( /\b(notre (équipe|agence|société|entreprise)|fondé en|créé en|à propos|qui sommes[- ]nous)\b/i.test( fullText ) ) {
		types.push( 'Organization' );
	}
	if ( /\b(nos services?|nos prestations?|ce que nous (faisons|proposons|offrons))\b/i.test( fullText ) ) {
		types.push( 'Service' );
	}
	if ( /\b(\d+\s*€|ajouter au panier|acheter maintenant|commander|en stock)\b/i.test( fullText ) ) {
		types.push( 'Product' );
	}
	return types;
}

// ── Critère 1 : Clarté (12 pts GENERIC) ───────────────────────────────────

function analyzeClarity( blocks ) {
	let score = 0;
	const details = [];

	const firstPara = blocks.find( ( b ) => b.name === 'core/paragraph' );
	if ( ! firstPara ) {
		details.push( mkDetail( 'error', 'Aucun paragraphe d\'introduction trouvé' ) );
		return { score: 0, max: CRITERIA_WEIGHTS.clarity, details };
	}

	const raw = ( firstPara.attributes?.content ?? '' ).replace( /<[^>]+>/g, '' );
	const wc  = wordCount( raw );

	if ( wc >= 30 && wc <= 120 ) {
		score += 5;
		details.push( mkDetail( 'ok', `Introduction bien dimensionnée (${ wc } mots)` ) );
	} else if ( wc > 0 ) {
		score += 2;
		const msg = wc < 30
			? `Introduction trop courte (${ wc } mots — visez 30 min.)`
			: `Introduction trop longue (${ wc } mots — visez 120 max.)`;
		details.push( mkDetail( 'warning', msg, 'medium' ) );
	}

	if ( /\b(permet|aide|offre|propose|fournit|assure|garantit|réalise|accompagne|spécialisé|expert)\b/i.test( raw ) ) {
		score += 4;
		details.push( mkDetail( 'ok', 'Phrase explicative du service présente' ) );
	} else {
		details.push( mkDetail( 'warning', 'Ajoutez une phrase qui explique clairement votre service', 'medium' ) );
	}

	const sentences = raw.split( /[.!?]+/ ).filter( ( s ) => s.trim().length > 10 );
	if ( sentences.length > 0 ) {
		const avgW = sentences.reduce( ( s, sent ) => s + wordCount( sent ), 0 ) / sentences.length;
		if ( avgW <= 25 ) {
			score += 3;
			details.push( mkDetail( 'ok', `Phrases lisibles (moy. ${ Math.round( avgW ) } mots)` ) );
		} else {
			details.push( mkDetail( 'warning', `Raccourcissez vos phrases (moy. ${ Math.round( avgW ) } mots, max. 25)`, 'low' ) );
		}
	}

	return { score: Math.min( score, CRITERIA_WEIGHTS.clarity ), max: CRITERIA_WEIGHTS.clarity, details };
}

// ── Critère 2 : Structure (12 pts GENERIC) ────────────────────────────────

function analyzeStructure( blocks ) {
	let score = 0;
	const details = [];

	const headings = blocks.filter( ( b ) => b.name === 'core/heading' );
	const h2       = headings.filter( ( b ) => b.attributes?.level === 2 );
	const h3       = headings.filter( ( b ) => b.attributes?.level === 3 );

	if ( h2.length >= 2 ) {
		score += 5;
		details.push( mkDetail( 'ok', `${ h2.length } titres H2 — structure claire` ) );
	} else if ( h2.length === 1 ) {
		score += 2;
		details.push( mkDetail( 'warning', '1 seul H2 — ajoutez-en au moins un autre', 'medium' ) );
	} else {
		details.push( mkDetail( 'error', 'Aucun H2 — la structure est absente' ) );
	}

	if ( h3.length >= 2 ) {
		score += 4;
		details.push( mkDetail( 'ok', `${ h3.length } sous-titres H3 présents` ) );
	} else if ( h3.length === 1 ) {
		score += 2;
		details.push( mkDetail( 'warning', 'Ajoutez des H3 pour détailler les sections', 'low' ) );
	} else {
		details.push( mkDetail( 'warning', 'Aucun H3 — subdivisez vos sections', 'low' ) );
	}

	const paragraphs = blocks.filter( ( b ) => b.name === 'core/paragraph' );
	const tooLong    = paragraphs.filter( ( b ) => wordCount( ( b.attributes?.content ?? '' ).replace( /<[^>]+>/g, '' ) ) > 80 );

	if ( paragraphs.length > 0 && tooLong.length === 0 ) {
		score += 3;
		details.push( mkDetail( 'ok', 'Tous les paragraphes bien dimensionnés' ) );
	} else if ( tooLong.length > 0 ) {
		details.push( mkDetail( 'warning', `${ tooLong.length } paragraphe(s) à découper (> 80 mots)`, 'low' ) );
	}

	return { score: Math.min( score, CRITERIA_WEIGHTS.structure ), max: CRITERIA_WEIGHTS.structure, details };
}

// ── Critère 3 : FAQ / Q&R (9 pts GENERIC) ─────────────────────────────────

function analyzeFAQ( blocks, fullText ) {
	let score = 0;
	const details = [];

	const faqBlock  = blocks.find( ( b ) => b.name === 'g2rd/faq' || b.name === 'g2rd/geo-faq' );
	const hasGeoFaq = !! faqBlock && ( faqBlock.name === 'g2rd/geo-faq' || faqBlock.attributes?.optimizeForGEO );
	const hasAnyFaq = hasGeoFaq || blocks.some( ( b ) => [ 'yoast/faq-block', 'g2rd/faq', 'core/faq' ].includes( b.name ) );

	if ( hasGeoFaq ) {
		score += 3;
		details.push( mkDetail( 'ok', 'Bloc FAQ en mode GEO — schema FAQPage actif' ) );

		const items = ( faqBlock?.attributes?.items ?? [] ).filter( ( i ) => i.question && i.answer );

		if ( items.length >= 5 ) {
			score += 2;
			details.push( mkDetail( 'ok', `${ items.length } questions — excellent` ) );
		} else if ( items.length >= 3 ) {
			score += 1;
			details.push( mkDetail( 'warning', `${ items.length } questions — visez 5 pour maximiser la couverture`, 'low' ) );
		} else if ( items.length > 0 ) {
			details.push( mkDetail( 'error', `Seulement ${ items.length } question(s) — ajoutez-en au moins 3` ) );
		} else {
			details.push( mkDetail( 'error', 'Le bloc FAQ est vide — ajoutez au moins 3 questions/réponses' ) );
		}

		if ( items.length > 0 ) {
			const avgAnswerLen = items.reduce(
				( sum, i ) => sum + wordCount( i.answer.replace( /<[^>]+>/g, '' ) ), 0
			) / items.length;

			if ( avgAnswerLen >= 30 ) {
				score += 1;
				details.push( mkDetail( 'ok', `Réponses détaillées (moy. ${ Math.round( avgAnswerLen ) } mots)` ) );
			} else {
				details.push( mkDetail( 'warning', `Étoffez vos réponses (moy. ${ Math.round( avgAnswerLen ) } mots, visez 30+)`, 'medium' ) );
			}
		}
	} else if ( hasAnyFaq ) {
		score += 2;
		details.push( mkDetail( 'warning', 'Bloc FAQ présent — activez le mode GEO pour le schema FAQPage', 'medium' ) );
	} else {
		details.push( mkDetail( 'error', 'Ajoutez un bloc FAQ et activez le mode GEO (≥ 3 questions)', 'high', {
			block: { name: 'g2rd/faq', attributes: { optimizeForGEO: true } },
		} ) );
	}

	const qCount = ( fullText.match( /\?/g ) ?? [] ).length;
	if ( qCount >= 3 ) {
		score += 3;
		details.push( mkDetail( 'ok', `${ qCount } questions dans le contenu` ) );
	} else if ( qCount >= 1 ) {
		score += 1;
		details.push( mkDetail( 'warning', `${ qCount } question(s) détectée(s) — visez au moins 3`, 'low' ) );
	} else {
		details.push( mkDetail( 'warning', 'Aucune question — adoptez un format Q&R', 'medium' ) );
	}

	return { score: Math.min( score, CRITERIA_WEIGHTS.faq ), max: CRITERIA_WEIGHTS.faq, details };
}

// ── Critère 4 : Entités (12 pts GENERIC) ──────────────────────────────────

function analyzeEntities( fullText, title, domain ) {
	let score = 0;
	const details = [];
	const text = ( title + ' ' + fullText ).toLowerCase();

	const hasCompany = /\b(agence|cabinet|studio|société|entreprise|sarl|sas|sasu|eurl|freelance)\b/i.test( fullText )
		|| /(?:chez|par|avec|l'équipe|notre agence)\s+[A-ZÀÉÈ]/u.test( fullText );
	if ( hasCompany ) {
		score += 4;
		details.push( mkDetail( 'ok', 'Entité entreprise détectée' ) );
	} else {
		details.push( mkDetail( 'warning', 'Mentionnez le nom ou le type de votre structure', 'medium' ) );
	}

	const hasLocation = /\b(paris|lyon|marseille|bordeaux|toulouse|nantes|lille|strasbourg|rennes|nice|grenoble|montpellier|région|département|france|\bà\s+[A-ZÀÉÈ][a-zàéè]+)\b/iu.test( fullText );
	if ( hasLocation ) {
		score += 3;
		details.push( mkDetail( 'ok', 'Localisation géographique présente' ) );
	} else {
		details.push( mkDetail( 'error', 'Ajoutez votre ville dans l\'introduction' ) );
	}

	const hasService = /\b(création|développement|conception|design|marketing|référencement|seo|audit|conseil|formation|accompagnement|maintenance|hébergement|site web|application|e-commerce|vitrine|wordpress)\b/i.test( text );
	if ( hasService ) {
		score += 3;
		details.push( mkDetail( 'ok', 'Service principal identifiable' ) );
	} else {
		details.push( mkDetail( 'warning', 'Nommez explicitement votre service principal', 'medium' ) );
	}

	if ( domain && DOMAIN_VOCABULARIES[ domain ] ) {
		const domainWords = DOMAIN_VOCABULARIES[ domain ];
		const domainHits  = domainWords.filter( ( w ) => fullText.toLowerCase().includes( w ) ).length;
		if ( domainHits >= 3 ) {
			score += 2;
			details.push( mkDetail( 'ok', `Vocabulaire ${ DOMAIN_LABELS[ domain ] } bien présent (${ domainHits } termes)` ) );
		} else {
			details.push( mkDetail( 'warning', `Vocabulaire ${ DOMAIN_LABELS[ domain ] } insuffisant — ajoutez des termes spécifiques`, 'low' ) );
		}
	} else {
		const metierWords = new Set( ( fullText.match( /\b[A-ZÀÉÈ][a-zàéè]{4,}\b/gu ) ?? [] ) );
		if ( metierWords.size >= 5 ) {
			score += 2;
			details.push( mkDetail( 'ok', `Vocabulaire métier présent (${ metierWords.size } termes)` ) );
		} else {
			details.push( mkDetail( 'warning', 'Enrichissez le vocabulaire métier de votre domaine', 'low' ) );
		}
	}

	return { score: Math.min( score, CRITERIA_WEIGHTS.entities ), max: CRITERIA_WEIGHTS.entities, details };
}

// ── Critère 5 : Crédibilité (12 pts GENERIC) ──────────────────────────────

function analyzeCredibility( fullText ) {
	let score = 0;
	const details = [];
	let signals   = 0;

	const numbersMatch = fullText.match( /\d+\s*(%|ans?|projets?|clients?|années?|mois|jours?|€|\$|k€)/gi ) ?? [];
	if ( numbersMatch.length >= 2 ) {
		score += 3;
		signals++;
		details.push( mkDetail( 'ok', `${ numbersMatch.length } chiffre(s) clé(s) — preuves concrètes` ) );
	} else {
		details.push( mkDetail( 'warning', 'Ajoutez des chiffres concrets (ex. : 150 clients, 10 ans)', 'medium' ) );
	}

	if ( /\b(avis|témoignage|recommande|satisfaction|clients? satisfaits?|ils nous font confiance|ce qu[''']ils disent|★|⭐|\d[\.,]\d\s*\/\s*5)\b/i.test( fullText ) ) {
		score += 3;
		signals++;
		details.push( mkDetail( 'ok', 'Témoignage ou preuve sociale détecté' ) );
	} else {
		details.push( mkDetail( 'warning', 'Ajoutez un témoignage client ou des avis', 'medium' ) );
	}

	if ( /(\b0[1-9](\s?\d{2}){4}\b|[\w.+-]+@[\w-]+\.[a-z]{2,}|\b(contactez|appelez|formulaire de contact)\b)/i.test( fullText ) ) {
		score += 3;
		signals++;
		details.push( mkDetail( 'ok', 'Informations de contact présentes' ) );
	} else {
		details.push( mkDetail( 'error', 'Ajoutez vos coordonnées (email, téléphone)' ) );
	}

	if ( /\b(tarif|prix|devis|à partir de|\d+\s*€|gratuit|dès\s+\d|délai|sous\s+\d+|en\s+\d+\s*jours?|semaines?)\b/i.test( fullText ) ) {
		score += 2;
		signals++;
		details.push( mkDetail( 'ok', 'Tarification ou délai mentionné' ) );
	} else {
		details.push( mkDetail( 'warning', 'Indiquez un tarif indicatif ou un délai de livraison', 'medium' ) );
	}

	if ( /\b([A-ZÀÉÈ]{2,}[a-zàéè]*\s+(SARL|SAS|SASU|EURL|SA)|notre (agence|société|cabinet|équipe))\b/u.test( fullText ) ) {
		score += 1;
		signals++;
		details.push( mkDetail( 'ok', 'Nom d\'entreprise mentionné explicitement' ) );
	}

	if ( signals <= 1 ) {
		details.push( mkDetail( 'warning', `Crédibilité faible (${ signals } signal — visez 3+)`, 'high' ) );
	} else if ( signals === 2 ) {
		details.push( mkDetail( 'warning', `Crédibilité correcte (${ signals } signaux — ajoutez encore 1)`, 'medium' ) );
	} else {
		details.push( mkDetail( 'ok', `Crédibilité solide (${ signals } signaux de confiance)` ) );
	}

	return { score: Math.min( score, CRITERIA_WEIGHTS.credibility ), max: CRITERIA_WEIGHTS.credibility, details };
}

// ── Critère 6 : Résumabilité (9 pts GENERIC) ──────────────────────────────

function analyzeSummary( blocks ) {
	let score = 0;
	const details = [];

	if ( blocks.some( ( b ) => b.name === 'g2rd/geo-summary' ) ) {
		score += 6;
		details.push( mkDetail( 'ok', 'Bloc Résumé GEO présent — parfait pour les IA' ) );
	} else {
		details.push( mkDetail( 'error', 'Ajoutez un bloc "Résumé GEO" en haut de page', 'high', {
			block: { name: 'g2rd/geo-summary', attributes: {} },
		} ) );
	}

	const lists = blocks.filter( ( b ) => b.name === 'core/list' );
	if ( lists.length >= 1 ) {
		score += 3;
		details.push( mkDetail( 'ok', `${ lists.length } liste(s) à puces — contenu scannable` ) );
	} else {
		details.push( mkDetail( 'warning', 'Ajoutez au moins une liste de points clés', 'medium' ) );
	}

	return { score: Math.min( score, CRITERIA_WEIGHTS.summary ), max: CRITERIA_WEIGHTS.summary, details };
}

// ── Critère 7 : Données structurées (9 pts GENERIC) ───────────────────────

function analyzeSchema( blocks, fullText ) {
	let score = 0;
	const details    = [];
	const schemaTypes = detectSchemaTypes( blocks, fullText );

	if ( schemaTypes.length === 0 ) {
		details.push( mkDetail( 'error', 'Aucune donnée structurée détectée — ajoutez un bloc FAQ GEO ou une section "Nos services"' ) );
		return { score: 0, max: CRITERIA_WEIGHTS.schema, details, schemaTypes };
	}

	if ( schemaTypes.includes( 'FAQPage' ) ) {
		score += 4;
		details.push( mkDetail( 'ok', 'Schema FAQPage prêt à l\'indexation' ) );
	} else {
		details.push( mkDetail( 'warning', 'Utilisez le bloc FAQ (mode GEO activé) pour le schema FAQPage', 'medium' ) );
	}

	if ( schemaTypes.includes( 'LocalBusiness' ) ) {
		score += 3;
		details.push( mkDetail( 'ok', 'Données LocalBusiness détectées (adresse / horaires)' ) );
	} else {
		details.push( mkDetail( 'warning', 'Ajoutez adresse et horaires pour le schema LocalBusiness', 'low' ) );
	}

	if ( schemaTypes.includes( 'Service' ) ) {
		score += 2;
		details.push( mkDetail( 'ok', 'Section services détectée (schema Service)' ) );
	} else if ( schemaTypes.includes( 'Organization' ) ) {
		score += 2;
		details.push( mkDetail( 'ok', 'Présence Organisation / Équipe détectée (schema Organization)' ) );
	} else if ( schemaTypes.includes( 'Product' ) ) {
		score += 2;
		details.push( mkDetail( 'ok', 'Données produit/prix détectées (schema Product)' ) );
	} else {
		details.push( mkDetail( 'warning', 'Créez une section "Nos services" pour le schema Service', 'low' ) );
	}

	details.push( mkDetail( 'ok', `Types détectés : ${ schemaTypes.join( ', ' ) }` ) );

	return { score: Math.min( score, CRITERIA_WEIGHTS.schema ), max: CRITERIA_WEIGHTS.schema, details, schemaTypes };
}

// ── Critère 8 : Cohérence (9 pts GENERIC) ─────────────────────────────────

function analyzeConsistency( blocks, title, fullText ) {
	let score = 0;
	const details = [];

	if ( ! title ) {
		details.push( mkDetail( 'error', 'Aucun titre de page défini' ) );
		return { score: 0, max: CRITERIA_WEIGHTS.consistency, details };
	}

	const titleWords   = title.toLowerCase().split( /\s+/ ).filter( ( w ) => w.length > 4 );
	const matchedWords = titleWords.filter( ( w ) => fullText.toLowerCase().includes( w ) );
	const ratio        = titleWords.length > 0 ? matchedWords.length / titleWords.length : 0;

	if ( ratio >= 0.6 ) {
		score += 3;
		details.push( mkDetail( 'ok', 'Bonne cohérence titre ↔ contenu' ) );
	} else if ( ratio >= 0.3 ) {
		score += 1;
		details.push( mkDetail( 'warning', 'Répétez davantage les termes du titre dans le texte', 'medium' ) );
	} else {
		details.push( mkDetail( 'error', 'Contenu peu cohérent avec le titre de la page' ) );
	}

	const serviceHits = ( fullText.toLowerCase().match( /\b(site web|application|design|développement|marketing|référencement|création|conseil|formation)\b/gi ) ?? [] ).length;
	if ( serviceHits >= 3 ) {
		score += 3;
		details.push( mkDetail( 'ok', `Service répété ${ serviceHits } fois — cohérence forte` ) );
	} else {
		details.push( mkDetail( 'warning', 'Répétez votre service principal au moins 3 fois dans le texte', 'medium' ) );
	}

	const headingTexts = blocks
		.filter( ( b ) => b.name === 'core/heading' )
		.map( ( b ) => ( b.attributes?.content ?? '' ).replace( /<[^>]+>/g, '' ).trim() )
		.filter( Boolean );

	if ( headingTexts.length >= 2 ) {
		score += 3;
		details.push( mkDetail( 'ok', 'Structure de titres cohérente et renseignée' ) );
	} else {
		details.push( mkDetail( 'warning', 'Ajoutez plus de titres pour une structure cohérente', 'low' ) );
	}

	return { score: Math.min( score, CRITERIA_WEIGHTS.consistency ), max: CRITERIA_WEIGHTS.consistency, details };
}

// ── Critère 9 : Lisibilité IA (16 pts GENERIC) ────────────────────────────

function analyzeReadability( blocks ) {
	let score = 0;
	const details = [];

	const paragraphs = blocks.filter( ( b ) => b.name === 'core/paragraph' );
	if ( paragraphs.length === 0 ) {
		details.push( mkDetail( 'error', 'Aucun paragraphe — impossible d\'analyser la lisibilité' ) );
		return { score: 0, max: CRITERIA_WEIGHTS.readability, details };
	}

	// Longueur moyenne des phrases (< 18 mots = idéal pour une IA)
	const allText   = paragraphs.map( ( b ) => ( b.attributes?.content ?? '' ).replace( /<[^>]+>/g, '' ) ).join( ' ' );
	const sentences = allText.split( /[.!?]+/ ).filter( ( s ) => s.trim().length > 5 );

	if ( sentences.length > 0 ) {
		const avgSentLen = sentences.reduce( ( s, sent ) => s + wordCount( sent ), 0 ) / sentences.length;
		if ( avgSentLen <= 18 ) {
			score += 6;
			details.push( mkDetail( 'ok', `Phrases courtes — idéales pour les IA (moy. ${ Math.round( avgSentLen ) } mots)` ) );
		} else if ( avgSentLen <= 25 ) {
			score += 3;
			details.push( mkDetail( 'warning', `Phrases raccourcissables (moy. ${ Math.round( avgSentLen ) } mots, visez ≤ 18)`, 'low' ) );
		} else {
			details.push( mkDetail( 'error', `Phrases trop longues (moy. ${ Math.round( avgSentLen ) } mots) — difficiles à résumer pour une IA` ) );
		}
	}

	// Longueur moyenne des paragraphes (< 50 mots = scannable)
	const avgParaLen = paragraphs.reduce(
		( s, b ) => s + wordCount( ( b.attributes?.content ?? '' ).replace( /<[^>]+>/g, '' ) ), 0
	) / paragraphs.length;

	if ( avgParaLen <= 50 ) {
		score += 5;
		details.push( mkDetail( 'ok', `Paragraphes courts et scannables (moy. ${ Math.round( avgParaLen ) } mots)` ) );
	} else if ( avgParaLen <= 80 ) {
		score += 2;
		details.push( mkDetail( 'warning', `Paragraphes trop longs (moy. ${ Math.round( avgParaLen ) } mots, visez < 50)`, 'medium' ) );
	} else {
		details.push( mkDetail( 'error', `Paragraphes trop denses (moy. ${ Math.round( avgParaLen ) } mots) — découpez-les` ) );
	}

	// Ratio titres / paragraphes (1 titre pour 3–4 paragraphes = bon)
	const headingCount = blocks.filter( ( b ) => b.name === 'core/heading' ).length;
	const ratio        = paragraphs.length > 0 ? headingCount / paragraphs.length : 0;

	if ( ratio >= 0.25 ) {
		score += 5;
		details.push( mkDetail( 'ok', 'Bonne densité de titres — structure claire pour les IA' ) );
	} else if ( ratio >= 0.1 ) {
		score += 2;
		details.push( mkDetail( 'warning', 'Ajoutez plus de titres H2/H3 pour guider les IA', 'medium' ) );
	} else {
		details.push( mkDetail( 'error', 'Très peu de titres — la page est difficile à scanner pour une IA' ) );
	}

	return { score: Math.min( score, CRITERIA_WEIGHTS.readability ), max: CRITERIA_WEIGHTS.readability, details };
}

// ── Suggestions IA ────────────────────────────────────────────────────────

const FAQ_SUGGESTION_TEMPLATES = {
	[ PAGE_TYPES.SERVICE ]: [
		( t ) => `Combien coûte ${ t || 'ce service' } ?`,
		( t ) => `Quel est le délai pour ${ t || 'ce service' } ?`,
		( t ) => `Comment se déroule ${ t || 'la prestation' } ?`,
		()    => 'Proposez-vous un devis gratuit ?',
		()    => 'Êtes-vous disponibles rapidement ?',
	],
	[ PAGE_TYPES.HOME ]: [
		()    => 'Qui êtes-vous et que faites-vous ?',
		()    => 'Quels services proposez-vous ?',
		()    => 'Dans quelle zone intervenez-vous ?',
	],
	[ PAGE_TYPES.BLOG_POST ]: [
		( t ) => `Qu\'est-ce que ${ t || 'ce sujet' } ?`,
		()    => 'Comment appliquer ces conseils concrètement ?',
		()    => 'Quels sont les points essentiels à retenir ?',
	],
	[ PAGE_TYPES.CONTACT ]: [
		()    => 'Comment vous contacter rapidement ?',
		()    => 'Quel est votre délai de réponse ?',
		()    => 'Proposez-vous un devis gratuit ?',
	],
	[ PAGE_TYPES.PORTFOLIO ]: [
		()    => 'Quels types de projets réalisez-vous ?',
		()    => 'Quels sont vos délais habituels ?',
		()    => 'Comment démarrer un projet avec vous ?',
	],
};

const STOP_WORDS = new Set( [
	'dans', 'pour', 'avec', 'vous', 'nous', 'votre', 'notre', 'les', 'des',
	'une', 'est', 'sont', 'cette', 'tout', 'plus', 'bien', 'peut', 'très',
	'aussi', 'comme', 'mais', 'donc', 'ainsi', 'entre', 'sans', 'leurs',
	'être', 'avoir', 'faire', 'dire', 'aller', 'voir', 'vouloir', 'venir',
] );

export function generateAISuggestions( blocks, title, fullText, pageType ) {
	const suggestions = { summary: null, faqQuestions: [] };

	// Résumé suggéré à partir du titre + premier paragraphe + mots-clés
	const firstPara     = blocks.find( ( b ) => b.name === 'core/paragraph' );
	const firstParaText = firstPara
		? ( firstPara.attributes?.content ?? '' ).replace( /<[^>]+>/g, '' ).trim()
		: '';

	if ( title && firstParaText.length > 20 ) {
		const intro = firstParaText.length > 180
			? firstParaText.slice( 0, 177 ) + '…'
			: firstParaText;

		const wordsArr    = fullText.toLowerCase().split( /\s+/ ).filter( ( w ) => w.length > 4 && ! STOP_WORDS.has( w ) );
		const freq        = {};
		wordsArr.forEach( ( w ) => { freq[ w ] = ( freq[ w ] || 0 ) + 1; } );
		const topKeywords = Object.entries( freq )
			.sort( ( a, b ) => b[ 1 ] - a[ 1 ] )
			.slice( 0, 3 )
			.map( ( [ w ] ) => w );

		const kwHint = topKeywords.length ? ` Mots-clés : ${ topKeywords.join( ', ' ) }.` : '';
		suggestions.summary = `${ title } — ${ intro }${ intro.endsWith( '.' ) ? '' : '.' }${ kwHint }`;
	}

	// 3 questions FAQ adaptées au type de page
	const templates  = FAQ_SUGGESTION_TEMPLATES[ pageType ] ?? FAQ_SUGGESTION_TEMPLATES[ PAGE_TYPES.SERVICE ];
	const shortTitle = title ? title.split( /\s+/ ).slice( 0, 4 ).join( ' ' ) : '';
	suggestions.faqQuestions = templates.slice( 0, 3 ).map( ( fn ) => fn( shortTitle ) );

	return suggestions;
}

// ── Fonction principale d'analyse ─────────────────────────────────────────

export function analyzeContent( blocks, title = '', postType = 'page' ) {
	const flat     = flattenBlocks( blocks );
	const fullText = extractText( flat );

	const pageType        = detectPageType( postType, title, fullText );
	const domain          = detectDomain( fullText );
	const adaptiveWeights = ADAPTIVE_WEIGHTS[ pageType ];

	const rawCriteria = {
		clarity:     analyzeClarity( flat ),
		structure:   analyzeStructure( flat ),
		faq:         analyzeFAQ( flat, fullText ),
		entities:    analyzeEntities( fullText, title, domain ),
		credibility: analyzeCredibility( fullText ),
		summary:     analyzeSummary( flat ),
		schema:      analyzeSchema( flat, fullText ),
		consistency: analyzeConsistency( flat, title, fullText ),
		readability: analyzeReadability( flat ),
	};

	const criteria = {};
	for ( const [ key, raw ] of Object.entries( rawCriteria ) ) {
		const newMax      = adaptiveWeights[ key ];
		const scaledScore = raw.max > 0 ? Math.round( ( raw.score / raw.max ) * newMax ) : 0;
		criteria[ key ]   = { ...raw, score: Math.min( scaledScore, newMax ), max: newMax };
	}

	const score         = Object.values( criteria ).reduce( ( sum, c ) => sum + c.score, 0 );
	const aiSuggestions = generateAISuggestions( flat, title, fullText, pageType );

	return { score: Math.min( score, 100 ), criteria, pageType, domain, aiSuggestions };
}

// ── Couleurs et labels ─────────────────────────────────────────────────────

export function getScoreColor( score, max ) {
	if ( max === 0 ) return '#94a3b8';
	const r = score / max;
	if ( r >= 0.8 ) return '#22c55e';
	if ( r >= 0.5 ) return '#f59e0b';
	return '#ef4444';
}

export function getGlobalColor( score ) {
	if ( score >= 75 ) return '#22c55e';
	if ( score >= 50 ) return '#f59e0b';
	return '#ef4444';
}

export function getStatusLabel( score, max ) {
	if ( max === 0 ) return 'N/A';
	const r = score / max;
	if ( r >= 0.8 ) return 'Excellent';
	if ( r >= 0.5 ) return 'Correct';
	if ( r > 0   ) return 'À améliorer';
	return 'Absent';
}
