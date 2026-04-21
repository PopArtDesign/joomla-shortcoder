<?php

namespace Joomla\Plugin\Content\Shortcoder\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Plugin\Content\Shortcoder\ShortcodeProcessor;

\defined('_JEXEC') or die;

class Shortcoder extends CMSPlugin
{
    private ShortcodeProcessor $processor;

    public function __construct(ShortcodeProcessor $processor)
    {
        $this->processor = $processor;
    }

    public function onContentPrepare($context, &$item, &$params, $page = 0): void
    {
        if (!in_array($context, ['com_content.article', 'com_content.category'], true)) {
            return;
        }

        $textProperties = ['text', 'introtext', 'fulltext', 'description'];
        foreach ($textProperties as $prop) {
            if (isset($item->$prop) && is_string($item->$prop) && $item->$prop !== '') {
                $item->$prop = $this->processor->processShortcodes($item->$prop, $item);
            }
        }
    }
}
