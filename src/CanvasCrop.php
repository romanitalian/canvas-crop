<?php

// Example of use
//
//require 'CanvasCrop.php';
//$cc = new CanvasCrop();
//
//$cc->loadImage('original1.png');
//$cc->cropBySize(100, 100, ccBOTTOMRIGHT);
//$cc->saveImage('final1.png');
//
//$cc->flushImages();
//
//$cc->loadImage('original2.png');
//$cc->cropByPercent(15, 50, ccCENTER);
//$cc->saveImage('final2.jpg', 90);
//
//$cc->flushImages();
//
//$cc->loadImage('original3.png');
//$cc->cropToDimensions(67, 37, 420, 255);
//$cc->showImage('png');


/**
 * Class CanvasCrop
 */
class CanvasCrop
{
    const ccTOPLEFT = 0;
    const ccTOP = 1;
    const ccTOPRIGHT = 2;
    const ccLEFT = 3;
    const ccCENTRE = 4;
    const ccCENTER = 5;
    const ccRIGHT = 6;
    const ccBOTTOMLEFT = 7;
    const ccBOTTOM = 8;
    const ccBOTTOMRIGHT = 9;

    public $_imgOrig;
    public $_imgFinal;
    public $_showDebug;
    public $_gdVersion;


    /**
     * @return canvasCrop
     * @param bool $debug
     * @desc Class initializer
     */
    function __construct($debug = false)
    {
        $this->_showDebug = ($debug ? true : false);
        $this->_gdVersion = (function_exists('imagecreatetruecolor')) ? 2 : 1;
    }

    /**
     * @return bool
     * @param string $filename
     * @desc Load an image from the file system - method based on file extension
     */
    function loadImage($filename)
    {
        if (!@file_exists($filename)) {
            $this->_debug('loadImage', "The supplied file name '$filename' does not point to a readable file.");
            return false;
        }
        $ext = strtolower($this->_getExtension($filename));
        $func = "imagecreatefrom$ext";
        if (!@function_exists($func)) {
            $this->_debug('loadImage', "That file cannot be loaded with the function '$func'.");
            return false;
        }
        $this->_imgOrig = @$func($filename);
        if ($this->_imgOrig == null) {
            $this->_debug('loadImage', 'The image could not be loaded.');
            return false;
        }
        return true;
    }


    /**
     * @return bool
     * @param string $string
     * @desc Load an image from a string (eg. from a database table)
     */
    function loadImageFromString($string)
    {
        $this->_imgOrig = @ImageCreateFromString($string);
        if (!$this->_imgOrig) {
            $this->_debug('loadImageFromString', 'The image could not be loaded.');
            return false;
        }
        return true;
    }


    /**
     * @return bool
     * @param string $filename
     * @param int $quality
     * @desc Save the cropped image
     */
    function saveImage($filename, $quality = 100)
    {
        if ($this->_imgFinal == null) {
            $this->_debug('saveImage', 'There is no processed image to save.');
            return false;
        }

        $ext = strtolower($this->_getExtension($filename));
        $func = "image$ext";

        if (!@function_exists($func)) {
            $this->_debug('saveImage', "That file cannot be saved with the function '$func'.");
            return false;
        }

        $saved = false;
        if ($ext == 'png') {
            $saved = $func($this->_imgFinal, $filename);
        }
        if ($ext == 'jpeg') {
            $saved = $func($this->_imgFinal, $filename, $quality);
        }
        if ($saved == false) {
            $this->_debug('saveImage', "Could not save the output file '$filename' as a $ext.");
            return false;
        }
        return true;
    }


    /**
     * @return bool
     * @param string $type
     * @param int $quality
     * @desc Shows the cropped image without any saving
     */
    function showImage($type = 'png', $quality = 100)
    {
        if ($this->_imgFinal == null) {
            $this->_debug('showImage', 'There is no processed image to show.');
            return false;
        }
        if ($type == 'png') {
            echo @ImagePNG($this->_imgFinal);
            return true;
        } else {
            if ($type == 'jpg' || $type == 'jpeg') {
                echo @ImageJPEG($this->_imgFinal, '', $quality);
                return true;
            } else {
                $this->_debug('showImage', "Could not show the output file as a $type.");
                return false;
            }
        }
    }


    /**
     * @return int
     * @param int $x
     * @param int $y
     * @param int $position
     * @desc Determines the dimensions to crop to if using the 'crop by size' method
     */
    function cropBySize($x, $y, $position = null)
    {
        $position = $position ?: static::ccCENTRE;
        if ($x == 0) {
            $nx = @ImageSX($this->_imgOrig);
        } else {
            $nx = @ImageSX($this->_imgOrig) - $x;
        }
        if ($y == 0) {
            $ny = @ImageSY($this->_imgOrig);
        } else {
            $ny = @ImageSY($this->_imgOrig) - $y;
        }
        return ($this->_cropSize(-1, -1, $nx, $ny, $position, 'cropBySize'));
    }


