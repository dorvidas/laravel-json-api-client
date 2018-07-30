<?php

namespace Dorvidas\JsonApiClient\Exceptions;

use Exception;

class ApiValidationException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        $this->message = $message;
        $this->code = $code;
    }

    /**
     * Report the exception.
     *
     * @return void
     */
    public function report()
    {
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        if ($request->wantsJson()) {
            return response($this->message, 422);
        } else {
            $errors = collect(\GuzzleHttp\json_decode($this->message, true)['errors']);
            $errors->transform(function ($error) {
                return $error['title'];
            });
            return \Redirect::back()->withErrors($errors);
        }
    }
}