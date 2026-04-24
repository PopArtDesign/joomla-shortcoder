<?php

define('_JEXEC', 1);

require_once __DIR__ . '/../vendor/autoload.php';

// Mock Joomla classes for testing without Joomla installation using class_alias
if (!class_exists('Joomla\CMS\Plugin\CMSPlugin')) {
    abstract class Mock_CMSPlugin
    {
    }
    class_alias('Mock_CMSPlugin', 'Joomla\CMS\Plugin\CMSPlugin');
}

if (!interface_exists('Joomla\Event\EventInterface')) {
    interface Mock_EventInterface
    {
    }
    class_alias('Mock_EventInterface', 'Joomla\Event\EventInterface');
}

if (!interface_exists('Joomla\Event\SubscriberInterface')) {
    interface Mock_SubscriberInterface
    {
        public static function getSubscribedEvents(): array;
    }
    class_alias('Mock_SubscriberInterface', 'Joomla\Event\SubscriberInterface');
}

if (!class_exists('Joomla\Event\Event')) {
    class Mock_Event implements Joomla\Event\EventInterface
    {
        protected array $arguments;

        public function __construct(string $name, array $arguments = [])
        {
            $this->arguments = $arguments;
        }

        public function getArgument(int $index)
        {
            return $this->arguments[$index] ?? null;
        }
    }
    class_alias('Mock_Event', 'Joomla\Event\Event');
}

if (!class_exists('Joomla\CMS\Event\Content\ContentPrepareEvent')) {
    class Mock_ContentPrepareEvent implements Joomla\Event\EventInterface
    {
        private $subject;
        private string $context;

        public function __construct(string $context, &$subject)
        {
            $this->context = $context;
            $this->subject = $subject;
        }

        public function getContext(): string
        {
            return $this->context;
        }

        public function getSubject()
        {
            return $this->subject;
        }
    }
    class_alias('Mock_ContentPrepareEvent', 'Joomla\CMS\Event\Content\ContentPrepareEvent');
}
