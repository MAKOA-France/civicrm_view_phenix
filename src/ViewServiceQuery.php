<?php


namespace Drupal\civicrm_view_phenix;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;
/**
 * Class CustomService
 * @package Drupal\civicrm_view_phenix\Services
 */
class ViewServiceQuery {

  /**
   * Get contact Id by address Id
   *
   * @param [type] $addressId
   * @return void
   */
  public function getContactIdByAdresseId ($addressId) {
    return \Civi\Api4\Address::get()
      ->addSelect('contact_id')
      ->addWhere('id', '=', $addressId)
      ->execute()->first()['contact_id'];
  }

  public function isCompanyLabelSE ($contactId) {
    /* return $certificationses = \Civi\Api4\CustomValue::get('Certifications')
      ->addSelect('nom_certification')
      ->addWhere('entity_id', '=', $contactId)
      ->execute()->column('nom_certification'); */
      return \Drupal::database()->query('select nom_certification from  civicrm_value_phx_certification where entity_id = ' . $contactId)->fetchCol();
  }

  /**
   * Get street address / city / postal code / email /phone number BY address Id
   *
   * @param [type] $addressId
   * @return void
   */
  public function getAllAgenceInfoByAdressId($addressId) {
    $street_address_postal_code_city = \Civi\Api4\Address::get(FALSE)
      ->addSelect('street_address', 'postal_code', 'city')
      ->addWhere('id', '=', $addressId)
      ->execute()->first();

    $emails = \Civi\Api4\Email::get(FALSE)
      ->addSelect('id', 'custom.*', '*')
      ->addWhere('contact_id', '=', $this->getContactIdByAdresseId($addressId))
      ->execute()->first()['email'];

    $phones = \Civi\Api4\Phone::get(FALSE)
      ->addSelect('phone')
      ->addWhere('contact_id', '=', $this->getContactIdByAdresseId($addressId))
      ->execute()->first()['phone'];


    return [
      'all_address' => $street_address_postal_code_city,
      'email' => $emails,
      'phone' => $phones
    ];

  }

  /**
   * Check in the database if a contact is deleted
   *
   * @param [type] $contactId
   * @return boolean
   */
  public function isContactDeleted ($contactId) {
    return reset(\Civi\Api4\Contact::get()
    ->addSelect('is_deleted')
    ->addWhere('id', '=', $contactId)
    ->execute()->column('is_deleted'));
  }

  public function orderBy ($query, $field, $direction, $offset) {
    $query->orderby[$offset]['field'] = $field;
    $query->orderby[$offset]['direction'] = $direction;
    return $query;
  }

  public function commonGenericQuery () {
    return \Civi\Api4\Contact::get()
    ->addSelect('id')
    ->addJoin('Phone AS phone', 'INNER')
    ->addJoin('Address AS address', 'INNER')
    ->addJoin('MembershipType AS membership_type', 'LEFT')
    ->addWhere('contact_type', '=', 'Organization')
    ->addWhere('contact_sub_type', '=', 'Cible')
    ->addWhere('phone.is_primary', '=', TRUE)
    ->addWhere('address.is_primary', '=', TRUE)
    ->addWhere('address.geo_code_1', 'IS NOT NULL')
    ->addWhere('address.city', 'IS NOT NULL')
    ->addWhere('is_deleted', '=', FALSE)
    ->addWhere('address.postal_code', 'IS NOT NULL')
    ->execute();
  }

  public function isContactMemberAssocie ($contactID) {
    $string_query = 'select id from civicrm_membership where membership_type_id IN (2, 3, 4) and contact_id = ' . $contactID;
    return \Drupal::database()->query($string_query)->fetch();
  }

  public function isContactAgence ($contactID) {
    return /* \Civi\Api4\Contact::get()
      ->addSelect('id')
      ->addWhere('contact_sub_type', '=', 'Agence')
      ->addWhere('id', '=', $contactID)
      ->execute(); */

      \Drupal::database()->query("select id from civicrm_contact where contact_sub_type = 'Agence' and  id = " . $contactID)->fetch();
  }


  public function getContactOrganizationName ($contactId) {
    $db = \Drupal::database();
    $name = $db->query('select organization_name from civicrm_contact where id = ' . $contactId)->fetchCol()[0];
    if (!$name) {
      \Drupal::service('civicrm')->initialize();
      $contacts = \Civi\Api4\Contact::get(FALSE)
      ->addSelect('display_name')
      ->addWhere('id', '=', $contactId)
      ->execute()->first();
      $name = $contacts ? $contacts['display_name'] : false;
      if (!$name) {
        $contacts = \Civi\Api4\Contact::get(FALSE)
        ->addSelect('organization_name')
        ->addWhere('id', '=', $contactId)
        ->execute()->first();
        $name = $contacts ? $contacts['organization_name'] : false;
      }
    }
    return $name;
  }

