<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require(__DIR__ . '/../../config.php');
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/userreport/report.php'));
$PAGE->set_title(get_string('pluginname', 'local_userreport'));
$PAGE->set_heading(get_string('pluginname', 'local_userreport'));

// 🔎 Requête pour récupérer les utilisateurs
$users = $DB->get_records('user', null, 'lastname ASC', 'id, firstname, lastname, email');

// 📋 Construction du tableau
$table = new html_table();
$table->head = ['ID', 'Nom complet', 'Rôles', 'Cours', 'Courriel'];
$table->data = [];

foreach ($users as $user) {
    $profileurl = new moodle_url('/user/profile.php', ['id' => $user->id]);
    $name_link = html_writer::link($profileurl, fullname($user));

    // 🔹 Récupérer les cours
    $courses = enrol_get_users_courses($user->id, true);
    $course_names = [];
    $role_names = [];

    foreach ($courses as $course) {
        $course_names[] = format_string($course->fullname);

        $context = context_course::instance($course->id);
        $roles = get_user_roles($context, $user->id, true);

        foreach ($roles as $role) {
            $role_names[] = role_get_name($role, $context);
        }
    }

    // Supprimer les doublons
    $role_names = array_unique($role_names);

    // 📋 Ajouter au tableau
    $table->data[] = [
        $user->id,
        $name_link,
        implode(', ', $role_names),
        implode(', ', $course_names),
        format_text(local_userreport_obfuscate_email($user->email), FORMAT_PLAIN)
    ];
}


// 🔁 Vérifie si on demande un export CSV
if (optional_param('export', '', PARAM_ALPHA) === 'csv') {
    // Définit les en-têtes HTTP
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="user_report.csv"');

    $output = fopen('php://output', 'w');

    // En-tête du tableau CSV
    fputcsv($output, ['ID', 'Nom complet', 'Rôles', 'Cours', 'Courriel']);

    // Les mêmes données que dans ton tableau
    foreach ($users as $user) {
        $profileurl = new moodle_url('/user/profile.php', ['id' => $user->id]);

        $courses = enrol_get_users_courses($user->id, true);
        $course_names = [];
        $role_names = [];

        foreach ($courses as $course) {
            $course_names[] = format_string($course->fullname);
            $context = context_course::instance($course->id);
            $roles = get_user_roles($context, $user->id, true);
            foreach ($roles as $role) {
                $role_names[] = role_get_name($role, $context);
            }
        }

        $role_names = array_unique($role_names);

        fputcsv($output, [
            $user->id,
            fullname($user),
            implode(', ', $role_names),
            implode(', ', $course_names),
            local_userreport_obfuscate_email($user->email)
        ]);
    }

    fclose($output);
    exit;
}

echo $OUTPUT->header();


echo $OUTPUT->heading('Rapport des utilisateurs Moodle');

$exporturl = new moodle_url('/local/userreport/report.php', ['export' => 'csv']);
echo html_writer::link($exporturl, '📥 Exporter en CSV');


echo html_writer::table($table);
echo $OUTPUT->footer();


// 🔐 Petite fonction pour cacher une partie du courriel
function local_userreport_obfuscate_email($email) {

    $parts = explode('@', $email);
    return substr($parts[0], 0, 2) . '***@' . $parts[1];
}
