<?php
$sBaseDirName = dirname(__FILE__).ICWP_DS;
include_once( $sBaseDirName.'widgets'.ICWP_DS.'icwp_widgets.php' );

function printOptionsPageHeader( $sSection = '', $sPluginName = ''  ) {
	$sLinkedIcwp = '<a href="http://icwp.io/3a" target="_blank">iControlWP</a>';
	echo '<div class="page-header">';
	echo '<h2><a id="pluginlogo_32" class="header-icon32" href="http://icwp.io/2k" target="_blank"></a>';
	$sBaseTitle = sprintf( '%s', $sPluginName );
	if ( !empty( $sSection ) ) {
		echo sprintf( '%s :: %s', $sSection, $sBaseTitle );
	}
	else {
		echo $sBaseTitle;
	}
	echo '</h2></div>';
}

function printAllPluginOptionsForm( $aAllPluginOptions, $sVarPrefix = '', $nOptionsPerRow = 1 ) {

	if ( empty($aAllPluginOptions) ) {
		return;
	}

	$nRowWidth = 8; //8 spans.
	$iOptionWidth = $nRowWidth / $nOptionsPerRow;

	//Take each Options Section in turn
	foreach ( $aAllPluginOptions as $sOptionSection ) {

		$sRowId = str_replace( ' ', '', $sOptionSection['section_title'] );
		//Print the Section Title
		echo '
				<div class="row" id="'.$sRowId.'">
					<div class="span9" style="margin-left:0px">
						<fieldset>
							<legend>'.$sOptionSection['section_title'].'</legend>
		';

		$rowCount = 1;
		$nOptionCount = 0;
		//Print each option in the option section
		foreach ( $sOptionSection['section_options'] as $aOption ) {

			$nOptionCount = $nOptionCount % $nOptionsPerRow;

			if ( $nOptionCount == 0 ) {
				echo '
				<div class="row row_number_'.$rowCount.'">';
			}

			echo getPluginOptionSpan( $aOption, $iOptionWidth, $sVarPrefix );

			$nOptionCount++;

			if ( $nOptionCount == $nOptionsPerRow ) {
				echo '
				</div> <!-- / options row -->';
				$rowCount++;
			}

		}

		echo '
					</fieldset>
				</div>
			</div>
		';
		/*
		//ensure the intermediate save button is not printed at the end.
		end($aAllPluginOptions);
		$skey = key($aAllPluginOptions);
		if ( $sOptionSection['section_title'] != $skey ) {
			echo '
				<div class="form-actions">
					<button type="submit" class="btn btn-primary" name="submit" '.($hlt_compiler_enabled ? '':' disabled').'>'. _hlt__( 'Save All Settings' ).'</button>
				</div>
			';
		}
		*/

	}//foreach section

}