  /**
   * Get matériel occasion  linked with the company
   *
   * @param [type] $contactID
   * @return void
   */
  public function getMaterielOccasion ($contactID) {

    $materielOccasion = \Civi\Api4\Contact::get(FALSE)
      ->addSelect('Materiel.nom_occasion:label')
      ->addWhere('id', '=', $contactID)
      ->addOrderBy('Materiel.nom_location:label', 'ASC')
      ->execute()->getIterator();

    $materielOccasion = iterator_to_array($materielOccasion);

    if ($materielOccasion && isset($materielOccasion[0])) {
      $materielOccasion = $materielOccasion[0];
        
      $allMaterielOcc = $materielOccasion['Materiel.nom_occasion:label'];
      if ($allMaterielOcc) {
          $html = '<ul>';
          foreach ($allMaterielOcc as $materiel) {
            $html .= '<li>' . $materiel .  '</li>';
          }
          $html .= '</ul>';
          return $html;
      }
    }
  }
 /**
   * Page Grue à tour filter by location or Montage or Réparation
   *
   * @param [type] $get_filter_secteurs
   * @param [type] $query
   * @return void
   */
  public function GrueATourFilterCheckboxes ($get_filter_secteurs, $query) {
    $conditions = [];
    foreach ($get_filter_secteurs as $get_filter_secteur) {
      switch ($get_filter_secteur) {
        case 'location':
          $column = 'phx_secteur_loueur';
          break;
        case 'montage':
          $column = 'monteur_190';
          break;
        case 'reparation':
          $column = 'phx_secteur_reparateur';
          break;
      }

      if($column) {
        $conditions[] = [
          'field' => 'civicrm_value_phx_secteur.'.$column,
          'value' => '1',
          'operator' => '=',
        ];
      }
    }


    $query->where[] =  [
      'conditions' => $conditions,
      'type' => 'OR',
    ];

    return $query;
  }

  /**
   * Recupère tous les node publicité publié, node correspondant à la page courante, ex si c'est la page vente (url : /annuaire/occasion) ça ajoute une condition de 
   * requete  ==> field_menu like '%vente%', comme ci-dessous
   */
  public function getNodePublicite ($orientation) {
    $currentPath = \Drupal::service('path.current')->getPath();
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'publicite')
      ->condition('field_menu', '%' . $this->pageOccurenceToFieldMenu()[$currentPath] . '%', 'like')
      ->condition('field_orientation', $orientation, '=')
      ->condition('status', 1)
      ->execute();

    return $nids;
  }

  public function getRandomPubVertical () {
    $custom_service = \Drupal::service('civicrm_view_phenix.view_services');
    $nidPublicite = $this->getNodePublicite('verticale');
    if ($nidPublicite) {
      $randomIndex = array_rand($nidPublicite, 1);
      $node = Node::load($nidPublicite[$randomIndex]);
      // $node = Node::load(self::NID_PUB_SIDEBAR_LEFT);
      $getImg = $custom_service->getNodeFieldValue ($node, 'field_publicite');
      
      $publishOn = $custom_service->getNodeFieldValue ($node, 'publish_on');
      $mediaImg = Media::load($getImg);
      $fileId = $custom_service->getNodeFieldValue ($mediaImg, 'thumbnail');
      $file = \Drupal\file\Entity\File::load($fileId);
      $image_path = $custom_service->getNodeFieldValue ($file, 'uri');
      
      
      $getLinkUrl = $custom_service->getNodeFieldValue ($node, 'field_lien_de_la_pu');
      
      // Create a URL object for the image.
      $image_url = \Drupal\Core\Url::fromUri(file_create_url($image_path));
      
      // Generate the renderable array for the image.
      $image_render_array = [
        '#theme' => 'image',
        '#uri' => $image_url->toString(),
        '#alt' => 'Alternative text for the image',
      ];
      
      // Get the Renderer service.
      $renderer = \Drupal::service('renderer');
      
      // Render the image using the Renderer service.
      $image_output = $renderer->renderRoot($image_render_array);
      // dump($image_path, $image_output);
      
      // If you want to return the rendered image as HTML, you can do so:
      $html = \Drupal\Core\Render\Markup::create($image_output);
      
      // dump($html);
      return [
        'pub_url' => $getLinkUrl,
        'pub_img' => $html
      ];


    }

    return false; // No pub
  }

  public function pageOccurenceToFieldMenu () {
    return [
      '/annuaire/alphabetique'              => "Alphabétique", 
      '/annuaire/occasion'                  => "Vente", 
      '/annuaire/location'                  => "Location", 
      '/annuaire/reparation'                => "réparation", 
      '/annuaire/Label_SE'                  =>  "Label SE+",
      '/annuaire/membres-associes'          => "Membres Associés",
      '/annuaire/table-liste-géographique'  => "Géographique",
      '/annuaire/grues_a_tour'              => "Grues à Tour" 
    ];
  }

}