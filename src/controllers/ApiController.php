<?php
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Elabftw\Tools;
use Elabftw\Interfaces\ControllerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * For API requests
 */
class ApiController implements ControllerInterface
{
    /** @var Request $Request instance of Request */
    private $Request;

    /** @var array $allowedMethods allowed HTTP methods */
    private $allowedMethods = array('GET', 'POST');

    /**
     * Constructor
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->Request = $request;
    }

    /**
     * Get Response from Request
     *
     * @return Response
     */
    public function getResponse(): Response
    {
        // Check the HTTP method is allowed
        if (!\in_array($this->Request->server->get('REQUEST_METHOD'), $this->allowedMethods, true)) {
            // send error 405 for Method Not Allowed, with Allow header as per spec:
            // https://tools.ietf.org/html/rfc7231#section-7.4.1
            return new Response('Invalid HTTP request method!', 405, array('Allow' => \implode(', ', $this->allowedMethods)));
        }

        // Check if the Authorization Token was sent along
        if (!$this->Request->server->has('HTTP_AUTHORIZATION')) {
            // send error 401 if it's lacking an Authorization header, with WWW-Authenticate as per spec:
            // https://tools.ietf.org/html/rfc7235#section-3.1
            return new Response('No access token provided!', 401, array('WWW-Authenticate' => 'Bearer'));
        }

        $Response = new JsonResponse();
        $Response->setData(array('error' => Tools::error()));
        return $Response;
    }
}
