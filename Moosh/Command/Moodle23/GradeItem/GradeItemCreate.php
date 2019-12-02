<?php

/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle23\GradeItem;
use Moosh\MooshCommand;

use GetOptionKit\Argument;

class GradeItemCreate extends MooshCommand {
    public function __construct() {

        parent::__construct('create', 'gradeitem');

        $this->addOption('t|itemtype:', 'mod/manual/"" etc', 'manual');
        $this->addOption('n|itemname:', 'item name', 'Grade');
        $this->addOption('m|grademax:', 'maximum grade', '100');
        $this->addOption('g|gradetype:', 'grade type (0 = none, 1 = value, 2 = scale, 3 = text)', '1');
        $this->addOption('c|calculation:', 'grade calculation from other items', null);
        $this->addOption('o|options:', 'any other options that should be passed for grade item creation', null);

        $this->addArgument('courseid');
        $this->addArgument('categoryid');

        $this->minArguments = 2;
    }

    public function execute() {
        global $CFG, $DB;

        require_once($CFG->libdir . '/grade/grade_item.php');
        require_once($CFG->libdir . '/gradelib.php');


        $itemdata = new \stdClass();
        $itemdata->courseid = $this->arguments[0];
        $itemdata->categoryid = $this->arguments[1];
        $itemdata->itemtype = 'manual';

        $options = $this->expandedOptions;

        foreach ($options as $k => $v) {
            if ($k == 'options' && !empty($v)) {
                $item_options = preg_split( '/\s+(?=--)/', $v);
                foreach ( $item_options as $option ) {
                    $arg = new Argument( $option );
                    $name = $this->getOptionName($arg);
                    $value = $arg->getOptionValue();
                    $itemdata->$name = $value;
                    if ($this->verbose) {
                        echo "\"$option\" -> $name=" . $value . "\n";
                    }
                }
            }
            else { $itemdata->$k = $v; }
        }

        // $params = NULL;

        $grade_item = new \grade_item($itemdata, false);
        echo print_r($itemdata) . "\n";
        //$fetched_item = $grade_item->fetch($params);
        //echo print_r($fetched_item) . "\n";
        echo false ."\n";
        echo true ."\n";
        $source = 'manual';
        $grade_item->insert($source);

        echo $grade_item->id . "\n";

        //$sql = "SELECT * ";
        //$sql .= "FROM {grade_items} i ";
        //$sql .= "WHERE '1'='1' ";

        //// Glue arguments together, so end user does not need to provide single argument.
        //if (isset($this->arguments[0]) && $this->arguments[0]) {
        //    $customwhere = implode(' ', $this->arguments);
        //    $sql .= " AND ($customwhere)";
        //}

        //if($this->verbose) {
        //   cli_problem("SQL query run: $sql");
        //   cli_problem("Params:");
        //   cli_problem(var_export($params, true));
        //}

        //$gradeitems = $DB->get_records_sql($sql, $params);

        //$this->display($gradeitems);

    }

    private function has_grade($item_id) {
        global $DB;
        if ($records = $DB->get_records('grade_grades', array("itemid" => $item_id))) {
            foreach ($records as $record) {
                if (isset( $record->rawgrade ) ) {
                    return true;
                }
            }
        }
        else { return false; }
    }

    private function get_category_path($id, $parentname = NULL) {
        global $DB;

        if ($parentcategory = $DB->get_record('grade_categories', array("id" => $id))) {
            if ($parentcategory->parent > 0) {
                $parentname .= $this->get_category_path($parentcategory->parent, $parentname);
            } else {
                $parentname .= "Top";
            }
            $parentname .= "/" . $parentcategory->fullname;
        }
        return $parentname;

    }

    protected function display($gradeitems, $json = false, $humanreadable = true) {

        $options = $this->expandedOptions;
        $fields = NULL;
        if ($options['fields']) {
            $fields = str_getcsv($options["fields"]);
            $fields = array_combine($fields, $fields);
        }

        $outputheader = $outputcontent = "";
        $doheader = 0;
        $header = array();
        $output = array();
        foreach ($gradeitems as $item) {
            $line = array();
            if ($options['hidden'] == 'yes' && $item->hidden == 0) {
                continue;
            }
            if ($options['hidden'] == 'no' && $item->hidden != 0) {
                continue;
            }
            if ($options['locked'] == 'yes' && $item->locked == 0) {
                continue;
            }
            if ($options['locked'] == 'no' && $item->locked != 0) {
                continue;
            }
            $id = $item->id;
            if ($options['empty'] == 'yes' && $this->has_grade($id) == true) {
                continue;
            }
            if ($options['empty'] == 'no' && $this->has_grade($id) == false) {
                continue;
            }
            if ($options['id']) {
                echo $id . "\n";
                continue;
            }
            foreach ($item as $field => $value) {
                if ($fields && !isset($fields[$field])) {
                    continue;
                }
                if ($doheader == 0) {
                    $header[] = $field;
                    //$outputheader .= str_pad($field, 20);
                }
                if ($field == "categoryid" && $value > 0) {
                    $value = $this->get_category_path($value);
                } elseif ($field == "categoryid") {
                    $value = "Top";
                }
                $line[] = $value;
                //$outputcontent .= str_pad($value, 20);
            }
            $output[] = $line;
            //$outputcontent .= "\n";
            $doheader++;
        }
        if (!$options['id']) {
            array_unshift($output, $header);
            //$outputheader .= "\n";
            //echo $outputheader;
        }
        //echo $outputcontent;
        foreach ($output as $line) {
            if ($options['output'] == 'csv') {

                foreach ($line as $k => $l) {
                    $line[$k] = "\"$l\"";
                }
                echo implode(',', $line) . "\n";

            } elseif ($options['output'] == 'tab') {
                echo implode("\t", $line) . "\n";
            }
        }
    }

    private function getOptionName($arg)
    {
        if (preg_match('/^[-]+([_a-zA-Z0-9-]+)/', $arg->arg, $regs)) {
            return $regs[1];
        }
    }
}
