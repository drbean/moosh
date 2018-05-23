<?php
/**
 * moosh - Moodle Shell
 *
 * @copyright  2016 onwards Tomasz Muras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moosh\Command\Moodle23\Assign;
use Moosh\MooshCommand;

class AssignDupe extends MooshCommand
{
    public function __construct()
    {
        parent::__construct('dupe', 'assign');

        //$this->addArgument('name');

        $this->addOption('d|dupe:', 'option with no value', '');
        $this->addOption('a|assign:', 'assignment id', '');
        $this->addOption('f|format:', 'submission format', 'onlinetext');

    }

    public function execute()
    {
	global $DB, $CFG;
        // Some variables you may want to use
        //  $this->cwd - the directory where moosh command was executed
        //  $this->mooshDir - moosh installation directory
        //  $this->expandedOptions - commandline provided options, merged with defaults
        //  $this->topDir - top Moodle directory
        //  $this->arguments[0] - first argument passed
        //  $this->pluginInfo - array with information about the current plugin (based on cwd), keys:'type','name','dir'
        //  $this->verbose - if set to true, then "moosh -v" was run - add more verbose / debug information

        $options = $this->expandedOptions;
	$assign = $options[ "assign" ];
	$text = $options[ "dupe" ];
	$format = $options[ "format" ];

	$sql = "SELECT mdl_user.firstname, mdl_user.lastname, mdl_assign_submission.timecreated, mdl_assign_submission.timemodified, mdl_assignsubmission_" . $format . "." . $format . "
			FROM mdl_assignsubmission_" . $format . "
				LEFT JOIN mdl_assign_submission ON
					mdl_assignsubmission_" . $format . ".submission = mdl_assign_submission.id
				LEFT JOIN mdl_user ON
					mdl_user.id = mdl_assign_submission.userid
			WHERE mdl_assignsubmission_" . $format . ".assignment=? AND
				mdl_assignsubmission_" . $format . "." . $format . " SIMILAR TO '%" . $text . "%'" ;

	$submits = $DB->get_records_sql( $sql, array( $assign ) );
	foreach ( $submits as $submit ) {
		$cleaned = strip_tags( $submit->$format );
		echo $submit->firstname . "\t" . $submit->lastname . " " . $submit->timecreated . " " . $submit->timemodified . " " . "$cleaned\n\n";
	}


        /* if verbose mode was requested, show some more information/debug messages
        if($this->verbose) {
            echo "Say what you're doing now";
        }
        */
    }
}
