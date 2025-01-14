<?php
/**
 * PSStatus Plugin
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PSI_Plugin_PSStatus
 * @author    Michael Cramer <BigMichi1@users.sourceforge.net>
 * @copyright 2009 phpSysInfo
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   SVN: $Id: class.psstatus.inc.php 692 2012-09-08 17:12:08Z namiltd $
 * @link      http://phpsysinfo.sourceforge.net
 */
 /**
 * process Plugin, which displays the status of configured processes
 * a simple view which shows a process name and the status
 * status determined by calling the "pidof" command line utility, another way is to provide
 * a file with the output of the pidof utility, so there is no need to run a executeable by the
 * webserver, the format of the command is written down in the psstatus.config.php file, where also
 * the method of getting the information is configured
 * processes that should be checked are also defined in psstatus.config.php
 *
 * @category  PHP
 * @package   PSI_Plugin_PSStatus
 * @author    Michael Cramer <BigMichi1@users.sourceforge.net>
 * @copyright 2009 phpSysInfo
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   Release: 3.0
 * @link      http://phpsysinfo.sourceforge.net
 */
class PSStatus extends PSI_Plugin
{
    /**
     * variable, which holds the content of the command
     */
    private array $_filecontent = [];

    /**
     * variable, which holds the result before the xml is generated out of this array
     */
    private array $_result = [];

    /**
     * read the data into an internal array and also call the parent constructor
     *
     * @param String $enc target encoding
     */
    public function __construct($enc)
    {
        parent::__construct(self::class, $enc);
        switch (strtolower((string) PSI_PLUGIN_PSSTATUS_ACCESS)) {
        case 'command':
            if (PSI_OS == 'WINNT') {
                try {
                    $objLocator = new COM("WbemScripting.SWbemLocator");
                    $wmi = $objLocator->ConnectServer();
                    $process_wmi = $wmi->InstancesOf('Win32_Process');
                    foreach ($process_wmi as $process) {
                        $this->_filecontent[] = [trim((string) $process->Caption), trim((string) $process->ProcessId)];
                    }
                } catch (Exception) {
                }
            } else {
                if ( defined('PSI_PLUGIN_PSSTATUS_PROCESSES') && is_string(PSI_PLUGIN_PSSTATUS_PROCESSES) ) {
                    if (preg_match(ARRAY_EXP, PSI_PLUGIN_PSSTATUS_PROCESSES)) {
                        $processes = eval(PSI_PLUGIN_PSSTATUS_PROCESSES);
                    } else {
                        $processes = [PSI_PLUGIN_PSSTATUS_PROCESSES];
                    }
                    if ( defined('PSI_PLUGIN_PSSTATUS_USE_REGEX') && PSI_PLUGIN_PSSTATUS_USE_REGEX === true) {
                        foreach ($processes as $process) {
                            CommonFunctions::executeProgram("pgrep", "-n -x ".$process, $buffer, PSI_DEBUG);
                            if (strlen(trim((string) $buffer)) > 0) {
                                $this->_filecontent[] = [$process, trim((string) $buffer)];
                            }
                        }
                    } else {
                        foreach ($processes as $process) {
                            CommonFunctions::executeProgram("pidof", "-s ".$process, $buffer, PSI_DEBUG);
                            if (strlen(trim((string) $buffer)) > 0) {
                                $this->_filecontent[] = [$process, trim((string) $buffer)];
                            }
                        }
                    }
                }
            }
            break;
        case 'data':
            CommonFunctions::rfts(APP_ROOT."/data/psstatus.txt", $buffer);
            $processes = preg_split("/\n/", (string) $buffer, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($processes as $process) {
                $ps = preg_split("/[\s]?\|[\s]?/", (string) $process, -1, PREG_SPLIT_NO_EMPTY);
                if ((is_countable($ps) ? count($ps) : 0) == 2) {
                    $this->_filecontent[] = [trim($ps[0]), trim($ps[1])];
                }
            }
            break;
        default:
            $this->global_error->addError("switch(PSI_PLUGIN_PSSTATUS_ACCESS)", "Bad psstatus configuration in psstatus.config.php");
            break;
        }
    }

    /**
     * doing all tasks to get the required informations that the plugin needs
     * result is stored in an internal array<br>the array is build like a tree,
     * so that it is possible to get only a specific process with the childs
     *
     * @return void
     */
    public function execute()
    {
        if ( empty($this->_filecontent)) {
            return;
        }
        if ( defined('PSI_PLUGIN_PSSTATUS_PROCESSES') && is_string(PSI_PLUGIN_PSSTATUS_PROCESSES) ) {
            if (preg_match(ARRAY_EXP, PSI_PLUGIN_PSSTATUS_PROCESSES)) {
                $processes = eval(PSI_PLUGIN_PSSTATUS_PROCESSES);
            } else {
                $processes = [PSI_PLUGIN_PSSTATUS_PROCESSES];
            }
            foreach ($processes as $process) {
                if ($this->_recursiveinarray($process, $this->_filecontent)) {
                    $this->_result[] = [$process, true];
                } else {
                    $this->_result[] = [$process, false];
                }
            }
        }
    }

    /**
     * generates the XML content for the plugin
     *
     * @return SimpleXMLElement entire XML content for the plugin
     */
    public function xml()
    {
        foreach ($this->_result as $ps) {
            $xmlps = $this->xml->addChild("Process");
            $xmlps->addAttribute("Name", $ps[0]);
            $xmlps->addAttribute("Status", $ps[1] ? 1 : 0);
        }

        return $this->xml->getSimpleXmlElement();
    }

    /**
     * checks an array recursive if an value is in, extended version of in_array()
     *
     * @param mixed $needle   what to find
     * @param array $haystack where to find
     *
     * @return boolean true - found<br>false - not found
     */
    private function _recursiveinarray(mixed $needle, $haystack)
    {
        foreach ($haystack as $stalk) {
            if ($needle == $stalk || (is_array($stalk) && $this->_recursiveinarray($needle, $stalk))) {
                return true;
            }
        }

        return false;
    }
}
