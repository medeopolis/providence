<?php
/** ---------------------------------------------------------------------
 * app/lib/Media/IIIFResponses/BaseIIIFResponse.php
 * ----------------------------------------------------------------------
 * CollectiveAccess
 * Open-source collections management software
 * ----------------------------------------------------------------------
 *
 * Software by Whirl-i-Gig (http://www.whirl-i-gig.com)
 * Copyright 2024 Whirl-i-Gig
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
 * @package CollectiveAccess
 * @subpackage WebServices
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License version 3
 *
 * ----------------------------------------------------------------------
 */
namespace CA\Media\IIIFResponses;

abstract class BaseIIIFResponse {
	# -------------------------------------------------------
	/**
	 *
	 */
	protected $config;
	
	/**
	 *
	 */
	protected $base_url;
	
	/**
	 *
	 */
	protected $response_url;
	
	/**
	 *
	 */
	protected $response_type;
	# -------------------------------------------------------
	/**
	 *
	 */
	public function __construct() {
		$this->config = \Configuration::load();
		$this->base_url = $this->config->get('site_host').$this->config->get('ca_url_root'); //.$request->getBaseUrlPath();
		
		if(isset($_SERVER['REQUEST_URI'])) {
			$this->response_url = $this->config->get('site_host').$_SERVER['REQUEST_URI'];
		} else {
			$this->response_url = $this->base_url."/".$this->response_type;
		}
	}
	# -------------------------------------------------------
	/**
	 * Return JSON IIIF response
	 */
	abstract public function response(array $data, ?array $options=null) : array;
	# -------------------------------------------------------
}
