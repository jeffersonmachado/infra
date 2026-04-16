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

/*
 * This file is part of the Imagine package.
 *
 * (c) Bulat Shakirzyanov <mallluhuct@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Imagine\Gd;

defined('_JEXEC') or die;

use Imagine\Image\AbstractFont;
use Imagine\Image\Box;

/**
 * Font implementation using the GD library
 */
final class Font extends AbstractFont
{
    /**
     * {@inheritdoc}
     */
    public function box($string, $angle = 0)
    {
        $angle = -1 * $angle;
        $info = imageftbbox($this->size, $angle, $this->file, $string);
        $xs = array($info[0], $info[2], $info[4], $info[6]);
        $ys = array($info[1], $info[3], $info[5], $info[7]);
        $width = abs(max($xs) - min($xs));
        $height = abs(max($ys) - min($ys));

        return new Box($width, $height);
    }
}
