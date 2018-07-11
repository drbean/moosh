<?php

/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle23\GradeCategory;
use Moosh\MooshCommand;

class GradeCategoryList extends MooshCommand {
    public function __construct() {
        parent::__construct('list', 'gradecategory');

        $this->addOption('i|id', 'display id column only');
        $this->addOption('c|coursesearch:', 'grade categories in given course id only');
        $this->addOption('h|hidden:', 'show all/only/not hidden', 'all');
        $this->addOption('e|empty:', 'show only empty grade categories: all/only/not empty', 'all');
        $this->addOption('f|fields:', 'show only those fields in the output (comma separated)');
        $this->addOption('o|output:', 'output format: tab, csv', 'csv');

        $this->addArgument('search');

        $this->minArguments = 0;
        $this->maxArguments = 255;
    }

    public function execute() {
        global $CFG, $DB;


        require_once $CFG->libdir . '/grade/grade_category.php';
        require_once $CFG->libdir . '/coursecatlib.php';

        foreach ($this->arguments as $argument) {
            $this->expandOptionsManually(array($argument));
        }

        $this->expandOptions();

        $options = $this->expandedOptions;

        $params = NULL;
        $sql = "SELECT c.id,c.courseid,";
        if ($options['empty'] == 'only' || $options['empty'] == 'not') {
            $sql .= "COUNT(c.id) AS items,";
        }
        $sql .= "c.parent,c.fullname,c.hidden,i.id AS itemid FROM {grade_categories} c ";

        if ($options['empty'] == 'only' || $options['empty'] == 'not') {
            $sql .= " LEFT OUTER JOIN {grade_items} i ON c.id=i.categoryid ";
        }

        $sql .= "WHERE '1'='1' ";
        if ($options['coursesearch'] ) {
            $category = \coursecat::get($options['coursesearch']);

            $categories = $this->get_categories($category);

            list($where, $params) = $DB->get_in_or_equal(array_keys($categories));

            $sql .= "AND c.category $where";
        }

        // Glue arguments together, so end user does not need to provide single argument.
        if (isset($this->arguments[0]) && $this->arguments[0]) {
            $customwhere = implode(' ', $this->arguments);
            $sql .= " AND ($customwhere)";
        }
        if ($options['empty'] == 'only') {
            $sql .= " GROUP BY c.id HAVING items < 2";
        }
        if ($options['empty'] == 'not') {
            $sql .= " GROUP BY c.id HAVING items > 1";
        }

        if($this->verbose) {
           cli_problem("SQL query run: $sql");
           cli_problem("Params:");
           cli_problem(var_export($params, true));
        }

        $gradecats = $DB->get_records_sql($sql, $params);

        // Filter out any that have any section information (summary)
        if ($options['empty'] == 'only') {
            $sql = "SELECT COUNT(*) AS C FROM {course_sections} WHERE course = ? AND summary <> ''";
            foreach ($gradecats as $k => $gradecat) {
                $sections = $DB->get_record_sql($sql, array($gradecat->id));
                if ($sections->c > 0) {
                    unset($gradecats[$k]);
                }
            }
        }

        // @TODO: If empty == no, then add those that have no modules but some modification to sections

        $this->display($gradecats);


    }

    private function find_item($category_id) {
        global $DB;

        if ($item = $DB->get_record('grade_items', array("categoryid" => $category_id))) {
            return true;
        }
        elseif ($children = $DB->get_record('grade_categories', array("parent" => $category_id))) {
            foreach ($children as $child) {
                return $this->find_item($child->id);
            }
        }
        else { return false; }
    }

    private function get_parent($id, $parentname = NULL) {
        global $DB;

        if ($parentcategory = $DB->get_record('grade_categories', array("id" => $id))) {
            if ($parentcategory->parent > 0) {
                $parentname .= $this->get_parent($parentcategory->parent, $parentname);
            } else {
                $parentname .= "Top";
            }
            $parentname .= "/" . $parentcategory->fullname;
        }
        return $parentname;

    }


    protected function get_categories(\coursecat $category) {
        static $categories = array();

        $categories[$category->id] = $category->name;

        foreach ($category->get_children() as $child) {
            $this->get_categories($child);
        }

        return $categories;
    }

    protected function display($gradecats) {

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
        foreach ($gradecats as $category) {
            $line = array();
            if ($options['hidden'] == 'only' && $category->hidden == 0) {
                continue;
            }
            if ($options['hidden'] == 'not' && $category->hidden != 0) {
                continue;
            }
            if ($options['id']) {
                echo $category->id . "\n";
                continue;
            }
            foreach ($category as $field => $value) {
                if ($fields && !isset($fields[$field])) {
                    continue;
                }
                if ($doheader == 0) {
                    $header[] = $field;
                    //$outputheader .= str_pad($field, 20);
                }
                if ($field == "parent" && $value > 0) {
                    $value = $this->get_parent($value);
                } elseif ($field == "parent") {
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
}
