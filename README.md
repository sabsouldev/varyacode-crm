# CRM VaryaCode

CRM de prospection de VaryaCode, en Symfony 7.4 LTS. Il reprend la base Notion « Prospects »
et automatise les règles du plan d'actions commerciales : chaque fiche en cours a toujours une
prochaine tâche, créée toute seule après chaque échange.

## Ce que fait cette première version

- **Pipeline** (`config/packages/workflow.yaml`) : À contacter → Contacté / En attente de réponse →
  RDV fixé → Devis envoyé → Gagné, avec Perdu et Pas maintenant possibles à chaque étape.
- **Relances automatiques** (`src/Prospection/PlanificateurRelances.php`) :
  - message sans réponse : relance à J+5 ouvrés puis J+10, puis mise en « Pas maintenant » et reprise dans 3 mois ;
  - devis : relances à J+3 et J+7 ;
  - client gagné : enquête de satisfaction à 6 mois et bilan au mois 10 ;
  - nouveau prospect : une tâche « vérifier site et coordonnées », séparée de l'envoi.
- **Garde-fous** (`src/Prospection/GardeSollicitation.php`) : 4 nouveaux contacts Club IVS maximum par
  semaine, plus aucune sollicitation après une demande d'opposition.
- **Température** calculée depuis les trois critères (besoin, capacité/budget, intention).
- **Jours ouvrés** français, jours fériés compris.

Les délais se règlent dans `config/services.yaml` (`prospection.*`).

## Installation

```bash
composer install
# renseigner DATABASE_URL dans .env.local (MariaDB chez o2switch)
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

## Importer la base Notion

Dans Notion, menu `•••` de la base Prospects → Exporter → Markdown & CSV, puis :

```bash
php bin/console app:import:notion chemin/vers/Prospects.csv --dry-run   # pour vérifier
php bin/console app:import:notion chemin/vers/Prospects.csv
```

Les fiches déjà présentes (même nom) sont ignorées : la commande peut être relancée sans créer de doublon.

## Tâches planifiées

`app:prospection:cloturer-sans-reponse` met en pause les prospects restés sans réponse après la
dernière relance. Elle est déclarée pour chaque jour ouvré à 7 h. Sur o2switch, deux options :

- un cron qui lance la commande directement : `0 7 * * 1-5 php bin/console app:prospection:cloturer-sans-reponse` ;
- ou un cron qui fait tourner le Scheduler : `* * * * * php bin/console messenger:consume scheduler_default --time-limit=55`.

## Tests

```bash
php bin/phpunit
```

Les tests d'intégration utilisent la base `varyacode_crm_test` (suffixe `_test` ajouté automatiquement).
