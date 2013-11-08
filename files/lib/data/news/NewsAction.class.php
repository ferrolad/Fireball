<?php
namespace cms\data\news;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\WCF;
use wcf\util\UserUtil;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\visitTracker\VisitTracker;
use wcf\system\language\LanguageFactory;
use wcf\system\tagging\TagEngine;
use wcf\system\user\activity\event\UserActivityEventHandler;
use wcf\system\user\activity\point\UserActivityPointHandler;

class NewsAction extends AbstractDatabaseObjectAction{

    protected $className = 'cms\data\news\NewsEditor';
    protected $permissionsDelete = array('mod.cms.news.canModerateNews');
    
    public $news = null;
    
    public function create(){
        $data = $this->parameters['data'];
        if (LOG_IP_ADDRESS) {
            // add ip address
            if (!isset($data['ipAddress'])) {
                $data['ipAddress'] = WCF::getSession()->ipAddress;
            }
        }
        else {
            // do not track ip address
            if (isset($data['ipAddress'])) {
                unset($data['ipAddress']);
            }
        }
        
        
        $news = call_user_func(array($this->className,'create'), $data);
        $newsEditor = new NewsEditor($news);
		
        //tags
        if (!empty($this->parameters['tags'])) {
            TagEngine::getInstance()->addObjectTags('de.codequake.cms.news', $news->newsID, $this->parameters['tags'], $news->languageID);
        }
		// handle categories
		$newsEditor->updateCategoryIDs($this->parameters['categoryIDs']);
		$newsEditor->setCategoryIDs($this->parameters['categoryIDs']);
        
        //langID != 0
        $languageID = (!isset($this->parameters['data']['languageID']) || ($this->parameters['data']['languageID'] === null)) ? LanguageFactory::getInstance()->getDefaultLanguageID() : $this->parameters['data']['languageID'];
        $newsEditor->update(array('languageID' => $languageID));
        
        //recent
        UserActivityEventHandler::getInstance()->fireEvent('de.codequake.cms.news.recentActivityEvent', $news->newsID, $news->languageID, $news->userID, $news->time);
        UserActivityPointHandler::getInstance()->fireEvent('de.codequake.cms.activityPointEvent.news', $news->newsID, $news->userID);
            
        return $news;
        
    }
    
    public function update(){
        parent::update();
        
        $objectIDs = array();
		foreach ($this->objects as $news) {
			if (isset($this->parameters['categoryIDs'])) {
				$news->updateCategoryIDs($this->parameters['categoryIDs']);
			}
            
            // update tags
            $tags = array();
            if (isset($this->parameters['tags'])) {
                $tags = $this->parameters['tags'];
                unset($this->parameters['tags']);
            }
            if (!empty($tags)) {

                $languageID = (!isset($this->parameters['data']['languageID']) || ($this->parameters['data']['languageID'] === null)) ? LanguageFactory::getInstance()->getDefaultLanguageID() : $this->parameters['data']['languageID'];
                TagEngine::getInstance()->addObjectTags('de.codequake.cms.news', $news->newsID, $tags, $languageID);
            }
		}
    }
    
    public function delete(){
        $newsIDs = array();
        foreach ($this->objects as $news) {
			$newsIDs[] = $news->newsID;
        }
        UserActivityPointHandler::getInstance()->removeEvents('de.codequake.cms.activityPointEvent.news', $newsIDs);
        parent::delete(); 
        
    }
    
    
    public function validateMarkAsRead() {
		if (empty($this->objects)) {
			$this->readObjects();
			
			if (empty($this->objects)) {
				throw new UserInputException('objectIDs');
			}
		}
	}
	
	public function markAsRead() {
		if (empty($this->parameters['visitTime'])) {
			$this->parameters['visitTime'] = TIME_NOW;
		}
		
		if (empty($this->objects)) {
			$this->readObjects();
		}
		
		$newsIDs = array();
		foreach ($this->objects as $news) {
			$newsIDs[] = $news->newsID;
			VisitTracker::getInstance()->trackObjectVisit('de.codequake.cms.news', $news->newsID, $this->parameters['visitTime']);
		}
		
		// reset storage
		if (WCF::getUser()->userID) {
			UserStorageHandler::getInstance()->reset(array(WCF::getUser()->userID), 'cmsUnreadNews');
		}
	}
	
    public function validateGetIpLog() {
		if (!LOG_IP_ADDRESS) {
			throw new PermissionDeniedException();
		}
		
		if (isset($this->parameters['newsID'])) {
			$this->news = new News($this->parameters['newsID']);
		}
		if ($this->news === null || !$this->news->newsID) {
			throw new UserInputException('newsID');
		}
		
		if (!$this->news->canRead()) {
			throw new PermissionDeniedException();
		}
	}
    
    public function getIpLog() {
		// get ip addresses of the author
		$authorIpAddresses = News::getIpAddressByAuthor($this->news->userID, $this->news->username, $this->news->ipAddress);
		
		// resolve hostnames
		$newIpAddresses = array();
		foreach ($authorIpAddresses as $ipAddress) {
			$ipAddress = UserUtil::convertIPv6To4($ipAddress);
			
			$newIpAddresses[] = array(
				'hostname' => @gethostbyaddr($ipAddress),
				'ipAddress' => $ipAddress
			);
		}
		$authorIpAddresses = $newIpAddresses;
		
		// get other users of this ip address
		$otherUsers = array();
		if ($this->news->ipAddress) {
			$otherUsers = News::getAuthorByIpAddress($this->news->ipAddress, $this->news->userID, $this->news->username);
		}
		
		$ipAddress = UserUtil::convertIPv6To4($this->news->ipAddress);
		
		if ($this->news->userID) {
			$sql = "SELECT	registrationIpAddress
				FROM	wcf".WCF_N."_user
				WHERE	userID = ?";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute(array(
				$this->news->userID
			));
			$row = $statement->fetchArray();
			
			if ($row !== false && $row['registrationIpAddress']) {
				$registrationIpAddress = UserUtil::convertIPv6To4($row['registrationIpAddress']);
				WCF::getTPL()->assign(array(
					'registrationIpAddress' => array(
						'hostname' => @gethostbyaddr($registrationIpAddress),
						'ipAddress' => $registrationIpAddress
					)
				));
			}
		}
		
		WCF::getTPL()->assign(array(
			'authorIpAddresses' => $authorIpAddresses,
			'ipAddress' => array(
				'hostname' => @gethostbyaddr($ipAddress),
				'ipAddress' => $ipAddress
			),
			'otherUsers' => $otherUsers,
			'news' => $this->news
		));
		
		return array(
			'newsID' => $this->news->newsID,
			'template' => WCF::getTPL()->fetch('newsIpAddress', 'cms')
		);
	}
}