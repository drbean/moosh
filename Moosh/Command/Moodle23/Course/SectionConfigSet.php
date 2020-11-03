<?php
/**
 * Moosh - Moodle Shell
 *
 * @author    Tomasz Muras <tmuras@github.com>
 * @copyright 2012 onwards Tomasz Muras
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link      http://github.com/tmuras/moosh
 */

namespace Moosh\Command\Moodle23\Course;
use Moosh\MooshCommand;

/**
 * Sub class representing a moosh command.
 *
 * @package   core_dml
 * @copyright 2012 onwards Tomasz Muras
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link      http://docs.moodle.org/dev/DML_functions
 */
class SectionConfigSet extends MooshCommand
{
    public function __construct()
    {

        parent::__construct('config-set', 'section');

        $this->addArgument('mode');
        $this->addArgument('courseid');
        $this->addArgument('sectionno');
        $this->addArgument('setting');
        $this->addArgument('value');
    }

    public function execute()
    {
        $sectionno = trim($this->arguments[2]);
        $setting = trim($this->arguments[3]);
        $value = trim($this->arguments[4]);


        switch ($this->arguments[0]) {
    case 'course':
            if(!self::setSectionSetting($this->arguments[1]/* courseid */,$sectionno,$setting,$value)){
                // the setting was not applied, exit with non-zero exit code
                cli_error('');
        }
            break;
        case 'category':
                //get all courses in category (recursive)
                $courselist = get_courses($this->arguments[1]/* categoryid */,'','c.id');
                $succeeded = 0;
                $failed = 0;
                foreach ($courselist as $course) {
                    if(self::setSectionSetting($course->id,$sectionno,$setting,$value)){
                        $succeeded++;
                    }else{
                        $failed++;
                    }
                }
                if($failed == 0){
                    echo "OK - successfully modified $succeeded courses\n";
                }else{
                    echo "WARNING - failed to mofify $failed courses (successfully modified $succeeded)\n";

                break;
        }

    }
    }

    private function setSectionSetting($courseid,$sectionno,$setting,$value) {
        
        global $DB, $CFG;
        
        require_once($CFG->dirroot . '/course/lib.php');

        $section = $DB->get_record('course_sections', array("course"=>$courseid, "section"=>$sectionno),'*',MUST_EXIST);
        $data = array( $setting => $value );
        if ( course_update_section( $courseid,$section,$data ) ) {
            echo "OK - Set $setting='$value' (courseid={$courseid})\n";
            return true;
        } else {
            echo "ERROR - failed to set $setting='$value' (courseid={$courseid})\n";
            return false;
        }

    }

    protected function getArgumentsHelp()
    {
        return "\n\nARGUMENTS:\n\tcourse courseid setting value\n\tOr...\n\tcategory categoryid[all] setting value";
    }

}
