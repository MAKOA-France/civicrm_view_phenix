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
    $id = $request->get('id');
    $database = \Drupal::database();
     /*   $query = $database->query("SELECT * FROM civicrm_value_phx_org_dlr WHERE entity_id = " . $id);
    $result = $query->fetch();
    $companyName = $result->organization_name;


    if (empty($result)) {
      $build = '<div class="tooltip-map" id="' . $id . '-tooltip">
      <ul>
        <li>There is no company there :( </li>
        <li>test 2</li>
        <li>test 3</li>
      </ul>
     </div>';
    }

    $entrepriseId = $result->org_dlr_activiteprincipale;

    //get entreprise name TODO do it with civi4 query later
    $query = $database->query("SELECT * FROM civicrm_contact WHERE id = " . $id);
    $result = $query->fetch();

    $queryMail = $database->query("SELECT * FROM civicrm_email WHERE contact_id = " . $id);
    $resultMail = $queryMail->fetch() ? $queryMail->fetch()->email : '';


    $queryWebsite = $database->query("SELECT * FROM civicrm_website WHERE contact_id = " . $id);
    $resultWebsite = $queryWebsite->fetch() ? $queryWebsite->fetch()->url : '';

    $queryPhoneNumber = $database->query("SELECT * FROM civicrm_phone WHERE contact_id = " . $id);
    $resultPhoneNumber = $queryPhoneNumber->fetch() ? $queryPhoneNumber->fetch()->url : '';



    $queryGetMainActivity = $database->query("SELECT org_dlr_activiteprincipale FROM civicrm_value_phx_org_dlr WHERE entity_id = " . $id);
    $resultIdMainActivity = $queryGetMainActivity->fetch()->org_dlr_activiteprincipale;
    $mainActivityLabel = $resultIdMainActivity ? $database->query("SELECT * FROM civicrm_option_value WHERE value = " . $resultIdMainActivity . " AND option_group_id = 100")->fetch() : '';
    $organizationName = $result->organization_name ? $result->organization_name : 'Non renseigné';

    //temp

    // List all available handlers
    //$handlers = \geocoder_handler_info();
   // $geocoder = \Drupal::service('geofield_map.geocoder')->geocoder($address);

    //temp

      /*   civicrm_api4('Contact', 'get', [
        'select' => [
          'organization_name',
          'addressee_id:name',
          'address.street_address',
          'address.city',
          'phone.*',
        ],
        'join' => [
          ['Address AS address', 'LEFT', ['id', '=', 21293]],
          ['Phone AS phone', 'LEFT'],
        ],
        'where' => [
          ['id', '=', 21293],
        ],
        'limit' => 25,
      ]); */

   // preg_match('details\/[0-9]+', \Drupal::request()->header->get('referer'), $matches);
    $allInfoAboutCompany = $database->query("SELECT organization_name, phone, street_address, city, postal_code
    FROM civicrm_contact as C
    left join civicrm_address as Ad ON Ad.contact_id = C.id
    left join civicrm_phone as P ON P.contact_id = C.id
    WHERE C.id = " . $id);

    $allInfoAboutCompany = $allInfoAboutCompany->fetch();

    if ($allInfoAboutCompany) {

      $build = '<div class="tooltip-map" id="' . $id . '-tooltip">
      <ul style="list-style-type: none;  ">
      <li><a target="_blank" href="/annuaire/details/'.$id.'">' . $allInfoAboutCompany->organization_name . '</a></li>
      <li>' . $allInfoAboutCompany->street_address . '</li>
      <li>'. $allInfoAboutCompany->postal_code . '  ' . $allInfoAboutCompany->city . '</li>
      <li>' . $allInfoAboutCompany->phone . '</li>
      </ul>
      </div>';
    }

 /*   $builded = [
    'id' => $id,
    'build' => $build
   ]; */
//    $html = \Drupal::service('renderer')->render($build);

    return new Response($build);
  }

  public function filterByAddress () {

  // dump();
  // $my_view->pre_execute();
  // return $my_view->render($my_display_name);
// }
    // dump('testings');
    // Geocode an address
   /*    $address = '4925 Gair Ave, Terrace, BC';
    $point = geocoder('google',$address);
    $geoJSON = $point->out('json'); */

    // List all available handlers
    // $handlers = geocoder_handler_info();

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


}