    /**
     * @return int
     * @param int $x
     * @param int $y
     * @param int $position
     * @desc Determines the dimensions to crop to if using the 'crop to size' method
     */
    function cropToSize($x, $y, $position = null)
    {
        $position = $position ?: static::ccCENTRE;
        if ($x == 0) {
            $x = 1;
        }
        if ($y == 0) {
            $y = 1;
        }
        return ($this->_cropSize(-1, -1, $x, $y, $position, 'cropToSize'));
    }


    /**
     * @return int
     * @param int $sx
     * @param int $sy
     * @param int $ex
     * @param int $ey
     * @desc Determines the dimensions to crop to if using the 'crop to dimensions' method
     */
    function cropToDimensions($sx, $sy, $ex, $ey)
    {
        $nx = abs($ex - $sx);
        $ny = abs($ey - $sy);
        $position = static::ccCENTRE;
        return ($this->_cropSize($sx, $sy, $nx, $ny, $position, 'cropToDimensions'));
    }


    /**
     * @return int
     * @param int $percentx
     * @param int $percenty
     * @param int $position
     * @desc Determines the dimensions to crop to if using the 'crop by percentage' method
     */
    function cropByPercent($percentx, $percenty, $position = null)
    {
        $position = $position ?: static::ccCENTER;
        if ($percentx == 0) {
            $nx = @ImageSX($this->_imgOrig);
        } else {
            $nx = @ImageSX($this->_imgOrig) - (($percentx / 100) * @ImageSX($this->_imgOrig));
        }
        if ($percenty == 0) {
            $ny = @ImageSY($this->_imgOrig);
        } else {
            $ny = @ImageSY($this->_imgOrig) - (($percenty / 100) * @ImageSY($this->_imgOrig));
        }
        return ($this->_cropSize(-1, -1, $nx, $ny, $position, 'cropByPercent'));
    }


    /**
     * @return int
     * @param int $percentx
     * @param int $percenty
     * @param int $position
     * @desc Determines the dimensions to crop to if using the 'crop to percentage' method
     */
    function cropToPercent($percentx, $percenty, $position = null)
    {
        $position = $position ?: static::ccCENTRE;
        if ($percentx == 0) {
            $nx = @ImageSX($this->_imgOrig);
        } else {
            $nx = ($percentx / 100) * @ImageSX($this->_imgOrig);
        }
        if ($percenty == 0) {
            $ny = @ImageSY($this->_imgOrig);
        } else {
            $ny = ($percenty / 100) * @ImageSY($this->_imgOrig);
        }
        return ($this->_cropSize(-1, -1, $nx, $ny, $position, 'cropByPercent'));
    }


    /**
     * @return bool
     * @param int $threshold
     * @desc Determines the dimensions to crop to if using the 'automatic crop by threshold' method
     */
    function cropByAuto($threshold = 254)
    {
        if ($threshold < 0) {
            $threshold = 0;
        }
        if ($threshold > 255) {
            $threshold = 255;
        }

        $sizex = @ImageSX($this->_imgOrig);
        $sizey = @ImageSY($this->_imgOrig);

        $sx = $sy = $ex = $ey = -1;
        for ($y = 0; $y < $sizey; $y++) {
            for ($x = 0; $x < $sizex; $x++) {
                if ($threshold >= $this->_getThresholdValue($this->_imgOrig, $x, $y)) {
                    if ($sy == -1) {
                        $sy = $y;
                    } else {
                        $ey = $y;
                    }
                    if ($sx == -1) {
                        $sx = $x;
                    } else {
                        if ($x < $sx) {
                            $sx = $x;
                        } else {
                            if ($x > $ex) {
                                $ex = $x;
                            }
                        }
                    }
                }
            }
        }
        $nx = abs($ex - $sx);
        $ny = abs($ey - $sy);
        return ($this->_cropSize($sx, $sy, $nx, $ny, static::ccTOPLEFT, 'cropByAuto'));
    }


    /**
     * @return void
     * @desc Destroy the resources used by the images
     */
    function flushImages()
    {
        @ImageDestroy($this->_imgOrig);
        @ImageDestroy($this->_imgFinal);
        $this->_imgOrig = $this->_imgFinal = null;
    }


