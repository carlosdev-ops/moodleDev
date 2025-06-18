<?php
// 🔧 Affichage des erreurs pour développement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 📦 Chargement de l'environnement Moodle
require(__DIR__ . '/../../config.php');
require_login();

// 📚 Librairie de tableau flexible
require_once($CFG->libdir . '/tablelib.php');

// 🎯 Contexte Moodle standard
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/userreport/report.php'));
$PAGE->set_title(get_string('pluginname', 'local_userreport'));
$PAGE->set_heading(get_string('pluginname', 'local_userreport'));

// 📊 Récupération des utilisateurs depuis la base
$users = $DB->get_records('user', null, 'lastname ASC', 'id, firstname, lastname, email');
$totalcount = count($users);

// 🔢 Détection du nombre d'enregistrements souhaité par l'utilisateur
$perpage = optional_param('perpage', 10, PARAM_INT);  // Valeur par défaut : 10

// 🚩 Gestion de l'export CSV avant tout affichage Moodle
if (optional_param('export', '', PARAM_ALPHA) === 'csv') {
    export_user_report_csv($users);
    exit;
}

// 🧾 Création de la table flexible
$table = new flexible_table('userreport_table');
$table->define_columns(['id', 'fullname', 'roles', 'courses', 'email']);
$table->define_headers(['ID', 'Nom complet', 'Rôles', 'Cours', 'Courriel']);
$table->define_baseurl(new moodle_url('/local/userreport/report.php', ['perpage' => $perpage]));
$table->set_attribute('class', 'generaltable userreport-table');
$table->pagesize($perpage, $totalcount);
$table->setup();

// 🖥 Affichage de la page Moodle
echo $OUTPUT->header();
echo $OUTPUT->heading('📋 Rapport des utilisateurs Moodle');

// 🔘 Sélecteur du nombre de lignes par page
$perpageurl = new moodle_url('/local/userreport/report.php');
echo '<form method="get" action="' . $perpageurl . '">';
echo '<label for="perpage">Nombre d\'éléments par page : </label>';
echo '<select name="perpage" onchange="this.form.submit()">';
foreach ([10, 25, 50, 100, 0] as $n) {
    $label = ($n === 0) ? 'Tous' : $n;
    $selected = ($perpage == $n) ? 'selected' : '';
    echo "<option value=\"$n\" $selected>$label</option>";
}
echo '</select>';
echo '</form><br>';

// 🔗 Lien export CSV
$exporturl = new moodle_url('/local/userreport/report.php', ['export' => 'csv']);
echo html_writer::link($exporturl, '📥 Exporter en CSV');
echo html_writer::empty_tag('br');

// 📋 Pagination : filtrer les utilisateurs pour la page en cours
if ($perpage > 0) {
    $users_page = array_slice($users, $table->get_page_start(), $table->get_page_size(), true);
} else {
    $users_page = $users; // Afficher tout
}

// 🧱 Construction du tableau
foreach ($users_page as $user) {
    $profileurl = new moodle_url('/user/profile.php', ['id' => $user->id]);
    $name_link = html_writer::link($profileurl, fullname($user));

    // 🔍 Recherche des cours et rôles
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

    $table->add_data([
        $user->id,
        $name_link,
        implode(', ', $role_names),
        implode(', ', $course_names),
        format_text(local_userreport_obfuscate_email($user->email), FORMAT_PLAIN)
    ]);
}

// 🖨 Affichage final
$table->print_html();
echo $OUTPUT->footer();


// 🔐 Fonction d'obfuscation d'email
function local_userreport_obfuscate_email($email) {
    $parts = explode('@', $email);
    return substr($parts[0], 0, 2) . '***@' . $parts[1];
}


// 🔄 Fonction d'export CSV vers un fichier physique dans Moodle
function export_user_report_csv($users) {
    global $DB, $CFG;

    $filename = 'user_report_' . date('Ymd_His') . '.csv';
    $filepath = $CFG->dataroot . '/local_userreport/' . $filename;

    // ✅ On crée le répertoire s'il n'existe pas
    if (!file_exists($CFG->dataroot . '/local_userreport/')) {
        mkdir($CFG->dataroot . '/local_userreport/', 0777, true);
    }

    $fp = fopen($filepath, 'w');
    if (!$fp) {
        print_error('Impossible de créer le fichier CSV sur le serveur.');
    }

    // En-tête CSV
    fputcsv($fp, ['ID', 'Nom complet', 'Rôles', 'Cours', 'Courriel']);

    foreach ($users as $user) {
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
        fputcsv($fp, [
            $user->id,
            fullname($user),
            implode(', ', $role_names),
            implode(', ', $course_names),
            local_userreport_obfuscate_email($user->email)
        ]);
    }

    fclose($fp);

    // ✅ On propose le téléchargement après génération
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    readfile($filepath);
}
