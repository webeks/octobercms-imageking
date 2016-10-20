<?php
namespace Code200\ImageKing\Classes;

use Config;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ImageKingMiddleware
{

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Only handle default responses (no redirects)
        // Ignore non-html responses and backend responses
        if (!$this->isRelevant($request, $response)) {
            return $response;
        }

        //@todo my stuff
        $response->setContent(
            (new ImageService($response->getContent()))->process()
        );

        return $response;
    }


    /**
     * Check if the requested path starts with
     * the configured backend uri.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isBackendRequest(Request $request)
    {
        return starts_with(trim($request->getPathInfo(), '/'), trim(Config::get('cms.backendUri', 'backend'), '/'));
    }


    /**
     * Check the content type of the response.
     *
     * @param Response $response
     *
     * @return bool
     */
    private function isHtmlResponse(Response $response)
    {
        return starts_with($response->headers->get('content-type'), 'text/html');
    }

    /**
     * Checks whether the response should be processed
     * by this middleware.
     *
     * @param $request
     * @param $response
     *
     * @return bool
     */
    protected function isRelevant($request, $response)
    {
        // Only default responses, no redirects
        if (!$response instanceof Response) {
            return false;
        }
        if (!$this->isHtmlResponse($response)) {
            return false;
        }
        if ($this->isBackendRequest($request)) {
            return false;
        }
        if ($response->getContent() === '') {
            return false;
        }

        return true;
    }


}