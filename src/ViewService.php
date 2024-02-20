<?php


namespace Drupal\civicrm_view_phenix;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Qantis\Tools\MkpUrlGenerator;
use Qantis\Tools\UrlGenerator;
use Drupal\Core\Http\Client;


include __DIR__.'/vendor/autoload.php';


/**
 * Class CustomService
 * @package Drupal\mymodule\Services
 */
class ViewService {

  protected $currentUser;

  const PAGE_GEOGRAPHIC_LIMIT = 900;
  const GROUP_ID_MEMBRE_ACTUEL_POUR_LES_CIBLES_ET_AGENCES = 195;
  const GROUP_ID_MEMBRE_ACTUEL_POUR_LES_CIBLES_SEULEMENT = 142;
  const QANTIS_KEY = '57dedf2c57a511ecb88e067e628b0734';
  const QANTIS_DOMAIN = 'https://achats.dlr.fr';

  const TITTLE_SITE = ' | Annuaire DLR distribution, location, réparation de matériels de chantier';

  /**
   * CustomService constructor.
   * @param AccountInterface $currentUser
   */
  public function __construct(AccountInterface $currentUser) {
    $this->currentUser = $currentUser;
  }

  /**
   * Recherche par nom officiel ou Nom de l'organisation
   */
  public function searchByOrganizationNameOrLegalName ($query, $table, $keyword) {
    $query->where[] =  array(
      'conditions' => array(
        array(
        'field' => $table. '.legal_name',
        'value' => "%$keyword%",
        'operator' => "LIKE",
      ),
      array(
        'field' => $table. '.organization_name',
        'value' => "%$keyword%",
        'operator' => "LIKE",
      ),
    ),
    'type' => 'OR',
  );
}

  public function numericFilter ($query, $generate_html) {
    $req = \Drupal::request();
    $current_uri = $req->getRequestUri();
      $number = '0-9';
      //replace by
      $url = '';
      if ($query) {
        if ((strpos ($query, 'letter' ) === false) ) {
          $url = $current_uri . '&letter='. $number;
        }else {
          preg_match('/letter=[a-zA-Z0-9]+/', $current_uri, $matches_number_without_e_commercial);
          $previous_value = $matches_number_without_e_commercial[0];
          $current_uri = str_replace($previous_value, '', $current_uri);
          if (substr($current_uri, -1) == '&') {
            $current_uri = str_replace('&', '', $current_uri);
          }

          $url = $current_uri . '&letter='. $number;
        }
      } else {
        $url = $current_uri . '?letter='. $number;
      }


      //add a condition to allow the css to detect the active letter for the filter
      $get_number = $req->query->get('letter');
     // dump([($get_number == $number) => [$get_number, $number]]);
      $is_active = (($get_number !== false) && ($get_number == '0-9')) ? 'active-letter' : ' ';
       $generate_html .= '<span>
        <a data-active-letter="' .$is_active . '" data-current-uri="' . $current_uri . '" href="' . $url . '" class="filter-by-letter is-active">' . $number . '</a>
      </span>';

     return $generate_html;
  }

  public function alphabeticFilter ($query, $generate_html) {
    $req = \Drupal::request();
    $current_uri = $req->getRequestUri();
    foreach (range('A', 'Z') as $letter) {
      $url = '';
      if ($query) {
        if ((strpos ($query, 'letter' ) === false) ) {
          $url = $current_uri . '&letter='. $letter;
        }else {
          preg_match('/letter=[a-zA-Z0-9]+/', $current_uri, $matches_letter_without_e_commercial);
          $previous_value = $matches_letter_without_e_commercial[0];
          $current_uri = str_replace($previous_value, '', $current_uri);
          $url = $current_uri . '&letter='. $letter;
        }
      } else {
        $url = $current_uri . '?letter='. $letter;
      }


      //add a condition to allow the css to detect the active letter for the filter
      $get_letter = $req->query->get('letter');
      $is_active = ($get_letter && ($get_letter == $letter)) ? 'active-letter' : ' ';
       $generate_html .= '<span>
        <a data-active-letter="' .$is_active . '" data-current-uri="' . $current_uri . '" href="' . $url . '" class="filter-by-letter is-active">' . $letter . '</a>
      </span>';
     }

     return $generate_html;
  }

