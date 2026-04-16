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

if ($this->files): ?>
    <?php foreach ($this->files as $file): ?>
        <div class="file" data-file-type="<?php echo $file['type']; ?>" data-url="<?php echo $file['url']; ?>">
            <div class="preview">
                <img src="<?php echo $file['thumbnail']; ?>"/>
            </div>
            <div class="title"><?php echo $file['filename']; ?></div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div style="padding: 20px;">No files found in this folder!</div>
<?php endif; ?>
