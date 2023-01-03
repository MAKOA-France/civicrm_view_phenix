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
  const TOUT_MEMBRE_ACTUEL_ID = 195;

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
   /*  return \Civi\Api4\CustomValue::get('Marques')
    ->addSelect('nom_Marque', 'nom_Marque:label')
    ->addOrderBy('nom_Marque:label', 'ASC')
    ->setLimit(25)
    ->execute(); */


    //TODO HERE
    $database = \Drupal::database();
    $query = $database->query("SELECT * FROM civicrm_option_value WHERE option_group_id = 105 and label  LIKE '". $label ."%' ");
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
      'contact_id_a' => $companyId,
      'contact_id_b.contact_type' => "Organization",
      'is_active' => 1,
    // 'relationship_type_id' => $relationship_type_id,
      'return' => ['id', 'contact_id_b', 'relationship_type_id.name_a_b', 'geo_code_1', 'geo_code_2'],
      'option.limit' => 0,
      'option.sort' => 'contact_id_b.display_name',
    ]);
  }

  public function getCompanyCibleByAgenceId ($agenceId) {
    return \Civi\Api4\Relationship::get()
    ->addWhere('contact_id_b', '=', $agenceId)
    ->addWhere('relationship_type_id', '=', 32)
    ->execute()->column('contact_id_a');
  }

  public function getContactTypeById ($id) {
    return \Civi\Api4\Contact::get()
        ->addSelect('contact_sub_type')
        ->addWhere('id', '=', $id)
        ->execute();
  }

  public function getContactNameById($contactId) {
    return \Civi\Api4\Contact::get()
    ->addSelect('display_name')
    ->addWhere('id', '=', $contactId)
    ->execute()->first();
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


}
