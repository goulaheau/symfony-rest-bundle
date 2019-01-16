<?php

namespace Goulaheau\RestBundle\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class JsonRequestTransformerListener
{
    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (false === $this->isAvailable($request)) {
            return;
        }

        if (false === $this->transform($request)) {
            $event->setResponse(new JsonResponse('Unable to parse request.', Response::HTTP_BAD_REQUEST));
        }
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    protected function isAvailable(Request $request)
    {
        return $request->getContentType() === 'json' && $request->getContent();
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    protected function transform(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        if (is_array($data)) {
            $request->request->replace($data);
        }

        return true;
    }
}
