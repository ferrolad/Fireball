<?php
use wcf\system\dashboard\DashboardHandler;
use wcf\system\WCF;
use cms\data\stylesheet\StylesheetAction;
use cms\data\module\ModuleAction;

$package = $this->installation->getPackage();

//default values
DashboardHandler::setDefaultValues('de.codequake.cms.news.newsList', array(
    'de.codequake.cms.latestNews' => 1
));

//install date
$sql = "UPDATE	wcf".WCF_N."_option
    SET	optionValue = ?
    WHERE	optionName = ?";
$statement = WCF::getDB()->prepareStatement($sql);
$statement->execute(array(TIME_NOW, 'cms_install_date'));

//install css templates

//two columns
$twoColumnStyle = "@media only screen and (min-width: 801px){
.col-2{ float: left; padding: 10px; width: 50%; box-sizing: border-box;}
	
}
.clear {clear:both;}";

$data = array('title' => 'Two Colums / Zweispaltig',
              'less' => $twoColumnStyle);
$objectAction = new StylesheetAction(array(), 'create', array('data' => $data));
$objectAction->executeAction();

//three columns
$threeColumnStyle = "@media only screen and (min-width: 801px){
.col-3{ float: left; padding: 10px; width: 33%; box-sizing: border-box;}
	
}
.clear {clear:both;}";

$data = array('title' => 'Three Colums / Dreispaltig',
              'less' => $threeColumnStyle);
$objectAction = new StylesheetAction(array(), 'create', array('data' => $data));
$objectAction->executeAction();

//four columns
$fourColumnStyle = "@media only screen and (min-width: 801px){
.col-4{ float: left; padding: 10px; width: 25%; box-sizing: border-box;}
	
}
.clear {clear:both;}";

$data = array('title' => 'Four Colums / Vierspaltig',
              'less' => $fourColumnStyle);
$objectAction = new StylesheetAction(array(), 'create', array('data' => $data));
$objectAction->executeAction();


//install modules

//JS Slider
$sliderJS = "<script data-relocate=\"true\">
		//<![CDATA[
		$(function() {
		$('.slideshowContainer').wcfSlideshow();
		});
		//]]>
	</script>";
$data = array('data' => array('moduleTitle' => $this->title),
                      'source' => array(
                                      'php' => '',
                                      'tpl' => $sliderJS));
$action  = new ModuleAction(array(), 'create', $data);
$action->executeAction();
