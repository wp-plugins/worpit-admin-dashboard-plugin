<?php
class ICWP_APP_OptionsVO extends ICWP_APP_Foundation {

	/**
	 * @var array
	 */
	protected $aOptionsValues;

	/**
	 * @var array
	 */
	protected $aRawOptionsConfigData;

	/**
	 * @var boolean
	 */
	protected $fNeedSave;

	/**
	 * @var string
	 */
	protected $aOptionsKeys;

	/**
	 * @var string
	 */
	protected $sOptionsStorageKey;

	/**
	 * @var string
	 */
	protected $sOptionsName;

	/**
	 * @param string $sOptionsName
	 */
	public function __construct( $sOptionsName ) {
		$this->sOptionsName = $sOptionsName;
	}

	/**
	 * @return bool
	 */
	public function doOptionsSave() {
		$this->cleanOptions();
		if ( !$this->getNeedSave() ) {
			return true;
		}
		$oWpFunc = $this->loadWpFunctionsProcessor();
		$this->setNeedSave( false );
		return $oWpFunc->updateOption( $this->getOptionsStorageKey(), $this->getAllOptionsValues() );
	}

	/**
	 * @return bool
	 */
	public function doOptionsDelete() {
		$oWpFunc = $this->loadWpFunctionsProcessor();
		return $oWpFunc->deleteOption( $this->getOptionsStorageKey() );
	}

	/**
	 * @return array
	 */
	public function getAllOptionsValues() {
		return $this->loadOptionsValuesFromStorage();
	}

	/**
	 * @param $sProperty
	 * @return null|mixed
	 */
	public function getFeatureProperty( $sProperty ) {
		$aRawConfig = $this->getRawData_FullFeatureConfig();
		return ( isset( $aRawConfig['properties'] ) && isset( $aRawConfig['properties'][$sProperty] ) ) ? $aRawConfig['properties'][$sProperty] : null;
	}

	/**
	 * @param string $sKey
	 * @return boolean
	 */
	public function getIsOptionKey( $sKey ) {
		return in_array( $sKey, $this->getOptionsKeys() );
	}

	/**
	 * Determines whether the given option key is a valid option
	 *
	 * @param string
	 * @return boolean
	 */
	public function getIsValidOptionKey( $sOptionKey ) {
		return in_array( $sOptionKey, $this->getOptionsKeys() );
	}

	/**
	 * @return array
	 */
	public function getHiddenOptions() {

		$aRawData = $this->getRawData_FullFeatureConfig();
		$aOptionsData = array();

		foreach( $aRawData['sections'] as $nPosition => $aRawSection ) {

			// if hidden isn't specified we skip
			if ( !isset( $aRawSection['hidden'] ) || !$aRawSection['hidden'] ) {
				continue;
			}
			foreach( $this->getRawData_AllOptions() as $aRawOption ) {

				if ( $aRawOption['section'] != $aRawSection['slug'] ) {
					continue;
				}
				$aOptionsData[ $aRawOption['key'] ] = $this->getOpt( $aRawOption['key'] );
			}
		}
		return $aOptionsData;
	}

	/**
	 * @return array
	 */
	public function getLegacyOptionsConfigData() {

		$aRawData = $this->getRawData_FullFeatureConfig();
		$aLegacyData = array();

		foreach( $aRawData['sections'] as $nPosition => $aRawSection ) {

			if ( isset( $aRawSection['hidden'] ) && $aRawSection['hidden'] ) {
				continue;
			}

			$aLegacySection = array();
			$aLegacySection['section_primary'] = isset( $aRawSection['primary'] ) && $aRawSection['primary'];
			$aLegacySection['section_slug'] = $aRawSection['slug'];
			$aLegacySection['section_options'] = array();
			foreach( $this->getRawData_AllOptions() as $aRawOption ) {

				if ( $aRawOption['section'] != $aRawSection['slug'] ) {
					continue;
				}

				if ( isset( $aRawOption['hidden'] ) && $aRawOption['hidden'] ) {
					continue;
				}

				$aLegacyRawOption = array();
				$aLegacyRawOption['key'] = $aRawOption['key'];
				$aLegacyRawOption['value'] = ''; //value
				$aLegacyRawOption['default'] = $aRawOption['default'];
				$aLegacyRawOption['type'] = $aRawOption['type'];

				$aLegacyRawOption['value_options'] = array();
				if ( in_array( $aLegacyRawOption['type'], array( 'select', 'multiple_select' ) ) ) {
					foreach( $aRawOption['value_options'] as $aValueOptions ) {
						$aLegacyRawOption['value_options'][ $aValueOptions['value_key'] ] = $aValueOptions['text'];
					}
				}

				$aLegacyRawOption['info_link'] = isset( $aRawOption['link_info'] ) ? $aRawOption['link_info'] : '';
				$aLegacyRawOption['blog_link'] = isset( $aRawOption['link_blog'] ) ? $aRawOption['link_blog'] : '';
				$aLegacySection['section_options'][] = $aLegacyRawOption;
			}

			if ( count( $aLegacySection['section_options'] ) > 0 ) {
				$aLegacyData[ $nPosition ] = $aLegacySection;
			}
		}
		return $aLegacyData;
	}