    /**
     * @return bool
     * @param int $ox Original image width
     * @param int $oy Original image height
     * @param int $nx New width
     * @param int $ny New height
     * @param int $position Where to place the crop
     * @param string $function Name of the calling function
     * @desc Creates the cropped image based on passed parameters
     */
    function _cropSize($ox, $oy, $nx, $ny, $position, $function)
    {
        if ($this->_imgOrig == null) {
            $this->_debug($function, 'The original image has not been loaded.');
            return false;
        }
        if (($nx <= 0) || ($ny <= 0)) {
            $this->_debug($function, 'The image could not be cropped because the size given is not valid.');
            return false;
        }
        if (($nx > @ImageSX($this->_imgOrig)) || ($ny > @ImageSY($this->_imgOrig))) {
            $this->_debug($function,
                'The image could not be cropped because the size given is larger than the original image.');
            return false;
        }
        if ($ox == -1 || $oy == -1) {
            list($ox, $oy) = $this->_getCopyPosition($nx, $ny, $position);
        }
        if ($this->_gdVersion == 2) {
            $this->_imgFinal = @ImageCreateTrueColor($nx, $ny);
            @ImageCopyResampled($this->_imgFinal, $this->_imgOrig, 0, 0, $ox, $oy, $nx, $ny, $nx, $ny);
        } else {
            $this->_imgFinal = @ImageCreate($nx, $ny);
            @ImageCopyResized($this->_imgFinal, $this->_imgOrig, 0, 0, $ox, $oy, $nx, $ny, $nx, $ny);
        }
        return true;
    }


    /**
     * @return array
     * @param int $nx
     * @param int $ny
     * @param int $position
     * @desc Determines dimensions of the crop
     */
    function _getCopyPosition($nx, $ny, $position)
    {
        $ox = @ImageSX($this->_imgOrig);
        $oy = @ImageSY($this->_imgOrig);

        switch ($position) {
            case static::ccTOPLEFT:
                return array(0, 0);
            case static::ccTOP:
                return array(ceil(($ox - $nx) / 2), 0);
            case static::ccTOPRIGHT:
                return array(($ox - $nx), 0);
            case static::ccLEFT:
                return array(0, ceil(($oy - $ny) / 2));
            case static::ccCENTRE:
                return array(ceil(($ox - $nx) / 2), ceil(($oy - $ny) / 2));
            case static::ccRIGHT:
                return array(($ox - $nx), ceil(($oy - $ny) / 2));
            case static::ccBOTTOMLEFT:
                return array(0, ($oy - $ny));
            case static::ccBOTTOM:
                return array(ceil(($ox - $nx) / 2), ($oy - $ny));
            case static::ccBOTTOMRIGHT:
                return array(($ox - $nx), ($oy - $ny));
        }
    }


    /**
     * @return float
     * @param resource $im
     * @param int $x
     * @param int $y
     * @desc Determines the intensity value of a pixel at the passed co-ordinates
     */
    function _getThresholdValue($im, $x, $y)
    {
        $rgb = ImageColorAt($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $intensity = ($r + $g + $b) / 3;
        return $intensity;
    }


    /**
     * @return string
     * @param string $filename
     * @desc Get the extension of a file name
     */
    function _getExtension($filename)
    {
        $ext = @strtolower(@substr($filename,
            (@strrpos($filename, ".") ? @strrpos($filename, ".") + 1 : @strlen($filename)), @strlen($filename)));
        return ($ext == 'jpg') ? 'jpeg' : $ext;
    }


    /**
     * @return void
     * @param string $function
     * @param string $string
     * @desc Shows debugging information
     */
    function _debug($function, $string)
    {
        if ($this->_showDebug) {
            echo "<p><strong style=\"color:#FF0000\">Error in function $function:</strong> $string</p>\n";
        }
    }


    /**
     * @return array
     * @desc Try to ascertain what the version of GD being used is, based on phpinfo output
     */
    function _getGDVersion()
    {
        static $version = array();
        if (empty($version)) {
            ob_start();
            phpinfo();
            $buffer = ob_get_contents();
            ob_end_clean();
            if (preg_match("|<B>GD Version</B></td><TD ALIGN=\"left\">([^<]*)</td>|i", $buffer, $matches)) {
                $version = explode('.', $matches[1]);
            } else {
                if (preg_match("|GD Version </td><td class=\"v\">bundled \(([^ ]*)|i", $buffer, $matches)) {
                    $version = explode('.', $matches[1]);
                } else {
                    if (preg_match("|GD Version </td><td class=\"v\">([^ ]*)|i", $buffer, $matches)) {
                        $version = explode('.', $matches[1]);
                    }
                }
            }
        }
        return $version;
    }
}
