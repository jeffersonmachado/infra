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

namespace Imagine\Image;

defined('_JEXEC') or die;

use Imagine\Image\BoxInterface;
use Imagine\Image\Color;

/**
 * The font interface
 */
interface FontInterface
{
    /**
     * Gets the fontfile for current font
     *
     * @return string
     */
    public function getFile();

    /**
     * Gets font's integer point size
     *
     * @return integer
     */
    public function getSize();

    /**
     * Gets font's color
     *
     * @return Color
     */
    public function getColor();

    /**
     * Gets BoxInterface of font size on the image based on string and angle
     *
     * @param string $string
     * @param integer $angle
     *
     * @return BoxInterface
     */
    public function box($string, $angle = 0);
}