	/**
	 * @return array
	 */
	public function getAdditionalMenuItems() {
		return $this->getRawData_MenuItems();
	}

	/**
	 * @return string
	 */
	public function getNeedSave() {
		return $this->fNeedSave;
	}

	/**
	 * @param string $sOptionKey
	 * @param mixed $mDefault
	 * @return mixed
	 */
	public function getOpt( $sOptionKey, $mDefault = false ) {
		$aOptionsValues = $this->getAllOptionsValues();
		if ( !isset( $aOptionsValues[ $sOptionKey ] ) ) {
			$this->setOpt( $sOptionKey, $this->getOptDefault( $sOptionKey, $mDefault ), true );
		}
		return $this->aOptionsValues[ $sOptionKey ];
	}

	/**
	 * @param string $sOptionKey
	 * @param mixed $mDefault
	 * @return mixed|null
	 */
	public function getOptDefault( $sOptionKey, $mDefault = null ) {
		$aOptions = $this->getRawData_AllOptions();
		foreach( $aOptions as $aOption ) {
			if ( $aOption['key'] == $sOptionKey ) {
				if ( isset( $aOption['value'] ) ) {
					return $aOption['value'];
				}
				else if ( isset( $aOption['default'] ) ) {
					return $aOption['default'];
				}
			}
		}
		return $mDefault;
	}

	/**
	 * @param $sKey
	 * @param mixed $mValueToTest
	 * @param boolean $fStrict
	 * @return bool
	 */
	public function getOptIs( $sKey, $mValueToTest, $fStrict = false ) {
		$mOptionValue = $this->getOpt( $sKey );
		return $fStrict? $mOptionValue === $mValueToTest : $mOptionValue == $mValueToTest;
	}

	/**
	 * @return string
	 */
	public function getOptionsKeys() {
		if ( !isset( $this->aOptionsKeys ) ) {
			$this->aOptionsKeys = array();
			foreach( $this->getRawData_AllOptions() as $aOption ) {
				$this->aOptionsKeys[] = $aOption['key'];
			}
		}
		return $this->aOptionsKeys;
	}

	/**
	 * @return string
	 */
	public function getOptionsStorageKey() {
		return $this->sOptionsStorageKey;
	}

	/**
	 * @return array
	 */
	public function getRawData_FullFeatureConfig() {
		if ( empty( $this->aRawOptionsConfigData ) ) {
			$this->aRawOptionsConfigData = $this->readYamlConfiguration( $this->sOptionsName );
		}
		return $this->aRawOptionsConfigData;
	}

	/**
	 * Return the section of the Raw config that is the "options" key only.
	 *
	 * @return array
	 */
	protected function getRawData_AllOptions() {
		$aAllRawOptions = $this->getRawData_FullFeatureConfig();
		return isset( $aAllRawOptions['options'] ) ? $aAllRawOptions['options'] : array();
	}

	/**
	 * Return the section of the Raw config that is the "options" key only.
	 *
	 * @return array
	 */
	protected function getRawData_MenuItems() {
		$aAllRawOptions = $this->getRawData_FullFeatureConfig();
		return isset( $aAllRawOptions['menu_items'] ) ? $aAllRawOptions['menu_items'] : array();
	}

