# Annuaire SDIS, actualités, offres et concours

Administration : menu **Annuaire SDIS** (tableau de bord, réglages, fiches). Journal détaillé : menu **Automatisations**.

## Sources (vérifiées le 30 octobre 2026)

| Contenu | Source | Licence | Rythme | Méthode |
| --- | --- | --- | --- | --- |
| 98 fiches (SDIS, SIS 67, SDMIS 69, BSPP) | API Annuaire de l'administration (DILA) | Licence ouverte | Chaque semaine | `api-lannuaire.service-public.fr`, filtre `pivot like "sdis"` |
| Fiche BMPM | Saisie du thème (absent de l'annuaire officiel) | — | — | Marquée « à vérifier », coordonnées verrouillées |
| Actualités | Sites officiels des services | Titre, résumé et lien | Chaque nuit, par lots de 3 services | Flux déclaré, `/feed/`, `/rss.xml`, `/rss`, API `wp-json`. robots.txt lu et respecté (cache 30 jours) |
| Images et logos | Sites officiels | Affichage sans copie | Avec les actualités | `og:image`, logo d'en-tête, image à la une, `enclosure`, première image de l'article |
| Offres d'emploi | Choisir le Service Public, data.gouv.fr (jeu 6322e99e12175f7eb26ff465) | Licence ouverte | Chaque semaine | CSV de 130 Mo lu par tranches HTTP Range de 8 Mo, ligne coupée recollée, lecture évitée si le fichier n'a pas changé |
| Casernes (carte) | OpenStreetMap via Overpass | ODbL | Chaque mois | 21 zones (16 carreaux métropole + 5 DROM), fichier `uploads/rescue18/casernes.json` |
| Fond de carte | IGN Géoplateforme (plan ou photos) ou OpenStreetMap | Licence ouverte / ODbL | — | Tuiles chargées par le navigateur |

Aucune source ne demande de compte ni d'abonnement.

## Automatique

- **Premier passage** : à l'activation de la version, l'annuaire s'importe seul (environ 15 secondes), puis un cycle complet récupère actualités, offres et casernes.
- **Flux introuvable** : nouvel essai tous les 7 jours. Un flux trouvé est revérifié tous les 30 jours.
- **Recrutements** : un article qui parle de recrutement, de volontaires, de JSP ou de concours porte le badge « Recrutement ».
- **Calendrier** : un article qui annonce un concours ou une campagne crée un brouillon dans *Concours et recrutements*. Tu ajoutes les dates, tu publies.
- **Offres** : une offre disparue du fichier ou dont la date limite est passée repasse en brouillon. Si plus de 60 % des offres disparaissent d'un coup, rien n'est retiré (fichier suspect).
- **Purge** : les actualités de plus de 120 jours sont supprimées (résumés seulement).

## Ce qui se règle dans l'admin

- Chaque fiche (SDIS, offre, actualité, date de concours) : tous les champs. Une valeur modifiée à la main est **verrouillée** (cadenas rouge) et n'est plus écrasée par la synchronisation. Décocher le cadenas la rend de nouveau automatique.
- Corbeille : une fiche mise à la corbeille n'est jamais recréée.
- Réglages : publication automatique ou avec validation (actualités, offres), ancienneté, nombre d'articles par service, conservation, mots à écarter, fond de carte, casernes, images externes, détection des concours, liens des parcours (SPV, BSPP, BMPM, Choisir le Service Public).
- Sources : activation, pause, test, simulation dans **Automatisations**.

## Pages et blocs

| URL | Contenu |
| --- | --- |
| `/sdis/` | Carte, recherche (numéro, nom, ville, région), « Autour de moi », liste accessible |
| `/sdis/<département>-<code>/` | Fiche : visuel, logo, boutons, coordonnées, carte, actualités et offres du service |
| `/actualites-des-sdis/` | Fil filtrable par région, département, recrutements |
| `/recrutement/` | Six parcours, calendrier, offres, recrutements annoncés |
| `/recrutement/offres-emploi/` | Offres filtrables (mot-clé, région, département, catégorie) |
| `/recrutement/concours/` | Calendrier |
| `/offre/<intitulé>-<référence>/` | Fiche d'offre, données JobPosting, lien de candidature |

Blocs (catégorie Rescue 18) : Annuaire des SDIS, Fiche SDIS (deux blocs), Fil des actualités des SDIS, Offres d'emploi, Offre (détails), Calendrier des concours. Les pages sont composées de blocs natifs (groupes, titres, paragraphes, boutons) modifiables dans l'éditeur.

## Accessibilité

- La liste complète s'affiche sans JavaScript. La carte double l'information, elle ne la remplace pas.
- Recherche, filtres et compteurs annoncés (`aria-live`), cibles de 44 px, focus visible.
- Liens externes signalés « nouvel onglet ». Titres en capitales ramenés en casse de phrase.
- Rappel « Urgence : 18 ou 112 » sur chaque fiche.

## Tests

```
php tests/annuaire-test.php   # 80 vérifications, extraits réels
php tests/veille-test.php     # 94 vérifications
```