  public function addWhereQuery ($query, $value, $table, $field, $operator) {
    $query->where[] =  array(
      'conditions' => array(
        array(
        'field' => $table . '.' . $field,
        'value' => $value,
        'operator' => $operator,
      ),
    ),
    'type' => 'AND',
  );
  }

  public function getMark ($label) {
   /*  return
 $optionValues = \Civi\Api4\OptionValue::get()
  ->addSelect('id', 'option_group_id', 'label')
  ->addWhere('option_group_id', '=', 105)
  ->addWhere('is_reserved', '=', TRUE)
  ->execute();*/


    $database = \Drupal::database();
    $query = $database->query("SELECT * FROM civicrm_option_value WHERE   option_group_id = 105 /*and is_reserved = 1*/ AND label  LIKE '". $label ."%' ");
    return $query->fetchAll();
  }

  public function alterViewFieldRender ($field, $fieldName, &$variables) {
    $row = $variables['row'];
    if ($field->field == $fieldName) {
      $value = $field->getValue($row);
      if ($value) {
        $variables['output'] = ['#markup' => '<p class="content-fiche mb-0">' . $value . ' &nbsp;</p>'];
      }
    }
  }

  /**
   * Check in database if a company has lat/long
   *
   * @param [type] $id
   * @return void
   */
  public function checkIfCompanyHasLatAndLongitude($id) {
    $res = \Civi\Api4\Address::get()
    ->addSelect('geo_code_1', 'geo_code_2')
    ->addWhere('contact_id', '=', $id)
    ->execute()->first();
    $hasLatitude = $res['geo_code_1'];
    //$hasLongitude = $res['geo_code_2'];
    $hasLatitude = \Drupal::database()->query(' select geo_code_1, geo_code_2 from civicrm_address where contact_id = ' . $id)->fetchAll();
    return $hasLatitude;
  }

  public function getLatitude ($id) {
    return civicrm_api4('Address', 'get', [
      'select' => [
        'geo_code_1',
      ],
      'where' => [
        ['contact_id', '=', trim($id)],
      ],
    ]);

  }

  public function getLongitude ($id) {
    return civicrm_api4('Address', 'get', [
      'select' => [
        'geo_code_2',
      ],
      'where' => [
        ['contact_id', '=', trim($id)],
      ],
    ]);
  }

  public function getDirigeant ($company_id) {
    $database = \Drupal::database();
    $query = $database->query("select * from civicrm_value_phx_Individual_contact_fonction where contact_fonction_fonction = '1' and contact_fonction_entreprise = " . $company_id);
    $indiviualContactFonctions = $query->fetch();
    // dump($indiviualContactFonctions, $company_id);
    if ($indiviualContactFonctions) {

      $dirigeant_id = $indiviualContactFonctions->entity_id;

      /* $contacts_name = \Civi\Api4\Contact::get()
      ->addSelect('display_name')
      ->addWhere('id', '=', $dirigeant_id)
      ->execute()->first();
      */

      $get_contacts_name = $database->query('select display_name from civicrm_contact  where is_deleted = 0 and id = ' . $dirigeant_id);
      $contacts_name = $get_contacts_name->fetch();
// dump($contacts_name);

      if ($contacts_name) {
        return $contacts_name->display_name;
      }
    }
  }

  public function doesCompanyHasAcronym () {
    $request = \Drupal::request();
    $database = \Drupal::database();
    $organisation_name = $request->query->get('organization_name');
    return $database->query("SELECT nick_name FROM civicrm_contact where nick_name = '" . $organisation_name . "'")->fetchAll();
  }

  public function getAgenceLinkedWithCompany ($companyId) {
    return civicrm_api3('Relationship', 'get', [
      'contact_id_b' => $companyId,
      'contact_id_a.contact_type' => "Organization",
      'is_active' => 1,
    // 'relationship_type_id' => $relationship_type_id,
      'return' => ['id', 'contact_id_a', 'relationship_type_id.name_a_b', 'geo_code_1', 'geo_code_2'],
      'option.limit' => 0,
      'option.sort' => 'contact_id_a.display_name',
    ]);
  }

