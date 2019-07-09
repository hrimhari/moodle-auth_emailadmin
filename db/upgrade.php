<?php
defined('MOODLE_INTERNAL') || die();
/**
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_auth_emailadmin_upgrade($oldversion) {
    global $CFG, $DB;

    if ($oldversion < 2019070800) {
        upgrade_fix_config_auth_plugin_names('emailadmin');
        upgrade_fix_config_auth_plugin_defaults('emailadmin');
        upgrade_plugin_savepoint(true, 2019070800, 'auth', 'emailadmin');
    }

    return true;
}
