<?php

return [
    'invalid-tag!' => fn () => true, // Invalid tag name
    'valid-tag' => 'not-a-callable', // Not a callable
    'another_invalid' => new stdClass() // Not a callable
];
