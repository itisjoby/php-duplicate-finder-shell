<?php

set_time_limit(0);
ini_set('memory_limit', '1000M');
require 'vendor\autoload.php';

$colors = new Wujunze\Colors();

$handle = fopen("php://stdin", "r");

echo $colors->getColoredString("Please enter what you want to look \n", "cyan");
echo $colors->getColoredString("1.)Duplicate finder\n", "white");
echo $colors->getColoredString("2.)System Info\n", "white");

$line = fgets($handle);
if (trim($line) == '1') {

    $obj = new DuplicateFinder($colors);
    $obj->index();
} else if (trim($line) == '2') {
    $obj = new SystemInfo($colors);
    $obj->index();
} else {
    echo $colors->getColoredString("ABORTED!\n", "red");
    exit;
}

class DuplicateFinder {

    public $path;
    public $colors;

    public function __construct($colors) {

        $this->path   = "D://music/";
        $this->colors = $colors;
    }

    function index() {

        $colors      = $this->colors;
        $destination = $this->path . "destination";
        if (!is_dir($destination)) {

            mkdir($destination);
            echo $colors->getColoredString("created a directory on path " . $destination . "\n", "green");
        } else {
            echo $colors->getColoredString("directory already exist at " . $destination . "n", "light_red");
        }

        $files = scandir($this->path);
        $arr   = [];
        foreach ($files as $k => $file) {
            if ($file == '.' || $file == '..' || $file == 'destination') {
                continue;
            }
            $temp         = [];
            $temp['file'] = $file;
            $temp['size'] = filesize($this->path . $file);
            $arr[]        = $temp;
        }
        echo $colors->getColoredString("\nfinding duplicates begins\n", "green");

        $counter       = 0;
        $removed_index = [];
        $skiped        = 0;
        foreach ($arr as $kk => $vv) {
            if (in_array($kk, $removed_index)) {
                continue;
            }

            $has_duplicate = false;
            foreach ($arr as $k => $v) {
                if ($k < $kk) {
                    continue;
                }
                $file_1_duration = $this->scanID3($this->path . $vv['file']);
                $file_2_duration = $this->scanID3($this->path . $v['file']);
                if ($k != $kk && (($v['size'] == $vv['size']) || $file_1_duration == $file_2_duration)) {
                    $has_duplicate = true;
                    echo $colors->getColoredString("\n" . $counter . "*********************************************************\n", "magenta");
                    echo $colors->getColoredString("\nPossible duplicate founded\n", "light_red");
                    echo $colors->getColoredString("\nMatching File 1\n", "yellow");
                    echo $colors->getColoredString("Name=>" . $vv['file'] . "\nDuration=>" . $file_1_duration . "\nSize=>" . filesize($this->path . $vv['file']) . "Bytes\n", "white");
                    echo $colors->getColoredString("\nMatching File 2\n", "yellow");
                    echo $colors->getColoredString("Name=>" . $v['file'] . "\nDuration=>" . $file_2_duration . "\nSize=>" . filesize($this->path . $v['file']) . "Bytes\n", "white");

                    echo $colors->getColoredString("Moving to " . $destination . "\n", "green");
                    rename($this->path . $v['file'], $destination . "/" . $v['file']);

                    $counter++;
                    $removed_index[] = $k;
//                    if ($counter > 350) {
//                        break 2;
//                    }
                } else {
                    $skiped++;
                    echo $skiped . ")skipped " . $vv['file'] . "[" . $file_1_duration . "]\t<= does not matched =>\t" . $v['file'] . "[" . $file_2_duration . "]\n";
                    echo $colors->getColoredString("\n*********************************************************\n", "magenta");
                }
            }
            if ($has_duplicate) {
                rename($this->path . $vv['file'], $destination . "/" . $vv['file']);
            }
        }
        if ($counter > 0) {
            echo $colors->getColoredString("\nprocessed " . $counter . " records\n", "green");
        } else {
            echo $colors->getColoredString("\nprocessed 0 records\n", "light_cyan");
        }
        echo "\n";
        echo $colors->getColoredString("execution ends\n", "green");
    }

    function formatSizeUnits($bytes) {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    private function scanID3($file) {
        $res      = false;
        $getID3   = new \getID3();
        $id3      = $getID3->analyze($file);
        $duration = @$id3['playtime_string'];
        return $duration;
    }

}

class SystemInfo {

    public $colors;

    function __construct($colors) {
        $this->colors = $colors;
    }

    function index() {
        $colors = $this->colors;

        if (strtolower(PHP_OS_FAMILY) == 'windows') {
            echo $colors->getColoredString("Hello windows user\n", "white");
            $ram_info = $this->getWindowsRamInfo();
            echo $colors->getColoredString("\n*********************************************************\n", "magenta");
            $mask     = "%-20s %s\n";

            foreach ($ram_info as $k => $v) {

                echo $colors->getColoredString(sprintf($mask, $k, $v) . "\n", "yellow");
            }
        } else {
            echo $colors->getColoredString("Hello linux user\n", "white");
        }
    }

    function get_server_memory_usage() {

        $free         = shell_exec('free');
        $free         = (string) trim($free);
        $free_arr     = explode("\n", $free);
        $mem          = explode(" ", $free_arr[1]);
        $mem          = array_filter($mem);
        $mem          = array_merge($mem);
        $memory_usage = $mem[2] / $mem[1] * 100;

        return $memory_usage;
    }

    function getWindowsInfo() {




        $free         = shell_exec('systeminfo');
        echo $free;
        die;
        $free         = (string) trim($free);
        $free_arr     = explode("\n", $free);
        $mem          = explode(" ", $free_arr[1]);
        $mem          = array_filter($mem);
        $mem          = array_merge($mem);
        $memory_usage = $mem[2] / $mem[1] * 100;

        return $memory_usage;
    }

    function getWindowsRamInfo() {
        $list     = shell_exec('wmic memorychip list full');
        $ram_info = explode("\n", $list);
        $temp     = [];
        foreach ($ram_info as $k => $v) {
            if (trim($v) != '') {
                $key_val           = explode("=", $v);
                $temp[$key_val[0]] = @$key_val[1];
            }
        }
        $ram_info = $temp;
        return $ram_info;
    }

    /* This function will return the Server CPU Usage: */

    function get_server_cpu_usage() {

        $load = sys_getloadavg();
        return $load[0];
    }

}

?>