  public function getCompanyCibleByAgenceId ($agenceId) {
    $db = \Drupal::database();
    $res = $db->query('select contact_id_b from civicrm_relationship where contact_id_a = ' .   $agenceId . ' and relationship_type_id = 32')->fetchCol();
    return $res;
  }

  public function getContactTypeById ($id) {
    $db = \Drupal::database();
    $res = $db->query('select contact_sub_type from civicrm_contact where id = ' . $id)->fetchCol();
    return $res;
  }

  public function getContactNameById($contactId) {
    return \Civi\Api4\Contact::get()
    ->addSelect('display_name')
    ->addWhere('id', '=', $contactId)
    ->execute()->first();
  }


  public function getAllContactIDInGroupDyanmicByGroupID ($groupId) {
    return \Civi\Api4\Contact::get(FALSE)
      ->addSelect('id')
      ->addWhere('groups', 'IN', [$groupId])
      ->execute()->column('id');
  }

  /**
   * Undocumented function
   *
   * @param [type] $query
   * @param [type] $currentTable --> table courant
   * @param [type] $targetTable  --> table à joindre
   * @param [type] $tableAlias
   * @param [type] $foreign_id
   * @param [type] $id
   * @return void
   */
  public function joinTable ($query, $currentTable, $targetTable, $tableAlias, $foreign_id, $id) {
    $definition = [
      'table' => $targetTable,
      'field' => $foreign_id,
      'left_table' => $currentTable,
      'left_field' => $id,
    ];
    $join = \Drupal::service('plugin.manager.views.join')->createInstance('standard', $definition);
    return $query->addRelationship($tableAlias, $join, $currentTable);

  }

  public function innerJoinTable ($query, $currentTable, $targetTable, $tableAlias, $foreign_id, $id) {
    $definition = [
      'type' => 'INNER',
      'table' => $targetTable,
      'field' => $foreign_id,
      'left_table' => $currentTable,
      'left_field' => $id,
    ];
    $join = \Drupal::service('plugin.manager.views.join')->createInstance('standard', $definition);
    return $query->addRelationship($tableAlias, $join, $currentTable);

  }

  public function isDirigeantVisible ($idContact) {
    return \Civi\Api4\Contact::get()
    ->addSelect('indiviual_dlr.contact_visible_site')
    ->addWhere('id', '=', $idContact)
    ->execute()->column('indiviual_dlr.contact_visible_site');
  }



  /**
   * Get all contact type cible /
   */
  public function getContactIdByBasicCommonGenericQueryFilters () {
    $contacts_cible_who_are_in_dynamic_group = $this->getAllContactIDInGroupDyanmicByGroupID(ViewService::GROUP_ID_MEMBRE_ACTUEL_POUR_LES_CIBLES_SEULEMENT);
    $contacts_cible_who_are_in_dynamic_group = implode (',', $contacts_cible_who_are_in_dynamic_group);
    $string_query = 'SELECT C.id from civicrm_contact as C
    inner join civicrm_address as A ON A.contact_id = C.id
    inner join civicrm_phone as P ON P.contact_id = C.id
    inner join civicrm_membership as M ON M.contact_id = C.id
    inner join civicrm_value_phx_org_annuaireenligne AN ON C.id = AN.entity_id
    WHERE contact_type = \'Organization\'
    AND contact_sub_type = \'Cible\'
    AND A.is_primary = 1
    AND P.is_primary = 1
    -- AND  A.city IS NOT NULL
    AND  C.is_deleted = 0
    -- AND A.postal_code IS NOT NULL
    AND A.geo_code_1 IS NOT NULL
    AND M.membership_type_id IN (1, 2, 3, 5)
    AND AN.org_annuaireenligne_DLR = 1
     AND C.id IN (' . $contacts_cible_who_are_in_dynamic_group . ')';


    $allContactId =  \Drupal::database()->query($string_query)->fetchAll();
    return array_column($allContactId, 'id');
  }



