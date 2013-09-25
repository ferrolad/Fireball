<?php
namespace cms\acp\form;

use cms\data\page\PageAction;
use cms\data\page\PageEditor;
use wcf\system\language\I18nHandler;
use wcf\form\AbstractForm;
use wcf\util\StringUtil;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;

class PageAddForm extends AbstractForm{
    
    public $templateName = 'pageAdd';
    public $neededPermissions = array('admin.cms.page.canAddPage');
    public $activeMenuItem = 'cms.acp.menu.link.cms.page.add';
    
    public $enableMultilangualism = true;
    
    public $title = '';
    public $description = '';
    public $metaDescription = '';
    public $metaKeywords = '';
    public $invisible = 0;
    public $robots = 'index,follow';

   public function readParameters(){
        parent::readParameters();
        I18nHandler::getInstance()->register('title');
        I18nHandler::getInstance()->register('description');
        I18nHandler::getInstance()->register('metaDescription');
        I18nHandler::getInstance()->register('metaKeywords');
    }
    
    public function readFormParameters(){
        parent::readFormParameters();
        I18nHandler::getInstance()->readValues();
        if (I18nHandler::getInstance()->isPlainValue('description')) $this->description = StringUtil::trim(I18nHandler::getInstance()->getValue('description'));
        if (I18nHandler::getInstance()->isPlainValue('title')) $this->title = StringUtil::trim(I18nHandler::getInstance()->getValue('title'));
        if (I18nHandler::getInstance()->isPlainValue('metaDescription')) $this->metaDescription = StringUtil::trim(I18nHandler::getInstance()->getValue('metaDescription'));
        if (I18nHandler::getInstance()->isPlainValue('metaKeywords')) $this->metaKeywords = StringUtil::trim(I18nHandler::getInstance()->getValue('metaKeywords'));
        
        if(isset($_POST['invisible'])) $this->invisible = intval($_POST['invisible']);
        if(isset($_POST['robots'])) $this->robots = StringUtil::trim($_POST['robots']);
    }
    
    public function validate(){
        parent::validate();
        if(empty($this->title)) throw new UserInputException('title');
    }
    
    public function save(){
        parent::save();
        $data = array('userID' => WCF::getUser()->userID,
                       'title' => $this->title,
                       'description' => $this->description,
                       'metaDescription' => $this->metaDescription,
                       'metaKeywords' => $this->metaKeywords,
                       'invisible' => $this->invisible,
                       'robots' => $this->robots);
        $objectAction = new PageAction(array(), 'create', array('data' => $data));
        $objectAction->executeAction();
        $returnValues = $objectAction->getReturnValues();
        $pageID = $returnValues['returnValues']->contentID;
        $update = array();
        
        if (!I18nHandler::getInstance()->isPlainValue('title')) {
            I18nHandler::getInstance()->save('title', 'cms.page.'.$pageID.'.title', 'cms.page', PACKAGE_ID);
            $update['title'] = 'cms.page.'.$pageID.'.title';
        }
        if (!I18nHandler::getInstance()->isPlainValue('description')) {
            I18nHandler::getInstance()->save('description', 'cms.page.'.$pageID.'.description', 'cms.page', PACKAGE_ID);
            $update['description'] = 'cms.page.'.$pageID.'.description';
        }
        if (!I18nHandler::getInstance()->isPlainValue('metaDescription')) {
            I18nHandler::getInstance()->save('metaDescription', 'cms.page.'.$pageID.'.metaDescription', 'cms.page', PACKAGE_ID);
            $update['metaDescription'] = 'cms.page.'.$pageID.'.metaDescription';
        }
        if (!I18nHandler::getInstance()->isPlainValue('metaKeywords')) {
            I18nHandler::getInstance()->save('metaKeywords', 'cms.page.'.$pageID.'.metaKeywords', 'cms.page', PACKAGE_ID);
            $update['metaKeywords'] = 'cms.page.'.$pageID.'.metaKeywords';
        }
        if (!empty($update)) {
            $editor = new PageEditor($returnValues['returnValues']);
            $editor->update($update);
        }
        
        $this->saved();
        WCF::getTPL()->assign('success', true);
        
        $this->title = $this->description = $this->metaDescription = $this->metaKeywords = $this->robots = '';
        $this->invisible = 0;
        I18nHandler::getInstance()->reset();
    }
    
    public function assignVariables(){
        parent::assignVariables();
        I18nHandler::getInstance()->assignVariables();
        WCF::getTPL()->assign(array('action' => 'add',
                                    'invisible' => $this->invisible,
                                    'robots' => $this->robots));
    }
    
    
}