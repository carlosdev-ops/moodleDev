<?php
defined('MOODLE_INTERNAL') || die();

// Vérifie si l'utilisateur est admin
if ($hassiteconfig) {

    // Ajoute une page de configuration dans le menu "Plugins locaux"
    $settings = new admin_settingpage('local_monsuperplugin_settings',
        get_string('pluginname', 'local_monsuperplugin'));

    // Ajoute un champ de texte : 'welcomemessage'
    $settings->add(new admin_setting_configtext(
        'local_monsuperplugin/welcomemessage',             // Nom du réglage
        get_string('welcomemessage', 'local_monsuperplugin'), // Titre affiché
        get_string('welcomemessage_desc', 'local_monsuperplugin'), // Description
        'Bienvenue dans Moodle!'                             // Valeur par défaut
    ));

    // Enregistre les réglages dans la section "Plugins locaux"
    $ADMIN->add('localplugins', $settings);
}

