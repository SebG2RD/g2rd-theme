/**
 * view.js — Hydratation frontend des avis Google
 */

const endpoint =
	window.G2RDGoogleReviewsEndpoint || '/wp-json/g2rd/v1/google-reviews';

/* ── Helpers ─────────────────────────────────────────────────────────────── */

function escHtml( str ) {
	const d = document.createElement( 'div' );
	d.textContent = String( str || '' );
	return d.innerHTML;
}

function renderStars( rating ) {
	let html = '<div class="g2rd-testimonial__stars">';
	for ( let i = 1; i <= 5; i++ ) {
		const cls = i <= rating
			? 'dashicons dashicons-star-filled'
			: 'dashicons dashicons-star-empty';
		html += `<span class="${ cls }" aria-hidden="true"></span>`;
	}
	html += `<span class="screen-reader-text">${ escHtml( rating ) }/5</span></div>`;
	return html;
}

function truncateText( text, max ) {
	if ( ! max || text.length <= max ) return escHtml( text );
	return escHtml( text.slice( 0, max ).trimEnd() ) + '…';
}

function renderCard( review, opts, index ) {
	const initial  = ( review.author || 'A' )[ 0 ].toUpperCase();
	const featured = opts.highlightFirst && index === 0;

	let avatar = '';
	if ( opts.showAvatar ) {
		avatar = review.avatar
			? `<img src="${ escHtml( review.avatar ) }" alt="${ escHtml( review.author ) }" class="g2rd-testimonial__avatar" width="40" height="40" loading="lazy" />`
			: `<div class="g2rd-testimonial__avatar-placeholder" aria-hidden="true">${ escHtml( initial ) }</div>`;
	}

	const authorName = opts.showAuthorLink && review.author_url
		? `<a href="${ escHtml( review.author_url ) }" target="_blank" rel="noopener noreferrer nofollow" class="g2rd-testimonial__name g2rd-testimonial__name--link">${ escHtml( review.author ) }</a>`
		: `<strong class="g2rd-testimonial__name">${ escHtml( review.author ) }</strong>`;

	const date = opts.showDate
		? `<span class="g2rd-testimonial__role">${ escHtml( review.relative_time ) }</span>`
		: '';

	const text = truncateText( review.text, opts.maxText );

	return `
		<div class="g2rd-testimonial__card${ featured ? ' is-featured' : '' }" data-card-style="${ escHtml( opts.cardStyle ) }">
			${ renderStars( review.rating ) }
			<p class="g2rd-testimonial__quote">${ text }</p>
			<div class="g2rd-testimonial__author">
				${ avatar }
				<div class="g2rd-testimonial__author-info">
					${ authorName }
					${ date }
				</div>
			</div>
		</div>
	`;
}

function renderGoogleBadge( data ) {
	const stars = '★'.repeat( Math.round( data.overall_rating ) );
	return `
		<div class="g2rd-testimonial__google-header">
			<div class="g2rd-testimonial__google-logo" aria-hidden="true">
				<svg width="20" height="20" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
					<path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
					<path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
					<path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
					<path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
				</svg>
			</div>
			<div class="g2rd-testimonial__google-overall">
				<span class="g2rd-testimonial__google-overall-rating">${ escHtml( data.overall_rating.toFixed( 1 ) ) }</span>
				<span class="g2rd-testimonial__google-stars">${ escHtml( stars ) }</span>
				<span class="g2rd-testimonial__google-count">${ escHtml( data.total_ratings ) } avis</span>
			</div>
		</div>
	`;
}

function buildCarousel( cardsHTML ) {
	const wrapper = document.createElement( 'div' );
	wrapper.className = 'g2rd-testimonial__carousel';

	const track = document.createElement( 'div' );
	track.className = 'g2rd-testimonial__carousel-track';
	track.innerHTML = cardsHTML;

	const prev = document.createElement( 'button' );
	prev.type = 'button';
	prev.className = 'g2rd-testimonial__nav g2rd-testimonial__nav-prev';
	prev.setAttribute( 'aria-label', 'Précédent' );
	prev.textContent = '‹';

	const next = document.createElement( 'button' );
	next.type = 'button';
	next.className = 'g2rd-testimonial__nav g2rd-testimonial__nav-next';
	next.setAttribute( 'aria-label', 'Suivant' );
	next.textContent = '›';

	const scrollAmt = () => {
		const card = track.querySelector( '.g2rd-testimonial__card' );
		const gap  = parseFloat( getComputedStyle( track ).columnGap ) || 20;
		return ( card ? card.offsetWidth : 300 ) + gap;
	};

	prev.addEventListener( 'click', () => track.scrollBy( { left: -scrollAmt(), behavior: 'smooth' } ) );
	next.addEventListener( 'click', () => track.scrollBy( { left:  scrollAmt(), behavior: 'smooth' } ) );

	wrapper.appendChild( prev );
	wrapper.appendChild( track );
	wrapper.appendChild( next );

	return wrapper;
}

/* ── Init ────────────────────────────────────────────────────────────────── */

function initBlock( el ) {
	if ( el.dataset.g2rdInit ) return;
	el.dataset.g2rdInit = '1';

	const placeId        = el.dataset.googlePlaceId;
	const minRating      = el.dataset.googleMinRating || 4;
	const max            = el.dataset.googleMaxReviews || 5;
	const layout         = el.dataset.googleLayout || 'grid';
	const columns        = parseInt( el.dataset.googleColumns ) || 3;
	const showHeader     = el.dataset.googleShowHeader !== 'false';
	const showAuthorLink = el.dataset.googleShowAuthorLink === 'true';
	const showDate       = el.dataset.googleShowDate !== 'false';
	const showAvatar     = el.dataset.googleShowAvatar !== 'false';
	const cardStyle      = el.dataset.googleCardStyle || 'shadow';
	const maxText        = parseInt( el.dataset.googleMaxText ) || 0;
	const highlightFirst = el.dataset.googleHighlightFirst === 'true';

	const opts = { showAuthorLink, showDate, showAvatar, cardStyle, maxText, highlightFirst };

	if ( ! placeId ) { el.innerHTML = ''; return; }

	el.setAttribute( 'data-google-layout', layout );
	el.style.setProperty( '--g2rd-t-cols', columns );

	const url = endpoint
		+ '?place_id='   + encodeURIComponent( placeId )
		+ '&min_rating=' + encodeURIComponent( minRating )
		+ '&max='        + encodeURIComponent( max );

	fetch( url )
		.then( ( r ) => r.json() )
		.then( ( data ) => {
			if ( data.code ) { el.innerHTML = ''; return; }

			const header    = showHeader ? renderGoogleBadge( data ) : '';
			const cardsHTML = ( data.reviews || [] )
				.map( ( r, i ) => renderCard( r, opts, i ) )
				.join( '' );

			el.innerHTML = header;
			el.removeAttribute( 'aria-busy' );

			if ( layout === 'carousel' ) {
				el.appendChild( buildCarousel( cardsHTML ) );
			} else {
				el.insertAdjacentHTML( 'beforeend', cardsHTML );
			}
		} )
		.catch( () => { el.innerHTML = ''; } );
}

document.querySelectorAll( '.g2rd-testimonial--google[data-google-place-id]' )
	.forEach( initBlock );