function getPluginOptionSpan( $inaOption, $iSpanSize, $sVarPrefix = '' ) {

	list( $sOptionKey, $sOptionSaved, $sOptionDefault, $mOptionType, $sOptionHumanName, $sOptionTitle, $sOptionHelpText ) = $inaOption;

	if ( $sOptionKey == 'spacer' ) {
		$sHtml = '
			<div class="span'.$iSpanSize.'">
			</div>
		';
	} else {

		$sSpanId = 'span_'.$sVarPrefix.$sOptionKey;
		$sHtml = '
			<div class="span'.$iSpanSize.'" id="'.$sSpanId.'">
				<div class="control-group">
					<label class="control-label" for="'.$sVarPrefix.$sOptionKey.'">'.$sOptionHumanName.'<br /></label>
					<div class="controls">
					  <div class="option_section'.( ($sOptionSaved == 'Y')? ' selected_item':'' ).'" id="option_section_'.$sVarPrefix.$sOptionKey.'">
						<label>
		';
		$sAdditionalClass = '';
		$sTextInput = '';
		$sChecked = '';

		if ( $mOptionType === 'checkbox' ) {

			$sChecked = ( $sOptionSaved == 'Y' )? 'checked="checked"' : '';

			$sHtml .= '
				<input '.$sChecked.'
						type="checkbox"
						name="'.$sVarPrefix.$sOptionKey.'"
						value="Y"
						class="'.$sAdditionalClass.'"
						id="'.$sVarPrefix.$sOptionKey.'" />
						'.$sOptionTitle;

			$sOptionHelpText = '<p class="help-block">'.$sOptionHelpText.'</p>';

		} else if ( $mOptionType === 'text' ) {
			$sTextInput = esc_attr( $sOptionSaved );
			$sHtml .= '
				<p>'.$sOptionTitle.'</p>
				<input type="text"
						name="'.$sVarPrefix.$sOptionKey.'"
						value="'.$sTextInput.'"
						placeholder="'.$sTextInput.'"
						id="'.$sVarPrefix.$sOptionKey.'" />';

			$sOptionHelpText = '<p class="help-block">'.$sOptionHelpText.'</p>';

		} else if ( is_array($mOptionType) ) { //it's a select, or radio

			$sInputType = array_shift($mOptionType);

			if ( $sInputType == 'select' ) {
				$sHtml .= '<p>'.$sOptionTitle.'</p>
				<select id="'.$sVarPrefix.$sOptionKey.'" name="'.$sVarPrefix.$sOptionKey.'">';
			}

			foreach( $mOptionType as $aInput ) {

				$sHtml .= '
					<option value="'.$aInput[0].'" id="'.$sVarPrefix.$sOptionKey.'_'.$aInput[0].'"' . (( $sOptionSaved == $aInput[0] )? ' selected="selected"' : '') .'>'. $aInput[1].'</option>';
			}

			if ($sInputType == 'select') {
				$sHtml .= '
				</select>';
			}

			$sOptionHelpText = '<p class="help-block">'.$sOptionHelpText.'</p>';

		} else if ( strpos( $mOptionType, 'less_' ) === 0 ) {	//dealing with the LESS compiler options

			if ( empty($sOptionSaved) ) {
				$sOptionSaved = $sOptionDefault;
			}

			$sHtml .= '<input class="span2'.$sAdditionalClass.'"
						type="text"
						placeholder="'.esc_attr( $sOptionSaved ).'"
						name="'.$sVarPrefix.$sOptionKey.'"
						value="'.esc_attr( $sOptionSaved ).'"
						id="'.$sVarPrefix.$sOptionKey.'" />';

			$sToggleTextInput = '';

			if ( $mOptionType === 'less_color' ) {

				if ( !getIsHexColour( $sOptionSaved ) ) {
					$sChecked = ' checked';
				}

				$sToggleTextInput= '
							<span class="toggle_checkbox">
							  <label>
								<input type="checkbox"
									name="hlt_toggle_'.$sOptionKey.'"
									id="hlt_toggle_'.$sOptionKey.'"'.$sChecked.'
									style="vertical-align: -2px;" /> edit as text
							  </label>
							</span>';

			} else if ( $mOptionType === 'less_size' || $mOptionType === 'less_font' ) {
			}

			$sHelpSection = '
					<div class="help_section">
						<span class="label label-less-name">@'.str_replace( HLT_BootstrapLess::$LESS_PREFIX, '', $sOptionKey ).'</span>
						'.$sToggleTextInput.'
						<span class="label label-less-name">'.$sOptionDefault.'</span>
					</div>';

		} else {
			echo 'we should never reach this point';
		}

		$sHtml .= '
						</label>
						'.$sOptionHelpText.'
					  </div>
					</div><!-- controls -->'
				  .$sHelpSection.'
				</div><!-- control-group -->
			</div>
		';
	}

	return $sHtml;
}
?>

<div class="wrap">
	<div class="bootstrap-wpadmin <?php echo isset($icwp_sFeatureSlug) ? $icwp_sFeatureSlug : ''; ?>">
		<div class="row">
			<div class="span12">
				<?php include_once( $sBaseDirName.'state_summary.php' ); ?>
			</div>
		</div>
<?php printOptionsPageHeader( $icwp_sFeatureName, $icwp_sPluginName );
