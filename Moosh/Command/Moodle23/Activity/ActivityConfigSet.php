<?php
/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle23\Activity;
use Moosh\MooshCommand;

class ActivityConfigSet extends MooshCommand
{
    public function __construct()
    {
        parent::__construct('config-set', 'activity');

        $this->addOption('s|sectionnumber:=number', 'sectionnumber', null);

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
        $id = $this->arguments[1];
        $modulename = $this->arguments[2];
        $setting = trim($this->arguments[3]);
        $value = trim($this->arguments[4]);

        $options = $this->expandedOptions;
        $sectionnumber = $options['sectionnumber'];

        switch ($this->arguments[0]) {
            case 'activity':
                if(!self::setActivitySetting($modulename, $id/* activityid */,$setting,$value)){
                	// the setting was not applied, exit with a non-zero exit code
                	cli_error('');
                }
                break;
            case 'course':
                //get all activities in the course
                $course_mod_info = get_fast_modinfo($id/* courseid */)->cms;
                $course_mod_list = array_values( $course_mod_info );
                $activitylist = array();
                foreach ($course_mod_list as $mod) {
                    if ( $mod->modname == $modulename ) {
                        if ( !empty($sectionnumber) and $mod->sectionnum == $sectionnumber ) {
                            $activitylist[] = $mod;
                        }else{ $activitylist[] = $mod; }
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
