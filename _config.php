<?php
Object::add_extension('Image', 'WatermarkedImageExtension');

Director::addRules(
	60,
	array(
		'admin/da//$Action/$ID/$OtherID' => 'DisplayAnythingAssetAdmin',
	)
);
?>