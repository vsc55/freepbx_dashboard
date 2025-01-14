<?php
/**
 * Nut class
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PSI_UPS
 * @author    Artem Volk <artvolk@mail.ru>
 * @author    Anders Häggström <hagge@users.sourceforge.net>
 * @copyright 2009 phpSysInfo
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   SVN: $Id: class.nut.inc.php 661 2012-08-27 11:26:39Z namiltd $
 * @link      http://phpsysinfo.sourceforge.net
 */
 /**
 * getting ups information from upsc program
 *
 * @category  PHP
 * @package   PSI_UPS
 * @author    Artem Volk <artvolk@mail.ru>
 * @author    Anders Häggström <hagge@users.sourceforge.net>
 * @copyright 2009 phpSysInfo
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   Release: 3.0
 * @link      http://phpsysinfo.sourceforge.net
 */
class Nut extends UPS
{
    /**
     * internal storage for all gathered data
     */
    private array $_output = [];

    /**
     * get all information from all configured ups and store output in internal array
     */
    public function __construct()
    {
        parent::__construct();
        CommonFunctions::executeProgram('upsc', '-l', $output);
        $ups_names = preg_split("/\n/", (string) $output, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($ups_names as $value) {
            CommonFunctions::executeProgram('upsc', $value, $temp);
            $this->_output[$value] = $temp;
        }
    }

    /**
     * check if a specific value is set in an array
     *
     * @param object $hash array in which a specific value should be found
     * @param object $key  key that is looked for in the array
     *
     * @return array
     */
    private function _checkIsSet($hash, $key)
    {
        return $hash[$key] ?? '';
    }

    /**
     * parse the input and store data in resultset for xml generation
     *
     * @return array
     */
    private function _info()
    {
        if (! empty($this->_output)) {
            foreach ($this->_output as $name=>$value) {
                $temp = preg_split("/\n/", (string) $value, -1, PREG_SPLIT_NO_EMPTY);
                $ups_data = [];
                foreach ($temp as $value) {
                    $line = preg_split('/: /', (string) $value, 2);
                    $ups_data[$line[0]] = isset($line[1]) ? trim($line[1]) : '';
                }
                $dev = new UPSDevice();
                //General
                $dev->setName($name);
                $dev->setModel($this->_checkIsSet($ups_data, 'ups.model'));
                $dev->setMode($this->_checkIsSet($ups_data, 'driver.name'));
                $dev->setStatus($this->_checkIsSet($ups_data, 'ups.status'));

                //Line
                $dev->setLineVoltage($this->_checkIsSet($ups_data, 'input.voltage'));
                $dev->setLoad($this->_checkIsSet($ups_data, 'ups.load'));

                //Battery
                $dev->setBatteryVoltage($this->_checkIsSet($ups_data, 'battery.voltage'));
                $dev->setBatterCharge($this->_checkIsSet($ups_data, 'battery.charge'));

                $this->upsinfo->setUpsDevices($dev);
            }
        }
    }

    /**
     * get the information
     *
     * @see PSI_Interface_UPS::build()
     *
     * @return Void
     */
    public function build()
    {
        $this->_info();
    }
}
