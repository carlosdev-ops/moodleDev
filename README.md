# 🎓 MoodleDev – Environnement de développement Moodle

Ce dépôt contient une **installation complète de Moodle 4.2**, utilisée pour le développement de plugins, de thèmes, et l’expérimentation locale.

> 📅 Initialisé le : 2025-04-13

---

## 🛠️ Objectif

Créer un environnement local prêt à l’emploi pour :
- Développement de plugins Moodle
- Tests de thèmes personnalisés
- Apprentissage de l’architecture Moodle
- Expérimentation avec Git, Xdebug, CI/CD, etc.

---

## 🧱 Stack technique

| Composant      | Version / Configuration          |
|----------------|----------------------------------|
| OS             | Ubuntu 24.04 LTS (VirtualBox)    |
| Serveur web    | Apache2                          |
| PHP            | 8.1 (via ppa:ondrej/php)         |
| Base de données| MariaDB 10.11                    |
| Moodle         | 4.2 (branche `MOODLE_402_STABLE`)|
| URL locale     | http://10.0.0.79                 |

---

## 📦 Installation manuelle

Voir le fichier [📘 Installation_Moodle_Dev_Local.md](Installation_Moodle_Dev_Local.md) pour le guide détaillé d’installation étape par étape.

---

## 📂 Structure recommandée

```bash
/var/www/moodle         # Code source Moodle
/var/moodledata         # Fichiers de données (hors webroot)
```

---

## 🔒 .gitignore personnalisé

Le dépôt ignore :
- `config.php`
- `moodledata/`
- fichiers temporaires et locaux (`*.log`, `.idea/`, `.vscode/`, etc.)
- dossiers build de thèmes et modules

---

## 🔗 Liens utiles

- [Moodle Dev Docs](https://moodledev.io/)
- [Moodle sur GitHub](https://github.com/moodle/moodle)
- [Moodle Plugin Dev Guide](https://moodledev.io/docs/guides/plugins)

---

## 📌 À venir

- [ ] Plugin local de test
- [ ] Dockerisation de l’environnement
- [ ] Intégration Xdebug + VS Code
- [ ] CI GitHub Actions pour validation syntaxique

---

## 🧑‍💻 Auteur

**Carlos Costa**  
[GitHub : carlosdev-ops](https://github.com/carlosdev-ops)

---

_Projet personnel à but éducatif._
