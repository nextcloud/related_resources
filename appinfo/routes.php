<?php

return [
	'ocs' => [
		['name' => 'Api#getRelatedResources', 'url' => '/related/{providerId}/{itemId}', 'verb' => 'GET'],
		['name' => 'Api#getRelatedAlternate', 'url' => '/related/{providerId}', 'verb' => 'GET']
	],
	'routes' => []
];
