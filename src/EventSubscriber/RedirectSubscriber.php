<?php

namespace Drupal\civicrm_view_phenix\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;


class RedirectSubscriber implements EventSubscriberInterface {

  public function checkForRedirection(RequestEvent $event) {
    $request = $event->getRequest();
    $current_uri = $request->getRequestUri();
    $current_domain = $request->getHost();
    $userAgent = $request->headers->get('User-Agent');

    if (strpos($userAgent, 'facebookexternalhit') !== false) {
      if (strpos($request->getPathInfo(), '/annuaire') === 0) {
        $event->setResponse(new Response('Access Denied', Response::HTTP_FORBIDDEN));
        return;
      }
    }

   switch ($current_domain) {

      case 'www.annuairedlr.fr':
       // Redirect www.annuairedlr.fr et TOUTE URL qui ne contient pas /annuaire to www.annuairedlr.fr/annuaire
       // séparé des autres cases pour éviter ERR TOO MANY REDIRECT
         if (strpos($current_uri, '/annuaire') !== 0) {
          $event->setResponse(new TrustedRedirectResponse('/annuaire', 301));
          }
        break;

      case 'annuairedlr.fr':
      case 'extranet.dlr.fr':
          if (strpos($current_uri, '/annuaire') === 0) {
              // Redirect extranet.dlr.fr/annuaire/* ou annuairedlr.fr/annuaire/* URLs to www.annuairedlr.fr/annuaire/*
              $new_url = 'https://www.annuairedlr.fr' . $current_uri;
              // redirige seulement si la nouvelle URL est différente de l'URL actuelle
              if ($new_url !== $request->getUri()) {
                $response = new RedirectResponse($new_url, 301);
                $event->setResponse($response);
              }
          } // redirect extranet.dlr.fr/ vers /bienvenue
            elseif ($current_uri === '/' && $current_domain === 'extranet.dlr.fr' ) {
            $event->setResponse(new RedirectResponse('/bienvenue'));
          } // redirect annuairedlr.fr/ ET TOUTE URL qui ne contient pas /annuaire vers /annuaire
          elseif (strpos($current_uri, '/annuaire') !== 0 && $current_domain === 'annuairedlr.fr' ) {
            $event->setResponse(new RedirectResponse('/annuaire', 301));
          }
       break;
      }
    }

  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkForRedirection'];
    return $events;
  }
}
