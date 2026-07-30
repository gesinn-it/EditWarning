<?php

namespace EditWarning;

use ApiBase;
use Exception;
use MediaWiki\MediaWikiServices;
use User;

class EditWarningApi extends ApiBase {

	public function execute() {
		// Get Params
		$params = $this->extractRequestParams();
		$ewAction = $params['ewaction'];
		$articleID = $params['articleid'];
		$sectionID = $params['section'];
		$user = User::newFromName( $params['user'] );

		// Create EditWarning Object of current user and article ID.
		$ew = new EditWarning();
		$ew->setUserID( $user->getId() );
		$ew->setUserName( $user->getName() );
		$ew->setArticleID( $articleID );

		// If the Lock should be related to a certain section
		if ( $sectionID !== null ) {
			$ew->setSection( $sectionID );
		}

		$section = $sectionID ?? 0;

		try {
			$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
			$ew->removeLock( $dbw, $section );

			if ( $ewAction === 'lock' ) {
				$ew->saveLock( $dbw, $section );
			}

			$output = [ $ewAction => [
				'articleid' => $articleID,
				'section' => $sectionID,
				'user' => $user->getName(),
			] ];

			$this->getResult()->addValue( 'success', $this->getModuleName(), $output );

		} catch ( Exception $e ) {
			$error = [
				'code' => 'api_exception',
				'info' => $e->getMessage(),
			];
			$this->getResult()->addValue( 'error', $this->getModuleName(), $error );
		}
	}

	/**
	 * @return array allowed parameters
	 */
	public function getAllowedParams() {
		return [
			'articleid' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG => 'apihelp-editwarning-apiparam-articleid',
			],
			'section' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false,
				ApiBase::PARAM_HELP_MSG => 'apihelp-editwarning-apiparam-section',
			],
			'ewaction' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG => 'apihelp-editwarning-apiparam-ewaction',
			],
			'user' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG => 'apihelp-editwarning-apiparam-user',
			]
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExamplesMessages() {
		return [
			'action=editwarning&ewaction=lock&articleid=1&user=Admin' => 'apihelp-editwarning-example-1',
			'action=editwarning&ewaction=unlock&articleid=56&user=Admin' => 'apihelp-editwarning-example-2',
			'action=editwarning&ewaction=lock&section=3&articleid=1&user=Admin' => 'apihelp-editwarning-example-3',
			'action=editwarning&ewaction=unlock&section=3&articleid=56&user=Admin' => 'apihelp-editwarning-example-4',
		];
	}
}
