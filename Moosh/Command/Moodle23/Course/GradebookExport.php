<?php
/**
 * moosh - Moodle Shell
 *
 * @copyright  2016 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle23\Course;
use Moosh\MooshCommand;

class GradebookExport extends MooshCommand
{
    public function __construct()
    {
        parent::__construct('export', 'gradebook');

        //$this->addArgument('name');

        $this->addOption('i|id:', 'id', 26);
        $this->addOption('e|itemids:', 'exercise grade ids', null);
        $this->addOption('g|groupid:', 'group id', 0);
        $this->addOption('f|exportfeedback:', 'exportfeedback', 0);
        $this->addOption('a|onlyactive:', 'onlyactive', 1);
        $this->addOption('d|displaytype:', 'displaytype', 'txt');
        $this->addOption('p|decimalpoints:', 'decimalpoints', 2);
        $this->addOption('s|separator:', 'separator', 'comma');



    }

    public function execute()
    {

        global $CFG, $DB;

        require_once($CFG->dirroot . '/grade/export/lib.php');
        require_once($CFG->dirroot . '/grade/export/txt/grade_export_txt.php');
        require_once($CFG->libdir . '/grade/grade_item.php');
        require_once($CFG->libdir . '/csvlib.class.php');


        // Some variables you may want to use
        //  $this->cwd - the directory where moosh command was executed
        //  $this->mooshDir - moosh installation directory
        //  $this->expandedOptions - commandline provided options, merged with defaults
        //  $this->topDir - top Moodle directory
        //  $this->arguments[0] - first argument passed
        //  $this->pluginInfo - array with information about the current plugin (based on cwd), keys:'type','name','dir'
        //  $this->verbose - if set to true, then "moosh -v" was run - add more verbose / debug information

        $options = $this->expandedOptions;
        if (!empty($options['id'])) {
            $id = $options['id'];
        }
        if (!empty($options['itemids'])) {
            $itemids = $options['itemids'];
        }
        if (isset($options['groupid'])) {
            $groupid = $options['groupid'];
        }
        if (!empty($options['exportfeedback'])) {
            $exportfeedback = $options['exportfeedback'];
        }
        if (!empty($options['onlyactive'])) {
            $onlyactive = $options['onlyactive'];
        }
        if (!empty($options['displaytype'])) {
            $displaytype = $options['displaytype'];
        }
        if (!empty($options['decimalpoints'])) {
            $decimalpoints = $options['decimalpoints'];
        }
        if (!empty($options['separator'])) {
            $separator = $options['separator'];
        }

        if (!$course = $DB->get_record('course', array('id'=>$id))) {
                    print_error('invalidcourseid');
        }

        $formdata = \grade_export::export_bulk_export_data($id, $itemids, $exportfeedback, $onlyactive, $displaytype,
                $decimalpoints, null, $separator);

        $export = new \grade_export_txt($course, $groupid, $formdata);
        $export->print_grades();

        // if verbose mode was requested, show some more information/debug messages
        if($this->verbose) {
            var_dump( $formdata );
        }
    }
}
