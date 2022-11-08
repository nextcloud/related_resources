<?php

return [
	'ocs' => [
		// TODO remove this route for NC26
		['name' => 'Api#getRelatedResources', 'url' => '/related/{providerId}/{itemId}', 'verb' => 'GET'],
		['name' => 'Api#getRelatedAlternate', 'url' => '/related/{providerId}', 'verb' => 'GET']
	],
	'routes' => []
];
