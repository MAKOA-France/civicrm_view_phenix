<?php

namespace Drupal\civicrm_view_phenix\Plugin\Block;

use Drupal\node\Entity\Node;
use \Drupal\node\NodeInterface;
use Drupal\Core\Block\BlockBase;
/**
 * Provides a 'DictionaryDetailBlock' block.
 *
 * @Block(
 *  id = "alphabetical_filter_view",
 *  admin_label = @Translation("Company profile second column view block"),
 *  category = @Translation("Head Views blocks"),
 * )
 */
class CompanyProfileSecondProfile  extends BlockBase  {

  /**
   * {@inheritdoc}
   */
  public function build() {
    \Drupal::service('cache.render')->invalidateAll();
    $id = \Drupal::request()->get('arg_0');
    $custom_service = \Drupal::service('civicrm_view_phenix.view_services');
    $database = \Drupal::database();
    $description = $database->query('select org_dlr_descriptif_entreprise from civicrm_value_phx_org_dlr where entity_id = ' . $id)->fetchCol();
    $idMainActivity = $database->query('select org_dlr_activiteprincipale from civicrm_value_phx_org_dlr where entity_id = ' . $id)->fetchCol();
    if ($idMainActivity[0]) {
      $mainActivity = $database->query('select  label from civicrm_option_value where value  = ' . $idMainActivity[0] . ' and option_group_id = 100')->fetchCol();
    }
    $equipmentRental = $database->query('select materiel_location from civicrm_value_phx_materiel where entity_id = ' . $id)->fetchCol();
    
    /* $contacts = \Civi\Api4\Contact::get()
      ->addSelect('org_dlr.descriptif_entreprise', 'org_dlr.activiteprincipale', 'Materiel.nom_location')
      ->addWhere('id', '=', $id)
      ->execute(); */
      $description = '';
      
      $description = $database->query('select org_dlr_descriptif_entreprise from civicrm_value_phx_org_dlr where entity_id = ' . $id)->fetchCol()[0];
      $idMainActivity = $database->query('select org_dlr_activiteprincipale from civicrm_value_phx_org_dlr where entity_id = ' . $id)->fetchCol()[0];
      $equipmentRental = $database->query('select materiel_location from civicrm_value_phx_materiel where entity_id = ' . $id)->fetchCol();
      if ($idMainActivity) {
        $mainActivityLabel = $database->query('select  label from civicrm_option_value where value  = ' . $idMainActivity . ' and option_group_id = 100')->fetchCol();
      }
      $equipmentRental = implode($equipmentRental, ', ');

      $isThereAnyLocation = false;
      if ($equipmentRental) {
        $equipmentRental = str_replace("\x01", "", $equipmentRental);
        
        $rentals = \Civi\Api4\Contact::get(FALSE)
          ->addSelect('Materiel.nom_location:label')
          ->addWhere('id', '=', $id)
          ->execute()->getIterator();
    
          $rentals = iterator_to_array($rentals); 

        $materielLocation = '<div class="field-content occasion-details content-fiche"><ul>';
        
        $isThereAnyLocation = !empty($rentals) ? true : false;
        $whitelistLocation = $this->getAllActiveMaterielLocation();
        foreach ($rentals[0]['Materiel.nom_location:label'] as $rental) {
          if (!in_array($rental, $whitelistLocation)) {
            $materielLocation .= '<li class="content-fiche">' . $rental. '</li>';
          }
        }


        $materielLocation .= '</ul></div>';

      }


      //Get used equipment (materiel d'occasion)
      $usedEquipment = $database->query('select materiel_occasion from civicrm_value_phx_materiel where entity_id = ' . $id)->fetchCol();
      if ($usedEquipment) {
        if (sizeof($usedEquipment) > 1) {
          // $usedEquipment = implode($usedEquipment, ', ');
          // $allUsedEquipments = $database->query('SELECT label FROM civicrm_option_value where option_group_id = 107 and  value IN (' . $usedEquipment . ') order by label asc')->fetchAll();
        }
      }


      //Gel all distributed brands
      $distributedBrands = $database->query("SELECT marque_nom FROM civicrm_value_phx_marques where entity_id = " . $id . "")->fetchAll();
      $distributedBrands = array_map(function($e) {
        return $e->marque_nom;
      }, $distributedBrands);
      $distributedBrands = implode($distributedBrands, ', ');

      $distributedBrand = '';
      if ($distributedBrands) {
        $distributedBrands = $database->query("SELECT label FROM `civicrm_option_value` where value IN (" . $distributedBrands . ") order by label asc")->fetchAll();

        $distributedBrand = '<div class="field-content occasion-details content-fiche"><ul>';

        foreach ($distributedBrands as $brand) {
          $distributedBrand .= '<li class="content-fiche">' . $brand->label . '</li>';
        }
        $distributedBrand .= '</ul></div>';
      }
      

      //todo  hook theme (create template twig)


      $htmlUsedEquipments = '<div class="field-content occasion-details content-fiche"><ul>';

       foreach ($allUsedEquipments as $equipment) {
         $htmlUsedEquipments .= '<li class="content-fiche">' . $equipment->label . '</li>';
       }

      $htmlUsedEquipments .= '</ul></div>';


      $materielLocation = ($equipmentRental  && $isThereAnyLocation) ? ' <strong class="views-label views-label-materiel-occasion title-fiche">Location : </strong>' . $materielLocation : '';

      $materielHtml = $allUsedEquipments ? '<strong class="views-label views-label-materiel-occasion title-fiche">Matériels : </strong>
        ' . $htmlUsedEquipments : '';


      $brandLabel = $distributedBrand ? '<strong class="views-label views-label-marque-nom title-fiche">Marques : </strong>' : '';

      if ($mainActivityLabel) {
        $mainActivityLabelVal = $mainActivityLabel[0];
        $mainActivityLabelVal = str_replace('Professionnel DLR : ', '', $mainActivityLabelVal);
      }

      //Check if company is linked with label SE or not
      $tagHtmlForLabelSE = '';
      $isCompanyLabelSE = $this->getQueryService()->isCompanyLabelSE($id);
      $isLabelSe = false;
      if (!empty ($isCompanyLabelSE) && in_array(6, $isCompanyLabelSE)) {
        $tagHtmlForLabelSE = '
        
        ';
        $isLabelSe = true;
      }

      $isMembreAssocie = $this->getQueryService()->isContactMemberAssocie ($id);
      $pointsForts = '';
      if ($isMembreAssocie) {
        if ($pointFortsVal = $custom_service->getMembreAssociePointForts($id)) {
          $pointsForts .= '<p class="views-label  mb-0 point-forts   title-fiche"><b>Points forts : </b></p>';
          $pointsForts .= '<div class=" second-column  content-fiche"> ' . $pointFortsVal . '</div>';
        }
        $tagHtmlForLabelSE = '
        <span class="company-profile-membre-associe">Membre Associé</span>
        <img src="/files/styles/thumbnail/public/2022-07/logoMA.png?itok=vURbgtIU">
               ';
      }

      //Get Matériels d'occasion
      $materiel_occasion_label = $this->getQueryService()->getMaterielOccasion($id);

      if ($mainActivityLabel) {
        $mainActivity = $mainActivityLabel[0] && $mainActivityLabelVal  ? '<div class="company-profile-SE">
        <strong class="views-label views-label-materiel-occasion title-fiche">Activité principale : </strong>
        ' . $tagHtmlForLabelSE . '
        <p class="content-fiche"> ' .  $mainActivityLabelVal .  ' </p>' : '</div>';
        if (strpos($mainActivityLabel[0], 'Fournisseur DLR :') !== false) {
          $mainActivity = $tagHtmlForLabelSE; 
        }
      }
      
      $htmlDescription = strlen($description) > 5 ? ' <p class="views-label views-label-marque-nom title-fiche">Descriptif de l\'entreprise : </p>
        <div class="content-fiche company-description">' . $description . '</div><br>' : '<span class="noafff hide hidden">tssssa</span>';

      $htmlDescription .= $pointsForts;  

      $materiel_occasion_html = $materiel_occasion_label ? ' <strong class="views-label views-label-marque-nom title-fiche">Matériels d\'occasion : </strong>
        <div class="content-fiche company-description">' . $materiel_occasion_label . '</div>' : '';

      $imgLabelSE = $isLabelSe ? '<img class="company-profile-img-label-se" src="/files/styles/thumbnail/public/2022-07/logo-pages-se_1.png">' : '';
      $html = $imgLabelSE . '<div class="second-column company-profile-block">
        ' . $mainActivity . $htmlDescription
         . $materielHtml . '

        ' . $brandLabel . '
        ' . $distributedBrand . '
        ' . $materielLocation . $materiel_occasion_html . '
      </div>';

      \Drupal::service('page_cache_kill_switch')->trigger();

    return [
      '#markup' => $html,
      '#cache' => ['max-age' => 0],
    ];
  }

  private function getQueryService () {
    return \Drupal::service('civicrm_view_phenix.view_query_services');
  }

  /**
   * @return int
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * Recupere les matériel location active
   */
  private function getAllActiveMaterielLocation() {
    $location = \Civi\Api4\OptionValue::get(FALSE)
    ->addSelect('label')
    ->addWhere('option_group_id', '=', 106)
    ->addWhere('is_active', '=', 0)
    ->execute();
    $location = iterator_to_array($location); 
    $location = array_column($location, 'label');
    return $location;

  }

}
