<?php
/**
 * Lexique : recherche instantanée et index alphabétique.
 *
 * Les sigles restent des listes natives dans la page (modifiables au
 * back-office) : ce bloc ajoute le champ de recherche, les lettres et le compteur.
 *
 * @package G2RD_Rescue18
 */

declare( strict_types = 1 );

namespace G2RD_Rescue18\Lexique;

\defined( 'ABSPATH' ) || exit;

/**
 * Bloc rescue18/lexique-outils.
 *
 * @return string
 */
function render_outils(): string {
	$lettres = '';
	foreach ( \range( 'A', 'Z' ) as $l ) {
		$lettres .= '<li><a href="#lex-' . \strtolower( $l ) . '" data-l="' . $l . '">' . $l . '</a></li>';
	}
	$js = <<<'JS'
(function(){
var b=document.currentScript.closest('.r18-lexo');if(!b)return;
var go=function(){
var idx=document.querySelector('.r18-lex__index');if(!idx)return;
var champ=b.querySelector('input'),compte=b.querySelector('[data-lex-n]'),vide=document.querySelector('.r18-lex__vide');
var plat=function(s){return s.normalize('NFD').replace(/[̀-ͯ]/g,'').toLowerCase();};
var items=[].slice.call(idx.querySelectorAll('.r18-lex__list li')).map(function(li){return [li,plat(li.textContent)];});
var lettres=[].slice.call(idx.querySelectorAll('.r18-lex__lettre'));
b.querySelectorAll('[data-l]').forEach(function(a){if(!document.getElementById('lex-'+a.dataset.l.toLowerCase())){a.setAttribute('aria-disabled','true');a.removeAttribute('href');a.parentNode.classList.add('is-off');}});
var total=items.length;compte.textContent=total+' sigles';
var t;champ.addEventListener('input',function(){clearTimeout(t);t=setTimeout(filtrer,120);});
function filtrer(){var q=plat(champ.value.trim()),n=0;
items.forEach(function(x){var ok=!q||x[1].indexOf(q)>-1;x[0].hidden=!ok;n+=ok?1:0;});
lettres.forEach(function(g){g.hidden=!!q&&!g.querySelector('li:not([hidden])');});
if(vide)vide.hidden=n>0;
compte.textContent=q?(n+' résultat'+(n>1?'s':'')+' sur '+total):total+' sigles';}
};
document.readyState==='loading'?document.addEventListener('DOMContentLoaded',go):go();
})();
JS;
	return '<div class="r18-lexo">'
		. '<form class="r18-lexo__f" role="search" onsubmit="return false"><label for="r18-lex-q" class="screen-reader-text">Rechercher un sigle ou un mot</label>'
		. '<input id="r18-lex-q" type="search" placeholder="Rechercher : CODIS, VSAV, échelle…" autocomplete="off" spellcheck="false">'
		. '<p class="r18-lexo__n" data-lex-n aria-live="polite"></p></form>'
		. '<nav class="r18-lexo__az" aria-label="Index alphabétique"><ul role="list">' . $lettres . '</ul></nav>'
		. '<script>' . $js . '</script></div>';
}

/**
 * Définition du bloc.
 *
 * @return array<string,array<string,mixed>>
 */
function block_definitions(): array {
	return array(
		'lexique-outils' => array(
			'title'  => 'Lexique : recherche et index',
			'icon'   => 'editor-spellcheck',
			'attrs'  => array(),
			'render' => 'Lexique\render_outils',
		),
	);
}
