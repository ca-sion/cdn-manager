# Spécification : Système d'Inscription aux Courses (Run Registrations)

## Présentation
Ce module permet la gestion complète des inscriptions pour la Course de Noël, incluant des formulaires publics sécurisés par des liens signés et une interface d'administration avancée pour le suivi et la facturation.

## Modèles et Données
### 1. Run (Courses)
- Champs : `name`, `distance`, `cost`, `available_for_types` (RunRegistrationTypesEnum enum), `start_blocs` (json), `registrations_deadline` (date), `registrations_limit` (int), `registrations_number`, `datasport_code`, `code`, `accepts_voucher` (bool), `provision_id`.

### 2. RunRegistration (Dossier d'inscription)
- Types : Entreprise, École, Groupe, Élite.
- Champs  :
client_id
run_registration_type
invoicing_company_name
invoicing_address
invoicing_address_extension
invoicing_postal_code
invoicing_locality
invoicing_email
invoicing_note
payment_iban
payment_note
company_name
school_name
school_postal_code
school_locality
school_country (selon liste dataposrt)
school_class_level (liste ou enum)
school_class_holder_first_name
school_class_holder_last_name
school_class_holder_email
school_class_holder_phone
contact_first_name
contact_last_name
contact_email
contact_phone
created_at
modified_at
- Spécificités École : `school_name`, `class_level`, `holder_details`.
- Gestion : Soft Deletes + Cascade delete sur les éléments.

### 3. RunRegistrationElement (Coureurs individuels)
- Champs :
run_registration_id
first_name
first_name
birthdate
gender (M,F)
nationality (selon liste datasport)
email
team (reprendre selon run_registration)
run_id
run_name (reprendre selon run)
bloc (liste selon run)
with_video
voucher_code
(pour elite)
address
address_extension
postal_code
locality
country
iban
payment_note
has_free_registration_fee
has_bonus_start
bonus_start_amount
bonus_ranking_amount
bonus_arrival_amount
has_accommodation (boolean)
accommodation_friday
accommodation_saturday
accommodation_precision
has_expense_reimbursement (boolean)
expense_reimbursement_precision
created_at
modified_at

### 4. RunRegistrationTypesEnum (enum) :
company
school
group
elite

## Fonctionnalités
### Interface Client (Front-end)
- **Quatre interfaces distinctes** basées sur le type d'inscription (URL dynamique).
- **Accès sécurisé via liens signés** : Pas de compte utilisateur, envoi d'un email avec un lien permanent d'édition à la création.
- **Grille de saisie "Excel-like"** :
    - Saisie directe dans le tableau (Livewire + bibliothèque JS style Handsontable/Grid.js).
    - Navigation par flèches clavier.
    - Support du copier-coller.
    - Menus déroulants (Select) intégrés pour le choix des courses/blocs.
    - Limite de modification basée sur le délai d'inscription (`registrations_deadline`).
- **Indicateurs en temps réel** : Taux de remplissage des courses, gestion des codes vouchers (utilisés vs disponibles).

### Interface Administrateur (Filament)
- **Gestion des inscriptions** : Tri par localité/nombre d'éléments, filtres par type.
- **Association Client** : Possibilité de lier à un `client_id` existant ou d'en créer un à la volée.
- **Facturation** : Boutons d'action (individuel et bulk) pour générer automatiquement les factures dans le système existant.
- **Exports avancés** : 
    - Liste Élite (Excel/CSV).
    - Format Datasport spécifique (Excel/CSV).
    - Export agrégé de toutes les inscriptions.

## Critères d'Acceptation
- [ ] Les liens signés permettent d'éditer l'inscription sans être connecté.
- [ ] Le tableau de saisie supporte au moins 150 lignes sans ralentissement.
- [ ] La navigation au clavier fonctionne entre les cellules du tableau de saisie.
- [ ] Les factures générées sont correctement liées au client et contiennent les montants exacts des inscriptions.
