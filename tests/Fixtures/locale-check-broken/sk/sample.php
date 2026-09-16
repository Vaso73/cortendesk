<?php

// Deliberately broken fixture: missing key (farewell), extra key
// (unexpected), empty value (blank), placeholder mismatch (welcome),
// and a bad bare-plural shape for Slovak (items keeps the 2-branch
// English shape instead of the required 3-branch sk shape).
return [
    'greeting' => 'Ahoj',
    'welcome' => 'Ahoj :meno, mate :count poloziek',
    'items' => 'Jedna polozka|:count poloziek',
    'blank' => '',
    'unexpected' => 'Navyse',
];
