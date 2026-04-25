<?php

namespace JoomlaShortcoder\Plugin\Content\Shortcoder\Exception;

\defined('_JEXEC') or die;

/**
 * Custom exception for shortcode processing errors.
 *
 * Thrown when an error occurs during the execution of a shortcode,
 * wrapping the original throwable for detailed logging.
 *
 * @author Oleg Voronkovich <oleg-voronkovich@yandex.ru>
 */
class ShortcodeProcessingException extends \RuntimeException implements ExceptionInterface
{
}
