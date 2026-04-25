import { useState, useEffect, useCallback } from '@wordpress/element';
import { useBlockProps, InspectorControls, PanelColorSettings } from '@wordpress/block-editor';
import {
	PanelBody, PanelRow, RangeControl, ToggleControl, SelectControl,
	TextControl, Button, ColorPalette, Spinner, Notice, TextareaControl,
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const API_BASE = window.wpApiSettings?.root
	? window.wpApiSettings.root.replace( /\/$/, '' )
	: '/wp-json';
const NONCE = window.wpApiSettings?.nonce || '';

const DESIGNS = [
	{ label: __( 'Cartes (ombres)', 'g2rd' ),       value: 'cards' },
	{ label: __( 'Minimal (lignes)', 'g2rd' ),      value: 'minimal' },
	{ label: __( 'Gradient (coloré)', 'g2rd' ),     value: 'gradient' },
	{ label: __( 'Encadré (bordures)', 'g2rd' ),    value: 'bordered' },
	{ label: __( 'Glassmorphism', 'g2rd' ),         value: 'glass' },
];

const DEFAULT_ORDER = ['badge','title','subtitle','price','description','features','cta'];

/** Génère un ID unique pour une nouvelle colonne */
function genId() {
	return 'col-' + Date.now().toString(36) + Math.random().toString(36).slice(2,6);
}

/** Colonne vide par défaut */
function emptyColumn() {
	return {
		id:           genId(),
		title:        __( 'Nouveau plan', 'g2rd' ),
		subtitle:     '',
		price:        '0',
		pricePeriod:  __( '/ mois', 'g2rd' ),
		pricePrefix:  '',
		description:  '',
		features:     [ __( 'Fonctionnalité 1', 'g2rd' ) ],
		ctaText:      __( 'Choisir', 'g2rd' ),
		ctaUrl:       '#',
		ctaTarget:    false,
		badge:        '',
		isFeatured:   false,
		accentColor:  '',
		productSource:'',
		productId:    0,
		elementsOrder: [...DEFAULT_ORDER],
	};
}

/** Aperçu d'une colonne dans l'éditeur */
function ColumnPreview( { col, attrs } ) {
	const {
		showTitle, showSubtitle, showPrice, showDescription,
		showFeatures, showCta, showBadge, design, showBoxShadow,
		featureIcon, borderRadius, globalAccentColor,
	} = attrs;

	const accent = col.accentColor || globalAccentColor || 'var(--wp--preset--color--primary, #2F425D)';
	const radius = ( borderRadius || 12 ) + 'px';

	const shadow = showBoxShadow
		? '0 4px 24px rgba(0,0,0,0.12)'
		: 'none';

	const cardStyle = {
		borderRadius: radius,
		boxShadow:    shadow,
		position:     'relative',
		overflow:     'hidden',
		transition:   'transform 0.2s',
		transform:    col.isFeatured && attrs.featuredScale ? 'scale(1.04)' : 'scale(1)',
		background:   design === 'gradient'
			? `linear-gradient(135deg, ${accent}22 0%, ${accent}08 100%)`
			: design === 'glass'
			? 'rgba(255,255,255,0.18)'
			: ( attrs.globalBgColor || '#fff' ),
		backdropFilter: design === 'glass' ? 'blur(10px)' : 'none',
		border:       design === 'bordered' ? `2px solid ${accent}` :
		              design === 'glass'    ? '1px solid rgba(255,255,255,0.3)' :
		              design === 'minimal'  ? `0 0 0 1px #e5e7eb` :
		              col.isFeatured        ? `2px solid ${accent}` : '1px solid #e5e7eb',
		padding:      '28px 24px',
		color:        attrs.globalTextColor || 'inherit',
	};

	const order = col.elementsOrder || DEFAULT_ORDER;

	const elements = {
		badge: showBadge && col.badge ? (
			<div key="badge" style={{
				display:'inline-block', background: accent, color:'#fff',
				borderRadius:'999px', padding:'3px 14px', fontSize:'12px',
				fontWeight:700, marginBottom:'12px', letterSpacing:'0.05em',
			}}>
				{ col.badge }
			</div>
		) : null,
		title: showTitle && col.title ? (
			<div key="title" style={{ fontWeight:700, fontSize:'22px', marginBottom:'4px' }}>{ col.title }</div>
		) : null,
		subtitle: showSubtitle && col.subtitle ? (
			<div key="subtitle" style={{ fontSize:'14px', opacity:0.7, marginBottom:'16px' }}>{ col.subtitle }</div>
		) : null,
		price: showPrice && col.price ? (
			<div key="price" style={{ margin:'16px 0', borderTop: design==='minimal' ? `2px solid ${accent}` : 'none', paddingTop: design==='minimal' ? '16px':'0' }}>
				<span style={{ fontSize:'42px', fontWeight:800, color: accent }}>
					{ col.pricePrefix }{ col.price }
				</span>
				{ col.pricePeriod && (
					<span style={{ fontSize:'14px', opacity:0.6, marginLeft:'4px' }}>{ col.pricePeriod }</span>
				) }
			</div>
		) : null,
		description: showDescription && col.description ? (
			<div key="desc" style={{ fontSize:'14px', opacity:0.8, marginBottom:'16px', lineHeight:1.6 }}>{ col.description }</div>
		) : null,
		features: showFeatures && col.features?.length ? (
			<ul key="features" style={{ listStyle:'none', padding:0, margin:'0 0 20px', fontSize:'14px' }}>
				{ col.features.map( ( f, i ) => (
					<li key={i} style={{ display:'flex', alignItems:'center', gap:'8px', marginBottom:'8px' }}>
						<span style={{ color: accent, fontWeight:700, flexShrink:0 }}>{ featureIcon || '✓' }</span>
						{ f }
					</li>
				) ) }
			</ul>
		) : null,
		cta: showCta && col.ctaText ? (
			<a key="cta" style={{
				display:'block', textAlign:'center', padding:'12px 24px',
				borderRadius:'8px', fontWeight:700, fontSize:'15px',
				background: accent, color:'#fff', textDecoration:'none',
				cursor:'pointer',
			}}>
				{ col.ctaText }
			</a>
		) : null,
	};

	return (
		<div style={ cardStyle }>
			{ order.map( key => elements[key] || null ) }
		</div>
	);
}

export default function Edit( { attributes, setAttributes } ) {
	const {
		columns, design, columnCount, showTitle, showSubtitle, showPrice,
		showDescription, showFeatures, showCta, showBadge, showBoxShadow,
		featuredScale, globalAccentColor, globalTextColor, globalBgColor,
		featureIcon, borderRadius, gapSize,
	} = attributes;

	const blockProps = useBlockProps( {
		className: `g2rd-pricing-table g2rd-pricing-table--${design}`,
	} );

	// ── Onglet actif dans la sidebar ──────────────────────────────────────────
	const [ activeColTab, setActiveColTab ] = useState( 0 );
	const [ productSearch, setProductSearch ] = useState( {} );
	const [ products, setProducts ] = useState( {} );
	const [ loadingProducts, setLoadingProducts ] = useState( {} );

	// ── Gestion des colonnes ──────────────────────────────────────────────────
	const updateColumn = useCallback( ( idx, key, value ) => {
		const next = columns.map( ( col, i ) =>
			i === idx ? { ...col, [key]: value } : col
		);
		setAttributes( { columns: next } );
	}, [ columns, setAttributes ] );

	const addColumn = () => {
		if ( columns.length >= 5 ) return;
		setAttributes( { columns: [ ...columns, emptyColumn() ], columnCount: columns.length + 1 } );
		setActiveColTab( columns.length );
	};

	const removeColumn = ( idx ) => {
		if ( columns.length <= 1 ) return;
		const next = columns.filter( ( _, i ) => i !== idx );
		setAttributes( { columns: next, columnCount: next.length } );
		setActiveColTab( Math.min( activeColTab, next.length - 1 ) );
	};

	const moveColumn = ( idx, dir ) => {
		const next = [ ...columns ];
		const target = idx + dir;
		if ( target < 0 || target >= next.length ) return;
		[ next[idx], next[target] ] = [ next[target], next[idx] ];
		setAttributes( { columns: next } );
		setActiveColTab( target );
	};

	// ── Recherche produits via REST g2rd/v1/posts ─────────────────────────────
	const searchProducts = async ( idx, source, search ) => {
		if ( ! source ) return;
		setLoadingProducts( prev => ( { ...prev, [idx]: true } ) );
		try {
			const params = new URLSearchParams( {
				post_type: source,
				per_page:  20,
				search:    search || '',
			} );
			const res  = await fetch( `${API_BASE}/g2rd/v1/posts?${params}`, {
				headers: { 'X-WP-Nonce': NONCE },
			} );
			const data = await res.json();
			setProducts( prev => ( { ...prev, [idx]: data.items || [] } ) );
		} catch {
			setProducts( prev => ( { ...prev, [idx]: [] } ) );
		} finally {
			setLoadingProducts( prev => ( { ...prev, [idx]: false } ) );
		}
	};

	const applyProduct = ( idx, product ) => {
		const col   = columns[ idx ];
		const price = product.product?.price ?? product.product?.price_html ?? '';
		updateColumn( idx, 'title',       col.title || product.title );
		updateColumn( idx, 'description', col.description || product.excerpt );
		updateColumn( idx, 'price',       price !== '' ? String( price ) : col.price );
		updateColumn( idx, 'productId',   product.id );
	};

	// ── Ordre des éléments (drag simplifié : boutons ↑↓) ──────────────────────
	const moveElement = ( idx, elemKey, dir ) => {
		const order = [ ...( columns[idx].elementsOrder || DEFAULT_ORDER ) ];
		const pos   = order.indexOf( elemKey );
		if ( pos < 0 ) return;
		const target = pos + dir;
		if ( target < 0 || target >= order.length ) return;
		[ order[pos], order[target] ] = [ order[target], order[pos] ];
		updateColumn( idx, 'elementsOrder', order );
	};

	const ELEMENT_LABELS = {
		badge:       __( 'Badge', 'g2rd' ),
		title:       __( 'Titre', 'g2rd' ),
		subtitle:    __( 'Sous-titre', 'g2rd' ),
		price:       __( 'Prix', 'g2rd' ),
		description: __( 'Description', 'g2rd' ),
		features:    __( 'Liste', 'g2rd' ),
		cta:         __( 'Bouton CTA', 'g2rd' ),
	};

	const col = columns[ activeColTab ] || columns[0];

	return (
		<>
			{/* ── Contrôles sidebar ────────────────────────────────────────── */}
			<InspectorControls>

				{/* Paramètres globaux */}
				<PanelBody title={ __( 'Paramètres globaux', 'g2rd' ) } initialOpen={ true }>

					<SelectControl
						label={ __( 'Design', 'g2rd' ) }
						value={ design }
						options={ DESIGNS }
						onChange={ val => setAttributes( { design: val } ) }
					/>

					<RangeControl
						label={ __( 'Colonnes', 'g2rd' ) }
						value={ columns.length }
						min={1} max={5}
						onChange={ val => {
							const diff = val - columns.length;
							if ( diff > 0 ) {
								const extras = Array.from( { length: diff }, emptyColumn );
								setAttributes( { columns: [ ...columns, ...extras ], columnCount: val } );
							} else if ( diff < 0 ) {
								setAttributes( { columns: columns.slice( 0, val ), columnCount: val } );
								setActiveColTab( Math.min( activeColTab, val - 1 ) );
							}
						} }
					/>

					<RangeControl
						label={ __( 'Border radius (px)', 'g2rd' ) }
						value={ borderRadius }
						min={0} max={32}
						onChange={ val => setAttributes( { borderRadius: val } ) }
					/>

					<ToggleControl
						label={ __( 'Ombre (box-shadow)', 'g2rd' ) }
						checked={ showBoxShadow }
						onChange={ val => setAttributes( { showBoxShadow: val } ) }
					/>

					<ToggleControl
						label={ __( 'Agrandir la colonne vedette', 'g2rd' ) }
						checked={ featuredScale }
						onChange={ val => setAttributes( { featuredScale: val } ) }
					/>

					<TextControl
						label={ __( 'Icône fonctionnalité', 'g2rd' ) }
						value={ featureIcon }
						onChange={ val =
						__next40pxDefaultSize
						__nextHasNoMarginBottom
onChange={ val => setAttributes( { featureIcon: val } ) }
						help={ __( 'Ex: ✓ ✔ → •', 'g2rd' ) }
					/>

				</PanelBody>

				{/* Couleurs */}
				<PanelColorSettings
					title={ __( 'Couleurs', 'g2rd' ) }
					initialOpen={ false }
					colorSettings={ [
						{
							value: globalAccentColor,
							onChange: val => setAttributes( { globalAccentColor: val } ),
							label: __( 'Couleur principale (accent)', 'g2rd' ),
						},
						{
							value: globalTextColor,
							onChange: val => setAttributes( { globalTextColor: val } ),
							label: __( 'Couleur du texte', 'g2rd' ),
						},
						{
							value: globalBgColor,
							onChange: val => setAttributes( { globalBgColor: val } ),
							label: __( 'Couleur de fond', 'g2rd' ),
						},
					] }
				/>

				{/* Visibilité des éléments */}
				<PanelBody title={ __( 'Éléments visibles', 'g2rd' ) } initialOpen={ false }>
					{ [
						[ 'showBadge',       __( 'Badge', 'g2rd' ) ],
						[ 'showTitle',       __( 'Titre', 'g2rd' ) ],
						[ 'showSubtitle',    __( 'Sous-titre', 'g2rd' ) ],
						[ 'showPrice',       __( 'Prix', 'g2rd' ) ],
						[ 'showDescription', __( 'Description', 'g2rd' ) ],
						[ 'showFeatures',    __( 'Liste de fonctionnalités', 'g2rd' ) ],
						[ 'showCta',         __( 'Bouton CTA', 'g2rd' ) ],
					].map( ( [ key, label ] ) => (
						<ToggleControl
							key={ key }
							label={ label }
							checked={ attributes[ key ] }
							onChange={ val => setAttributes( { [ key ]: val } ) }
						/>
					) ) }
				</PanelBody>

				{/* Édition par colonne */}
				<PanelBody title={ __( 'Colonnes', 'g2rd' ) } initialOpen={ true }>

					{/* Onglets colonnes */}
					<div style={{ display:'flex', gap:'4px', marginBottom:'12px', flexWrap:'wrap' }}>
						{ columns.map( ( col, i ) => (
							<Button
								key={ col.id }
								variant={ activeColTab === i ? 'primary' : 'secondary' }
								size="small"
								onClick={ () => setActiveColTab( i ) }
							>
								{ col.title ? col.title.substring( 0, 12 ) : `Col ${i+1}` }
							</Button>
						) ) }
						{ columns.length < 5 && (
							<Button variant="tertiary" size="small" onClick={ addColumn }>+ { __( 'Ajouter', 'g2rd' ) }</Button>
						) }
					</div>

					{ col && (
						<div>

							{/* Déplacer / Supprimer */}
							<div style={{ display:'flex', gap:'4px', marginBottom:'12px' }}>
								<Button size="small" variant="secondary" onClick={ () => moveColumn( activeColTab, -1 ) } disabled={ activeColTab === 0 }>←</Button>
								<Button size="small" variant="secondary" onClick={ () => moveColumn( activeColTab, 1 ) } disabled={ activeColTab === columns.length - 1 }>→</Button>
								<Button size="small" variant="secondary" isDestructive onClick={ () => removeColumn( activeColTab ) } disabled={ columns.length <= 1 }>
									{ __( 'Supprimer', 'g2rd' ) }
								</Button>
							</div>

							<ToggleControl
								label={ __( 'Colonne vedette (featured)', 'g2rd' ) }
								checked={ col.isFeatured }
								onChange={ val => updateColumn( activeColTab, 'isFeatured', val ) }
							/>

							<TextControl label={ __( 'Badge', 'g2rd' ) }
								value={ col.badge }
								onChange={ val =
								__next40pxDefaultSize
								__nextHasNoMarginBottom
onChange={ val => updateColumn( activeColTab, 'badge', val ) }
								placeholder={ __( 'Ex: Populaire', 'g2rd' ) }
							/>
							<TextControl label={ __( 'Titre', 'g2rd' ) }
								value={ col.title }
								onChange={ val =
								__next40pxDefaultSize
								__nextHasNoMarginBottom
onChange={ val => updateColumn( activeColTab, 'title', val ) }
							/>
							<TextControl label={ __( 'Sous-titre', 'g2rd' ) }
								value={ col.subtitle }
								onChange={ val =
								__next40pxDefaultSize
								__nextHasNoMarginBottom
onChange={ val => updateColumn( activeColTab, 'subtitle', val ) }
							/>

							{/* Prix */}
							<fieldset style={{ border:'1px solid #ddd', borderRadius:'4px', padding:'8px 12px', marginBottom:'12px' }}>
								<legend style={{fontSize:'12px',fontWeight:600}}>{ __( 'Prix', 'g2rd' ) }</legend>
								<TextControl label={ __( 'Préfixe (ex: à partir de)', 'g2rd' ) }
									value={ col.pricePrefix }
									onChange={ val =
									__next40pxDefaultSize
									__nextHasNoMarginBottom
onChange={ val => updateColumn( activeColTab, 'pricePrefix', val ) }
								/>
								<TextControl label={ __( 'Montant (ex: 49 ou Sur devis)', 'g2rd' ) }
									value={ col.price }
									onChange={ val =
									__next40pxDefaultSize
									__nextHasNoMarginBottom
onChange={ val => updateColumn( activeColTab, 'price', val ) }
								/>
								<TextControl label={ __( 'Période (ex: / mois)', 'g2rd' ) }
									value={ col.pricePeriod }
									onChange={ val =
									__next40pxDefaultSize
									__nextHasNoMarginBottom
onChange={ val => updateColumn( activeColTab, 'pricePeriod', val ) }
								/>
							</fieldset>

							<TextareaControl label={ __( 'Description', 'g2rd' ) }
								value={ col.description }
								onChange={ val =
								__nextHasNoMarginBottom
onChange={ val => updateColumn( activeColTab, 'description', val ) }
								rows={ 3 }
							/>

							{/* Fonctionnalités */}
							<fieldset style={{ border:'1px solid #ddd', borderRadius:'4px', padding:'8px 12px', marginBottom:'12px' }}>
								<legend style={{fontSize:'12px',fontWeight:600}}>{ __( 'Fonctionnalités', 'g2rd' ) }</legend>
								{ col.features.map( ( feat, fi ) => (
									<div key={fi} style={{ display:'flex', gap:'4px', marginBottom:'4px' }}>
										<TextControl
											value={ feat }
											onChange={ val =
											__next40pxDefaultSize
											__nextHasNoMarginBottom
onChange={ val => {
												const next = [ ...col.features ];
												next[fi] = val;
												updateColumn( activeColTab, 'features', next );
											} }
											style={{flex:1}}
										/>
										<Button size="small" isDestructive variant="tertiary"
											onClick={ () => {
												const next = col.features.filter( (_, i) => i !== fi );
												updateColumn( activeColTab, 'features', next );
											} }
										>✕</Button>
									</div>
								) ) }
								<Button size="small" variant="secondary"
									onClick={ () => updateColumn( activeColTab, 'features', [ ...col.features, __( 'Nouvelle fonctionnalité', 'g2rd' ) ] ) }
								>+ { __( 'Ajouter', 'g2rd' ) }</Button>
							</fieldset>

							{/* CTA */}
							<TextControl label={ __( 'Texte du bouton', 'g2rd' ) }
								value={ col.ctaText }
								onChange={ val =
								__next40pxDefaultSize
								__nextHasNoMarginBottom
onChange={ val => updateColumn( activeColTab, 'ctaText', val ) }
							/>
							<TextControl label={ __( 'URL du bouton', 'g2rd' ) }
								value={ col.ctaUrl }
								onChange={ val =
								__next40pxDefaultSize
								__nextHasNoMarginBottom
onChange={ val => updateColumn( activeColTab, 'ctaUrl', val ) }
							/>
							<ToggleControl
								label={ __( 'Ouvrir dans un nouvel onglet', 'g2rd' ) }
								checked={ col.ctaTarget }
								onChange={ val => updateColumn( activeColTab, 'ctaTarget', val ) }
							/>

							{/* Couleur accent */}
							<p style={{fontSize:'12px',margin:'8px 0 4px',fontWeight:600}}>{ __( 'Couleur accent de cette colonne', 'g2rd' ) }</p>
							<ColorPalette
								value={ col.accentColor }
								onChange={ val => updateColumn( activeColTab, 'accentColor', val || '' ) }
								clearable={ true }
							/>

							{/* Ordre des éléments */}
							<fieldset style={{ border:'1px solid #ddd', borderRadius:'4px', padding:'8px 12px', marginBottom:'12px', marginTop:'12px' }}>
								<legend style={{fontSize:'12px',fontWeight:600}}>{ __( '↕ Ordre des éléments', 'g2rd' ) }</legend>
								{ ( col.elementsOrder || DEFAULT_ORDER ).map( ( key, pos ) => (
									<div key={key} style={{ display:'flex', alignItems:'center', gap:'4px', marginBottom:'4px' }}>
										<Button size="small" onClick={ () => moveElement( activeColTab, key, -1 ) } disabled={ pos === 0 }>↑</Button>
										<Button size="small" onClick={ () => moveElement( activeColTab, key, 1 ) } disabled={ pos === col.elementsOrder.length - 1 }>↓</Button>
										<span style={{fontSize:'13px'}}>{ ELEMENT_LABELS[key] || key }</span>
									</div>
								) ) }
							</fieldset>

						</div>
					) }

				</PanelBody>

				{/* Import produit ecommerce */}
				<PanelBody title={ __( 'Import produit e-commerce', 'g2rd' ) } initialOpen={ false }>
					{ col && (
						<div>
							<Notice status="info" isDismissible={false} style={{marginBottom:'12px'}}>
								<small>{ __( 'Importe automatiquement le nom, la description et le prix depuis WooCommerce, SureCart ou FluentCart.', 'g2rd' ) }</small>
							</Notice>
							<SelectControl
								label={ __( 'Source', 'g2rd' ) }
								value={ col.productSource }
								options={ [
									{ label: __( '— Sélectionner —', 'g2rd' ), value: '' },
									{ label: 'WooCommerce',  value: 'product' },
									{ label: 'SureCart',    value: 'sc-product' },
									{ label: 'FluentCart',  value: 'fluent_cart_product' },
								] }
								onChange={ val => {
									updateColumn( activeColTab, 'productSource', val );
									setProducts( prev => ( { ...prev, [activeColTab]: [] } ) );
								} }
							/>
							{ col.productSource && (
								<>
									<div style={{ display:'flex', gap:'4px', marginBottom:'8px' }}>
										<TextControl
											value={ productSearch[activeColTab] || '' }
											onChange={ val =
											__next40pxDefaultSize
											__nextHasNoMarginBottom
onChange={ val => setProductSearch( prev => ( { ...prev, [activeColTab]: val } ) ) }
											placeholder={ __( 'Rechercher un produit…', 'g2rd' ) }
											style={{flex:1}}
										/>
										<Button variant="secondary" size="small"
											onClick={ () => searchProducts( activeColTab, col.productSource, productSearch[activeColTab] || '' ) }
										>{ __( 'Chercher', 'g2rd' ) }</Button>
									</div>
									{ loadingProducts[activeColTab] && <Spinner /> }
									{ ( products[activeColTab] || [] ).map( product => (
										<div key={product.id} style={{ display:'flex', justifyContent:'space-between', alignItems:'center', padding:'6px 0', borderBottom:'1px solid #eee', fontSize:'13px' }}>
											<span>{ product.title }</span>
											<Button size="small" variant="primary" onClick={ () => applyProduct( activeColTab, product ) }>
												{ __( 'Appliquer', 'g2rd' ) }
											</Button>
										</div>
									) ) }
									{ ( products[activeColTab] || [] ).length === 0 && ! loadingProducts[activeColTab] && productSearch[activeColTab] !== undefined && (
										<p style={{fontSize:'12px', opacity:0.7}}>{ __( 'Aucun produit trouvé.', 'g2rd' ) }</p>
									) }
								</>
							) }
						</div>
					) }
				</PanelBody>

			</InspectorControls>

			{/* ── Aperçu dans l'éditeur ─────────────────────────────────────── */}
			<div { ...blockProps }>
				<div
					className="g2rd-pricing-table__grid"
					style={ {
						display:             'grid',
						gridTemplateColumns: `repeat(${ columns.length }, 1fr)`,
						gap:                 gapSize || 'var(--wp--preset--spacing--m)',
						alignItems:          featuredScale ? 'end' : 'stretch',
					} }
				>
					{ columns.map( ( col, i ) => (
						<div
							key={ col.id }
							onClick={ () => setActiveColTab( i ) }
							style={ { cursor:'pointer', outline: activeColTab === i ? '2px solid #0073aa' : 'none', outlineOffset:'4px', borderRadius: ( borderRadius || 12 ) + 'px' } }
						>
							<ColumnPreview col={ col } attrs={ attributes } />
						</div>
					) ) }
				</div>
				<p style={{ fontSize:'11px', color:'#999', textAlign:'center', marginTop:'8px' }}>
					{ __( 'Cliquez sur une colonne pour l\'éditer dans la barre latérale', 'g2rd' ) }
				</p>
			</div>
		</>
	);
}
