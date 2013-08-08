<?php
/**
 * ownCloud - Mail app
 *
 * @author Sebastian Schmid
 * @copyright 2013 Sebastian Schmid mail@sebastian-schmid.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Controller;

use OCA\AppFramework\Controller\Controller;
use OCA\AppFramework\Db\DoesNotExistException;
use OCA\AppFramework\Db\MultipleObjectsReturnedException;

use OCA\Mail\Db\MailAccount;

class PageController extends Controller {

	/**
	 * @var \OCA\Mail\Db\MailAccountMapper
	 */
	private $mailAccountMapper;

	/**
	 * @param \OCA\AppFramework\Http\Request $request: an instance of the request
	 * @param \OCA\AppFramework\Core\API $api: an api wrapper instance
	 * @param \OCA\Mail\Db\MailAccountMapper $mailAccountMapper: a mail account wrapper instance
	 */
	public function __construct($api, $request, $mailAccountMapper){
		parent::__construct($api, $request);
		$this->mailAccountMapper = $mailAccountMapper;
	}

	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 *
	 * @return \OCA\AppFramework\Http\TemplateResponse renders the index page
	 */
	public function index() {

		$accounts = array();
		try {
			$accounts = $this->mailAccountMapper->findByUserId($this->api->getUserId());
			$templateName = 'index';
			$params = array(
				'accounts' => $accounts,
				'api' => $this->api
			);
		} catch (DoesNotExistException $e) {
			$templateName = 'noaccount';
			$params = array(
				'accounts' => false,
				'api' => $this->api,
				'legend' => 'Connect your mail account',
				'mailAddress' => 'Mail Address',
				'imapPassword' => 'IMAP Password',
				'connect' => 'Connect'
			);
		}

		return $this->render($templateName, $params);
	}
}
