<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    */

    'attributes' => [
        'sortableAndSearchable' => 'The :value is not orderable or searchable.',
        'sortableAndSearchableExist' => 'The :value is not exist.',        
        'sortType'  =>  'This :attr must be \'asc\' or \'desc\'',
    ],

];
