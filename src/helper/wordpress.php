<?php

class Helper_WordPress {
	
	protected $m_sWpVersion;
	
	public function __construct() {
		global $wp_version;
		
		$this->m_sWpVersion = &$wp_version;
	}

	/**
	 * @param string $insKey
	 * @return object
	 */
	public function getTransient( $insKey ) {
		
		// TODO: Handle multisite
		
		if ( version_compare( $this->m_sWpVersion, '2.7.9', '<=' ) ) {
			return get_option( $insKey );
		}
		
		if ( function_exists( 'get_site_transient' ) ) {
			return get_site_transient( $insKey );
		}
		
		if ( version_compare( $this->m_sWpVersion, '2.9.9', '<=' ) ) {
			return apply_filters( 'transient_'.$insKey, get_option( '_transient_'.$insKey ) );
		}
		
		return apply_filters( 'site_transient_'.$insKey, get_option( '_site_transient_'.$insKey ) );
	}
	
	/**
	 * @param string $insKey
	 * @param mixed $inoObject
	 * @return boolean
	 */
	public function setTransient( $insKey, $inmData ) {
		
		// TODO: Handle multisite
		
		if ( version_compare($this->m_sWpVersion, '2.7.9', '<=' ) ) {
			update_option( $insKey, $inmData );
		}

		// @since 2.9.0
		if ( function_exists( 'set_site_transient' ) ) {
			return set_site_transient( $insKey, $inmData );
		}
		
		if ( version_compare( $this->m_sWpVersion, '2.9.9', '<=' ) ) {
			return update_option( '_transient_'.$insKey, $inmData );
		}
		
		return update_option( '_site_transient_'.$insKey, $inmData );
	}
	
	/**
	 * @param string $insKey
	 * @return boolean
	 */
	public function deleteTransient( $insKey ) {
		if ( version_compare( $this->m_sWpVersion, '2.7.9', '<=' ) ) {
			return delete_option( $insKey );
		}
		
		if ( function_exists( 'delete_site_transient' ) ) {
			return delete_site_transient( $insKey );
		}
		
		if ( version_compare( $this->m_sWpVersion, '2.9.9', '<=' ) ) {
			return delete_option( '_transient_'.$insKey );
		}
		
		return delete_option( '_site_transient_'.$insKey );
	}
}