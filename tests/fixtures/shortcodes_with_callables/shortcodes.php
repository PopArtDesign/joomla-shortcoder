<?php

return [
    'callable_tag' => fn () => 'test output from callable',
    'overwrite_me' => fn () => 'output from callable (overwritten)',
    'another_callable' => function ($attributes, $content, $item) {
        return 'Attributes: ' . json_encode($attributes) . ', Content: ' . $content . ', Item: ' . ($item->title ?? 'N/A');
    }
];
