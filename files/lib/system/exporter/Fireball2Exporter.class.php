<?php

namespace cms\system\exporter;
use wcf\data\category\Category;
use wcf\data\category\CategoryEditor;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\database\DatabaseException;
use wcf\system\exporter\AbstractExporter;
use wcf\system\importer\ImportHandler;
use wcf\system\WCF;
use wcf\util\FileUtil;

/**
 * Provides an exporter from Fireball CMS 2.0/2.1 into Fireball CMS 2.1
 *
 * @author	Florian Gail
 * @copyright	2013 - 2016 codeQuake
 * @license	GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl-3.0.txt>
 * @package	de.codequake.cms
 */
class Fireball2Exporter extends AbstractExporter {
	/**
	 * wcf installation number
	 * @var	integer
	 */
	protected $dbNo = 0;

	/**
	 * array with cached categories from old installation
	 * @var array
	 */
	protected $categoryCache = array();

	/**
	 * @see \wcf\system\exporter\AbstractExporter::$methods
	 */
	protected $methods = array(
		// WoltLab
		'com.woltlab.wcf.user' => 'Users',
		'com.woltlab.wcf.user.group' => 'UserGroups',

		// Fireball
		'de.codequake.cms.page' => 'Pages',
		'de.codequake.cms.page.acl' => 'PagesACLs',
		'de.codequake.cms.content' => 'Contents',
		'de.codequake.cms.file.category' => 'FileCategories',
		'de.codequake.cms.file' => 'Files',
		'de.codequake.cms.page.comment' => 'Comments',
		'de.codequake.cms.page.comment.response' => 'CommentResponses',
		'de.codequake.cms.stylesheet' => 'Stylesheets'
	);

	/**
	 * @see	\wcf\system\exporter\AbstractExporter::$limits
	 */
	protected $limits = array(
		'com.woltlab.wcf.user' => 100,
		'de.codequake.cms.page' => 300,
		'de.codequake.cms.content' => 100,
		'de.codequake.cms.file.category' => 300,
		'de.codequake.cms.file' => 100,
		'de.codequake.cms.page.comment' => 300,
		'de.codequake.cms.page.comment.response' => 300,
		'de.codequake.cms.stylesheet' => 300
	);

	/**
	 * @see	\wcf\system\exporter\IExporter::init()
	 */
	public function init() {
		parent::init();

		if (preg_match('/^cms(\d+)_$/', $this->databasePrefix, $match))
			$this->dbNo = $match[1];
	}

	/**
	 * @see	\wcf\system\exporter\IExporter::validateFileAccess()
	 */
	public function validateFileAccess() {
		if (in_array('de.codequake.cms.file', $this->selectedData)) {
			if (empty($this->fileSystemPath) || !@file_exists($this->fileSystemPath.'lib/core.functions.php')) {
				return false;
			} else {
				return true;
			}
		}
	}

	/**
	 * @see \wcf\system\exporter\IExporter::getSupportedData()
	 */
	public function getSupportedData() {
		return array(
			// WoltLab
			'com.woltlab.wcf.user' => array(
				'com.woltlab.wcf.user.group'
			),
			// Fireball
			'de.codequake.cms.page' => array(
				'de.codequake.cms.page.acl',
				'de.codequake.cms.page.comment',
				'de.codequake.cms.content'
			),
			'de.codequake.cms.file.category' => array(
				'de.codequake.cms.file'
			),
			'de.codequake.cms.stylesheet' => array()
		);
	}

	/**
	 * @see \wcf\system\exporter\IExporter::validateDatabaseAccess()
	 */
	public function validateDatabaseAccess() {
		parent::validateDatabaseAccess();

		$sql = "SELECT	packageID, packageDir, packageVersion
			FROM	wcf".$this->dbNo."_package
			WHERE	package = ?";
		$statement = $this->database->prepareStatement($sql, 1);
		$statement->execute(array('de.codequake.cms'));
		$row = $statement->fetchArray();

		if ($row !== false) {
			if (substr($row['packageVersion'], 0, 3) != '2.1' && substr($row['packageVersion'], 0, 3) != '2.0')
				throw new DatabaseException('Cannot find Fireball CMS 2.0/2.1 installation', $this->database);
		} else {
			throw new DatabaseException('Cannot find Fireball CMS installation', $this->database);
		}
	}

