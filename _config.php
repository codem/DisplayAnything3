<?php
Director::addRules(
	100,
	array(
		'$URLSegment/da/$Action/$ID/$OtherID' =>  'DisplayAnythingAssetAdmin',
	)
);
Object::add_extension('Image', 'WatermarkedImageExtension');
//Transliterator::create() requires at least one argument.
FileNameFilter::$default_use_transliterator = FALSE;

/**
 * CONFIG
 * You should add and config settings in your own site _config.php as any changes here will be overwritten on upgrade
 * DisplayAnythingGallery::SetPermissions(0600, 0700);//change default permissions
 */
?>