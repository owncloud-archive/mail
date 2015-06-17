<?php
/**
 * ownCloud - Mail app
 *
 * @author Thomas Müller
 * @copyright 2013-2014 Thomas Müller thomas.mueller@tmit.eu
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Controller;

use Horde_Imap_Client;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Http\AttachmentDownloadResponse;
use OCA\Mail\Http\HtmlResponse;
use OCA\mail\lib\service\SecurityToken;
use OCA\Mail\Service\ContactsIntegration;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;

class MessagesController extends Controller {

	/**
	 * @var \OCA\Mail\Db\MailAccountMapper
	 */
	private $mapper;

	/**
	 * @var string
	 */
	private $currentUserId;

	/**
	 * @var ContactsIntegration
	 */
	private $contactsIntegration;

	/**
	 * @var \OCA\Mail\Service\Logger
	 */
	private $logger;

	/**
	 * @var \OCP\Files\Folder
	 */
	private $userFolder;

	/**
	 * @var IL10N
	 */
	private $l10n;
	/** @var SecurityToken */
	private $securityToken;

	/**
	 * @param string $appName
	 * @param \OCP\IRequest $request
	 * @param MailAccountMapper $mapper
	 * @param $currentUserId
	 * @param $userFolder
	 * @param $contactsIntegration
	 * @param $logger
	 * @param $l10n
	 * @param SecurityToken $securityToken
	 */
	public function __construct($appName,
								$request,
								MailAccountMapper $mapper,
								$currentUserId,
								$userFolder,
								$contactsIntegration,
								$logger,
								$l10n,
								SecurityToken $securityToken) {
		parent::__construct($appName, $request);
		$this->mapper = $mapper;
		$this->currentUserId = $currentUserId;
		$this->userFolder = $userFolder;
		$this->contactsIntegration = $contactsIntegration;
		$this->logger = $logger;
		$this->l10n = $l10n;
		$this->securityToken = $securityToken;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $from
	 * @param int $to
	 * @param string $filter
	 * @return JSONResponse
	 */
	public function index($from=0, $to=20, $filter=null) {
		$mailBox = $this->getFolder();

		$folderId = $mailBox->getFolderId();
		$this->logger->debug("loading messages $from to $to of folder <$folderId>");

		$json = $mailBox->getMessages($from, $to-$from+1, $filter);

		$ci = $this->contactsIntegration;
		$json = array_map(function($j) use ($ci, $mailBox) {
			if ($mailBox->getSpecialRole() === 'trash') {
				$j['delete'] = (string)$this->l10n->t('Delete permanently');
			}

			if ($mailBox->getSpecialRole() === 'sent') {
				$j['fromEmail'] = $j['toEmail'];
				$j['from'] = $j['to'];
				if((count($j['toList']) > 1) || (count($j['ccList']) > 0)) {
					$j['from'] .= ' ' . $this->l10n->t('& others');
				}
			}

			$j['senderImage'] = $ci->getPhoto($j['fromEmail']);
			return $j;
		}, $json);

		return new JSONResponse($json);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $id
	 * @return JSONResponse
	 */
	public function show($id) {
		$accountId = $this->params('accountId');
		$folderId = $this->params('folderId');
		$mailBox = $this->getFolder();

		$account = $this->getAccount();
		try {
			$m = $mailBox->getMessage($id);
		} catch (DoesNotExistException $ex) {
			return new JSONResponse([], 404);
		}
		$json = $m->getFullMessage($account->getEmail(), $mailBox->getSpecialRole());
		$json['senderImage'] = $this->contactsIntegration->getPhoto($m->getFromEmail());
		if (isset($json['hasHtmlBody'])){
			$json['htmlBodyUrl'] = $this->buildHtmlBodyUrl($accountId, $folderId, $id);
		}

		if (isset($json['attachment'])) {
			$json['attachment'] = $this->enrichDownloadUrl($accountId, $folderId, $id, $json['attachment']);
		}
		if (isset($json['attachments'])) {
			$json['attachments'] = array_map(function($a) use ($accountId, $folderId, $id) {
				return $this->enrichDownloadUrl($accountId, $folderId, $id, $a);
			}, $json['attachments']);
		}

		return new JSONResponse($json);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @SignedRequestRequired
	 *
	 * @param int $messageId
	 * @return JSONResponse
	 */
	public function getHtmlBody($messageId) {
		try {
			$mailBox = $this->getFolder();

			$m = $mailBox->getMessage($messageId, true);
			$html = $m->getHtmlBody();

			$htmlResponse = new HtmlResponse($html);

			// Harden the default security policy
			// FIXME: Remove once ownCloud 8.1 is a requirement for the mail app
			if(class_exists('\OCP\AppFramework\Http\ContentSecurityPolicy')) {
				$policy = new ContentSecurityPolicy();
				$policy->allowEvalScript(false);
				$policy->disallowScriptDomain('\'self\'');
				$policy->disallowConnectDomain('\'self\'');
				$policy->disallowFontDomain('\'self\'');
				$policy->disallowMediaDomain('\'self\'');
				$htmlResponse->setContentSecurityPolicy($policy);
			}

			return $htmlResponse;
		} catch(\Exception $ex) {
			return new TemplateResponse($this->appName, 'error', ['message' => $ex->getMessage()], 'none');
		}
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $messageId
	 * @param string $attachmentId
	 * @return AttachmentDownloadResponse
	 */
	public function downloadAttachment($messageId, $attachmentId) {
		$mailBox = $this->getFolder();

		$attachment = $mailBox->getAttachment($messageId, $attachmentId);

		return new AttachmentDownloadResponse(
			$attachment->getContents(),
			$attachment->getName(),
			$attachment->getType());
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $messageId
	 * @param string $attachmentId
	 * @param string $targetPath
	 * @return JSONResponse
	 */
	public function saveAttachment($messageId, $attachmentId, $targetPath) {
		$mailBox = $this->getFolder();

		$attachmentIds = [$attachmentId];
		if($attachmentId === 0) {
			$m = $mailBox->getMessage($messageId);
			$attachmentIds = array_map(function($a){
				return $a['id'];
			}, $m->attachments);
		}
		foreach($attachmentIds as $attachmentId) {
			$attachment = $mailBox->getAttachment($messageId, $attachmentId);

			$fileName = $attachment->getName();
			$fileParts = pathinfo($fileName);
			$fileName = $fileParts['filename'];
			$fileExtension = $fileParts['extension'];
			$fullPath = "$targetPath/$fileName.$fileExtension";
			$counter = 2;
			while($this->userFolder->nodeExists($fullPath)) {
				$fullPath = "$targetPath/$fileName ($counter).$fileExtension";
				$counter++;
			}

			$newFile = $this->userFolder->newFile($fullPath);
			$newFile->putContent($attachment->getContents());
		}

		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $messageId
	 * @param boolean $starred
	 * @return JSONResponse
	 */
	public function toggleStar($messageId, $starred) {
		$mailBox = $this->getFolder();

		$mailBox->setMessageFlag($messageId, Horde_Imap_Client::FLAG_FLAGGED, !$starred);

		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return JSONResponse
	 */
	public function destroy($id) {
		try {
			$account = $this->getAccount();
			$m = new \OCA\Mail\Account($account);
			$m->deleteMessage(base64_decode($this->params('folderId')), $id);
			return new JSONResponse();

		} catch (DoesNotExistException $e) {
			return new JSONResponse();
		}
	}

	/**
	 * TODO: private functions below have to be removed from controller -> imap service to be build
	 */
	private function getAccount() {
		$accountId = $this->params('accountId');
		return $this->mapper->find($this->currentUserId, $accountId);
	}

	/**
	 * @return \OCA\Mail\Mailbox
	 */
	private function getFolder() {
		$account = $this->getAccount();
		$m = new \OCA\Mail\Account($account);
		$folderId = base64_decode($this->params('folderId'));
		return $m->getMailbox($folderId);
	}

	/**
	 * @param integer $id
	 * @param $accountId
	 * @param $folderId
	 * @return callable
	 */
	private function enrichDownloadUrl($accountId, $folderId, $id, $attachment) {
		$downloadUrl = \OCP\Util::linkToRoute('mail.messages.downloadAttachment', [
			'accountId' => $accountId,
			'folderId' => $folderId,
			'messageId' => $id,
			'attachmentId' => $attachment['id'],
		]);
		$downloadUrl = \OC::$server->getURLGenerator()->getAbsoluteURL($downloadUrl);
		$attachment['downloadUrl'] = $downloadUrl;
		$attachment['mimeUrl'] = \OC_Helper::mimetypeIcon($attachment['mime']);
		return $attachment;
	}

	/**
	 * @param $accountId
	 * @param $folderId
	 * @param int $id
	 * @return string
	 */
	private function buildHtmlBodyUrl($accountId, $folderId, $id) {
		$htmlBodyUrl = \OC::$server->getURLGenerator()->linkToRoute('mail.messages.getHtmlBody', [
			'accountId' => $accountId,
			'folderId' => $folderId,
			'messageId' => $id,
		]);

		$signedRequest = $this->securityToken->calculateToken(parse_url($htmlBodyUrl)['path']);
		return $htmlBodyUrl . '?token=' . urlencode($signedRequest);
	}

}
