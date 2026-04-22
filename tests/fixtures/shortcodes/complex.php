<?php

$attr_val = $params['attr'] ?? 'default_attr';
$item_title = $item->title ?? 'default_title';

echo "Attr: {$attr_val}, Content: {$content}, Item: {$item_title}";
