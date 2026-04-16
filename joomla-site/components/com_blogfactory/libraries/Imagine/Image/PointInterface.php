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
use Imagine\Image\ImageInterface;

/**
 * The point interface
 */
interface PointInterface
{
    /**
     * Gets points x coordinate
     *
     * @return integer
     */
    public function getX();

    /**
     * Gets points y coordinate
     *
     * @return integer
     */
    public function getY();

    /**
     * Checks if current coordinate is inside a given bo
     *
     * @param BoxInterface $box
     *
     * @return Boolean
     */
    public function in(BoxInterface $box);

    /**
     * Returns another point, moved by a given amount from current coordinates
     *
     * @param  integer $amount
     * @return ImageInterface
     */
    public function move($amount);

    /**
     * Gets a string representation for the current point
     *
     * @return string
     */
    public function __toString();
}
