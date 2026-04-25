<?php

namespace JoomlaShortcoder\Plugin\Content\Shortcoder\Exception;

\defined('_JEXEC') or die;

/**
 * Exception thrown when shortcodes cannot be loaded.
 *
 * @author Oleg Voronkovich <oleg-voronkovich@yandex.ru>
 */
class ShortcodeLoadingException extends \RuntimeException implements ExceptionInterface
{
}
