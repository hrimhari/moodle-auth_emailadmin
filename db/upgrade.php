<?php
defined('MOODLE_INTERNAL') || die();
/**
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_auth_emailadmin_upgrade($oldversion) {
    global $CFG, $DB;

    return true;
}
