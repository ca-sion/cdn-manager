# GEMINI.md - Guide & Contexte du Projet CDN Manager

Ce fichier centralise l'architecture, la stack technique, les normes métier et les conventions de code pour les agents IA et développeurs travaillant sur ce projet.

---

## 🎯 Vision et objectifs du produit

**CDN Manager** est un système de gestion administrative pour la prise en charge des clients, des contacts, de la facturation et des prestations de services.

### Objectifs Principaux
- **Gestion de la relation client (CRM) :** Centralisation des informations clients et contacts.
- **Automation de la facturation suisse :** Génération et traitement automatisé des factures QR suisses (**Swiss QR-Bill**) et génération de documents PDF.
- **Traitement des relevés bancaires :** Importation et traitement des fichiers de relevés bancaires **CAMT** (`genkgo/camt`).
- **Suivi des engagements et prestations :** Gestion des prestations de services à travers différentes éditions et phases de projet.

---

## 🛠️ Stack Technique (Mise à jour)

### Backend et frameworks
- **PHP :** `^8.2`
- **Laravel Framework :** `^11.0`
- **Filament Admin :** `^5.0` (Panel d'administration basé sur le TALL stack)
- **Livewire :** `^4.0` (Composants réactifs)

### Frontend et styles
- **Tailwind CSS :** `^4.3` (avec `@tailwindcss/vite`)
- **Vite :** `^5.0` (Build tool)
- **Alpine.js & Flowbite :** Support UI réactif et composants

### Paquets et bibliothèques clés
- `sprain/swiss-qr-bill` (`^4.12`) : Génération des QR-bills suisses conformes aux normes financières.
- `genkgo/camt` (`^2.10`) : Parsing et traitement des fichiers bancaires CAMT.
- `barryvdh/laravel-dompdf` (`^2.2`) : Génération de documents PDF (factures, décomptes, rapports).
- `outerweb/filament-settings` (`^2.0`) : Gestion des paramètres système dans Filament.
- `spatie/eloquent-sortable` (`^4.4`) & `spatie/laravel-media-library-plugin` (`^5.0`) : Gestion des médias et tri d'enregistrements.
- `rap2hpoutre/fast-excel` (`^5.6`) : Import / Export Excel rapide.
- `elic-dev/laravel-site-protection` (`^1.2`) : Protection de l'accès au site / environnement.

### Qualité et tests
- **Pest PHP :** `^2.0` (Framework de tests unitaires et fonctionnels)
- **Laravel Pint :** `^1.15` (Formateur et linter PHP)

---

## 📐 Directives métier et principes de conduite

1. **Conformité aux normes suisses :**
   - Toutes les fonctionnalités financières (facturation QR, décomptes, montants en CHF, TVA) doivent respecter strictement les réglementations financières suisses.
2. **Intégrité et auditabilité des données :**
   - Validation stricte en temps réel sur les saisies pour minimiser l'erreur humaine.
   - Traçabilité et journaux d'audit sur les modifications des données sensibles.
3. **Langue et fnterface :**
   - **Langue principale de l'application et des échanges : Français.**
   - Les norme typographiques sont suisses. Pas de majuscules au milieu des titres ni d'esperluette.

---

## 💻 Conventions de code

- **PHP moderne et typé :** Utiliser le typage strict (`declare(strict_types=1);`), les propriétés typées et le pattern match PHP 8.2+.
- **Style de code :** Exécuter `vendor/bin/pint` pour maintenir la conformité du code PHP.
- **Simplicité et lisibilité :** Privilégier la clarté et la simplicité architecturale par rapport aux abstractions excessives.

---

## 🚀 Commandes fréquentes

```bash
# Lancement de l'environnement de développement
npm run dev
# Utilisation de Herd

# Tests & Qualité
vendor/bin/pint           # Formater le code PHP
php artisan test          # Exécuter les tests via Pest

# Filament & Migration
php artisan filament:upgrade
php artisan migrate
```
