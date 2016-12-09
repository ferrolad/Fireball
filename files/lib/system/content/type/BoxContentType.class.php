<?php
namespace cms\system\content\type;

use cms\data\content\Content;
use wcf\data\box\Box;
use wcf\data\box\BoxList;
use wcf\system\request\RequestHandler;
use wcf\system\WCF;

/**
 * @author	Florian Gail
 * @copyright	2014 codeQuake
 * @license	GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl-3.0.txt>
 * @package	de.codequake.cms
 */
class BoxContentType extends AbstractContentType {
	/**
	 * @see	\cms\system\content\type\AbstractContentType::$icon
	 */
	protected $icon = 'fa-dashboard';

	/**
	 * @see	\cms\system\content\type\IContentType::getFormTemplate()
	 */
	public function getFormTemplate() {
		$boxList = new BoxList();
		$boxList->readObjects();
		$boxList = $boxList->getObjects();

		$boxesByPosition = array(
			'content' => array(),
			'sidebarLeft' => array(),
			'sidebarRight' => array()
		);

		foreach ($boxList as $box) {
			$controller = $box->getController();
			$positions = $controller::getSupportedPositions();

			if (in_array('contentTop', $positions) || in_array('contentBottom', $positions)) {
				$boxesByPosition['content'] = $box;
			}

			if (in_array('sidebarLeft', $positions)) {
				$boxesByPosition['sidebarLeft'] = $box;
			}

			if (in_array('sidebarRight', $positions)) {
				$boxesByPosition['sidebarRight'] = $box;
			}
		}

		WCF::getTPL()->assign(array(
			'boxList' => $boxList,
			'boxesByPosition' => $boxesByPosition
		));

		return parent::getFormTemplate();
	}

	/**
	 * @see	\cms\system\content\type\IContentType::getOutput()
	 */
	public function getOutput(Content $content) {
		$box = new Box($content->boxID);

		if ($box === null) {
			return '';
		}

		return $box->render();
	}
}