  /**
   *
   */
  public function getIdContactAlphabetiqueForVerification () {
    $contacts_cible_who_are_in_dynamic_group = $this->getAllContactIDInGroupDyanmicByGroupID(ViewService::GROUP_ID_MEMBRE_ACTUEL_POUR_LES_CIBLES_SEULEMENT);
    $contacts_cible_who_are_in_dynamic_group = implode (',', $contacts_cible_who_are_in_dynamic_group);
    $string_query = 'SELECT C.id from civicrm_contact as C
    inner join civicrm_address as A ON A.contact_id = C.id
    inner join civicrm_phone as P ON P.contact_id = C.id
    inner join civicrm_membership as M ON M.contact_id = C.id
    inner join civicrm_value_phx_org_annuaireenligne AN ON C.id = AN.entity_id
    WHERE contact_type = \'Organization\'
    AND C.contact_sub_type = \'Cible\'
    AND A.is_primary = 1
    AND P.is_primary = 1
    AND  A.city IS NOT NULL
    AND  C.is_deleted = 0
    AND A.postal_code IS NOT NULL
    AND A.geo_code_1 IS NOT NULL
    AND M.membership_type_id IN (1)
    AND AN.org_annuaireenligne_DLR = 1';
    // AND C.id IN (' . $contacts_cible_who_are_in_dynamic_group . ')


    $allContactId =  \Drupal::database()->query($string_query)->fetchAll();
    return array_column($allContactId, 'id');
  }


  /**
   * Recuperation des contacts agences liés avec des cibles dlr 
   */
  public function geographiqueGetAllAgencesLinkedWithCibleDlr () {
    //Recuperation des id contact qui sont membre dlr (membership_type_id = 1) et status_id (nouveau , courant, delai de grace, en instance)
    $queryMember = 'select distinct contact_id from civicrm_membership where status_id IN (1,2,3,5 ) and membership_type_id = 1;';
    $resMember = \Drupal::database()->query($queryMember)->fetchAll();
    $resMember = array_column($resMember, 'contact_id');
    $resMember = implode(', ', $resMember);

    //Recuperation des agences
    $queryRelation = 'select contact_id_a from civicrm_relationship where relationship_type_id = 32 and contact_id_b in (' . $resMember . ')';
    $queryRelation = \Drupal::database()->query($queryRelation)->fetchAll();
    $queryRelation = array_column($queryRelation, 'contact_id_a');
    $ids = implode(', ', $queryRelation);

    $string_query = 'SELECT C.id from civicrm_contact as C
      left join civicrm_value_phx_org_annuaireenligne AN ON C.id = AN.entity_id

      left join civicrm_address as A ON A.contact_id = C.id
      left join civicrm_phone as P ON P.contact_id = C.id

      WHERE C.contact_sub_type = \'Agence\'
        AND C.contact_type = \'Organization\'
        AND AN.org_annuaireenligne_DLR = 1
        AND A.is_primary = 1
        AND P.is_primary = 1
      -- AND A.city IS NOT NULL
        AND C.is_deleted = 0
      -- AND A.postal_code IS NOT NULL
        /* AND A.geo_code_1 IS NOT NULL */';


  if ($ids) {
    $string_query .= ' AND C.id IN (' . $ids . ')';
  }

  $allContactId =  \Drupal::database()->query($string_query)->fetchAll();
  return array_column($allContactId, 'id');
 }

  public function CibleMembreActuel () {
    $contacts_cible_who_are_in_dynamic_group = $this->getAllContactIDInGroupDyanmicByGroupID(ViewService::GROUP_ID_MEMBRE_ACTUEL_POUR_LES_CIBLES_SEULEMENT);
    return $contacts_cible_who_are_in_dynamic_group;
  }

  
 /**
   * 
   */
  public function encryptString($id) {
    $cipher = 'AES-256-CBC';
      $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
      $encrypted = openssl_encrypt($id, $cipher, 'makoa_phenix', OPENSSL_RAW_DATA, $iv);
      return bin2hex($iv . $encrypted);
  }
  
  /**
   * Redirect to the homepage
   */
  public function redirectHomePage () {
    $response = new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal\Core\Url::fromRoute('<front>')->toString());
    return $response->send();
  }
  
  /**
   * 
   */
  public function decryptString($encryptedId) {
    $cipher = 'AES-256-CBC';
      $data = hex2bin($encryptedId);
      $iv = substr($data, 0, openssl_cipher_iv_length($cipher));
      $encrypted = substr($data, openssl_cipher_iv_length($cipher));
      $decryptedId = openssl_decrypt($encrypted, $cipher, 'makoa_phenix', OPENSSL_RAW_DATA, $iv);
  
    if (!is_numeric($decryptedId)) {
      // return $this->redirectHomePage();
    }
  
    return $decryptedId;
  }

