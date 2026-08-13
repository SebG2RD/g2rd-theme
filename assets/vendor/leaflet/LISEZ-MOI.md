# Leaflet — auto-hébergé

Le bloc **G2RD Carte de parcours GPX** (`g2rd/route-map`) utilise Leaflet. La
bibliothèque est servie depuis le thème, jamais depuis un CDN : un script tiers
de plus pèse sur le budget de performance, et une carte n'a aucune raison
d'attendre un bandeau de consentement cookies.

## Fichiers présents

```text
assets/vendor/leaflet/
├── leaflet.js       1.9.4, minifié (~42 Ko gzip)
├── leaflet.css      1.9.4
└── images/          marqueurs et ombres par défaut de Leaflet
```

`leaflet-src.js` et les `.map` ne servent qu'au débogage : ils ne sont pas
embarqués.

## Mise à jour

```bash
curl -L -o leaflet.zip https://github.com/Leaflet/Leaflet/releases/download/vX.Y.Z/leaflet.zip
unzip leaflet.zip
# Ne conserver que dist/leaflet.js, dist/leaflet.css et dist/images/.
```

Penser à reporter le numéro de version dans la constante `LEAFLET_VERSION` de
`classes/class-route-map-support.php` : elle sert de cache-buster.

Leaflet est sous licence BSD 2-Clause. L'auto-hébergement est autorisé, y
compris sur un site commercial. La mention de copyright doit rester présente
dans `leaflet.js` — ne pas le reminifier en la supprimant.

## Si les fichiers sont retirés

`G2RD\RouteMapSupport::register_leaflet()` vérifie la présence de `leaflet.js`
avant d'enregistrer quoi que ce soit. Sans les fichiers, aucun script ni aucune
feuille de style n'est déclaré et aucune 404 n'est émise : le bloc affiche son
conteneur vide, tandis que les statistiques, le profil altimétrique et les liens
de téléchargement — calculés côté serveur — continuent de fonctionner.

## Les tuiles

Le fond de plan vient de `tile.openstreetmap.org`. C'est une requête vers un
tiers, mais elle ne dépose **aucun cookie** et ne transmet que l'adresse IP,
comme toute requête d'image externe — contrairement à Google Maps, qui impose
une clé facturée et un consentement.

Pour s'affranchir totalement de la requête externe : un abonnement à un
fournisseur de tuiles européen (IGN Géoplateforme, Jawg, Stadia Maps), ou un
fond de plan statique utilisé comme image simple — la carte perd alors le zoom
et le déplacement.
