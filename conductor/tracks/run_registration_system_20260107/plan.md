# Plan de mise en œuvre : Système d'Inscription aux Courses

Ce plan détaille les étapes de création du système d'inscription, de la structure de données aux interfaces utilisateur avancées.

## Phase 1 : Fondations et Structure de Données
- [x] Task: Migration et Modèle pour les Courses (Runs) [2aa1375]
    - [x] Write Tests: Créer les tests unitaires pour le modèle `Run`.
    - [x] Implement: Créer la migration, le modèle et la factory pour `Run`.
- [x] Task: Migration et Modèle pour les Inscriptions (RunRegistrations) [5f9a1b3]
    - [x] Write Tests: Créer les tests unitaires pour le modèle `RunRegistration` (incluant SoftDeletes).
    - [x] Implement: Créer la migration, le modèle et la factory pour `RunRegistration`.
- [x] Task: Migration et Modèle pour les Éléments d'Inscription (RunRegistrationElements) [379affd]
    - [x] Write Tests: Créer les tests unitaires pour le modèle `RunRegistrationElement` (incluant la suppression en cascade).
    - [x] Implement: Créer la migration, le modèle et la factory pour `RunRegistrationElement`.
- [~] Task: Conductor - User Manual Verification 'Phase 1 : Fondations et Structure de Données' (Protocol in workflow.md)

## Phase 2 : Logique Métier et Services
- [x] Task: Service de Gestion des Inscriptions (RunRegistrationService) [362110b]
    - [x] Write Tests: Définir les tests pour la création, l'édition et la validation des inscriptions.
    - [x] Implement: Créer le service gérant la logique métier (calcul des totaux, vérification des délais, gestion des vouchers).
- [x] Task: Intégration de la Facturation Automatique [0a88eaf]
    - [x] Write Tests: Tester la liaison entre une `RunRegistration` et la génération d'une `Invoice`.
    - [x] Implement: Créer la logique de transformation d'une inscription en facture (liée au module existant).
- [x] Task: Système de Notifications (Emails et Liens Signés) [7ef87ad]
    - [x] Write Tests: Tester la génération des URLs signées et l'envoi des emails de confirmation.
    - [x] Implement: Créer les notifications pour l'envoi du lien d'édition permanent.
- [~] Task: Conductor - User Manual Verification 'Phase 2 : Logique Métier et Services' (Protocol in workflow.md)

## Phase 3 : Interface Administrateur (Filament)
- [x] Task: Ressource Filament pour les Courses (Runs)
    - [x] Implement: Créer `RunResource` avec formulaires et tables.
- [x] Task: Ressource Filament pour les Inscriptions (RunRegistrations)
    - [x] Implement: Créer `RunRegistrationResource` avec filtres et tris personnalisés.
- [x] Task: Actions de Facturation et d'Export
    - [x] Implement: Ajouter les actions individuelles et groupées (BulkActions) pour la facturation et les exports Excel/CSV (Elite, Datasport).
- [ ] Task: Conductor - User Manual Verification 'Phase 3 : Interface Administrateur (Filament)' (Protocol in workflow.md)

## Phase 4 : Interface Client (Front-end Livewire)
- [x] Task: Composants Livewire de Base pour les Formulaires
    - [x] Write Tests: Tester le rendu des formulaires selon le type (Entreprise, École, etc.).
    - [x] Implement: Créer les composants Livewire pour les 4 types de formulaires publics.
- [x] Task: Sécurisation des Accès (Routes Signées)
    - [x] Implement: Configurer les routes et les middlewares pour l'accès aux formulaires via liens signés.
- [x] Task: Grille de Saisie Interactive (Style Excel)
    - [x] Implement: Intégrer une bibliothèque JS (ex: Handsontable ou Grid.js) dans le composant Livewire pour la saisie des coureurs.
    - [x] Implement: Gérer la synchronisation des données de la grille avec la base de données (ajout/suppression de lignes).
- [x] Task: Validation et Verrouillage (Délais)
    - [x] Implement: Mettre en place la logique de verrouillage du tableau une fois le délai passé.
- [ ] Task: Conductor - User Manual Verification 'Phase 4 : Interface Client (Front-end Livewire)' (Protocol in workflow.md)
