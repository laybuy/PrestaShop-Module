<?php

namespace Laybuy\Response;

class ExceptionResponse extends AbstractResponse
{
    public static function createResponse($error = null)
    {
        if (null === $error) {
            $error = 'An error occured';
        }

        $response = new self();
        $response->result = 'ERROR';
        $response->error = $error;
    }
}