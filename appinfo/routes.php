<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Sebastian Krupinski <krupinski01@gmail.com>
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\Data\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
	'routes' => [
		[
			'name' => 'Data#csv',
			'url' => '/csv/{id}/',
			'verb' => 'GET'
		],
		[
			'name' => 'Data#json',
			'url' => '/json/{id}/',
			'verb' => 'GET'
		],
		[
			'name' => 'Data#xml',
			'url' => '/xml/{id}/',
			'verb' => 'GET'
		],
		[
			'name' => 'Data#phone',
			'url' => '/phone/{id}/',
			'verb' => 'GET'
		],
		[
			'name' => 'data#listCollections',
			'url' => '/list-collections',
			'verb' => 'GET'
		],
		[
			'name' => 'data#listFormats',
			'url' => '/list-formats',
			'verb' => 'GET'
		],
		[
			'name' => 'data#listServices',
			'url' => '/list-services',
			'verb' => 'GET'
		],
	]
];
