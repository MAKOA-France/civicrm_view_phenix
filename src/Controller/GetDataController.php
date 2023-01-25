<?php

namespace Drupal\civicrm_view_phenix\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Language\LanguageManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\Element\EntityAutocomplete;

/**
 * Defines GetDetailController class.
 */
class GetDataController extends ControllerBase
{

  public function autocompleteSousFamille() {

    $results = [];
    $request = \Drupal::request();
    $custom_service = \Drupal::service('civicrm_view_phenix.view_services');
    $subFamilys = $custom_service->sousFamille();
    $sorted = asort($subFamilys);

    $input = $request->query->get('q');
    foreach ($subFamilys as $key => $subFamily) {
       if (stripos($subFamily, $input) !== false) {
        $results[] = [
          'value' => $subFamily . '(' . $key . ')',
          'label' => $subFamily,
          'data-val' => $key,
        ];
       }
    }

    return new JsonResponse ($results);
  }
}
