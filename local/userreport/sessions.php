<?php
// 🔧 Activer l'affichage des erreurs pour le développement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 📦 Initialiser Moodle
require(__DIR__ . '/../../config.php');
require_login();

// 📚 Charger la bibliothèque du plugin Face-to-face
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

// 🌐 Contexte global du site
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/userreport/sessions.php'));
$PAGE->set_title('Sessions de formation');
$PAGE->set_heading('Sessions de formation');

// 📊 Requête pour récupérer les sessions facetoface
$sessions = $DB->get_records('facetoface_sessions', null, '', '*');

// 📋 Préparer le tableau HTML
$table = new html_table();
$table->head = ['ID', 'Durée (min)', 'Capacité', 'Créé le'];
$table->data = [];

foreach ($sessions as $session) {
    $table->data[] = [
        $session->id,
        $session->duration,
        $session->capacity,
        userdate($session->timecreated)
    ];
}

// 🖥️ Affichage de la page
echo $OUTPUT->header();
echo $OUTPUT->heading('📆 Liste des sessions enregistrées');
echo html_writer::table($table);
echo $OUTPUT->footer();