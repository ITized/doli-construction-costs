<?php
/**
 * PHPUnit bootstrap file for standalone testing (without Dolibarr)
 *
 * This bootstrap provides minimal stubs so that unit tests
 * can run in CI without a full Dolibarr installation.
 */

// Define Dolibarr constants used by the module
if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', '/usr/share/dolibarr/htdocs');
}
if (!defined('DOL_DATA_ROOT')) {
	define('DOL_DATA_ROOT', '/var/lib/dolibarr/documents');
}
if (!defined('DOL_VERSION')) {
	define('DOL_VERSION', '19.0.0');
}

// Stub for DolibarrModules base class
if (!class_exists('DolibarrModules')) {
	/**
	 * Stub base class for module descriptor
	 */
	class DolibarrModules
	{
		/** @var object Database handler */
		public $db;
		/** @var int Module number */
		public $numero;
		/** @var string Rights class */
		public $rights_class;
		/** @var string Module family */
		public $family;
		/** @var string Module position */
		public $module_position;
		/** @var string Module name */
		public $name;
		/** @var string Module description */
		public $description;
		/** @var string Long description */
		public $descriptionlong;
		/** @var string Editor name */
		public $editor_name;
		/** @var string Editor URL */
		public $editor_url;
		/** @var string Version */
		public $version;
		/** @var string Const name */
		public $const_name;
		/** @var string Picto */
		public $picto;
		/** @var array Module parts */
		public $module_parts;
		/** @var array Dirs */
		public $dirs;
		/** @var array Config page URL */
		public $config_page_url;
		/** @var bool Hidden */
		public $hidden;
		/** @var array Dependencies */
		public $depends;
		/** @var array Required by */
		public $requiredby;
		/** @var array Conflict with */
		public $conflictwith;
		/** @var array Lang files */
		public $langfiles;
		/** @var array PHP min version */
		public $phpmin;
		/** @var array Need Dolibarr version */
		public $need_dolibarr_version;
		/** @var int Need JS AJAX */
		public $need_javascript_ajax;
		/** @var array Warnings activation */
		public $warnings_activation;
		/** @var array Warnings activation ext */
		public $warnings_activation_ext;
		/** @var array Constants */
		public $const;
		/** @var array Tabs */
		public $tabs;
		/** @var array Dictionaries */
		public $dictionaries;
		/** @var array Boxes */
		public $boxes;
		/** @var array Cron jobs */
		public $cronjobs;
		/** @var array Rights */
		public $rights;
		/** @var array Menu */
		public $menu;

		/**
		 * Constructor
		 * @param object $db Database handler
		 */
		public function __construct($db)
		{
			$this->db = $db;
		}
	}
}

// Stub for CommonObject base class
if (!class_exists('CommonObject')) {
	/**
	 * Stub base class for Dolibarr objects
	 */
	class CommonObject
	{
		/** @var object Database handler */
		public $db;
		/** @var int ID */
		public $id;
		/** @var string Error */
		public $error;
		/** @var array Errors */
		public $errors = array();

		/**
		 * Constructor
		 * @param object $db Database handler
		 */
		public function __construct($db)
		{
			$this->db = $db;
		}
	}
}

// Stub helper functions
if (!function_exists('getDolGlobalInt')) {
	/**
	 * @param string $key Constant key
	 * @param int $default Default value
	 * @return int
	 */
	function getDolGlobalInt($key, $default = 0)
	{
		return $default;
	}
}

if (!function_exists('getDolGlobalString')) {
	/**
	 * @param string $key Constant key
	 * @param string $default Default value
	 * @return string
	 */
	function getDolGlobalString($key, $default = '')
	{
		return $default;
	}
}

if (!function_exists('isModEnabled')) {
	/**
	 * @param string $module Module name
	 * @return bool
	 */
	function isModEnabled($module)
	{
		return true;
	}
}

if (!function_exists('dol_buildpath')) {
	/**
	 * @param string $path Relative path
	 * @param int $type Type
	 * @return string
	 */
	function dol_buildpath($path, $type = 0)
	{
		return $path;
	}
}

if (!function_exists('img_picto')) {
	/**
	 * @param string $titlealt Title alt
	 * @param string $picto Picto name
	 * @param string $moreatt More attributes
	 * @return string
	 */
	function img_picto($titlealt, $picto, $moreatt = '')
	{
		return '<img src="' . $picto . '" alt="' . $titlealt . '" ' . $moreatt . '>';
	}
}

if (!function_exists('dol_syslog')) {
	/**
	 * @param string $message Log message
	 * @param int $level Log level
	 * @return void
	 */
	function dol_syslog($message, $level = 0)
	{
		// No-op in test
	}
}

if (!function_exists('price2num')) {
	/**
	 * @param float|string $amount Amount
	 * @param string $rounding Rounding type
	 * @return float
	 */
	function price2num($amount, $rounding = '')
	{
		return (float) $amount;
	}
}

// Autoload module classes
$classDir = dirname(__FILE__) . '/../htdocs/constructioncosts/class/';
if (is_dir($classDir)) {
	foreach (glob($classDir . '*.php') as $file) {
		require_once $file;
	}
}
