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

namespace Imagine\Image\Histogram;

defined('_JEXEC') or die;

use Imagine\Exception\OutOfBoundsException;

/**
 * Range histogram
 */
final class Range
{
    /**
     * @var integer
     */
    private $start;

    /**
     * @var integer
     */
    private $end;

    /**
     * @param integer $start
     * @param integer $end
     *
     * @throws OutOfBoundsException
     */
    public function __construct($start, $end)
    {
        if ($end <= $start) {
            throw new OutOfBoundsException(sprintf(
                'Range end cannot be bigger than start, %d %d given ' .
                'accordingly', $this->start, $this->end
            ));
        }

        $this->start = $start;
        $this->end = $end;
    }

    /**
     * @param integer $value
     *
     * @return Boolean
     */
    public function contains($value)
    {
        return $value >= $this->start && $value < $this->end;
    }
}
