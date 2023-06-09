<?php

namespace Drupal\civicrm_view_phenix\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Language\LanguageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines GetDetailController class.
 */
class GetDetailController
{


  public function getGuideDetail () {
    $request = \Drupal::request();
    $address_id = $request->get('idAddress');
    $database = \Drupal::database();
    $custom_service = \Drupal::service('civicrm_view_phenix.view_services');
    $custom_query_service = \Drupal::service('civicrm_view_phenix.view_query_services');
    $build = '';
    $id = $database->query("SELECT contact_id
      FROM civicrm_address as C
      WHERE C.id = " . $address_id);

    $id = $id->fetch();
    $marker_current_contact_id = $id->contact_id;

      if ($id) {
        $id = $id->contact_id;
        // preg_match('details\/[0-9]+', \Drupal::request()->header->get('referer'), $matches);
        $allInfoAboutCompany = $database->query("SELECT organization_name, phone, street_address, city, postal_code
        FROM civicrm_contact as C
        left join civicrm_address as Ad ON Ad.contact_id = C.id
        left join civicrm_phone as P ON P.contact_id = C.id
        WHERE C.id = " . $id);

        //if the type of company is an "agence" then we will put the link to the parent profile
        $get_contact_type =   $database->query("select contact_sub_type from civicrm_contact where id = " . $id)->fetch();
        if ($get_contact_type) {
          $contact_type = $get_contact_type->contact_sub_type;
          if (strpos($contact_type, 'Agence') !== false) {
            $get_contact_cible =   $database->query("select contact_id_a from civicrm_relationship where relationship_type_id = 32 and contact_id_b = " . $id)->fetch();
            if ($get_contact_cible) {
              $id = $get_contact_cible->contact_id_a;
            }
          }
        }

        $allInfoAboutCompany = $allInfoAboutCompany->fetch();
        $dirigeant = $custom_service->getDirigeant($id);


        //Adding parameters
        $isContactAgence = $custom_query_service->isContactAgence ($marker_current_contact_id);
        if ($isContactAgence) {
          $paramsToAppend = '?agenceId=' . $address_id;
        }else {
          $paramsToAppend = '?addressId=' . $address_id;
        }

        $crypted_id = $custom_service->encryptString($id);
        if ($allInfoAboutCompany) {//Todo hint

          $build .= '<div class="tooltip-map" id="' . $id . '-tooltip">
          <ul style="list-style-type: none;  ">
          <li><a target="_blank" href="/annuaire/details/'. $id . '?token=' . $crypted_id.'">' . $allInfoAboutCompany->organization_name . '</a></li> 
          <li>' . $allInfoAboutCompany->street_address . '</li>
          <li>'. $allInfoAboutCompany->postal_code . '  ' . $allInfoAboutCompany->city . '</li>
          <li>' . $allInfoAboutCompany->phone . '</li>
          <li>' . $dirigeant . '</li>
          <li><a target="_blank" href="/annuaire/details/' . $id . '?token=' . $crypted_id . $paramsToAppend .'"> > Voir la fiche</a></li> 
          </ul>
          </div>';
        }

      }
    return new Response($build);
  }

  public function filterByAddress () {

     return new Response('');
  }

  public function filterByDepartment () {
    $req = \Drupal::request();
    $departmentId = $req->query->get('depId');
    if ($departmentId) {

      $departments = \Drupal::database()->query("SELECT adr.contact_id  FROM civicrm_address as adr LEFT JOIN civicrm_contact as c on c.id = adr.contact_id WHERE
      c.contact_type = 'Organization' and
    adr.postal_code LIKE '". $departmentId . "%'");

      $departments = $departments->fetchAll();
      if ($departments) {
        $departments = array_column($departments, 'contact_id');
        // $departments = array_map('intval', $departments);
        $encodedDprt = json_encode($departments);
        return new Response($encodedDprt);
      }
    }

    return false;
  }

  public function filterAutocomplete() {
    $req = \Drupal::request()->get('q');
    $brands = \Drupal::service('civicrm_view_phenix.view_services')->getMark($req);
    $result = [];
    if ($brands) {
      foreach ($brands as $brand) {
        $results[] = [
            'label' => $brand->label,
            'value' => $brand->label,
            'alt' => $brand->value,
            'data-id' => $brand->value,
        ];
      }
    }

    return new JsonResponse($results);
  }

  public function setDefaultValue() {
    $brandLabel = \Drupal::request()->get('idMarque');
    $database = \Drupal::database();
    if ($brandLabel) {
      $query = $database->query("SELECT * FROM civicrm_option_value where label = '" . $brandLabel . "'");
      $result = $query->fetch();
      if($result) {
        $id = $result->value;
        return new JsonResponse(['id' => $id, 'label' => $brandLabel]);
      }

    }else {
        $getId = \Drupal::request()->get('get_id');
        preg_match('/marque_nom=[0-9]+/i', \Drupal::request()->server->get('HTTP_REFERER'), $output_array);
        if ($getId) {
          $query = $database->query("SELECT * FROM civicrm_option_value where value = '" . $getId . "'");
          $result = $query->fetch();
          if($result) {
              $label = $result->label;
              return new Response($label);
          }

        }
      }

    return new Response (2);

  }

  public function isCompanyACible () {

    $isCible = false;
    $request = \Drupal::request();
    $address_id = $request->get('idAddress');

    return new Response ($isCible);
  }


}
