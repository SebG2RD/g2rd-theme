# Dépréciations — g2rd-faq

## Règle

Toute évolution cassante des attributs ou du markup doit passer par une entrée `deprecated` avec migration.

## Vérifications

- Le contenu ancien reste lisible.
- La migration ne perd pas de données.
- Les tests JS de bloc valident les versions legacy.