/**
 * 
 */
public function getNameEntrepriseById ($idContact) {
  $legalName = \Civi\Api4\Contact::get(FALSE)
  ->addSelect('legal_name')
  ->addWhere('id', '=', $idContact)
  ->execute()->first()['legal_name'];
  if ($legalName) {
    return $legalName;
  }
  $display_name = \Civi\Api4\Contact::get(FALSE)
  ->addSelect('display_name')
  ->addWhere('id', '=', $idContact)
  ->execute()->first()['display_name'];
  return $display_name;
}


/**
 * Contac Cible dlr membre standard actuel
 */
public function getAdherentMembreStandardActuel () {
  $cibles = \Civi\Api4\Contact::get(FALSE)
  ->addSelect('id')
  ->addJoin('Membership AS membership', 'LEFT')
  ->addWhere('membership.membership_type_id', '=', 1)
  ->addWhere('contact_type', '=', 'Organization')
  ->addWhere('contact_sub_type', '=', 'Cible')
  ->addWhere('is_deleted', '=', FALSE)
  ->addWhere('membership.status_id', 'IN', [1, 3, 5, 2])
  ->addWhere('org_annuaireenligne.annuaireenligne_DLR', '=', 1)
  ->execute()->getIterator();
  $cibles = iterator_to_array($cibles); 
  $cibles = array_column($cibles, 'id'); 
  
  return $cibles;
}

/**
 * Contact cible membre associés
 */
public function getContactCibleMembreAssocies () {
  $cibleAssocie = \Civi\Api4\Contact::get(FALSE)
  ->addSelect('id')
  ->addJoin('Membership AS membership', 'LEFT')
  ->addWhere('membership.membership_type_id', 'IN', [2, 3, 4])
  ->addWhere('contact_type', '=', 'Organization')
  ->addWhere('contact_sub_type', '=', 'Cible')
  ->addWhere('is_deleted', '=', FALSE)
  ->addWhere('membership.status_id', 'IN', [1, 3, 5, 2])
  ->addWhere('org_annuaireenligne.annuaireenligne_DLR', '=', 1)
  ->execute()->getIterator();
  $cibleAssocie = iterator_to_array($cibleAssocie); 
  $cibleAssocie = array_column($cibleAssocie, 'id'); 
  return $cibleAssocie;
}


/**
 * Tous les ids contact dans alphabetique
 */
public function getContactAlphabetique () {
  $contacts = \Civi\Api4\Contact::get(FALSE)
  ->addSelect('id')
  ->addJoin('Membership AS membership', 'LEFT')
  ->addJoin('Address AS address', 'LEFT', ['address_primary', '=', 1])
  ->addJoin('Phone AS phone', 'LEFT', ['phone_primary', '=', 1])
  ->addGroupBy('id')
  ->addWhere('contact_type', '=', 'Organization')
  ->addWhere('contact_sub_type', '=', 'Cible')
  ->addWhere('is_deleted', '=', FALSE)
  ->addWhere('membership.status_id', 'IN', [2, 3, 1, 5])
  ->addWhere('org_annuaireenligne.annuaireenligne_DLR', '=', 1)
  ->addWhere('membership.membership_type_id', '=', 1)
  ->execute()->getIterator();
  $contacts = iterator_to_array($contacts);
  if ($contacts) {
    $idscontact = array_column($contacts, 'id');
    return $idscontact;
  }
  return [];
}


/**
 *  Tous les ids contact pour la page membre associé
 */
