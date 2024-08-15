<?php

namespace Drupal\civicrm_view_phenix\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Cache\CacheableRedirectResponse;


class RedirectSubscriber implements EventSubscriberInterface {

  public function checkForRedirection(RequestEvent $event) {
    $request = $event->getRequest();
    $current_uri = $request->getRequestUri();
    $current_domain = $request->getHost();
 //   $frontPage = $this->configFactory->get('system.site')->get('page.front');

/* // cette fonction sert à renvoyer une erreur aux bots qui font trop de requetes sur le site (ici amazonbot)
   $userAgent = $request->headers->get('User-Agent');

     if (strpos($userAgent, 'amazonbot') !== false) {
      if (strpos($request->getPathInfo(), '/annuaire') === 0) {
        $event->setResponse(new Response('Access Denied', Response::HTTP_FORBIDDEN));
        return;
      }
    } */

/* //  Usha 15/05/24 code qui fonctionnait mais commenté car remplacé par module Redirect et Domain redirect
// a noter annuairedlr.fr est redirigé au niveau de gandi (et non par le code) sur www.annuairedlr.fr

switch ($current_domain) {

      case 'www.annuairedlr.fr':
       // Redirect www.annuairedlr.fr et TOUTE URL qui ne contient pas /annuaire to www.annuairedlr.fr/annuaire
       // séparé des autres cases pour éviter ERR TOO MANY REDIRECT
         if (strpos($current_uri, '/annuaire') !== 0) {
          $event->setResponse(new RedirectResponse('/annuaire', 301));
          }
        break;

        case 'annuairedlr.fr':
                $event->setResponse(new Response('', 410));
                       break;
       case 'extranet.dlr.fr':
          if (strpos($current_uri, '/annuaire') === 0) {
                $event->setResponse(new Response('', 410));
          }
       break;
      } */
    }

  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkForRedirection'];
    return $events;
  }
}
