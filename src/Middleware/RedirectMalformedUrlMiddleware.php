<?php

namespace Drupal\civicrm_view_phenix\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RedirectMalformedUrlMiddleware implements HttpKernelInterface {

  protected $httpKernel;

  public function __construct(HttpKernelInterface $http_kernel) {
    $this->httpKernel = $http_kernel;
  }

  public function handle(Request $request, $type = self::MAIN_REQUEST, $catch = TRUE) {

    $query_string = $request->getQueryString();

    // Vérifier si l'URL contient des paramètres malformés
    if ($query_string && (
    strpos($query_string, '-9') !== false ||
    preg_match('/&\d+(?:=|$)/', $query_string) ||
    preg_match('/=\d+-9/', $query_string)

    )) {      // Rediriger vers la page d'accueil
      //return new RedirectResponse('/annuaire', 301);

      // retourner une erreur 410 Anciennes URLs obsolètes pour indiquer aux bots de désindexer rapidement ces pages
      return new Response('', 410);
    }

    return $this->httpKernel->handle($request, $type, $catch);
  }
}
