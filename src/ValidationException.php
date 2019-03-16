<?php

namespace KhanCode\LaravelBaseRest;

class ValidationException extends \Exception
{
    public function responseJson()
    {
        return \Response::json(
            [
                'error' => [
                    'message' => 'Error Found.',
                    'status_code' => 406,
                    'error' => json_decode($this->message, true),
                ],
            ],
        406);
    }
}
