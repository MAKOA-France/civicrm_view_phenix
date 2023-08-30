<?php


namespace Drupal\civicrm_view_phenix;

use Drupal\Core\Session\AccountInterface;

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
    $name = $db->query('select organization_name from civicrm_contact where id = ' . $contactId)->fetchCol();
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

}