<?php

class ExtendedUser extends PluginAbstract
{
  /**
   * @var string Name of plugin
   */
  public $name = 'Extended User';

  /**
   * @var string Description of plugin
   */
  public $description = 'Store metadata for users without having to modify the user table in the DB.';

  /**
   * @var string Name of plugin author
   */
  public $author = 'Justin Henry';

  /**
   * @var string URL to plugin's website
   */
  public $url = 'https://uvm.edu/~jhenry/';

  /**
   * @var string Current version of plugin
   */
  public $version = '0.0.1';

  /**
   * Performs install operations for plugin. Called when user clicks install
   * plugin in admin panel.
   *
   */
  public function install()
  {

    // Array of attributes to save to the users_meta table.
    // i.e. we don't need to save name, email, etc there since those are being saved to the users table.
    $attributes = array('homeDirectory', 'ou', 'eduPersonPrimaryAffiliation');

    Settings::set('extended_user_attributes', json_encode($attributes));

    $db = Registry::get('db');
    if (!ExtendedUser::tableExists($db, 'users_meta')) {
      $query = "CREATE TABLE IF NOT EXISTS users_meta (
        meta_id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id bigint(20) NOT NULL,
        meta_key varchar(255) NOT NULL,
        meta_value longtext NOT NULL);";

      $db->query($query);
    }
  }

  /**
   * Performs uninstall operations for plugin. Called when user clicks
   * uninstall plugin in admin panel and prior to files being removed.
   *
   */
  public function uninstall()
  {
    Settings::remove('extended_user_attributes');

    $db = Registry::get('db');
    $drop_meta = "DROP TABLE IF EXISTS users_meta;";
    $db->query($drop_meta);
  }
  /**
   * Attaches plugin methods to hooks in code base
   */
  public function load()
  {
  }
  /**
   * Set user meta data.
   *
   * @param User $user User object.
   *
   */
  public function save($user)
  {
    //TODO: validation & error handling.
    if( class_exists('LDAP') ) {

      // Get directory entry for this user
      $entry = LDAP::get($user->username);
    }

    ExtendedUser::save_meta_attributes($user, $entry);
    ExtendedUser::save_user_attributes($user, $entry);

  }

  /**
   * Save non-meta table attributes to the users table/model.
   *
   * @param User $user User object.
   *
   */
  public function save_meta_attributes($user, $entry)
  {
    $meta_attributes = json_decode( Settings::get('extended_user_attributes') );

    foreach ($entry as $attribute => $value) {
      // Only update/save certain attributes to users_meta table.
      if (in_array($attribute, $meta_attributes)){
        ExtendedUser::save_meta_attribute($user->userId, $attribute, $value);
      }
    }

  }

  /**
   * Save non-meta table attributes to the users table/model.
   *
   * @param User $user User object.
   *
   */
  public function save_user_attributes($user, $ldap)
  {
    // If they don't have an email address listed in the directory, 
    // just use what's there by default in the user object.
    $user->email = $ldap['mail'] ?? $user->email; 
    $user->firstName = $ldap['givenName'] ?? NULL;
    $user->lastName = $ldap['sn'] ?? NULL;
    $user->website = $ldap['labeledURI'] ?? NULL;

    $userMapper = new UserMapper();
    $userMapper->save($user);
  }


  /**
   * Save/Create a single user meta entry.
   * 
   * @param int $user_id Id of the user this meta belongs to 
   * @param string $meta_key reference label for the meta item we are updating
   * @param string $meta_value data entry being updated
   * 
   */
  public static function save_meta_attribute($user_id, $meta_key, $meta_value) {
    
    include_once "UserMeta.php";
    $userMeta = new UserMeta();

    // If there's meta for this file, we want the meta id
    $existing_meta = ExtendedUser::get_all_meta($user_id, $meta_key);
    if($existing_meta){
      $userMeta->meta_id = $existing_meta->meta_id;
    }

    $userMeta->user_id = $user_id;
    $userMeta->meta_key = $meta_key;
    $userMeta->meta_value = $meta_value;
    
    include_once 'UserMetaMapper.php';
    $userMetaMapper = new UserMetaMapper();
    $userMetaMapper->save($userMeta);
  }

  /**
   * Get single meta record for this user
   * 
   * @param int $user_id Id of the user this meta belongs to 
   * @param string $meta_key reference label for the meta item to retrieve
   * @return false if not found 
   */
  public static function get_meta($user_id, $meta_key)
  {
    include_once 'UserMetaMapper.php';
    $mapper = new UserMetaMapper();
    $meta = $mapper->getByCustom(array('user_id' => $user_id, 'meta_key' => $meta_key));
    return $meta;
  }

  /**
   * Get all meta records for this user
   * 
   * @param int $user_id Id of the user this meta belongs to 
   * @param string $meta_key reference label for the meta item to retrieve
   * @return false if not found 
   */
  public static function get_all_meta($user_id)
  {
    include_once 'UserMetaMapper.php';
    $mapper = new UserMetaMapper();
    $meta = $mapper->getAllMeta($user_id);
    return $meta;
  }

  /**
   * Check if a table exists in the current database.
   *
   * @param PDO $pdo PDO instance connected to a database.
   * @param string $table Table to search for.
   * @return bool TRUE if table exists, FALSE if no table found.
   */
  public static function tableExists($pdo, $table)
  {
    // Try a select statement against the table
    // Run it in try/catch in case PDO is in ERRMODE_EXCEPTION.
    try {
      $result = $pdo->basicQuery("SELECT 1 FROM $table LIMIT 1");
    } catch (Exception $e) {
      // We got an exception == table not found
      return FALSE;
    }

    // Result is either boolean FALSE (no table found) or PDOStatement Object (table found)
    return $result !== FALSE;
  }
}
