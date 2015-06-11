<?php

/**
 * Zend Framework addition by skoch
 * 
 * @category   Skoch
 * @package    Skoch_Filter
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author     Stefan Koch <cct@stefan-koch.name>
 */
 
require_once 'Skoch/Filter/File/Resize/Adapter/Abstract.php';
 
/**
 * Resizes a given file with the gd adapter and saves the created file
 *
 * @category   Skoch
 * @package    Skoch_Filter
 */
class Skoch_Filter_File_Resize_Adapter_Gd extends
    Skoch_Filter_File_Resize_Adapter_Abstract
{
    public function resize($width, $height, $keepRatio, $file, $target, $keepSmaller = true, $cropToFit = false, $quality = null)
    {
        list($oldWidth, $oldHeight, $type) = getimagesize($file);
 
        switch ($type) {
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($file);
                break;
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($file);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($file);
                break;
        }

        $newsWidth = $newsHeight = $srcX = $srcY = 0;
        if ($cropToFit) {
			list($srcX, $srcY, $oldWidth, $oldHeight) = 
				$this->_calculateSourceRectangle($oldWidth, $oldHeight, $width, $height);
        } elseif (!$keepSmaller || $oldWidth > $width || $oldHeight > $height) {
            if ($keepRatio) {
				list($__width, $__height) = $this->_calculateWidth($oldWidth, $oldHeight, $width, $height);

				$crop = false;

				if ($__width < 1) {
					$__height = min($height, $__height * (1 / $__width));
					$__width = 1;
					$crop = true;
				}

				if ($__height < 1) {
					$__width = min($width, $__width * (1 / $__height));
					$__height = 1;
					$crop = true;
				}

				if ($crop) {
					$newsWidth = $oldWidth;
					$newsHeight = $oldHeight;
				}

				$width = $__width;
				$height = $__height;
            }
        } else {
            $width = $oldWidth;
            $height = $oldHeight;
        }

		if (!$newsWidth || !$newsHeight) {
			$newsWidth = $width;
			$newsHeight = $height;
		}

        $thumb = imagecreatetruecolor($width, $height);
 
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
 
        imagecopyresampled($thumb, $source, 0, 0, $srcX, $srcY, $newsWidth, $newsHeight, $oldWidth, $oldHeight);
 
        switch ($type) {
            case IMAGETYPE_PNG:
				if ($quality === null) {
					$quality = 0;
				}
                imagepng($thumb, $target, $quality);
                break;
            case IMAGETYPE_JPEG:
				if ($quality === null) {
					$quality = 75;
				}
                imagejpeg($thumb, $target, $quality);
                break;
            case IMAGETYPE_GIF:
                imagegif($thumb, $target);
                break;
        }
        return $target;
    }
}
