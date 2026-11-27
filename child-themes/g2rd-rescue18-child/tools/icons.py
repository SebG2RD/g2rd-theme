# Source unique des icônes : génère inc/icons.php et assets/css/icons.css
import urllib.parse, sys
T=sys.argv[1]
I={
'search':'<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
'arrow':'<path d="M4 12h16m-7-7 7 7-7 7"/>',
'back':'<path d="M20 12H4m7 7-7-7 7-7"/>',
'book':'<path d="M12 6c-3-2-6-2-10-1v14c4-1 7-1 10 1 3-2 6-2 10-1V5c-4-1-7-1-10 1Zm0 0v14"/>',
'file':'<path d="M5 2h9l5 5v15H5zM14 2v6h5M8 12h8M8 16h8"/>',
'flame':'<path d="M13 2c.4 4-1 5-3 8-.1-2-1-3-2-4-1 4-5 7-5 11a9 9 0 0 0 18 0c0-5-4-7-8-15Zm-1 12c1 3-2 3-2 5a3 3 0 0 0 6 0c0-2-2-3-4-5Z"/>',
'tree':'<path d="M12 2 5 12h4l-4 6h14l-4-6h4L12 2Zm0 16v4"/>',
'med':'<path d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6z"/>',
'car':'<path d="m5 9 2-5h10l2 5M3 9h18v9H3zM5 18v3m14-3v3M6 13h2m8 0h2"/>',
'layers':'<path d="m12 3 9 5-9 5-9-5 9-5Zm-9 9 9 5 9-5M3 16l9 5 9-5"/>',
'alert':'<path d="M12 3 2 20h20L12 3Zm0 6v5m0 3v.5"/>',
'compass':'<circle cx="12" cy="12" r="10"/><path d="m15.5 8.5-2 5-5 2 2-5 5-2Z"/>',
'radio':'<path d="M4 10h16v10H4zM8 10l8-6M8 15h.01M13 15h4"/>',
'users':'<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-4 3-6 7-6s7 2 7 6M16 4.5a3.5 3.5 0 0 1 0 7M18 14c2.5.6 4 2.6 4 6"/>',
'box':'<path d="M3 7l9-4 9 4v10l-9 4-9-4V7Zm0 0 9 4 9-4M12 11v10"/>',
'shield':'<path d="m12 2 9 4v7c0 6-5 8-9 9-4-1-9-3-9-9V6l9-4Zm-4 10 3 3 5-6"/>',
'heart':'<path d="M12 21s-8-5.2-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 5.8-8 11-8 11Z"/>',
'truck':'<path d="M2 6h12v10H2zM14 9h4l4 4v3h-8M6 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm12 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>',
'history':'<path d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5M12 7v5l3 2"/>',
'grad':'<path d="m2 9 10-5 10 5-10 5L2 9Zm5 3v6c3 2 7 2 10 0v-6m5-3v7"/>',
'headphones':'<path d="M3 13v-2a9 9 0 0 1 18 0v2M3 13h5v8H5a2 2 0 0 1-2-2v-6Zm13 0h5v6a2 2 0 0 1-2 2h-3v-8Z"/>',
'store':'<path d="M4 9 5 3h14l1 6v12H4V9ZM4 9c1 3 4 3 5 0 1 3 4 3 6 0 1 3 4 3 5 0M9 21v-8h6v8"/>',
'clock':'<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 3"/>',
'check':'<path d="m4 12 5 5L20 6"/>',
'cart':'<path d="M3 4h2l2.5 11h11L21 7H6.5M9 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm9 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/>',
'star':'<path d="m12 2 3 6 7 .9-5 4.8 1.2 7L12 17.4l-6.2 3.3 1.2-7L2 8.9 9 8z"/>',
'play':'<path d="M8 5v14l11-7z"/>',
'pause':'<path d="M7 5h3v14H7zM14 5h3v14h-3z"/>',
'clip':'<rect x="4" y="4" width="16" height="18" rx="2"/><path d="M9 4V2h6v2M8 11h8M8 15h8"/>',
'plus':'<path d="M12 5v14M5 12h14"/>',
'refresh':'<path d="M20 8V3l-4 2A9 9 0 1 0 21 12M20 8h-6"/>',
'ext':'<path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6"/>',
'help':'<circle cx="12" cy="12" r="10"/><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6V14M12 17.5v.5"/>',
'info':'<circle cx="12" cy="12" r="10"/><path d="M12 11v6M12 7.5v.5"/>',
'bell':'<path d="M6 9a6 6 0 0 1 12 0c0 7 3 8 3 8H3s3-1 3-8M10 21h4"/>',
'download':'<path d="M12 3v13m-5-5 5 5 5-5M4 18v3h16v-3"/>',
'link':'<path d="M10 14a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1M14 10a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/>',
'grid':'<path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/>',
'list':'<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
'mail':'<rect x="2" y="5" width="20" height="14" rx="2"/><path d="m2 7 10 7L22 7"/>',
'lock':'<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
'tool':'<path d="M21 7a6 6 0 0 1-8 6L6 20a3 3 0 0 1-4-4l7-7a6 6 0 0 1 8-8l-4 4 5 5 3-3Z"/>',
'menu':'<path d="M3 6h18M3 12h18M3 18h18"/>',
'pin':'<path d="M12 22s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12Z"/><circle cx="12" cy="10" r="2.5"/>',
'map':'<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3V6Zm6-3v15m6-12v15"/>',
'phone':'<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A17 17 0 0 1 3 5a2 2 0 0 1 2-2Z"/>',
'briefcase':'<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"/>',
'calendar':'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4m8-4v4"/>',
'globe':'<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2c3 3 4 6.5 4 10s-1 7-4 10c-3-3-4-6.5-4-10s1-7 4-10Z"/>',
'locate':'<circle cx="12" cy="12" r="4"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/>',
'news':'<path d="M4 5h13v14a2 2 0 0 0 2 2H6a2 2 0 0 1-2-2V5Zm13 4h3v10a2 2 0 0 1-4 0M8 9h5M8 13h5M8 17h3"/>',
}
php=["<?php","/**"," * Icônes du thème (24 × 24, trait 1,9). Fichier généré par tools/icons.py :"," * la même source alimente assets/css/icons.css (masques CSS des compositions)."," *"," * @package G2RD_Rescue18"," */","","declare( strict_types = 1 );","","namespace G2RD_Rescue18;","","\\defined( 'ABSPATH' ) || exit;","","/**"," * Tracés des icônes."," *"," * @return array<string,string>"," */","function icones(): array {","\treturn array("]
for k,v in I.items(): php.append(f"\t\t'{k}' => '{v}',")
php+=["\t);","}","",
"/**"," * Icône SVG inline, décorative par défaut."," *"," * @param string $nom        Nom de l'icône."," * @param string $class_name Classe CSS."," * @param string $label      Intitulé accessible ; vide = décorative."," * @return string"," */",
"function icone( string $nom, string $class_name = 'r18-svg', string $label = '' ): string {",
"\t$traces = icones()[ $nom ] ?? '';","\tif ( '' === $traces ) {","\t\treturn '';","\t}",
"\t$a11y = '' !== $label ? 'role=\"img\" aria-label=\"' . \\esc_attr( $label ) . '\"' : 'aria-hidden=\"true\" focusable=\"false\"';",
"\t// Tracés internes au thème, jamais issus d'une saisie.",
"\treturn \\sprintf( '<svg class=\"%1$s\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.9\" stroke-linecap=\"round\" stroke-linejoin=\"round\" %2$s>%3$s</svg>', \\esc_attr( $class_name ), $a11y, $traces );",
"}",""]
open(T+'/inc/icons.php','w').write("\n".join(php))
css=["/* Icônes en masque CSS — généré par tools/icons.py, ne pas éditer à la main.","   Usage dans une composition : ajouter la classe r18-i--<nom> au bloc ;","   l'icône s'affiche via ::before (tuile) avec la couleur courante. */",""]
for k,v in I.items():
    svg=f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">{v}</svg>'
    css.append(f'.r18-i--{k}{{--r18-icon:url("data:image/svg+xml,{urllib.parse.quote(svg,safe=" =:/,.-")}")}}')
open(T+'/assets/css/icons.css','w').write("\n".join(css)+"\n")
print(len(I))
