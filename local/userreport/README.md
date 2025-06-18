# 📊m Moodle Plugin `local_userreport`

## Description

Ce plugin local Moodle `local_userreport` permet de générer un rapport complet des utilisateurs avec :

- ✅ Nom complet et courriel (partiellement masqué)
- ✅ Rôles attribués dans les cours
- ✅ Liste des cours inscrits
- ✅ Export CSV téléchargeable (généré physiquement sur le serveur)
- ✅ Lien direct vers le profil utilisateur Moodle
- ✅ Pagination dynamique avec choix du nombre de lignes par page

---

## Fonctionnalités techniques

- 🔒 Respect total des API Moodle (lib API, context API, enrol API, DB API, DB access layer)
- 🧱 Architecture propre, modulaire, maintenable
- 📅 Génération sécurisée du fichier CSV dans `moodledata` (hors arborescence de code)
- 🔐 Aucune donnée confidentielle exposée sur le web
- 🎯 Compatible Moodle 4.x (testé avec 4.2)
- 🚀 Extensible facilement pour du reporting avancé

---

## 📁 Arborescence du plugin

```
local/
└── userreport/
    ├── version.php
    ├── lang/
    │   └── en/local_userreport.php
    ├── report.php
    └── (moodledata)/local_userreport/*.csv
```

---

## 🔧 Installation

1️⃣ Copier le dossier `userreport/` dans le répertoire `local/` de votre instance Moodle.

2️⃣ Se connecter à Moodle (Administration du site > Notifications) pour exécuter l'installation automatique du plugin.

3️⃣ Le rapport est accessible directement via :

```
/local/userreport/report.php
```

---

## 📄 Utilisation

- **Affichage HTML :**

```
/local/userreport/report.php
```

- **Export CSV (généré sur le serveur et téléchargeable immédiatement) :**

```
/local/userreport/report.php?export=csv
```

> ⚠ Les fichiers CSV générés sont stockés dans :  
> `$CFG->dataroot/local_userreport/`

---

## 🤉 Dépendances

- Moodle >= 4.0 (testé avec 4.2)
- PHP >= 8.0
- Aucun plugin externe requis

---

## 📋 Tests réalisés

- Jeu de données simulé d'utilisateurs et d’inscriptions
- Tests multi-cours avec rôles multiples
- Vérification de l’export CSV sous Excel et LibreOffice
- Pagination et sélection du nombre de lignes par page
- Sécurité des chemins et accès contrôlé

---

## ✅ Checklist de développement Git (avant fusion)

- [x] Le code respecte les normes de développement Moodle (coding guidelines)
- [x] Les fonctionnalités sont testées en interface
- [x] Le code est isolé dans la branche `feature/userreport`
- [x] Le présent `README.md` est fourni
- [x] Export CSV fonctionnel et vérifié
- [x] Stockage sécurisé dans `moodledata`

---

## 👤 Auteur

Carlos Costa  
Dépôt GitHub : [https://github.com/carlosdev-ops/moodleDev](https://github.com/carlosdev-ops/moodleDev)

---

## 🚀 Roadmap d’évolution

- [ ] Ajouter des filtres dynamiques (nom, cours, rôle…)
- [ ] Export Excel natif avec formats enrichis
- [ ] Permettre la planification d’exports via Scheduled Tasks Moodle
- [ ] Ajout d’une interface administrateur de configuration
- [ ] Intégration à Moodle Workplace

---

## 📜 Licence

Ce plugin est publié sous licence GNU GPL v3 — Logiciel libre open-source

[Consulter la licence](https://www.gnu.org/licenses/gpl-3.0.html)
