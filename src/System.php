<?php
/**
 * SugarCRM Tools
 *
 * PHP Version 5.3 -> 5.6
 * SugarCRM Versions 6.5 - 7.6
 *
 * @author Emmanuel Dyan
 * @copyright 2005-2015 iNet Process
 *
 * @package inetprocess/sugarcrm
 *
 * @license Apache License 2.0
 *
 * @link http://www.inetprocess.com
 */

namespace Inet\SugarCRM;

use Inet\SugarCRM\Bean;
use Inet\SugarCRM\Exception\BeanNotFoundException;

/**
 * SugarCRM Logic Hooks Management
 *
 */
class System
{
    /**
     * Prefix that should be set by each class to identify it in logs
     *
     * @var string
     */
    protected $logPrefix;

    /**
     * SugarCRM EntryPoint
     */
    protected $entryPoint;

    /**
     * Messages sent by Sugar as an output
     * @var    array
     */
    protected $messages = array();

    /**
     * Set the LogPrefix to be unique and ask for an Entry Point to SugarCRM
     *
     * @param EntryPoint $entryPoint Enters the SugarCRM Folder
     */
    public function __construct(EntryPoint $entryPoint)
    {
        $this->logPrefix = __CLASS__ . ': ';
        $this->entryPoint = $entryPoint;
    }

    public function getEntryPoint()
    {
        return $this->entryPoint;
    }

    public function getLogger()
    {
        return $this->getEntryPoint()->getLogger();
    }

    /**
     * Add a message to the array
     * @param    string    $message
     */
    public function addMessage($message)
    {
        $this->messages[] = $message;
    }

    /**
     * Taken from fayebsg/sugarcrm-cli
     * Repair and rebuild sugarcrm
     * @param     boolean    $executeSql    Launch the SQL queries
     * @return    array                     Messages
     */
    public function repair($executeSql = false)
    {
        // Config ang language
        $sugarConfig = $this->getEntryPoint()->getApplication()->getSugarConfig();
        $currentLanguage = $sugarConfig['default_language'];

        // check that I can repair (old sugar)
        $qrrFile = 'modules/Administration/QuickRepairAndRebuild.php';
        if (!file_exists($qrrFile)) {
            throw new \RuntimeException("Can't load the QuickRepairAndRebuild class from SugarCRM.");
        }
        require_once($qrrFile);

        $self = $this;
        ob_start(function ($message) use ($self) {
            $message = preg_replace('#<script.*</script>#i', '', $message);
            $message = preg_replace('#<(br\s*/?|/h3)>#i', PHP_EOL, $message);
            $message = trim(strip_tags($message));
            $message = preg_replace('#'.PHP_EOL.'{2,}#', PHP_EOL, $message);
            $self->addMessage(trim($message));
            return '';
        });

        // Repair and catch the output
        require_once('include/utils/layout_utils.php');
        $GLOBALS['mod_strings'] = return_module_language($currentLanguage, 'Administration');
        $repair = new \RepairAndClear();
        $repair->repairAndClearAll(array('clearAll'), array(translate('LBL_ALL_MODULES')), $executeSql, true, '');
        ob_end_flush();

        //remove the js language files
        if (!method_exists('LanguageManager', 'removeJSLanguageFiles')) {
            $this->getLogger()->warning('No removeJSLanguageFiles method (sugar too old?). Check that it\'s clean.');
        } else {
            \LanguageManager::removeJSLanguageFiles();
        }

        $this->tearDown();

        return $this->messages;
    }

    /**
     * Disable trackers in SugarCRM
     */
    public function disableActivity()
    {
        \Activity::disable();
    }

    /**
     * Is activity Enabled ?
     * @return    boolean
     */
    public function isActivityEnabled()
    {
        return \Activity::isEnabled();
    }

    /**
     * Taken from fayebsg/sugarcrm-cli
     * Useful to clean Sugar before leaving it
     */
    protected function tearDown()
    {
        sugar_cleanup(false);
        if (class_exists('DBManagerFactory')) {
            $db = \DBManagerFactory::getInstance();
            $db->disconnect();
        }
    }
}
