<?php
require(__DIR__ . '/../../config.php');
require_login();

require_once($CFG->dirroot . '/mod/facetoface/lib.php');

echo '<pre>';

$sessions = $DB->get_records('facetoface_sessions', null, '', '*');

print_r(reset($sessions));
echo '</pre>';
exit;
