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
    $db = Registry::get('db');
    if (!UserMetadata::tableExists($db, 'users_meta')) {
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
    $db = Registry::get('db');

    $drop_meta = "DROP TABLE IF EXISTS users_meta;";

    $db->query($drop_meta);
  }

  /**
   * Get user meta entry
   * 
   * @param int $user_id Id of the user this meta belongs to 
   * @param string $meta_key reference label for the meta item to retrieve
   * @return false if not found 
   */
  public static function get($user_id, $meta_key)
  {
    include_once 'UserMetaMapper.php';
    $mapper = new UserMetaMapper();
    $meta = $mapper->getByCustom(array('video_id' => $user_id, 'meta_key' => $meta_key));
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
