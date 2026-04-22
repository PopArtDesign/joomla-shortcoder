<?php

return [
    'callable_tag' => fn() => 'test output from callable',
    'overwrite_me' => fn() => 'output from callable (overwritten)',
    'another_callable' => function($params, $content, $item) {
        return 'Params: ' . json_encode($params) . ', Content: ' . $content . ', Item: ' . ($item->title ?? 'N/A');
    }
];