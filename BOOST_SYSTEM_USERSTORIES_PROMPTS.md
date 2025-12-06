# Système de Boost d'Annonce – Prompts & User Stories

## User Stories

### Frontend

1. **En tant qu'utilisateur, je veux pouvoir booster mon annonce lors de sa création**
   - Lors de la création d'une annonce, je peux choisir d'activer un boost (payant ou essai gratuit si disponible).
   - Je vois le prix du boost et la durée.
   - Si j'ai droit à un essai gratuit, l'option m'est proposée.
   - Après paiement ou validation de l'essai, mon annonce est boostée.

2. **En tant qu'utilisateur, je veux booster une annonce déjà existante**
   - Depuis la liste de mes annonces, je peux cliquer sur "Booster" pour une annonce.
   - Je vois les options de boost (payant ou essai gratuit si disponible).
   - Après paiement ou validation de l'essai, l'annonce est boostée.

3. **En tant qu'utilisateur, je veux voir mes annonces boostées mises en avant**
   - Les annonces boostées apparaissent en haut de la liste ou avec un badge spécial.
   - Je peux voir la durée restante du boost.

4. **En tant qu'utilisateur, je veux être notifié quand mon boost expire**
   - Je reçois une notification ou un email à l'expiration du boost.

### Backend

1. **En tant qu'API, je dois permettre de créer un boost pour une annonce**
   - Endpoint POST `/annonces/{id}/boost` avec gestion du paiement ou de l'essai gratuit.
   - Vérification de l'éligibilité à l'essai gratuit.
   - Création d'une entrée dans la table `boost_annonces`.
   - Mise à jour du champ `boosted_until` de l'annonce.

2. **En tant qu'API, je dois lister les annonces boostées**
   - Endpoint GET `/annonces/boosted` pour retourner les annonces boostées, triées par priorité.

3. **En tant qu'API, je dois gérer l'expiration des boosts**
   - Tâche planifiée pour désactiver les boosts expirés et mettre à jour les statuts.

4. **En tant qu'API, je dois notifier l'utilisateur à l'expiration du boost**
   - Envoi d'un email ou notification à l'utilisateur concerné.

5. **En tant qu'API, je dois intégrer le paiement pour le boost**
   - Intégration avec une API de paiement (callback pour activer le boost après paiement réussi).

## Prompts pour le Développement

### Frontend
- "Ajouter une option de boost lors de la création d'annonce, avec affichage du prix et gestion de l'essai gratuit."
- "Afficher un bouton 'Booster' sur chaque annonce existante de l'utilisateur."
- "Mettre en avant les annonces boostées dans la liste (badge, position, durée restante)."
- "Afficher une notification à l'utilisateur à l'expiration du boost."

### Backend
- "Créer un endpoint pour booster une annonce, avec gestion du paiement et de l'essai gratuit."
- "Créer une table boost_annonces pour stocker les boosts actifs et historiques."
- "Mettre à jour le champ boosted_until dans la table des annonces lors de l'activation d'un boost."
- "Créer une tâche planifiée pour désactiver les boosts expirés et notifier les utilisateurs."
- "Intégrer l'API de paiement pour activer le boost après paiement."

---

Adapte ces prompts et user stories selon tes besoins spécifiques ou ton workflow.

passons a l'implementation premierement pour la creation d'annonce, donc ce que je veux c'est que lors de la creation d'annonce uniquement on peut booster l'annonce pour x jours gratuitement (la date sera definie par l'admin) et en suite il aura le choix entre jour semaine et mois le prix sera aussi fixé par l'admin lors de l'insertion , tu connais mieux que moi ce qui va se passer mais est ce qu'on aura un service de paiement qui sera reutilisable pour la deuxieme methode a savoir la methode de l'annonce existante ? si oui voici ce que je propose, lors de la creation d'annonce boostée on cree d'abord l'annonce puis on appelle le service de paiement avec une valeur facultative is new dans ce cas en cas d'echec de paiement, il peut decider de booster son annonce gratuitement pour x jours cette option est valable uniquement pour les nouvelles annonces les annonces existantes ne l'auront pas maintenant n'oublie pas les fonctions pour reessayer un paiement etc on va utiliser le mobile money et Campay je suis au cameroun dis moi ce que tu en penses ? 
et pour les durées de boost prix etc on stocke ca dans la table promotion_packs lis cambizzle_api et tu comprends donc on ne va plus se casser la tete

fais tout commence par le modele ensuite service puis controlleur et routes je vais superviser

j'ai vu le probleme, dans la bd la reference est differete de celle de campay on dirait qu'on genere regarde 
reference en bd : AD_BOOST_15_6903314ea4cc3_1761816910
reference campay :