	/**
	 * @see \wcf\system\exporter\IExporter::getQueue()
	 */
	public function getQueue() {
		$queue = array();

		// user
		if (in_array('com.woltlab.wcf.user', $this->selectedData)) {
			if (in_array('com.woltlab.wcf.user.group', $this->selectedData)) {
				$queue[] = 'com.woltlab.wcf.user.group';
			}
		}

		if (in_array('de.codequake.cms.stylesheet', $this->selectedData)) {
			$queue[] = 'de.codequake.cms.stylesheet';
		}

		if (in_array('de.codequake.cms.file.category', $this->selectedData)) {
			$queue[] = 'de.codequake.cms.file.category';

			if (in_array('de.codequake.cms.file', $this->selectedData))
				$queue[] = 'de.codequake.cms.file';
		}

		if (in_array('de.codequake.cms.page', $this->selectedData)) {
			$queue[] = 'de.codequake.cms.page';

			if (in_array('de.codequake.cms.page.acl', $this->selectedData))
				$queue[] = 'de.codequake.cms.page.acl';

			if (in_array('de.codequake.cms.content', $this->selectedData))
				$queue[] = 'de.codequake.cms.content';

			if (in_array('de.codequake.cms.page.comment', $this->selectedData)) {
				$queue[] = 'de.codequake.cms.page.comment';
				$queue[] = 'de.codequake.cms.page.comment.response';
			}
		}

		return $queue;
	}

	/**
	 * @see	\wcf\system\exporter\IExporter::getDefaultDatabasePrefix()
	 */
	public function getDefaultDatabasePrefix() {
		return 'cms1_';
	}

	/* ---------------------------------- */
	/* CODE BY WOLTLAB (license: LGPL v3) */
	/* ---------------------------------- */

	/**
	 * Counts user groups.
	 */
	public function countUserGroups() {
		$sql = "SELECT	COUNT(*) AS count
			FROM	wcf".$this->dbNo."_user_group";
		$statement = $this->database->prepareStatement($sql);
		$statement->execute();
		$row = $statement->fetchArray();
		return $row['count'];
	}

	/**
	 * Exports user groups.
	 */
	public function exportUserGroups($offset, $limit) {
		$sql = "SELECT	*
			FROM	wcf".$this->dbNo."_user_group
			ORDER BY	groupID";
		$statement = $this->database->prepareStatement($sql, $limit, $offset);
		$statement->execute();
		while ($row = $statement->fetchArray()) {
			ImportHandler::getInstance()->getImporter('com.woltlab.wcf.user.group')->import($row['groupID'], array(
				'groupName' => $row['groupName'],
				'groupDescription' => $row['groupDescription'],
				'groupType' => $row['groupType'],
				'priority' => $row['priority'],
				'userOnlineMarking' => (!empty($row['userOnlineMarking']) ? $row['userOnlineMarking'] : ''),
				'showOnTeamPage' => (!empty($row['showOnTeamPage']) ? $row['showOnTeamPage'] : 0)
			));
		}
	}

	/**
	 * Counts users.
	 */
	public function countUsers() {
		return $this->__getMaxID("wcf".$this->dbNo."_user", 'userID');
	}

	/**
	 * Exports users.
	 */
	public function exportUsers($offset, $limit) {
		// cache existing user options
		$existingUserOptions = array();
		$sql = "SELECT	optionName, optionID
			FROM	wcf".WCF_N."_user_option
			WHERE	optionName NOT LIKE 'option%'";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		while ($row = $statement->fetchArray()) {
			$existingUserOptions[$row['optionName']] = true;
		}

