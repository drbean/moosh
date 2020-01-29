<?php
/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace Moosh\Command\Moodle25\Activity;
use Moosh\MooshCommand;

class ActivityMove extends MooshCommand
{
    public function __construct()
    {
        parent::__construct('move', 'activity');

        $this->addOption('s|sectionnumber:=number', 'sectionnumber', null);

        $this->addArgument('moduleid');
        $this->addArgument('beforemodid');

        $this->minArguments = 1;
        $this->maxArguments = 2;
    }

    public function execute() 
    {
        global $CFG, $DB;
        require_once $CFG->dirroot . '/course/lib.php';

        $options = $this->expandedOptions;

        $moduleid = intval($this->arguments[0]);
        $beforemodid = intval($this->arguments[1]);

        if ($moduleid <= 0) {
            cli_error("Argument 'moduleid' must be bigger than 0.");
        } 
	$module = get_coursemodule_from_id('', $moduleid);
        if ( empty($module) ) {
            cli_error("There is no such activity to delete.");
        }

	$courseid = $module->course;
	$sectionid = $module->section;
	$course = $DB->get_record('course', array('id' => $courseid));
	$section = $DB->get_record('course_sections', array('id' => $sectionid));

	moveto_module($module, $section, $beforemodid);
        echo "Moved activity $moduleid\n";
    }
}

