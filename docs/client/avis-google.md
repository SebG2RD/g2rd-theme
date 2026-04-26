# Avis Google (Google Business)

Le thème permet d’afficher des **avis Google** (Google Business / Places) dans le bloc **Témoignages** lorsque vous activez l’option correspondante dans le bloc et que vous fournissez les bons identifiants.

## 1. Clé API Google (Places)

Les avis sont récupérés via l’**API Google Places**. Vous devez créer une clé dans [Google Cloud Console](https://console.cloud.google.com/) et activer l’API **Places** (voir aussi le lien d’aide affiché sous le champ dans les options du thème).

1. **Apparence → Options G2RD → Configuration**
2. Section **Intégrations — Google Maps**
3. Collez la **clé API**, puis **Enregistrez**.

**Sécurité** : restreignez la clé dans Google Cloud (par exemple par **adresse IP du serveur**), ne la partagez pas publiquement et ne la commitez pas dans un dépôt Git public.

## 2. Place ID de l’établissement

Le **Place ID** identifie votre fiche Google Business. Vous le retrouvez via les outils Google (recherche « Place ID finder » sur la documentation Google Maps) ou via votre agence.

Dans le **bloc Témoignages**, activez la section du type **Avis Google Business** et renseignez le Place ID demandé par le bloc.

## 3. Cache des avis

Pour limiter les appels payants à Google, les avis sont **mis en cache environ 12 heures** côté site.

Si vous venez de recevoir de nouveaux avis et voulez forcer un rafraîchissement :

1. **Apparence → Options G2RD → Configuration**
2. En bas de la section Google Maps, zone **Vider le cache des avis**
3. Saisissez le **Place ID** concerné, puis cliquez sur **Vider le cache**.

## Problèmes fréquents

- **Aucun avis** : vérifiez la clé API, le Places API activé, le Place ID exact, et les restrictions de la clé (IP / referrer).
- **Avis anciens** : videz le cache comme ci-dessus ou attendez la fin du cache 12 h.

## Liens utiles

- [Résolution des problèmes](depannage.md)
- [Utilisation des blocs](utilisation-blocs.md)