		// cache user options
		$userOptions = array();
		$sql = "SELECT	optionName, optionID
			FROM	wcf".$this->dbNo."_user_option";
		$statement = $this->database->prepareStatement($sql);
		$statement->execute();
		while ($row = $statement->fetchArray()) {
			$userOptions[$row['optionID']] = (isset($existingUserOptions[$row['optionName']]) ? $row['optionName'] : $row['optionID']);
		}

		// prepare password update
		$sql = "UPDATE	wcf".WCF_N."_user
			SET	password = ?
			WHERE	userID = ?";
		$passwordUpdateStatement = WCF::getDB()->prepareStatement($sql);

		// get users
		$sql = "SELECT	user_option_value.*, user_table.*,
				(
					SELECT	GROUP_CONCAT(groupID)
					FROM	wcf".$this->dbNo."_user_to_group
					WHERE	userID = user_table.userID
				) AS groupIDs,
				(
					SELECT		GROUP_CONCAT(language.languageCode)
					FROM		wcf".$this->dbNo."_user_to_language user_to_language
					LEFT JOIN	wcf".$this->dbNo."_language language
					ON		(language.languageID = user_to_language.languageID)
					WHERE		user_to_language.userID = user_table.userID
				) AS languageCodes
			FROM	wcf".$this->dbNo."_user user_table
			LEFT JOIN	wcf".$this->dbNo."_user_option_value user_option_value
			ON	(user_option_value.userID = user_table.userID)
			WHERE	user_table.userID BETWEEN ? AND ?
			ORDER BY	user_table.userID";
		$statement = $this->database->prepareStatement($sql);
		$statement->execute(array($offset + 1, $offset + $limit));
		while ($row = $statement->fetchArray()) {
			$data = array(
				'username' => $row['username'],
				'password' => '',
				'email' => $row['email'],
				'registrationDate' => $row['registrationDate'],
				'banned' => $row['banned'],
				'banReason' => $row['banReason'],
				'activationCode' => $row['activationCode'],
				'oldUsername' => $row['oldUsername'],
				'registrationIpAddress' => $row['registrationIpAddress'],
				'disableAvatar' => $row['disableAvatar'],
				'disableAvatarReason' => $row['disableAvatarReason'],
				'enableGravatar' => $row['enableGravatar'],
				'signature' => $row['signature'],
				'signatureEnableBBCodes' => $row['signatureEnableBBCodes'],
				'signatureEnableHtml' => $row['signatureEnableHtml'],
				'signatureEnableSmilies' => $row['signatureEnableSmilies'],
				'disableSignature' => $row['disableSignature'],
				'disableSignatureReason' => $row['disableSignatureReason'],
				'profileHits' => $row['profileHits'],
				'userTitle' => $row['userTitle'],
				'lastActivityTime' => $row['lastActivityTime']
			);
			$additionalData = array(
				'groupIDs' => explode(',', $row['groupIDs']),
				'languages' => explode(',', $row['languageCodes']),
				'options' => array()
			);

			// handle user options
			foreach ($userOptions as $optionID => $optionName) {
				if (isset($row['userOption'.$optionID])) {
					$additionalData['options'][$optionName] = $row['userOption'.$optionID];
				}
			}

			// import user
			$newUserID = ImportHandler::getInstance()->getImporter('com.woltlab.wcf.user')->import($row['userID'], $data, $additionalData);

			// update password hash
			if ($newUserID) {
				$passwordUpdateStatement->execute(array($row['password'], $newUserID));
			}
		}
	}

	/* ---------------------- */
	/* END OF CODE BY WOLTLAB */
	/* ---------------------- */

	/**
	 * Counts pages.
	 */
	public function countPages() {
		$sql = "SELECT	COUNT(*) AS count
			FROM	cms" . $this->dbNo . "_page";
		$statement = $this->database->prepareStatement($sql);
		$statement->execute();
		$row = $statement->fetchArray();

		return $row['count'];
	}

	/**
	 * Exports pages.
	 */
	public function exportPages($offset, $limit) {
		$sql = "SELECT	*
			FROM	cms" . $this->dbNo . "_page
			ORDER BY	pageID";
		$statement = $this->database->prepareStatement($sql, $limit, $offset);
		$statement->execute();

		while ($row = $statement->fetchArray()) {
			$objectTypeID = ObjectTypeCache::getInstance()->getObjectTypeIDByName('de.codequake.cms.page.type', 'de.codequake.cms.page.type.page');
			if (!$objectTypeID)
				continue;
			$row['objectTypeID'] = $objectTypeID;

			ImportHandler::getInstance()->getImporter('de.codequake.cms.page')->import($row['pageID'], $row);
		}
	}

	/**
	 * Counts contents.
	 */
	public function countContents() {
		$sql = "SELECT	COUNT(*) AS count
			FROM	cms" . $this->dbNo . "_content";
		$statement = $this->database->prepareStatement($sql);
		$statement->execute();
		$row = $statement->fetchArray();

		return $row['count'];
	}

	/**
	 * Exports contents.
	 */
	public function exportContents($offset, $limit) {
		$sql = "SELECT	*
			FROM	cms" . $this->dbNo . "_content
			ORDER BY	contentID";
		$statement = $this->database->prepareStatement($sql, $limit, $offset);
		$statement->execute();

		while ($row = $statement->fetchArray()) {
			$contentType = $this->getObjectType($row['contentTypeID']);
			if ($contentType === null)
				continue;
			$contentTypeID = ObjectTypeCache::getInstance()->getObjectTypeIDByName('de.codequake.cms.content.type', $contentType['objectType']);
			if (!$contentTypeID)
				continue;

			$row['contentTypeID'] = $contentTypeID;
			ImportHandler::getInstance()->getImporter('de.codequake.cms.content')->import($row['contentID'], $row);
		}
	}

	/**
	 * Counts stylesheets.
	 */
	public function countStylesheets() {
		$sql = "SELECT	COUNT(*) AS count
			FROM	cms" . $this->dbNo . "_stylesheet";
		$statement = $this->database->prepareStatement($sql);
		$statement->execute();
		$row = $statement->fetchArray();

		return $row['count'];
	}

	/**
	 * Exports stylesheets.
	 */
	public function exportStylesheets($offset, $limit) {
		$sql = "SELECT	*
			FROM	cms" . $this->dbNo . "_stylesheet
			ORDER BY	stylesheetID";
		$statement = $this->database->prepareStatement($sql, $limit, $offset);
		$statement->execute();

		while ($row = $statement->fetchArray()) {
			ImportHandler::getInstance()->getImporter('de.codequake.cms.stylesheet')->import($row['stylesheetID'], $row);
		}
	}

	/**
	 * Counts categories.
	 */
	public function countFileCategories() {
		$objectTypeID = $this->getObjectTypeID('com.woltlab.wcf.category', 'de.codequake.cms.file');

		$sql = "SELECT	COUNT(*) AS count
			FROM	wcf".$this->dbNo."_category
			WHERE	objectTypeID = ?";
		$statement = $this->database->prepareStatement($sql);
		$statement->execute(array($objectTypeID));
		$row = $statement->fetchArray();

		return $row['count'];
	}

	/**
	 * Exports categories.
	 */
	public function exportFileCategories($offset, $limit) {
		$objectTypeID = $this->getObjectTypeID('com.woltlab.wcf.category', 'de.codequake.cms.file');

		$sql = "SELECT	*
			FROM	wcf".$this->dbNo."_category
			WHERE	objectTypeID = ?
			ORDER BY parentCategoryID, showOrder, categoryID";
		$statement = $this->database->prepareStatement($sql, $limit, $offset);
		$statement->execute(array($objectTypeID));

		while ($row = $statement->fetchArray()) {
			$this->categoryCache[$row['parentCategoryID']][] = $row;
		}

		$this->exportCategoriesRecursively();
	}

	/**
	 * Exports the categories of the given parent recursively.
	 *
	 * @param int $parentID
	 */
	protected function exportCategoriesRecursively($parentID = 0) {
		if (!isset($this->categoryCache[$parentID])) {
			return;
		}

		foreach ($this->categoryCache[$parentID] as $category) {
			$additionalData = @unserialize($category['additionalData']);

			// import category
			$categoryID = ImportHandler::getInstance()->getImporter('de.codequake.cms.file.category')->import($category['categoryID'], array(
				'parentCategoryID' => $category['parentCategoryID'],
				'title' => $category['title'],
				'description' => $category['description'],
				'showOrder' => $category['showOrder'],
				'time' => $category['time'],
				'isDisabled' => $category['isDisabled'],
				'additionalData' => serialize(array()),
			));

			$this->updateCategoryI18nData($categoryID, $category);

			$this->exportCategoriesRecursively($category['categoryID']);
		}
	}

	/**
	 * Counts files.
	 */
	public function countFiles() {
		$sql = "SELECT	COUNT(*) AS count
			FROM	cms" . $this->dbNo . "_file";
		$statement = $this->database->prepareStatement($sql);
		$statement->execute();
		$row = $statement->fetchArray();

		return $row['count'];
	}

	/**
	 * Exports files.
	 */
	public function exportFiles($offset, $limit) {
		$sql = "SELECT	packageDir
			FROM	wcf".$this->dbNo."_package
			WHERE	package = ?";
		$statement = $this->database->prepareStatement($sql, 1);
		$statement->execute(array('de.codequake.cms'));
		$packageDir = $statement->fetchColumn();
		$path = FileUtil::getRealPath($this->fileSystemPath . '/' . $packageDir);

		$sql = "SELECT	*
			FROM	cms" . $this->dbNo . "_file
			ORDER BY	fileID";
		$statement = $this->database->prepareStatement($sql, $limit, $offset);
		$statement->execute();

		while ($row = $statement->fetchArray()) {
			$additionalData = array();
			$additionalData['fileLocation'] = $path . 'files/' . substr($row['fileHash'], 0, 2) . '/' . $row['fileID'] . '-' . $row['fileHash'];

			ImportHandler::getInstance()->getImporter('de.codequake.cms.file')->import($row['fileID'], $row, $additionalData);
		}
	}

	/**
	 * Counts comments.
	 *
	 * @return int
	 */
	public function countComments() {
		$objectTypeID = $this->getObjectTypeID('com.woltlab.wcf.comment.commentableContent', 'de.codequake.cms.page.comment');

		$sql = 'SELECT COUNT(*) AS count
			FROM wcf'.$this->dbNo.'_comment
			WHERE objectTypeID = ?';
		$statement = $this->database->prepareStatement($sql);
		$statement->execute(array($objectTypeID));
		$row = $statement->fetchArray();

		return $row['count'];
	}

	/**
	 * Exports comments.
	 *
	 * @param int $offset
	 * @param int $limit
	 */
	public function exportComments($offset, $limit) {
		$objectTypeID = $this->getObjectTypeID('com.woltlab.wcf.comment.commentableContent', 'de.codequake.cms.page.comment');

		$sql = 'SELECT *
			FROM wcf'.$this->dbNo.'_comment
			WHERE objectTypeID = ?
			ORDER BY commentID';
		$statement = $this->database->prepareStatement($sql, $limit, $offset);
		$statement->execute(array($objectTypeID));

		while ($row = $statement->fetchArray()) {
			ImportHandler::getInstance()->getImporter('de.codequake.cms.page.comment')->import($row['commentID'], array(
				'objectID' => $row['objectID'],
				'userID' => $row['userID'],
				'username' => $row['username'],
				'message' => $row['message'],
				'time' => $row['time'],
				'objectTypeID' => $objectTypeID,
				'responses' => 0,
				'responseIDs' => serialize(array()),
			));
		}
	}

	/**
	 * Counts comment responses.
	 *
	 * @return int
	 */
	public function countCommentResponses() {
		$objectTypeID = $this->getObjectTypeID('com.woltlab.wcf.comment.commentableContent', 'de.codequake.cms.page.comment');

		$sql = 'SELECT COUNT(*) AS count
	            FROM wcf'.$this->dbNo.'_comment_response
	            WHERE commentID IN (
	                SELECT commentID
	                FROM wcf'.$this->dbNo.'_comment
	                WHERE	objectTypeID = ?
	            )';
		$statement = $this->database->prepareStatement($sql);
		$statement->execute(array($objectTypeID));
		$row = $statement->fetchArray();

		return $row['count'];
	}

	/**
	 * Exports comment responses.
	 *
	 * @param int $offset
	 * @param int $limit
	 */
	public function exportCommentResponses($offset, $limit) {
		$objectTypeID = $this->getObjectTypeID('com.woltlab.wcf.comment.commentableContent', 'de.codequake.cms.page.comment');

		$sql = 'SELECT *
	            FROM wcf'.$this->dbNo.'_comment_response
	            WHERE commentID IN (
	                SELECT commentID
	                FROM wcf'.$this->dbNo.'_comment
	                WHERE objectTypeID = ?
	            )
	            ORDER BY responseID';
		$statement = $this->database->prepareStatement($sql, $limit, $offset);
		$statement->execute(array($objectTypeID));

		while ($row = $statement->fetchArray()) {
			ImportHandler::getInstance()->getImporter('de.codequake.cms.page.comment.response')->import($row['responseID'], array(
				'commentID' => $row['commentID'],
				'time' => $row['time'],
				'userID' => $row['userID'],
				'username' => $row['username'],
				'message' => $row['message'],
			));
		}
	}

	/**
	 * Counts server acls.
	 */
	public function countPagesACLs() {
		$this->countACLs();
	}

	/**
	 * Exports server acls.
	 */
	public function exportPagesACLs($offset, $limit) {
		$this->exportACLs($offset, $limit, 'de.codequake.cms.page', 'de.codequake.cms.page.acl');
	}

	/**
	 * Updates the i18n data of the category with the given id.
	 *
	 * @param int   $categoryID
	 * @param array $category
	 */
	private function updateCategoryI18nData($categoryID, array $category) {
		// get title
		if (preg_match('~wcf.category.category.title.category\d+~', $category['title'])) {
			$titleValues = $this->getLanguageItemValues($category['title']);
			$title = $this->importLanguageVariable('wcf.category', 'wcf.category.category.title.category'.$categoryID, $titleValues);
			if ($title === false) {
				$title = 'Imported Category '.$categoryID;
			}
		}

		// get description
		if (preg_match('~wcf.category.category.title.category\d+.description~', $category['description'])) {
			$descriptionValues = $this->getLanguageItemValues($category['description']);
			$description = $this->importLanguageVariable('wcf.category', 'wcf.category.category.description.category'.$categoryID, $descriptionValues);
			if ($description === false) {
				$description = '';
			}
		}

		// update category
		$updateData = array();
		if (!empty($title)) {
			$updateData['title'] = $title;
		}
		if (!empty($description)) {
			$updateData['description'] = $description;
		}

		if (count($updateData)) {
			$importedCategory = new Category(null, array('categoryID' => $categoryID));
			$editor = new CategoryEditor($importedCategory);
			$editor->update($updateData);
		}
	}

	/**
	 * Returns the values of the language item with the given name.
	 *
	 * @param string $languageItem
	 *
	 * @return array
	 */
	private function getLanguageItemValues($languageItem) {
		$sql = '
	            SELECT language_item.languageItemValue, language_item.languageCustomItemValue, language_item.languageUseCustomValue, language.languageCode
	            FROM wcf'.$this->dbNo.'_language_item language_item
	            LEFT JOIN wcf'.$this->dbNo.'_language language ON (language.languageID = language_item.languageID)
	            WHERE language_item.languageItem = ?';
		$statement = $this->database->prepareStatement($sql);
		$statement->execute(array($languageItem));

		$values = array();
		while ($row = $statement->fetchArray()) {
			$values[$row['languageCode']] = ($row['languageUseCustomValue'] ? $row['languageCustomItemValue'] : $row['languageItemValue']);
		}

		return $values;
	}

	/**
	 * Returns the id of the object type with the given name.
	 *
	 * @param	string		$definitionName
	 * @param	string		$objectTypeName
	 * @return	integer
	 */
	protected function getObjectTypeID($definitionName, $objectTypeName) {
		$sql = "SELECT	objectTypeID
			FROM	wcf".$this->dbNo."_object_type
			WHERE	objectType = ?
				AND definitionID = (
					SELECT definitionID FROM wcf".$this->dbNo."_object_type_definition WHERE definitionName = ?
				)";
		$statement = $this->database->prepareStatement($sql, 1);
		$statement->execute(array($objectTypeName, $definitionName));
		$row = $statement->fetchArray();

		if ($row !== false)
			return $row['objectTypeID'];
		return null;
	}

	/**
	 * Returns the object type with the given id.
	 *
	 * @param	string		$objectTypeID
	 * @return	array
	 */
	protected function getObjectType($objectTypeID) {
		$sql = "SELECT	*
			FROM	wcf".$this->dbNo."_object_type object_type,
					wcf".$this->dbNo."_object_type_definition definition
			WHERE	object_type.objectTypeID = ?
				AND object_type.definitionID = definition.definitionID";
		$statement = $this->database->prepareStatement($sql, 1);
		$statement->execute(array($objectTypeID));
		$row = $statement->fetchArray();

		if ($row !== false)
			return $row;

		return null;
	}

	/**
	 * Counts ACLs.
	 */
	protected function countACLs() {
		$sql = "SELECT	(SELECT COUNT(*) FROM wcf".$this->dbNo."_acl_option_to_group)
				+ (SELECT COUNT(*) FROM wcf".$this->dbNo."_acl_option_to_user) AS count";
		$statement = $this->database->prepareStatement($sql);
		$statement->execute();
		$row = $statement->fetchArray();
		return $row['count'];
	}

	/**
	 * Exports ACLs.
	 */
	protected function exportACLs($offset, $limit, $aclObjectType, $importerObjectType) {
		$objectTypeID = $this->getObjectTypeID('com.woltlab.wcf.acl', $aclObjectType);

		$sql = "(
				SELECT		acl_option.optionName, acl_option.optionID,
						option_to_group.objectID, option_to_group.optionValue, 0 AS userID, option_to_group.groupID
				FROM		wcf".$this->dbNo."_acl_option_to_group option_to_group,
						wcf".$this->dbNo."_acl_option acl_option
				WHERE		acl_option.optionID = option_to_group.optionID
						AND acl_option.objectTypeID = ?
			)
			UNION
			(
				SELECT		acl_option.optionName, acl_option.optionID,
						option_to_user.objectID, option_to_user.optionValue, option_to_user.userID, 0 AS groupID
				FROM		wcf".$this->dbNo."_acl_option_to_user option_to_user,
						wcf".$this->dbNo."_acl_option acl_option
				WHERE		acl_option.optionID = option_to_user.optionID
						AND acl_option.objectTypeID = ?
			)
			ORDER BY	optionID, objectID, userID, groupID";
		$statement = $this->database->prepareStatement($sql, $limit, $offset);
		$statement->execute(array($objectTypeID, $objectTypeID));
		while ($row = $statement->fetchArray()) {
			$data = array(
				'objectID' => $row['objectID'],
				'optionValue' => $row['optionValue']
			);
			if ($row['userID']) $data['userID'] = $row['userID'];
			if ($row['groupID']) $data['groupID'] = $row['groupID'];

			ImportHandler::getInstance()->getImporter($importerObjectType)->import(0, $data, array('optionName' => $row['optionName']));
		}
	}
}
