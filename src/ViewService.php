<?php


namespace Drupal\civicrm_view_phenix;

use Drupal\Core\Session\AccountInterface;

/**
 * Class CustomService
 * @package Drupal\mymodule\Services
 */
class ViewService {

  protected $currentUser;

  const PAGE_GEOGRAPHIC_LIMIT = 900;
  const GROUP_ID_MEMBRE_ACTUEL_POUR_LES_CIBLES_ET_AGENCES = 195;
  const GROUP_ID_MEMBRE_ACTUEL_POUR_LES_CIBLES_SEULEMENT = 142;

  const TITTLE_SITE = ' | Annuaire DLR distribution, location, réparation de matériels de chantier';

  /**
   * CustomService constructor.
   * @param AccountInterface $currentUser
   */
  public function __construct(AccountInterface $currentUser) {
    $this->currentUser = $currentUser;
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
    $query = $database->query('select * from civicrm_value_phx_Individual_contact_fonction where contact_fonction_entreprise = ' . $company_id);
    $indiviualContactFonctions = $query->fetch();
    if ($indiviualContactFonctions) {

      $dirigeant_id = $indiviualContactFonctions->entity_id;

      /* $contacts_name = \Civi\Api4\Contact::get()
      ->addSelect('display_name')
      ->addWhere('id', '=', $dirigeant_id)
      ->execute()->first();
      */

      $get_contacts_name = $database->query('select display_name from civicrm_contact where id = ' . $dirigeant_id);
      $contacts_name = $get_contacts_name->fetch();


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
    /* return \Civi\Api4\Contact::get()
    ->addSelect('id')
    ->addWhere('groups', 'IN', [$groupId])
    ->execute()->column('id'); */
    $db = \Drupal::database();
    $res = $db->query('select contact_id from civicrm_group_contact_cache where group_id = ' . $groupId)->fetchCol();
    return $res;
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

  public function geographiqueGetAllAgencesLinkedWithCibleDlr () {
    //Les agences liées à des cibles DLR membres actuels
    $contacts_cible_who_are_in_dynamic_group = $this->getAllContactIDInGroupDyanmicByGroupID(ViewService::GROUP_ID_MEMBRE_ACTUEL_POUR_LES_CIBLES_SEULEMENT);
  // $contacts_cible_who_are_in_dynamic_group = implode (',', $contacts_cible_who_are_in_dynamic_group); 
    if ($contacts_cible_who_are_in_dynamic_group) {

     $all_agence_id_linked_whith_cible_dlr = \Civi\Api4\Relationship::get()
       ->addSelect('contact_id_b')
       ->addWhere('contact_id_a', 'IN', $contacts_cible_who_are_in_dynamic_group)
       ->execute();
       $ids = $all_agence_id_linked_whith_cible_dlr->column('contact_id_');
     $ids = implode(', ', $ids);
     $string_query = 'SELECT C.id from civicrm_contact as C
   left join civicrm_membership as M ON M.contact_id = C.id
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
    AND A.geo_code_1 IS NOT NULL';


   if ($ids) {
     $string_query .= ' AND C.id IN (' . $ids . ')';
   }

   $allContactId =  \Drupal::database()->query($string_query)->fetchAll();
   return array_column($allContactId, 'id');
   } 
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
      return $this->redirectHomePage();
    }
  
    return $decryptedId;
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
      '01' =>  'Ain',
      '02' =>  'Aisne',
      '03' =>  'Allier',
      '04' =>  'Alpes-de-Haute-Provence',
      '05' =>  'Hautes-Alpes',
      '06' =>  'Alpes-Maritimes',
      '07' =>  'Ardèche',
      '08' =>  'Ardennes',
      '09' =>  'Ariège',
      '10' => 'Aube',
      '11' => 'Aude',
      '12' => 'Aveyron',
      '13' => 'Bouches-du-Rhône',
      '14' => 'Calvados',
      '15' => 'Cantal',
      '16' => 'Charente',
      '17' => 'Charente-Maritime',
      '18' => 'Cher',
      '19' => 'Corrèze',
      '2A' => 'Corse-du-Sud',
      '2B' => 'Haute-Corse',
      '21' => 'Côte-dOr',
      '22' => 'Côtes-dArmor',
      '23' => 'Creuse',
      '24' => 'Dordogne',
      '25' => 'Doubs',
      '26' => 'Drôme',
      '27' => 'Eure',
      '28' => 'Eure-et-Loir',
      '29' => 'Finistère',
      '30' => 'Gard',
      '31' => 'Haute-Garonne',
      '32' => 'Gers',
      '33' => 'Gironde',
      '34' => 'Hérault',
      '35' => 'Ille-et-Vilaine',
      '36' => 'Indre',
      '37' => 'Indre-et-Loire',
      '38' => 'Isère',
      '39' => 'Jura',
      '40' => 'Landes',
      '41' => 'Loir-et-Cher',
      '42' => 'Loire',
      '43' => 'Haute-Loire',
      '44' => 'Loire-Atlantique',
      '45' => 'Loiret',
      '46' => 'Lot',
      '47' => 'Lot-et-Garonne',
      '48' => 'Lozère',
      '49' => 'Maine-et-Loire',
      '50' => 'Manche',
      '51' => 'Marne',
      '52' => 'Haute-Marne',
      '53' => 'Mayenne',
      '54' => 'Meurthe-et-Moselle',
      '55' => 'Meuse',
      '56' => 'Morbihan',
      '57' => 'Moselle',
      '58' => 'Nièvre',
      '59' => 'Nord',
      '60' => 'Oise',
      '61' => 'Orne',
      '62' => 'Pas-de-Calais',
      '63' => 'Puy-de-Dôme',
      '64' => 'Pyrénées-Atlantiques',
      '65' => 'Hautes-Pyrénées',
      '66' => 'Pyrénées-Orientales',
      '67' => 'Bas-Rhin',
      '68' => 'Haut-Rhin',
      '69' => 'Rhône',
      '70' => 'Haute-Saône',
      '71' => 'Saône-et-Loire',
      '72' => 'Sarthe',
      '73' => 'Savoie',
      '74' => 'Haute-Savoie',
      '75' => 'Paris',
      '76' => 'Seine-Maritime',
      '77' =>  'Seine-et-Marne',
      '78' =>  'Yvelines',
      '79' =>  'Deux-Sèvres',
      '80' =>  'Somme',
      '81' =>  'Tarn',
      '82' =>  'Tarn-et-Garonne',
      '83' =>  'Var',
      '84' =>  'Vaucluse',
      '85' =>  'Vendée',
      '86' =>  'Vienne',
      '87' =>  'Haute-Vienne',
      '88' =>  'Vosges',
      '89' =>  'Yonne',
      '90' =>  'Territoire de Belfort',
      '91' =>  'Essonne',
      '92' =>  'Hauts-de-Seine',
      '93' =>  'Seine-Saint-Denis',
      '94' =>  'Val-de-Marne',
      '95' =>  'Val-dOise',
      '971' =>  'Guadeloupe',
      '972' =>  'Martinique',
      '973' =>  'Guyane',
      '974' =>  'La Réunion'
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

}
