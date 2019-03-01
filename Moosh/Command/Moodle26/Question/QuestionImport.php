<?php
/**
 * moosh - Moodle Shell
 *
 * @copyright  2012 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle26\Question;

use Moosh\MooshCommand;

class QuestionImport extends MooshCommand
{
    public function __construct()
    {
        parent::__construct('import', 'question');
        $this->addOption('r|random:', 'random number of (tagged) questions from the category', NULL);
        $this->addOption('t|tag:', 'tag on questions from the category', NULL);
        $this->addArgument('questions.xml');
        $this->addArgument('quiz_id');
        $this->addArgument('question_category_id');
    }

    public function execute()
    {
        global $DB,$CFG;

        require_once($CFG->dirroot . '/question/editlib.php');
        require_once($CFG->dirroot . '/question/import_form.php');
        require_once($CFG->dirroot . '/question/format.php');
        require_once($CFG->dirroot . '/lib/questionlib.php');

        $options = $this->expandedOptions;
        $arguments = $this->arguments;
        $this->checkFileArg($arguments[0]);

        $file = $arguments[0];
        $quiz = $arguments[1];
        $category_id = $arguments[2];
        $quiz = $DB->get_record('quiz', array('id'=>$quiz),'*',MUST_EXIST);
        $course = $DB->get_record('course', array('id'=>$quiz->course),'*',MUST_EXIST);
        $coursecontext = \context_course::instance($course->id);
        $coursemodule = get_coursemodule_from_instance('quiz',$quiz->id);
        $quizcontext = \context_module::instance($coursemodule->id,MUST_EXIST);
        $contexts = new \question_edit_contexts($quizcontext);

        // Use existing questions category for quiz
        if (!$category = $DB->get_record('question_categories',array('id'=>$category_id))) {
            print_error("no question category with $category_id id", '');
        }

        $formatfile = $CFG->dirroot .  '/question/format/xml/format.php';
        if (!is_readable($formatfile)) {
            throw new moodle_exception('formatnotfound', 'question', '', 'xml');
        }

        require_once($formatfile);

        $qformat = new \qformat_xml();

        // load data into class
        $qformat->setCategory($category);
        $qformat->setContexts(array($quizcontext));
        $qformat->setCourse($course);
        $qformat->setFilename($file);
        $qformat->setRealfilename($file);
        $qformat->setMatchgrades('nearest');
        $qformat->setStoponerror(true);
        // Do anything before that we need to
        if (!$qformat->importpreprocess()) {
            print_error('cannotimport', '');
        }

        // Process the uploaded file
        if (!$qformat->importprocess($category)) {
            print_error('cannotimport', '');
        }

        // In case anything needs to be done after
        if (!$qformat->importpostprocess()) {
            print_error('cannotimport', '');
        }
        $addonpage = 1;
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        if (!empty($options['random'])) {
            if (empty($options['tag'])) {
                print_error("No tag for choosing {$options['random']} random questions", '');
            }

            echo "tags = {$options['tag']}\n";
            $tag = \core_tag_tag::get_by_name(1, $options['tag'], "id", MUST_EXIST);
            if (empty($tag)){
                print_error("No '$tag' tagid for '{$options['tag']}' tag\n", '');
            }
            quiz_add_random_questions($quiz, $addonpage, $category_id, $options['random'], true, $tag->id);
            echo "tagid = " . $tag->id . "\n";
        }
        else {
            foreach ($qformat->questionids as $addquestion) {
                quiz_require_question_use($addquestion);
                quiz_add_quiz_question($addquestion, $quiz, $addonpage);
                echo "no tagid = {$options['random']}\n";
            }
        }
        quiz_delete_previews($quiz);
        quiz_update_sumgrades($quiz);
    }

    public function importQuiz($courseid, $quizid)
    {
        global $CFG;

        $course = get_record('course', 'id', $courseid);
        $quiz = get_record('quiz', 'id', $quizid);
        $fileformat = 'xml'; //Moodle XML format
        $questioncat = null;
    }
}
