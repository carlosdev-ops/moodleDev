<?php
require(__DIR__ . '/../../config.php');
require_login();

// Obtenir le contexte global de Moodle
$context = context_system::instance();

// Définir l'URL, le titre et l'entête de la page
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/monsuperplugin/index.php'));
$PAGE->set_title(get_string('pluginname', 'local_monsuperplugin'));
$PAGE->set_heading(get_string('pluginname', 'local_monsuperplugin'));

// 🔁 Lire la configuration du message
$welcomemessage = get_config('local_monsuperplugin', 'welcomemessage');

// Affichage
echo $OUTPUT->header();
echo $OUTPUT->heading($welcomemessage); // 🔁 message personnalisé
echo $OUTPUT->footer();

