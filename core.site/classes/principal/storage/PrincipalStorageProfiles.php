<?php
die('need to refactor');
/*

Хранилище профилей принципала, основанная на структуре, подобной "гостевым профилям"

PrincipalStorage_profiles( &$principal )
- $principal       -- к какому принципалу прикрепляться

-------------------

Суть структуры:
* формат профиля полностью идентичен "гостевому".
* все профили хранятся в файлах:
- principal_profiles/<login>.php           -- для логинов "пустого" рилма
- principal_profiles/<realm>/<login>.php   -- для логинов рилма <realm>
* карта соответствия id => login хранится:
- principal_profiles/_storage_profiles.php -- как массив ( "login"=>..., "realm"=>... )

================================================================== v.1 (kuso@npj)
*/
Finder::useClass('principal/PrincipalStorageInterface');
class PrincipalStorageProfiles implements PrincipalStorageInterface
{
	protected $profilesHash = array();
	
	public function __construct( &$principal )
	{
		parent::__construct($principal);
		
		// find script or return
//		$file_source = Finder::findScript_( "principal_profiles", "_storage_profiles", false, -1 );
		// uplink
//		include( $file_source );
//		$this->profiles_hash = $included_profiles_hash;
	}

	public function loadById($id)
	{
		if (isset($this->profilesHash[$id]))
		{
			return $this->loadByLogin( $this->profilesHash[$id]["login"], $this->profilesHash[$id]["realm"] );
		}
		else
		{
			return false;
		}
	}
	
	public function loadByLogin($login, $realm="")
	{
		if ($realm != "") $login = $realm."/".$login;
		// find script or return
		$file_source = Finder::findScript( "principal_profiles", $login, false, -1 );
		if ($file_source === false) return false;
		// uplink
		include( $file_source );
		return $included_profile;
	}

        public function loadByEmail($email)
        {
            
        }

	// EOC{ PrincipalStorage_profiles }
}


?>