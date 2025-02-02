<?php

/**
 * @file controllers/grid/AddThisStatisticsGridHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class AddThisStatisticsGridHandler
 * @ingroup plugins_generic_addThis
 *
 * @brief Handle addThis plugin requests for statistics.
 */

use PKP\controllers\grid\GridHandler;
use PKP\controllers\grid\GridColumn;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\file\FileWrapper;
use PKP\security\Role;

class AddThisStatisticsGridHandler extends GridHandler {
	/** @var Plugin */
	static $_plugin;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		$this->addRoleAssignment(
			[Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN],
			['fetchGrid', 'fetchRow']
		);
	}


	//
	// Getters/Setters
	//
	/**
	 * Get the plugin associated with this grid.
	 * @return Plugin
	 */
	static function getPlugin() {
		return self::$_plugin;
	}

	/**
	 * Set the Plugin
	 * @param $plugin Plugin
	 */
	static function setPlugin($plugin) {
		self::$_plugin = $plugin;
	}

	//
	// Overridden methods from PKPHandler
	//
	/**
	 * @see PKPHandler::authorize()
	 * @param $request PKPRequest
	 * @param $args array
	 * @param $roleAssignments array
	 */
	function authorize($request, &$args, $roleAssignments) {
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		$plugin = $this->getPlugin();
		$plugin->addLocaleData();

		// Basic grid configuration

		$this->setTitle('plugins.generic.addThis.grid.title');

		// Columns
		$plugin->import('controllers.grid.AddThisStatisticsGridCellProvider');
		$cellProvider = new AddThisStatisticsGridCellProvider();
		$gridColumn = new GridColumn(
			'url',
			'common.url',
			null,
			null,
			$cellProvider,
			array('width' => 50, 'alignment' => GridColumn::COLUMN_ALIGNMENT_LEFT)
		);

		$gridColumn->addFlag('html', true);

		$this->addColumn($gridColumn);

		$this->addColumn(
			new GridColumn(
				'shares',
				'plugins.generic.addThis.grid.shares',
				null,
				null,
				$cellProvider
			)
		);
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * @see GridHandler::getRowInstance()
	 * @return AddThisStatisticsGridRow
	 */
	function getRowInstance() {
		$plugin = $this->getPlugin();
		$plugin->import('AddThisStatisticsGridRow');
		return new AddThisStatisticsGridRow();
	}

	/**
	 * @copydoc GridHandler::loadData
	 */
	function loadData($request, $filter = null) {
		$plugin = $this->getPlugin();
		$context = $request->getContext();

		$addThisProfileId = $context->getData('addThisProfileId');
		$addThisUsername = $context->getData('addThisUsername');
		$addThisPassword = $context->getData('addThisPassword');

		$data = array();

		if (isset($addThisProfileId) && isset($addThisUsername) && isset($addThisPassword)) {
			$topSharedUrls = 'https://api.addthis.com/analytics/1.0/pub/shares/url.json?period=week&pubid='.urlencode($addThisProfileId).
				'&username='.urlencode($addThisUsername).
				'&password='.urlencode($addThisPassword);

			$wrapper = FileWrapper::wrapper($topSharedUrls);
			$jsonData = $wrapper->contents();

			if ($jsonData != '') {
				$jsonMessage = json_decode($jsonData);
				foreach ($jsonMessage as $statElement) {
					$data[] = array('url' => $statElement->url, 'shares' => $statElement->shares);
				}
			}
		}
		return $data;
	}
}

