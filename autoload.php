<?php
//require_once('splClassLoader.php');
ini_set('date.timezone','UTC');
//$load = new splClassLoader(null,__DIR__.'/PushBullet');
//$load->setFileExtension('.class.php');
//$load->register();
class ClassAutoLoader {
	public function __construct() {
		spl_autoload_register(array($this,'loader'));
	}
	private function loader($className) {
		require $className . '.php';
	}
}
$autoload = new ClassAutoLoader();
$ini_array = parse_ini_file("db.ini",true);
$version_info = $ini_array['VERSION'];
$debug = $version_info['DEBUG'];
if(!$debug)
	$DB = $ini_array['LIBRICK_DB'];
else
	$DB = $ini_array['DB'];
?>
