<?php
Director::addRules(
	100,
	array(
		'$URLSegment/da/$Action/$ID/$OtherID' =>  'DisplayAnythingAssetAdmin',
		//'content/admin/da//$Action/$ID/$OtherID' => 'DisplayAnythingAssetAdmin',
		//'content/admin/da//$Action/$ID/field' => 'DisplayAnythingAssetAdmin',
	)
);
Object::add_extension('Image', 'WatermarkedImageExtension');
?>