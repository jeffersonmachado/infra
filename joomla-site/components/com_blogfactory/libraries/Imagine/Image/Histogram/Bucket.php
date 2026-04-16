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

/**
 * Bucket histogram
 */
final class Bucket implements \Countable
{
    /**
     * @var Range
     */
    private $range;

    /**
     * @var integer
     */
    private $count;

    /**
     * @param Range $range
     * @param integer $count
     */
    public function __construct(Range $range, $count = 0)
    {
        $this->range = $range;
        $this->count = $count;
    }

    /**
     * @param integer $value
     */
    public function add($value)
    {
        if ($this->range->contains($value)) {
            $this->count++;
        }
    }

    /**
     * @return integer The number of elements in the bucket.
     */
    public function count()
    {
        return $this->count;
    }
}
