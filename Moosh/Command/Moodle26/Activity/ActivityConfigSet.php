<?php
/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle26\Activity;
use Moosh\MooshCommand;

class ActivityConfigSet extends MooshCommand
{
    public function __construct()
    {
        parent::__construct('config-set', 'activity');

        $this->addArgument('mode');
        $this->addArgument('id');
        $this->addArgument('module');
        $this->addArgument('setting');
        $this->addArgument('value');

        $this->minArguments = 5;
    }

    public function execute()
    {
        $mode = $this->arguments[0];
        $activityid = $this->arguments[1];
        $modulename = $this->arguments[2];
        $setting = trim($this->arguments[3]);
        $value = trim($this->arguments[4]);


        switch ($this->arguments[0]) {
            case 'activity':
                if(!self::setActivitySetting($modulename, $this->arguments[1]/* activityid */,$setting,$value)){
                	// the setting was not applied, exit with a non-zero exit code
                	cli_error('');
                }
                break;
            case 'course':
                //get all activities in the course
                $course_mod_list = get_course_mods($this->arguments[1]/* courseid */);
                $activitylist = array();
                foreach ($course_mod_list as $mod) {
                   if ( $mod->modname = $modulename ) {
                       $activitylist[] = $mod;
                   }
                }
                $succeeded = 0;
                $failed = 0;
                foreach ($activitylist as $activity) {
                    if(self::setActivitySetting($modulename,$activity->instance,$setting,$value)){
                        $succeeded++;
                    }else{
                        $failed++;
                    }
                }
                if($failed == 0){
                    echo "OK - successfully modified $succeeded activities\n";
                }else{
                    echo "WARNING - failed to mofify $failed activities (successfully modified $succeeded)\n";
                }
                break;
        }

    }

    private function setActivitySetting($modulename,$activityid,$setting,$value) {
        
        global $DB;
        
        if ($DB->set_field($modulename,$setting,$value,array('id'=>$activityid))) {
            echo "OK - Set $setting='$value' ($modulename activityid={$activityid})\n";
            return true;
        } else {
            echo "ERROR - failed to set $setting='$value' ($modulename activityid={$activityid})\n";
            return false;
        }

    }

    protected function getArgumentsHelp()
    {
        return "\n\nARGUMENTS:\n\tactivity activityid module setting value\n\tOr...\n\tcourse courseid[all] module setting value";
    }

}
