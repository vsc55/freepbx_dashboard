<?php
/**
 * QNX System Class
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PSI QNX OS class
 * @author    Mieczyslaw Nalewaj <namiltd@users.sourceforge.net>
 * @copyright 2012 phpSysInfo
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   SVN: $Id: class.QNX.inc.php 687 2012-09-06 20:54:49Z namiltd $
 * @link      http://phpsysinfo.sourceforge.net
 */
 /**
 * QNX sysinfo class
 * get all the required information from QNX system
 *
 * @category  PHP
 * @package   PSI QNX OS class
 * @author    Mieczyslaw Nalewaj <namiltd@users.sourceforge.net>
 * @copyright 2012 phpSysInfo
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   Release: 3.0
 * @link      http://phpsysinfo.sourceforge.net
 */
class QNX extends OS
{
    /**
     * content of the syslog
     */
    private array $_dmesg = [];

    /**
     * call parent constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * get the cpu information
     *
     * @return array
     */
    protected function _cpuinfo()
    {
        if (CommonFunctions::executeProgram('pidin', 'info', $buf)
           && preg_match('/^Processor\d+: (.*)/m' ,(string) $buf)) {
            $lines = preg_split("/\n/", (string) $buf, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($lines as $line) {
                if (preg_match('/^Processor\d+: (.+)/' ,(string) $line, $proc)) {
                    $dev = new CpuDevice();
                    $dev->SetModel(trim($proc[1]));
                    if (preg_match('/(\d+)MHz/' ,$proc[1], $mhz)) {
                        $dev->setCpuSpeed($mhz[1]);
                    }
                    $this->sys->setCpus($dev);
                }
            }
        }
    }

    /**
     * QNX Version
     *
     * @return void
     */
    private function _kernel()
    {
        if (CommonFunctions::executeProgram('uname', '-rvm', $ret)) {
            $this->sys->setKernel($ret);
        }
    }

    /**
     * Distribution
     *
     * @return void
     */
    protected function _distro()
    {
        if (CommonFunctions::executeProgram('uname', '-sr', $ret))
            $this->sys->setDistribution($ret);
        else
            $this->sys->setDistribution('QNX');

        $this->sys->setDistributionIcon('QNX.png');
    }

    /**
     * UpTime
     * time the system is running
     *
     * @return void
     */
    private function _uptime()
    {

        if (CommonFunctions::executeProgram('pidin', 'info', $buf)
           && preg_match('/^.* BootTime:(.*)/' ,(string) $buf, $bstart)
           && CommonFunctions::executeProgram('date', '', $bstop)) {
            /* default error handler */
            if (function_exists('errorHandlerPsi')) {
                restore_error_handler();
            }
            /* fatal errors only */
            $old_err_rep = error_reporting();
            error_reporting(E_ERROR);

            $uptime = strtotime((string) $bstop)-strtotime($bstart[1]);
            if ($uptime > 0) $this->sys->setUptime($uptime);

            /* restore error level */
            error_reporting($old_err_rep);
            /* restore error handler */
            if (function_exists('errorHandlerPsi')) {
                set_error_handler('errorHandlerPsi');
            }
        }
    }

    /**
     * Number of Users
     *
     * @return void
     */
    private function _users()
    {
        $this->sys->setUsers(1);
    }

    /**
     * Virtual Host Name
     *
     * @return void
     */
    private function _hostname()
    {
        if (PSI_USE_VHOST === true) {
            $this->sys->setHostname(getenv('SERVER_NAME'));
        } else {
            if (CommonFunctions::executeProgram('uname', '-n', $result, PSI_DEBUG)) {
                $ip = gethostbyname($result);
                if ($ip != $result) {
                    $this->sys->setHostname(gethostbyaddr($ip));
                }
            }
        }
    }

    /**
     * IP of the Virtual Host Name
     *
     *  @return void
     */
    private function _ip()
    {
        if (PSI_USE_VHOST === true) {
            $this->sys->setIp(gethostbyname($this->sys->getHostname()));
        } else {
            if (!($result = getenv('SERVER_ADDR'))) {
                $this->sys->setIp(gethostbyname($this->sys->getHostname()));
            } else {
                $this->sys->setIp($result);
            }
        }
    }

    /**
     *  Physical memory information and Swap Space information
     *
     *  @return void
     */
    private function _memory()
    {
        if (CommonFunctions::executeProgram('pidin', 'info', $buf)
           && preg_match('/^.* FreeMem:(\S+)Mb\/(\S+)Mb/' ,(string) $buf, $memm)) {
            $this->sys->setMemTotal(1024*1024*$memm[2]);
            $this->sys->setMemFree(1024*1024*$memm[1]);
            $this->sys->setMemUsed(1024*1024*($memm[2]-$memm[1]));
        }
    }

    /**
     * filesystem information
     *
     * @return void
     */
    private function _filesystems()
    {
        $arrResult = Parser::df("-P 2>/dev/null");
        foreach ($arrResult as $dev) {
            $this->sys->setDiskDevices($dev);
        }
    }

    /**
     * network information
     *
     * @return void
     */
    private function _network()
    {
        $dev = null;
        if (CommonFunctions::executeProgram('ifconfig', '', $bufr, PSI_DEBUG)) {
            $lines = preg_split("/\n/", (string) $bufr, -1, PREG_SPLIT_NO_EMPTY);
            $notwas = true;
            foreach ($lines as $line) {
                if (preg_match("/^([^\s:]+)/", (string) $line, $ar_buf)) {
                    if (!$notwas) {
                        $this->sys->setNetDevices($dev);
                    }
                    $dev = new NetDevice();
                    $dev->setName($ar_buf[1]);
                    $notwas = false;
                } else {
                    if (!$notwas) {
                        if (defined('PSI_SHOW_NETWORK_INFOS') && (PSI_SHOW_NETWORK_INFOS)) {
                            if (preg_match('/^\s+address:\s*(\S+)/i', (string) $line, $ar_buf2)) {
                                    $dev->setInfo(($dev->getInfo()?$dev->getInfo().';':'').$ar_buf2[1]);
                            } elseif (preg_match('/^\s+inet\s+(\S+)\s+netmask/i', (string) $line, $ar_buf2))
                                $dev->setInfo(($dev->getInfo()?$dev->getInfo().';':'').$ar_buf2[1]);

                        }
                    }
                }
            }
            if (!$notwas) {
                $this->sys->setNetDevices($dev);
            }
        }
    }

    /**
     * get the information
     *
     * @return Void
     */
    public function build()
    {
        $this->error->addError("WARN", "The QNX version of phpSysInfo is a work in progress, some things currently don't work");
        $this->_hostname();
        $this->_ip();
        $this->_distro();
        $this->_kernel();
        $this->_uptime();
        $this->_users();
        $this->_cpuinfo();
        $this->_memory();
        $this->_filesystems();
        $this->_network();
    }
}
