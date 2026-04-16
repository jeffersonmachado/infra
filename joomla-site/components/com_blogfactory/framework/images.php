<?php

/**
-------------------------------------------------------------------------
blogfactory - Blog Factory 4.3.0
-------------------------------------------------------------------------
 * @author thePHPfactory
 * @copyright Copyright (C) 2011 SKEPSIS Consult SRL. All Rights Reserved.
 * @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * Websites: http://www.thePHPfactory.com
 * Technical Support: Forum - http://www.thePHPfactory.com/forum/
-------------------------------------------------------------------------
*/

defined('_JEXEC') or die;

use Imagine\Image\Box;
use Imagine\Image\Point;

class BlogFactoryImages
{
    protected $imagine;

    public function __construct()
    {
        $base = JPATH_SITE . '/components/com_blogfactory/libraries/Imagine';

        require_once $base . '/Image/ImagineInterface.php';
        require_once $base . '/Image/ManipulatorInterface.php';
        require_once $base . '/Image/ImageInterface.php';
        require_once $base . '/Image/BoxInterface.php';
        require_once $base . '/Image/PointInterface.php';
        require_once $base . '/Image/Color.php';
        require_once $base . '/Image/Box.php';
        require_once $base . '/Image/Point.php';
        require_once $base . '/Gd/Imagine.php';
        require_once $base . '/Gd/Image.php';
    }

    public static function getInstance()
    {
        $instance = null;

        if (is_null($instance)) {
            $instance = new self();
        }

        return $instance;
    }

    public function resize($src, $size, $dest, $options = array('quality' => 100))
    {
        $imagine = new Imagine\Gd\Imagine();
        $image = $imagine->open($src);

        $height = $image->getSize()->getHeight();
        $width = $image->getSize()->getWidth();

        if ($height <= $size && $width <= $size) {
            return $image->save($dest, $options);
        }

        $box = new Imagine\Image\Box($size, $size);

        $image->resize($box)
            ->save($dest, $options);
    }

    public function resizeScale($src, $scale, $dest, $options = array('quality' => 100))
    {
        $imagine = new Imagine\Gd\Imagine();
        $image = $imagine->open($src);

        $height = $image->getSize()->getHeight();
        $width = $image->getSize()->getWidth();

        if ($height < $scale && $width < $scale) {
            return $image->save($dest, $options);
        }

        if ($height > $width) {
            $percent = $scale * 100 / $height;
            $box = new Imagine\Image\Box($width * $percent / 100, $scale);
        } else {
            $percent = $scale * 100 / $width;
            $box = new Imagine\Image\Box($scale, $height * $percent / 100);
        }

        $image->resize($box)
            ->save($dest, $options);
    }

    public function resizeCrop($src, $size, $dest, $options = array('quality' => 100))
    {
        $imagine = new Imagine\Gd\Imagine();
        $image = $imagine->open($src);

        $height = $image->getSize()->getHeight();
        $width = $image->getSize()->getWidth();

        $diff = abs($height - $width) / 2;
        $newSize = min($width, $height);

        $box = new Imagine\Image\Box($size, $size);

        $image->crop(new Point($height > $width ? 0 : $diff, $height < $width ? 0 : $diff), new Box($newSize, $newSize))
            ->resize($box)
            ->save($dest, $options);
    }
}
