<?php

namespace Drupal\civicrm_view_phenix\Controller;

use Drupal\Core\Controller\ControllerBase;

class BienvenueController extends ControllerBase {

  public function content() {
    return [
      '#markup' => $this->t('Bienvenue sur extranet.dlr.fr !'),
    ];
  }

}
