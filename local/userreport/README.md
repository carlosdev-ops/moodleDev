# 📊 Plugin local_userreport – Moodle

Ce plugin local Moodle génère un rapport des utilisateurs incluant :

- Nom complet et courriel
- Rôles attribués dans les cours
- Cours inscrits
- Export CSV téléchargeable
- Lien vers le profil utilisateur

---

## 🔧 Installation

1. Copier le dossier `userreport/` dans `local/`
2. Accéder à Moodle pour finaliser l’installation
3. Aller sur : `/local/userreport/report.php`

---

## 📄 Exemple d’accès

- Affichage HTML : `/local/userreport/report.php`
- Export CSV : `/local/userreport/report.php?export=csv`

---

## 🧩 Dépendances

- Moodle 4.2+
- Aucun plugin externe requis

---

## 📋 Testé avec

- Liste d’utilisateurs fictifs
- Rôles assignés en contexte de cours
- Export CSV vérifié sous Excel / LibreOffice

---

## ✅ Checklist avant fusion

- [x] Le code respecte les normes Moodle
- [x] La fonctionnalité a été testée dans l’interface
- [x] Le code est isolé dans une branche `feature/`
- [x] Un `README.md` a été ajouté
- [x] L’export CSV fonctionne avec les rôles et cours

---

## 👤 Auteur

Carlos Costa  
Dépôt : https://github.com/carlosdev-ops/moodleDev
