<?php

// Prices are integer cents: the domain never touches floats.
return [

    'catalogue' => [
        ['code' => 'R01', 'name' => 'Red Widget', 'price_cents' => 3295],
        ['code' => 'G01', 'name' => 'Green Widget', 'price_cents' => 2495],
        ['code' => 'B01', 'name' => 'Blue Widget', 'price_cents' => 795],
    ],

    'delivery_tiers' => [
        ['from_cents' => 0, 'cost_cents' => 495],
        ['from_cents' => 5000, 'cost_cents' => 295],
        ['from_cents' => 9000, 'cost_cents' => 0],
    ],

    'offers' => [
        ['type' => 'buy_one_get_second_half_price', 'product' => 'R01'],
    ],

];