	/**
	 * Return the section of the Raw config that is the "options" key only.
	 *
	 * @param string $sOptionKey
	 * @return array
	 */
	public function getRawData_SingleOption( $sOptionKey ) {
		$aAllRawOptions = $this->getRawData_AllOptions();
		foreach( $aAllRawOptions as $aOption ) {
			if ( $sOptionKey == $aOption['key'] ) {
				return $aOption;
			}
		}
		return null;
	}

	/**
	 * @param string $sOptionKey
	 * @return boolean
	 */
	public function resetOptToDefault( $sOptionKey ) {
		return $this->setOpt( $sOptionKey, $this->getOptDefault( $sOptionKey ), true );
	}

	/**
	 * @param string $sKey
	 */
	public function setOptionsStorageKey( $sKey ) {
		$this->sOptionsStorageKey = $sKey;
	}

	/**
	 * @param boolean $fNeed
	 */
	public function setNeedSave( $fNeed ) {
		$this->fNeedSave = $fNeed;
	}

	/**
	 * @param string $sOptionKey
	 * @param mixed $mValue
	 * @param boolean $fForce
	 * @return mixed
	 */
	public function setOpt( $sOptionKey, $mValue, $fForce = false ) {

		if ( $fForce || $this->getOpt( $sOptionKey ) !== $mValue ) {
			$this->setNeedSave( true );

			//Load the config and do some pre-set verification where possible. This will slowly grow.
			$aOption = $this->getRawData_SingleOption( $sOptionKey );
			if ( !empty( $aOption['type'] ) ) {
				if ( $aOption['type'] == 'boolean' && !is_bool( $mValue ) ) {
					return $this->resetOptToDefault( $sOptionKey );
				}
			}

			$this->aOptionsValues[ $sOptionKey ] = $mValue;
		}
		return true;
	}

	/**
	 * @param string $sOptionKey
	 * @return mixed
	 */
	public function unsetOpt( $sOptionKey ) {

		unset( $this->aOptionsValues[$sOptionKey] );
		$this->setNeedSave( true );
		return true;
	}

	/** PRIVATE STUFF */

	/**
	 */
	private function cleanOptions() {
		if ( empty( $this->aOptionsValues ) || !is_array( $this->aOptionsValues ) ) {
			return;
		}
		foreach( $this->aOptionsValues as $sKey => $mValue ) {
			if ( !$this->getIsValidOptionKey( $sKey ) ) {
				$this->setNeedSave( true );
				unset( $this->aOptionsValues[$sKey] );
			}
		}
	}

	/**
	 * @param boolean $fReload
	 * @return array
	 * @throws Exception
	 */
	private function loadOptionsValuesFromStorage( $fReload = false ) {

		if ( $fReload || empty( $this->aOptionsValues ) ) {

			$sStorageKey = $this->getOptionsStorageKey();
			if ( empty( $sStorageKey ) ) {
				throw new Exception( 'Options Storage Key Is Empty' );
			}

			$oWpFunc = $this->loadWpFunctionsProcessor();
			$this->aOptionsValues = $oWpFunc->getOption( $sStorageKey, array() );
			if ( empty( $this->aOptionsValues ) ) {
				$this->aOptionsValues = array();
				$this->setNeedSave( true );
			}
		}
		return $this->aOptionsValues;
	}

	/**
	 * @param string $sName
	 * @return array
	 * @throws Exception
	 */
	private function readYamlConfiguration( $sName ) {
		$oFs = $this->loadFileSystemProcessor();

		$aConfig = array();
		$sConfigFile = dirname( __FILE__ ). sprintf( ICWP_DS.'config'.ICWP_DS.'feature-%s.txt', $sName );
		$sContents = $oFs->getFileContent( $sConfigFile );
		if ( !empty( $sContents ) ) {
			$oYaml = $this->loadYamlProcessor();
			$aConfig = $oYaml->parseYamlString( $sContents );
			if ( is_null( $aConfig ) ) {
				throw new Exception( 'YAML parser could not load to process the options configuration.' );
			}
		}
		return $aConfig;
	}
}