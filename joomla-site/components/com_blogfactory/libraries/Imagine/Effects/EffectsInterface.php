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

namespace Imagine\Effects;

defined('_JEXEC') or die;

use Imagine\Exception\RuntimeException;
use Imagine\Image\Color;

/**
 * Interface for the effects
 */
interface EffectsInterface
{
    /**
     * Apply gamma correction
     *
     * @param  float $correction
     * @return EffectsInterface
     *
     * @throws RuntimeException
     */
    public function gamma($correction);

    /**
     * Invert the colors of the image
     *
     * @return EffectsInterface
     *
     * @throws RuntimeException
     */
    public function negative();

    /**
     * Grayscale the image
     *
     * @return EffectsInterface
     *
     * @throws RuntimeException
     */
    public function grayscale();

    /**
     * Colorize the image
     *
     * @param Color $color
     *
     * @return EffectsInterface
     *
     * @throws RuntimeException
     */
    public function colorize(Color $color);

    /**
     * Sharpens the image
     *
     * @return EffectsInterface
     *
     * @throws RuntimeException
     */
    public function sharpen();
}
