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

        $this->addOption('i|idnumber:', 'idnumber', null);

        $this->addArgument('moduleid');
        $this->addArgument('beforemodid');
    }

    public function execute() 
    {
        global $CFG, $DB;
        require_once $CFG->dirroot . '/course/lib.php';

        $moduleid = intval($this->arguments[0]);
        $beforemodid = intval($this->arguments[1]);

        if ($moduleid <= 0) {
            cli_error("Argument 'moduleid' must be bigger than 0.");
        } 

	list($course, $module) = get_course_and_cm_from_instance($moduleid, 'quiz', 40);
	$section = get_fast_modinfo($course)->cms[$moduleid]->section;
	moveto_module($module, $section, $beforemodid);
        echo "Moved activity $moduleid\n";
    }
}

