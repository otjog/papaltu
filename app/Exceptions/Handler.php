<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Models\Site\Image;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof NotFoundHttpException) {

            $requestUri = $request->getRequestUri();

            $pathElements = explode('/', $requestUri);
            //[0] - ''; [1] - storage; [2] - img; [3] - Component (shop); [4] Resource (product); [5] Size (m); [6] FileName;

            if (count($pathElements) === 7) {

                if ($pathElements[1] === 'storage' && $pathElements[2] === 'img') {

                    $images = new Image();

                    $resultImage = $images->createResizeImage($requestUri);

                    if ($resultImage !== null) {
                        return $resultImage;
                    }

                }

            }

        }

        return parent::render($request, $exception);
    }
}
