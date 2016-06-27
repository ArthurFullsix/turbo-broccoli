<?php
/*
   _____ _    _          _   _  _____ ______
  / ____| |  | |   /\   | \ | |/ ____|  ____|
 | |    | |__| |  /  \  |  \| | |  __| |__
 | |    |  __  | / /\ \ | . ` | | |_ |  __|
 | |____| |  | |/ ____ \| |\  | |__| | |____
  \_____|_|  |_/_/____\_\_|_\_|\_____|______|
 |__   __| |  | |_   _|/ ____|
    | |  | |__| | | | | (___
    | |  |  __  | | |  \___ \
    | |  | |  | |_| |_ ____) |
    |_|  |_|  |_|_____|_____/
*/
define('SALT', '{X@M6@q)hVKj~y;b');

//commment if you do not want error to be reported
error_reporting(-1);
ini_set('display_errors', '1');
date_default_timezone_set('Europe/Paris');

//load twig
require_once './vendor/autoload.php';

$controller = new Controller();
$controller->mainAction();

class Controller
{
    private $templates_directory;
    private $listing_templates;
    private $salt;

    public function __construct()
    {
        $this->templates_directory = realpath(dirname(__FILE__).'/templates');
        $this->listing_templates = realpath(dirname(__FILE__).'/listing');
        $this->salt = SALT;
    }

    /**
     * display the directory or render the file.
     */
    public function mainAction()
    {
        if (isset($_GET['viewpage'])) {
            if ($_GET['sc'] != $this->secgen($_GET['viewpage'])) {
                exit('SECURITY BREACH');
            }

            $twig_page = substr(realpath($_GET['viewpage']), strlen($this->templates_directory) + 1);
            $twig_directory = $this->templates_directory;
            $directoryArray = null;
        } else {
            $twig_directory = $this->listing_templates;
            $twig_page = 'listing.html.twig';
            if (isset($_GET['viewdir'])) {
                if ($_GET['sc'] != $this->secgen($_GET['viewdir'])) {
                    exit('SECURITY BREACH');
                }

                if (realpath($_GET['viewdir']) == $this->templates_directory) {
                    $parent = false;
                } else {
                    $parent = true;
                }
                $directoryArray = $this->getFileList(realpath($_GET['viewdir']), $parent);
            } else {
                $directoryArray = $this->getFileList($this->templates_directory, false);
            }
        }
        $loader = new Twig_Loader_Filesystem($twig_directory);
        $twig = new Twig_Environment($loader, array(
            'cache' => false,
            'debug' => true,
        ));

        echo $twig->render($twig_page, array('directory' => $directoryArray));
    }

    /**
     * Return array files and directories of a directory.
     *
     * @param string $dir    the directory to parse
     * @param bool   $parent display or not the directory
     *
     * @return array $retval
     */
    public function getFileList($dir, $parent)
    {
        $retval = array();
        if (substr($dir, -1) != '/') {
            $dir .= '/';
        }

        if ($parent) {
            $entry = '../';
            $fullpath = realpath($dir.$entry);
            $retval[] = array(
                'name' => '..',
                'type' => 'folder',
                'size' => '&mdash;',
                'lastmod' => date(filemtime("$dir$entry")),
                'link' => '/?viewdir='.urlencode($fullpath).'&sc='.$this->secgen($fullpath),
            );
        }

        // open pointer to directory and read list of files
        $d = @dir($dir) or die("getFileList: Failed opening directory $dir for reading");
        while (false !== ($entry = $d->read())) {
            // skip hidden files
            if ($entry[0] == '.') {
                continue;
            }
            if (is_dir("$dir$entry")) {
                $fullpath = realpath($dir.$entry);
                $retval[] = array(
                    'name' => "$entry",
                    'type' => 'folder',
                    'size' => '&mdash;',
                    'lastmod' => date(filemtime("$dir$entry")),
                    'link' => '/?viewdir='.urlencode($fullpath).'&sc='.$this->secgen($fullpath),
                );
            } elseif (is_readable("$dir$entry")) {
                $fullpath = realpath($dir.$entry);
                $retval[] = array(
                    'name' => "$entry",
                    'type' => 'code',
                    'size' => $this->formatSizeUnits(filesize("$dir$entry")),
                    'lastmod' => date(filemtime("$dir$entry")),
                    'link' => '/?viewpage='.urlencode($fullpath).'&sc='.$this->secgen($fullpath),
                );
            }
        }
        $d->close();

        return $retval;
    }

    /**
     * Return size in human readable format.
     *
     * @param int $bytes
     *
     * @return string $bytes
     */
    public function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2).' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes.' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes.' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    /**
     * Return an md5 base on filename or directory in order to prevent to parse unwanted directory.
     *
     * @param $entry
     *
     * @return string
     */
    public function secGen($entry)
    {
        $str = $this->salt.$entry;

        return md5($str);
    }
}
