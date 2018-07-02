<?php

/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle26\Activity;
use Moosh\MooshCommand;

use GetOptionKit\Argument;

/**
 * Modifies an existing activity
 *
 * @copyright 2013 David Monllaó
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ActivityMod extends MooshCommand
{

    public function __construct()
    {
        parent::__construct('mod', 'activity');

        $this->addOption('n|name:', 'new activity instance name');
        $this->addOption('s|section:', 'new section number', '1');
        $this->addOption('i|idnumber:', 'new idnumber', null);
        $this->addOption('c|gradecat:', 'new gradecategory id', null);
        $this->addOption('o|options:', 'any options that should be passed for activity modification', null);

        $this->addArgument('coursemoduleid');

        $this->minArguments = 1;
    }

    /**
     * @param int $id course id
     * @param int $id course module id
     * @return Displays the activity id (not the course module, it depends on the activity type).
     */
    public function execute()
    {
        global $CFG, $DB;
        require_once $CFG->dirroot . '/course/lib.php';

        $moduleid = $this->arguments[0];
        $cm = get_coursemodule_from_id('', $moduleid, 0, false, MUST_EXIST);
        $courseid = $cm->course;
        $module = $cm->modname;
        $instance = $cm->instance;
        $modinfo = get_fast_modinfo($courseid)->instances[$module][$instance];
        $activitytype = $modinfo->get_module_type_name();

        // $options are course module options.
        $options = $this->expandedOptions;

        if (!empty($options['name'])) {
            $name = $options['name'];
            $success = set_coursemodule_name( $moduleid, $name );
            if ( !$success ) {
                    die( "No renaming of coursemodule $moduleid to \"$name\"\n" );
            }
        }

        if (!empty($options['section'])) {
            $sectionnum = $options['section'];
            $section = $DB->get_record( 'course_sections'
                    , array( 'course'=>$courseid, 'section'=>$sectionnum ), '*', MUST_EXIST );
            $visibility = moveto_module( $modinfo, $section );
            if ($this->verbose) {
                echo "Visibility of coursemodule $moduleid added at end of section $sectionid is $visibility\n";
            }
        }

        if (!empty($options['idnumber'])) {
            $idnumber = $options['idnumber'];
            $success = set_coursemodule_idnumber( $moduleid, $idnumber );
            if ( !$success ) {
                    die( "No resetting of coursemodule $moduleid idnumber to \"$idnumber\"\n" );
            }
        }

/*
        * @param array|stdClass $moduledata data for module being modified. Requires 'course' key
        *     (an id or the full object). Also can have any fields from add module form.
*/
        $mod_info_mod = new \stdClass();

        if (!empty($options['options'])) {
            $course_module_options = preg_split( '/\s+(?=--)/', $options['options']);
            foreach ( $course_module_options as $option ) {
                $arg = new Argument( $option );
                $name = $this->getOptionName($arg);
                $value = $arg->getOptionValue();
                if ( $name == 'intro' ) {
                    $introeditor = array("text"=>$value,
                        "format"=> FORMAT_MARKDOWN, "itemid" => 0 );
                    $mod_info_mod->introeditor = $introeditor;
                }
                else { $mod_info_mod->$name = $value; }
            }
        }
        foreach ( $modinfo as $name => $value ) {
            if ( !isset($mod_info_mod->$name ) ) {
                $mod_info_mod->$name = $value;
            }
        }
        if ( !isset($mod_info_mod->introeditor) ) {
                if ( isset($modinfo->intro) ) {
                   $intro = $modinfo->intro;
                }
                else { $intro = ''; }
            $introeditor = array("text"=>$intro,
                "format"=> FORMAT_MARKDOWN, "itemid" => 0 );
            $mod_info_mod->introeditor = $introeditor;
        }

        if ( isset($moduledata->groupmode) ) {
            $groupmode = $moduledata->groupmode;
            $success = set_coursemodule_groupmode($moduleid, $groupmode );
            if ( !$success ) {
                    die( "No resetting of coursemodule $moduleid groupmode to \"$groupmode\"\n" );
            }
        }

        $mod_info_mod->course = $mod_info_mod->course;
        $mod_info_mod->coursemodule = $moduleid;
        if ( $activitytype == 'URL' || $activitytype == 'Page' ) {
            $mod_info_mod->display = 0;
        }
        var_dump($mod_info_mod);
        $record = update_module($mod_info_mod);
        \core\event\course_module_updated::create_from_cm($cm)->trigger();
        rebuild_course_cache($cm->course, true);

        if ($this->verbose) {
            echo "Activity {$this->arguments[0]} updated successfully\n";
            // var_dump($record);
            echo "display=" . $mod_info_mod->display . "\n";
            echo "display=" . $record->display . "\n";
        }

        // Return the activity id.
        echo "{$mod_info_mod->id}\n";
    }

    private function getOptionName($arg)
    {
        if (preg_match('/^[-]+([_a-zA-Z0-9-]+)/', $arg->arg, $regs)) {
            return $regs[1];
        }
    }
}
