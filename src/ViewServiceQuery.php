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

  /**
   * Get street address / city / postal code / email /phone number BY address Id
   *
   * @param [type] $addressId
   * @return void
   */
  public function getAllAgenceInfoByAdressId($addressId) {
    $street_address_postal_code_city = \Civi\Api4\Address::get()
      ->addSelect('street_address', 'postal_code', 'city')
      ->addWhere('id', '=', $addressId)
      ->execute()->first();

    $emails = \Civi\Api4\Email::get()
      ->addSelect('id', 'custom.*', '*')
      ->addWhere('contact_id', '=', $this->getContactIdByAdresseId($addressId))
      ->execute()->first()['email'];

    $phones = \Civi\Api4\Phone::get()
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


}
