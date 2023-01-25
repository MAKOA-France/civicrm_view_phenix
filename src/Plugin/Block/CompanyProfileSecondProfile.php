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
 *  context_definitions = {
 *     "mark" = @ContextDefinition("entity:mark", required = FALSE),
 *  }
 * )
 */
class CompanyProfileSecondProfile  extends BlockBase  {

  /**
   * {@inheritdoc}
   */
  public function build() {
    \Drupal::service('cache.render')->invalidateAll();
    $id = \Drupal::request()->get('arg_0');
    $database = \Drupal::database();

    $contacts = \Civi\Api4\Contact::get()
      ->addSelect('org_dlr.descriptif_entreprise', 'org_dlr.activiteprincipale', 'Materiel.nom_location')
      ->addWhere('id', '=', $id)
      ->execute();
      $description = '';
      if ($contacts) {
        $contacts = $contacts->first();
        $description = $contacts['org_dlr.descriptif_entreprise'];
        $idMainActivity = $contacts['org_dlr.activiteprincipale'];
        $equipmentRental = $contacts['Materiel.nom_location'];
        $mainActivityLabel = \Civi\Api4\ActivityContact::get()
          ->addSelect('contact_id.org_dlr.activiteprincipale:label')
          ->addWhere('contact_id', '=', $id)
          ->setLimit(1)
          ->execute();

        $equipmentRental = implode($equipmentRental, ', ');

        if ($equipmentRental) {
           $rentals = $database->query('SELECT label FROM civicrm_option_value where option_group_id = 106 and  value IN (' . $equipmentRental . ') order by label asc')->fetchAll();
           $materielLocation = '<div class="field-content occasion-details content-fiche"><ul>';

           foreach ($rentals as $rental) {
             $materielLocation .= '<li class="content-fiche">' . $rental->label . '</li>';
            }

            $materielLocation .= '</ul></div>';

          }


          //Get used equipment (materiel d'occasion)
          $usedEquipment = \Civi\Api4\Contact::get()
            ->addSelect('Materiel.nom_occasion')
            ->addWhere('id', '=', $id)
            ->execute();
            if ($usedEquipment) {
              $usedEquipment = $usedEquipment->first()['Materiel.nom_occasion'];
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
      }

      //todo  hook theme (create template twig)


      $htmlUsedEquipments = '<div class="field-content occasion-details content-fiche"><ul>';

       foreach ($allUsedEquipments as $equipment) {
         $htmlUsedEquipments .= '<li class="content-fiche">' . $equipment->label . '</li>';
       }

      $htmlUsedEquipments .= '</ul></div>';


      $materielLocation = $equipmentRental ? ' <strong class="views-label views-label-materiel-occasion title-fiche">Location : </strong>' . $materielLocation : '';

      $materielHtml = $allUsedEquipments ? '<strong class="views-label views-label-materiel-occasion title-fiche">Matériels : </strong>
        ' . $htmlUsedEquipments : '';


      $brandLabel = $distributedBrand ? '<strong class="views-label views-label-marque-nom title-fiche">Marques : </strong>' : '';

      $mainActivityLabelVal = $mainActivityLabel->first()['contact_id.org_dlr.activiteprincipale:label'];
      $mainActivityLabelVal = str_replace('Professionnel DLR : ', '', $mainActivityLabelVal);

      //Check if company is linked with label SE or not
      $tagHtmlForLabelSE = '';
      $isCompanyLabelSE = $this->getQueryService()->isCompanyLabelSE($id);
      if (!empty ($isCompanyLabelSE) && in_array(6, $isCompanyLabelSE)) {
        $tagHtmlForLabelSE = '
        <span class="company-profile-label-se">Label SE+</span>
        <img class="company-profile-img-label-se" src="https://dlr-guide.dev.makoa.net/files/styles/thumbnail/public/2022-07/logo-pages-se_1.png">
        ';
      }

      $isMembreAssocie = $this->getQueryService()->isContactMemberAssocie ($id);
      if ($isMembreAssocie) {
        $tagHtmlForLabelSE = '
        <span class="company-profile-membre-associe">Membre Associé</span>
        <img src="/files/styles/thumbnail/public/2022-07/logoMA.png?itok=vURbgtIU">
               ';
      }

      //Get Matériels d'occasion
      $materiel_occasion_label = $this->getQueryService()->getMaterielOccasion($id);


      $mainActivity = $mainActivityLabel->first() ? '<div class="company-profile-SE">
      <strong class="views-label views-label-materiel-occasion title-fiche">Activité principale : </strong>
      ' . $tagHtmlForLabelSE . '
      <p class="content-fiche"> ' .  $mainActivityLabelVal .  ' </p>' : '</div>';

      $htmlDescription = strlen($description) > 5 ? ' <strong class="views-label views-label-marque-nom title-fiche">Descriptif de l\'entreprise : </strong>
        <div class="content-fiche company-description">' . $description . '</div><br>' : '';

      $materiel_occasion_html = $materiel_occasion_label ? ' <strong class="views-label views-label-marque-nom title-fiche">Matériels d\'occasion : </strong>
        <div class="content-fiche company-description">' . $materiel_occasion_label . '</div>' : '';

      $html = '<div class="second-column company-profile-block">
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

}
