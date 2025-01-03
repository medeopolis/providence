<?php
/* ----------------------------------------------------------------------
 * manage/search_forms_list_html.php :
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2009-2016 Whirl-i-Gig
 *
 * For more information visit http://www.CollectiveAccess.org
 *
 * This program is free software; you may redistribute it and/or modify it under
 * the terms of the provided license as published by Whirl-i-Gig
 *
 * CollectiveAccess is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTIES whatsoever, including any implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This source code is free and modifiable under the terms of
 * GNU General Public License. (http://www.gnu.org/copyleft/gpl.html). See
 * the "license.txt" file for details, or visit the CollectiveAccess web site at
 * http://www.CollectiveAccess.org
 *
 * ----------------------------------------------------------------------
 */
 	$t_form 		= $this->getVar('t_form');
	$va_form_list 	= $this->getVar('form_list');


	$t_list = new ca_lists();
	
	$vs_set_type_menu = '<div class="sf-small-menu form-header-button rounded">'.
							'<div class="caNavHeaderIcon">'.
								'<a href="#" onclick="_navigateToNewForm(jQuery(\'#tableList\').val());">'.caNavIcon(__CA_NAV_ICON_ADD__, 2).'</a>'.
							'</div>'.
						'<form action="#">'._t('New search form for ').' '.caHTMLSelect('table_num', $this->getVar('table_list'), array('id' => 'tableList')).'</form>'.
						'</div>';
						
	//$vs_set_type_menu = caNavHeaderButton($this->request, __CA_NAV_ICON_ADD__, _t('New form'), 'manage/search_forms', 'SearchFormEditor', 'Edit', array('form_id' => 0));
?>
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
	jQuery(document).ready(function(){
		jQuery('#caFormList').caFormatListTable();
	});
	
	function _navigateToNewForm(table_num) {
		document.location = '<?= caNavUrl($this->request, 'manage/search_forms', 'SearchFormEditor', 'Edit', array('form_id' => 0)); ?>' + '/table_num/' + table_num;
	}
/* ]]> */
</script>
<div class="sectionBox">
	<?php 
		print caFormControlBox(
			'<div class="list-filter">'._t('Filter').': <input type="text" name="filter" value="" onkeyup="$(\'#caFormList\').caFilterTable(this.value); return false;" size="20"/></div>', 
			'', 
			$vs_set_type_menu
		); 
	?>
	
	<table id="caFormList" class="listtable">
		<thead>
			<tr>
				<th class="list-header-unsorted">
					<?= _t('Form name'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Owner'); ?>
				</th>
				<th class="list-header-unsorted">
					<?= _t('Content type'); ?>
				</th>
				<th class="{sorter: false} list-header-nosort listtableEdit"> </th>
			</tr>
		</thead>
		<tbody>
<?php
	if (sizeof($va_form_list)) {
		foreach($va_form_list as $va_form) {
?>
			<tr>
				<td>
					<?= $va_form['name']; ?>
				</td>
				<td>
					<?= $va_form['fname'].' '.$va_form['lname']; ?>
				</td>
				<td>
					<?= $va_form['search_form_content_type']; ?>
				</td>
				<td class="listtableEditDelete">
					<?= caNavButton($this->request, __CA_NAV_ICON_EDIT__, _t("Edit"), '', 'manage/search_forms', 'SearchFormEditor', 'Edit', array('form_id' => $va_form['form_id']), array(), array('icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
					<?= caNavButton($this->request, __CA_NAV_ICON_DELETE__, _t("Delete"), '', 'manage/search_forms', 'SearchFormEditor', 'Delete', array('form_id' => $va_form['form_id']), array(), array('icon_position' => __CA_NAV_ICON_ICON_POS_LEFT__, 'use_class' => 'list-button', 'no_background' => true, 'dont_show_content' => true)); ?>
				
				</td>
			</tr>
<?php
		TooltipManager::add('.deleteIcon', _t("Delete"));
		TooltipManager::add('.editIcon', _t("Edit"));
		
		}
	} else {
?>
		<tr>
			<td colspan='4'>
				<div align="center">
					<?= _t('No forms have been configured'); ?>
				</div>
			</td>
		</tr>
<?php


	}
?>
		</tbody>
	</table>
</div>
	<div class="editorBottomPadding"><!-- empty --></div>
