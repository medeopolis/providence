<?php
/* ----------------------------------------------------------------------
 * app/printTemplates/summary/footer.php : standard PDF report footer
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2014-2021 Whirl-i-Gig
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
 * -=-=-=-=-=- CUT HERE -=-=-=-=-=-
 * Template configuration:
 *
 * @name Footer
 * @type fragment
 *
 * ----------------------------------------------------------------------
 */

$t_item = $this->getVar('t_subject');

if ($this->request->config->get('summary_header_enabled')) {
    $footer = '<table class="footerText" style="width: 100%;"><tr>';
    if ($this->request->config->get('summary_show_identifier')) {
        $footer .= "<td class='footerText'>".$t_item->getLabelForDisplay()." (".$t_item->get($t_item->getProperty('ID_NUMBERING_ID_FIELD')).")</td>";
    }

    if ($this->request->config->get('summary_show_timestamp')) {
        $footer .= "<td class='footerText'>".caGetLocalizedDate(null, array('dateFormat' => 'delimited'))."</td>";
    }
    $footer .= "</tr></table>";


    switch($this->getVar('PDFRenderer')) {
        case 'domPDF':
            ?>
<div id='footer'>
	<?= $footer; ?>
</div>
<?php
                            break;
        case 'wkhtmltopdf':
            ?>
<!--BEGIN FOOTER-->
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link type="text/css" href="<?php print $this->getVar('base_path'); ?>/pdf.css" rel="stylesheet" />
</head>
<body>
<?= $footer; ?>
</body>
</html>
<!--END FOOTER-->

<?php
                    break;
    }
}