public function getContactMembreAssocie () {
  $contacts = \Civi\Api4\Contact::get(FALSE)
  ->addSelect('id')
  ->addJoin('Membership AS membership', 'LEFT')
  ->addJoin('Address AS address', 'LEFT', ['address.is_primary', '=', 1])
  ->addJoin('Phone AS phone', 'LEFT', ['address.is_primary', '=', 1])
  ->addGroupBy('id')
  ->addWhere('membership.membership_type_id', 'IN', [2, 3, 4])
  ->addWhere('contact_type', '=', 'Organization')
  ->addWhere('contact_sub_type', '=', 'Cible')
  ->addWhere('is_deleted', '=', FALSE)
  ->addWhere('membership.status_id', 'IN', [2, 1, 3, 5])
  ->addWhere('org_annuaireenligne.annuaireenligne_DLR', '=', 1)
  ->execute()->getIterator();
  $contacts = iterator_to_array($contacts);
  if ($contacts) {
    $idscontact = array_column($contacts, 'id');
    return $idscontact;
  }
  return [];
}


/**
 *  Tous les ids contact pour la page membre associé
 */
public function getContactVente () {
  $contacts = \Civi\Api4\Contact::get(FALSE)
  ->addSelect('id')
  ->addJoin('Membership AS membership', 'LEFT')
  ->addJoin('Address AS address', 'LEFT', ['address.is_primary', '=', 1])
  ->addJoin('Phone AS phone', 'LEFT', ['address.is_primary', '=', 1])
  ->addJoin('Custom_Secteurs AS custom_secteurs', 'LEFT')
  ->addGroupBy('id')
  ->addWhere('membership.membership_type_id', 'IN', [2, 3, 4, 1])
  ->addWhere('contact_type', '=', 'Organization')
  ->addWhere('contact_sub_type', '=', 'Cible')
  ->addWhere('is_deleted', '=', FALSE)
  ->addWhere('membership.status_id', 'IN', [2, 1, 3, 5])
  ->addWhere('org_annuaireenligne.annuaireenligne_DLR', '=', 1)
  ->addClause('OR', ['custom_secteurs.Secteur_occasion', '=', 1], ['custom_secteurs.Secteur_distributeur', '=', 1])
  ->addOrderBy('organization_name', 'ASC')
  ->execute()->getIterator();
  $contacts = iterator_to_array($contacts);
  if ($contacts) {
    $idscontact = array_column($contacts, 'id');
    return $idscontact;
  }
  return [];
}

/**
 * Recupère le site web via id contact
 */
public function getWebsiteApiV4ById($contactId) {
  
  return $getWebsiteByQuery = \Civi\Api4\Website::get(FALSE)
  ->addSelect('url')
  ->addWhere('contact_id', '=', $contactId)
  ->execute()->column('url')[0];

}

