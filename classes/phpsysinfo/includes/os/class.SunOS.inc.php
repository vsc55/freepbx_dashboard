<?php
/**
 * SunOS System Class
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PSI SunOS OS class
 * @author    Michael Cramer <BigMichi1@users.sourceforge.net>
 * @copyright 2009 phpSysInfo
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   SVN: $Id: class.SunOS.inc.php 687 2012-09-06 20:54:49Z namiltd $
 * @link      http://phpsysinfo.sourceforge.net
 */
 /**
 * SunOS sysinfo class
 * get all the required information from SunOS systems
 *
 * @category  PHP
 * @package   PSI SunOS OS class
 * @author    Michael Cramer <BigMichi1@users.sourceforge.net>
 * @copyright 2009 phpSysInfo
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @version   Release: 3.0
 * @link      http://phpsysinfo.sourceforge.net
 */
class SunOS extends OS
{
    /**
     * Extract kernel values via kstat() interface
     *
     * @param string $key key for kstat programm
     *
     * @return string
     */
    private function _kstat($key)
    {
        if (CommonFunctions::executeProgram('kstat', '-p d '.$key, $m, PSI_DEBUG) &&
         !is_null($m) && (trim((string) $m)!=="")) {
            [$key, $value] = preg_split("/\t/", trim((string) $m), 2);

            return $value;
        } else {
            return '';
        }
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
     * Kernel Version
     *
     * @return void
     */
    private function _kernel()
    {
        if (CommonFunctions::executeProgram('uname', '-s', $os, PSI_DEBUG)) {
            if (CommonFunctions::executeProgram('uname', '-r', $version, PSI_DEBUG)) {
                $this->sys->setKernel($os.' '.$version);
            } else {
                $this->sys->setKernel($os);
            }
        }
    }

    /**
     * UpTime
     * time the system is running
     *
     * @return void
     */
    private function _uptime()
    {
        $this->sys->setUptime(time() - $this->_kstat('unix:0:system_misc:boot_time'));
    }

    /**
     * Number of Users
     *
     * @return void
     */
    private function _users()
    {
        if (CommonFunctions::executeProgram('who', '-q', $buf, PSI_DEBUG)) {
            $who = preg_split('/=/', (string) $buf);
            $this->sys->setUsers($who[1]);
        }
    }

    /**
     * Processor Load
     * optionally create a loadbar
     *
     * @return void
     */
    private function _loadavg()
    {
        $load1 = $this->_kstat('unix:0:system_misc:avenrun_1min');
        $load5 = $this->_kstat('unix:0:system_misc:avenrun_5min');
        $load15 = $this->_kstat('unix:0:system_misc:avenrun_15min');
        $this->sys->setLoad(round($load1 / 256, 2).' '.round($load5 / 256, 2).' '.round($load15 / 256, 2));
    }

    /**
     * CPU information
     *
     * @return void
     */
    private function _cpuinfo()
    {
        $dev = new CpuDevice();
        if (CommonFunctions::executeProgram('uname', '-i', $buf, PSI_DEBUG)) {
            $dev->setModel(trim((string) $buf));
        }
        $dev->setCpuSpeed($this->_kstat('cpu_info:0:cpu_info0:clock_MHz'));
        $dev->setCache($this->_kstat('cpu_info:0:cpu_info0:cpu_type') * 1024);
        $this->sys->setCpus($dev);
    }

    /**
     * Network devices
     *
     * @return void
     */
    private function _network()
    {
        if (CommonFunctions::executeProgram('netstat', '-ni | awk \'(NF ==10){print;}\'', $netstat, PSI_DEBUG)) {
            $lines = preg_split("/\n/", (string) $netstat, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($lines as $line) {
                $ar_buf = preg_split("/\s+/", (string) $line);
                if (!empty($ar_buf[0]) && $ar_buf[0] !== 'Name') {
                    $dev = new NetDevice();
                    $dev->setName($ar_buf[0]);
                    $results[$ar_buf[0]]['errs'] = $ar_buf[5] + $ar_buf[7];
                    if (preg_match('/^(\D+)(\d+)$/', $ar_buf[0], $intf)) {
                        $prefix = $intf[1].':'.$intf[2].':'.$intf[1].$intf[2].':';
                    } elseif (preg_match('/^(\D.*)(\d+)$/', $ar_buf[0], $intf)) {
                        $prefix = $intf[1].':'.$intf[2].':mac:';
                    } else {
                        $prefix = "";
                    }
                    if ($prefix !== "") {
                        $cnt = $this->_kstat($prefix.'drop');
                        if ($cnt > 0) {
                            $dev->setDrops($cnt);
                        }
                        $cnt = $this->_kstat($prefix.'obytes64');
                        if ($cnt > 0) {
                            $dev->setTxBytes($cnt);
                        }
                        $cnt = $this->_kstat($prefix.'rbytes64');
                        if ($cnt > 0) {
                            $dev->setRxBytes($cnt);
                        }
                    }
                    if (defined('PSI_SHOW_NETWORK_INFOS') && (PSI_SHOW_NETWORK_INFOS)) {
                        if (CommonFunctions::executeProgram('ifconfig', $ar_buf[0], $bufr2, PSI_DEBUG)
                           && !is_null($bufr2) && (trim((string) $bufr2) !== "")) {
                            $bufe2 = preg_split("/\n/", (string) $bufr2, -1, PREG_SPLIT_NO_EMPTY);
                            foreach ($bufe2 as $buf2) {
                                if (preg_match('/^\s+ether\s+(\S+)/i', (string) $buf2, $ar_buf2))
                                    $dev->setInfo(($dev->getInfo()?$dev->getInfo().';':'').preg_replace('/:/', '-', $ar_buf2[1]));
                                elseif (preg_match('/^\s+inet\s+(\S+)\s+netmask/i', (string) $buf2, $ar_buf2))
                                    $dev->setInfo(($dev->getInfo()?$dev->getInfo().';':'').$ar_buf2[1]);
                            }
                        }
                        if (CommonFunctions::executeProgram('ifconfig', $ar_buf[0].' inet6', $bufr2, PSI_DEBUG)
                           && !is_null($bufr2) && (trim((string) $bufr2) !== "")) {
                            $bufe2 = preg_split("/\n/", (string) $bufr2, -1, PREG_SPLIT_NO_EMPTY);
                            foreach ($bufe2 as $buf2) {
                                if (preg_match('/^\s+inet6\s+([^\s\/]+)/i', (string) $buf2, $ar_buf2)
                                      && !preg_match('/^fe80::/i',$ar_buf2[1]))
                                    $dev->setInfo(($dev->getInfo()?$dev->getInfo().';':'').$ar_buf2[1]);
                            }
                        }
                    }

                    $this->sys->setNetDevices($dev);
                }
            }
        }
    }

    /**
     * Physical memory information and Swap Space information
     *
     * @return void
     */
    private function _memory()
    {
        $pagesize = $this->_kstat('unix:0:seg_cache:slab_size');
        $this->sys->setMemTotal($this->_kstat('unix:0:system_pages:pagestotal') * $pagesize);
        $this->sys->setMemUsed($this->_kstat('unix:0:system_pages:pageslocked') * $pagesize);
        $this->sys->setMemFree($this->_kstat('unix:0:system_pages:pagesfree') * $pagesize);
        $dev = new DiskDevice();
        $dev->setName('SWAP');
        $dev->setFsType('swap');
        $dev->setMountPoint('SWAP');
        $dev->setTotal($this->_kstat('unix:0:vminfo:swap_avail') / 1024);
        $dev->setUsed($this->_kstat('unix:0:vminfo:swap_alloc') / 1024);
        $dev->setFree($this->_kstat('unix:0:vminfo:swap_free') / 1024);
        $this->sys->setSwapDevices($dev);
    }

    /**
     * filesystem information
     *
     * @return void
     */
    private function _filesystems()
    {
        if (CommonFunctions::executeProgram('df', '-k', $df, PSI_DEBUG)) {
            $df = preg_replace('/\n\s/m',' ', (string) $df);
            $mounts = preg_split("/\n/", $df, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($mounts as $mount) {
                $ar_buf = preg_split('/\s+/', (string) $mount, 6);
                if (!empty($ar_buf[0]) && $ar_buf[0] !== 'Filesystem') {
                    $dev = new DiskDevice();
                    $dev->setName($ar_buf[0]);
                    $dev->setTotal($ar_buf[1] * 1024);
                    $dev->setUsed($ar_buf[2] * 1024);
                    $dev->setFree($ar_buf[3] * 1024);
                    $dev->setMountPoint($ar_buf[5]);
                    if (CommonFunctions::executeProgram('df', '-n', $dftypes, PSI_DEBUG)) {
                        $mounttypes = preg_split("/\n/", (string) $dftypes, -1, PREG_SPLIT_NO_EMPTY);
                        foreach ($mounttypes as $type) {
                            $ty_buf = preg_split('/:/', (string) $type, 2);
                            if (trim($ty_buf[0]) == $dev->getMountPoint()) {
                                $dev->setFsType($ty_buf[1]);
                                break;
                            }
                        }
                    } elseif (CommonFunctions::executeProgram('df', '-T', $dftypes, PSI_DEBUG)) {
                        $dftypes = preg_replace('/\n\s/m',' ', (string) $dftypes);
                        $mounttypes = preg_split("/\n/", $dftypes, -1, PREG_SPLIT_NO_EMPTY);
                        foreach ($mounttypes as $type) {
                            $ty_buf = preg_split("/\s+/", (string) $type, 3);
                            if ($ty_buf[0] == $dev->getName()) {
                                $dev->setFsType($ty_buf[1]);
                                break;
                            }
                        }
                    }
                    $this->sys->setDiskDevices($dev);
                }
            }
        }
    }

    /**
     * Distribution Icon
     *
     * @return void
     */
    private function _distro()
    {
        $this->sys->setDistribution('SunOS');
        $this->sys->setDistributionIcon('SunOS.png');
    }

    /**
     * get the information
     *
     * @see PSI_Interface_OS::build()
     *
     * @return Void
     */
    public function build()
    {
        $this->error->addError("WARN", "The SunOS version of phpSysInfo is a work in progress, some things currently don't work");
        $this->_hostname();
        $this->_ip();
        $this->_distro();
        $this->_kernel();
        $this->_uptime();
        $this->_users();
        $this->_loadavg();
        $this->_cpuinfo();
        $this->_network();
        $this->_memory();
        $this->_filesystems();
    }
}