/**
 * Undocumented function
 *
 * @return void
 */
  public function sousFamille () {
    return [
      1 => 'Air comprimé',
      2 => 'Blindage',
      3 => 'Carrière',
      4 => 'Chariot industriel',
      5 => 'Chariot télescopique',
      6 => 'Compactage',
      6 => 'Compactage',
      8 => 'Fournitures électriques & éclairage',
      10 => 'Echafaudage',
      11 => 'Forage/Sondage/Injection',
      12 => 'Forage horizontal & trancheuses',
      13 => 'Grues à tour',
      14 => 'Hébergement, base-vie',
      15 => 'Levage de charge',
      16 => 'Métronomie/Controle',
      17 => 'Nacelle/Plateforme Elévatrice',
      18 => 'Perforation/Abattage',
      19 => 'Pompage',
      20 => 'Nettoyage',
      21 => 'Sécurité, environnement',
      22 => 'Sciage',
      23 => 'Rabotage',
      24 => 'Second oeuvre',
      25 => 'Outillage électroportatif',
      26 => 'Signalisation, accès, stabilisation',
      27 => 'Terrassement',
      28 => 'Traitement surface et sol',
      29 => 'Traitement béton/Projection',
      39 => 'Etaiement',
      40 => 'Sanitaire, hygiène',
      42 => 'Coffrage',
      43 => 'Soudage',
      44 => 'Démolition',
      46 => 'Drones',
      47 => 'Groupe électrogène',
      48 => 'Maritime & fluviale',
      49 => 'Véhicules électriques',
      50 => 'Route',
      51 => 'Recyclage, concassage, criblage',
      52 => 'Unités mobiles de décontamination',
      60 => 'Brumisateurs',
      61 => 'Sablage',
      62 => 'Toilettes sèches',
      63 => 'Topographie',
      64 => 'Laser',
      65 => 'Camion-benne',
      66 => 'Fourgon',
      67 => 'Remorques',
      68 => 'Chauffage',
      69 => 'Climatisation',
      70 => 'Décoration',
      71 => 'Bricolage',
      72 => 'Coupe et broyage',
      73 => 'Taille et entretien',
      74 => 'Agriculture',
      75 => 'Transport des végétaux',
      76 => 'Préparation des sols',
      77 => 'Tentes, Chapiteaux, Barnums…',
      78 => 'Mobilier',
      79 => 'Cuisine professionnelle',
      80 => 'Audio-visuel',

    ];
  }

  public function  allDepartment () {

    return
    [
      'All' => t('Choisir departement'),
      '01' =>  'Ain - 01',
      '02' =>  'Aisne - 02',
      '03' =>  'Allier - 03',
      '04' =>  'Alpes-de-Haute-Provence - 04',
      '05' =>  'Hautes-Alpes - 05',
      '06' =>  'Alpes-Maritimes - 06',
      '07' =>  'Ardèche - 07',
      '08' =>  'Ardennes - 08',
      '09' =>  'Ariège - 09',
      '10' => 'Aube - 10',
      '11' => 'Aude - 11',
      '12' => 'Aveyron - 12',
      '13' => 'Bouches-du-Rhône - 13',
      '14' => 'Calvados - 14',
      '15' => 'Cantal - 15',
      '16' => 'Charente - 16',
      '17' => 'Charente-Maritime - 17',
      '18' => 'Cher - 18',
      '19' => 'Corrèze - 19',
      '2A' => 'Corse-du-Sud - 2A',
      '2B' => 'Haute-Corse - 2B',
      '21' => 'Côte-dOr - 21',
      '22' => 'Côtes-dArmor - 22',
      '23' => 'Creuse - 23',
      '24' => 'Dordogne - 24',
      '25' => 'Doubs - 25',
      '26' => 'Drôme - 26',
      '27' => 'Eure - 27',
      '28' => 'Eure-et-Loir - 28',
      '29' => 'Finistère - 29',
      '30' => 'Gard - 30',
      '31' => 'Haute-Garonne - 31',
      '32' => 'Gers - 32',
      '33' => 'Gironde - 33',
      '34' => 'Hérault - 34',
      '35' => 'Ille-et-Vilaine - 35',
      '36' => 'Indre - 36',
      '37' => 'Indre-et-Loire - 37',
      '38' => 'Isère - 38',
      '39' => 'Jura - 39',
      '40' => 'Landes - 40',
      '41' => 'Loir-et-Cher - 41',
      '42' => 'Loire - 42',
      '43' => 'Haute-Loire - 43',
      '44' => 'Loire-Atlantique - 44',
      '45' => 'Loiret - 45',
      '46' => 'Lot - 46',
      '47' => 'Lot-et-Garonne - 47',
      '48' => 'Lozère - 48',
      '49' => 'Maine-et-Loire - 49',
      '50' => 'Manche - 50',
      '51' => 'Marne - 51',
      '52' => 'Haute-Marne - 52',
      '53' => 'Mayenne - 53',
      '54' => 'Meurthe-et-Moselle - 54',
      '55' => 'Meuse - 55',
      '56' => 'Morbihan - 56',
      '57' => 'Moselle - 57',
      '58' => 'Nièvre - 58',
      '59' => 'Nord - 59',
      '60' => 'Oise - 60',
      '61' => 'Orne - 61',
      '62' => 'Pas-de-Calais - 62',
      '63' => 'Puy-de-Dôme - 63',
      '64' => 'Pyrénées-Atlantiques - 64',
      '65' => 'Hautes-Pyrénées - 65',
      '66' => 'Pyrénées-Orientales - 66',
      '67' => 'Bas-Rhin - 67',
      '68' => 'Haut-Rhin - 68',
      '69' => 'Rhône - 69',
      '70' => 'Haute-Saône - 70',
      '71' => 'Saône-et-Loire - 71',
      '72' => 'Sarthe - 72',
      '73' => 'Savoie - 73',
      '74' => 'Haute-Savoie - 74',
      '75' => 'Paris - 75',
      '76' => 'Seine-Maritime - 76',
      '77' =>  'Seine-et-Marne - 77',
      '78' =>  'Yvelines - 78',
      '79' =>  'Deux-Sèvres - 79',
      '80' =>  'Somme - 80',
      '81' =>  'Tarn - 81',
      '82' =>  'Tarn-et-Garonne - 82',
      '83' =>  'Var - 83',
      '84' =>  'Vaucluse - 84',
      '85' =>  'Vendée - 85',
      '86' =>  'Vienne - 86',
      '87' =>  'Haute-Vienne - 87',
      '88' =>  'Vosges - 88',
      '89' =>  'Yonne - 89',
      '90' =>  'Territoire de Belfort - 90',
      '91' =>  'Essonne - 91',
      '92' =>  'Hauts-de-Seine - 92',
      '93' =>  'Seine-Saint-Denis - 93',
      '94' =>  'Val-de-Marne - 94',
      '95' =>  'Val-dOise - 95',
      '971' =>  'Guadeloupe - 971',
      '972' =>  'Martinique - 972',
      '973' =>  'Guyane - 973',
      '974' =>  'La Réunion - 974'
    ];
  }

  
  public function getNodeFieldValue ($node, $field) {
    $value = '';
    $getValue = $node->get($field)->getValue();
    if (!empty($getValue)) {
      if (isset($getValue[0]['target_id'])) { //For entity reference (img / taxonomy ...)
        $value = $getValue[0]['target_id'];
      }elseif (isset($getValue[0]['value']))  { //For simple text / date
        $value = $getValue[0]['value'];
      }else if(isset($getValue[0]['uri'])) {
        $value = $getValue[0]['uri'];
      }else { //other type of field

      }
    }
    return $value;
  }

  /**
   * 
   */
  public function getMembreAssociePointForts ($id) {
    return \Civi\Api4\Contact::get(FALSE)
      ->addSelect('org_dlr.points_forts')
      ->addWhere('id', '=', $id)
      ->execute()->first()['org_dlr.points_forts'];
  }

  
  /**
   * Récupère les contact ids qui sont "Location"
   */
  public function getContactLocation () {
    return \Civi\Api4\Contact::get(FALSE)
      ->addJoin('Membership AS membership', 'LEFT')
      ->addWhere('membership.membership_type_id', 'IN', [1])
      ->addWhere('contact_type', '=', 'Organization')
      ->addWhere('contact_sub_type', '=', 'Cible')
      ->addWhere('membership.status_id', 'IN', [1, 2, 3, 5])
      ->addWhere('is_deleted', '=', FALSE)
      ->addWhere('org_annuaireenligne.annuaireenligne_DLR', '=', 1)
      ->addWhere('org_dlr.activiteprincipale', 'IN', [51, 74, 54])
      ->execute()->column('id');
  }

  /**
   * Permet de generer un lien marketplace qui redirige l'user vers l'espace marketplace de qantis sans se connecter
   * doc github : https://github.com/GroupeQantis/urlGenerator#marketplace-qantis
   */
  public function generateUlrMarketPlace (&$build) {
    $user = \Drupal::currentUser();
    $build['#cache']['max-age'] = 0;
    // Get the email address of the current user.
    $email = $user->getEmail();
    if ($email) {
      // $urlGeneratorPlateform = new UrlGenerator(self::QANTIS_KEY);
      // $urlPlateform = $urlGeneratorPlateform($email);
      //Générer un url 
      $urlGeneratorMarket = new MkpUrlGenerator(self::QANTIS_KEY);
      $urlMarket = $urlGeneratorMarket($email);
      // Décoder les caractères spéciaux de l'URL
      $urlMarket = urldecode($urlMarket);
      //TODO dans le futur  les appels devront être effectuer sur le nom de domaine https://achats.dlr.fr/$urlMarket
      // $build['#suffix'] = '<a href="https://qantis.co' . $urlPlateform . '">lien connexion</a>';
      $response = file_get_contents(self::QANTIS_DOMAIN . $urlMarket);
      if ($response) {
        $url = json_decode($response)->url;
        $build['#prefix'] = '<a id="link-market-place" class="link-market-place" target="__blank" href="' . $url . '">lien marketplace</a>';
      }
    }
    return $build;
  }
}
