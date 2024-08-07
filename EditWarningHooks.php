<?php
namespace EditWarning;

use DatabaseUpdater;
use OutputPage;
use Skin;

class EditWarningHooks {

	/**
	 * Setup EditWarning extension
	 *
	 * @return bool Returns always true.
	 */
	public static function init() {
		if ( !defined( 'MEDIAWIKI' ) && !defined( 'EDITWARNING_UNITTEST' ) ) {
			echo <<<EOT
			<p>To install this extension, put the following line in LocalSettings.php:\n
			<pre>require_once "\$IP/extensions/EditWarning/EditWarning.php";</pre></p>\n\n
			
			<p>See <a href="http://www.mediawiki.org/wiki/Extension:EditWarning/0.4">
			http://www.mediawiki.org/wiki/Extension:EditWarning/0.4</a> for more information.</p>
EOT;
			exit( 1 );
		}

		return true;
	}

	/**
	 * Gets the section id from GET or POST
	 *
	 * @return int Section id.
	 */
	public function getSection() {
		if ( defined( 'EDITWARNING_UNITTEST' ) ) {
			return $GLOBALS['unitGetSection'];
		}

		if ( isset( $_GET['section'] ) && !isset( $_POST['wpSection'] ) ) {
			return intval( $_GET['section'] );
		} else {
			if ( isset( $_POST['wpSection'] ) ) {
				return intval( $_POST['wpSection'] );
			} else {
				return 0;
			}
		}
	}

	/**
	 * Function to show info message about created or updated locks for sections
	 * or articles.
	 *
	 * @param int $msgtype Type of edit (article or section).
	 * @param int $timestamp Timestamp indicating when the lock was created or updated.
	 * @param string $cancel_url URL to cancel the current action.
	 */
	private function showInfoMsg( $msgtype, $timestamp, $cancel_url ) {
		global $wgEditWarning_ShowInfoBox, $wgType_Article;

		if ( $wgEditWarning_ShowInfoBox ) {
			$type = ( $msgtype == $wgType_Article ) ? "ArticleNotice" : "SectionNotice";

			// Show info message with updated timestamp.
			$msg_params[] = date( "Y-m-d", $timestamp );
			$msg_params[] = date( "H:i", $timestamp );
			$msg = EditWarningMsg::getInstance( $type, $cancel_url, $msg_params );
			$msg->show( $msgtype );
			unset( $msg );
		}
	}

	/**
	 * Function to show warning message about existing locks for sections or
	 * articles.
	 *
	 * @param int $msgtype Type of message to display (Article, ArticleSectionConflict, Section).
	 * @param EditWarningLock $lockobj The EditWarningLock object containing lock information.
	 * @param string $cancel_url URL to cancel the current action.
	 */
	private function showWarningMsg( $msgtype, $lockobj, $cancel_url ) {
		global $wgType_Article, $wgType_Article_Section_Conflict, $wgType_Section;
		switch ( $msgtype ) {
			case $wgType_Article:
				$type = "ArticleWarning";
				break;
			case $wgType_Article_Section_Conflict:
				$type = "ArticleSectionWarning";
				break;
			case $wgType_Section:
				$type = "SectionWarning";
				break;
		}

		// Calculate time to wait
		$difference = floatval( abs( time() - $lockobj->getTimestamp() ) );
		$time_to_wait = round( $difference / 60, 0 );

		// Parameters for message string
		if ( $msgtype == $wgType_Article || $msgtype == $wgType_Section ) {
			$msg_params[] = $lockobj->getUserName();
			$msg_params[] = date( "Y-m-d", $lockobj->getTimestamp() );
			$msg_params[] = date( "H:i", $lockobj->getTimestamp() );
		}

		$msg_params[] = $time_to_wait + 1;

		// Use minutes or seconds string?
		$msg_params[] = wfMessage( 'ew-minutes' )->text();

		$msg = EditWarningMsg::getInstance( $type, $cancel_url, $msg_params );
		$msg->show( $type );
		unset( $msg );
	}

	/**
	 * Action on article editing
	 *
	 * This method is called by the BeforePageDisplay hook.
	 *
	 * @param OutputPage $out OutputPage object.
	 * @param Skin $skin Skin object.
	 * @return bool|int It returns a constant int if it runs in unit test
	 *                     environment, else true.
	 */
	public static function edit( OutputPage $out, Skin $skin ) {
		global $wgRequest, $wgPHP_SELF, $wgTS_Timeout, $wgType_Article,
		$wgType_Article_Section_Conflict, $wgType_Section;

		$out->addModules( 'ext.editwarning' );

		$dbr = null;
		$dbw = null;

		$user = $out->getUser();

		$hook = new EditWarningHooks();
		$ew = new EditWarning();

		$wgRequest = $out->getRequest();

		if ( $wgRequest->getVal( 'action' ) === 'edit' ||
			$wgRequest->getVal( 'action' ) === 'formedit' ||
			$wgRequest->getVal( 'veaction' ) === 'edit' ) {

			// Abort on nonexisting pages
			if ( $out->getTitle()->getArticleID() < 1 ) {
				return true;
			}

			if ( !defined( 'EDITWARNING_UNITTEST' ) ) {
				$dbr = wfGetDB( DB_REPLICA );
				$dbw = wfGetDB( DB_MASTER );
			}

			$ew->setUserID( $user->getID() );
			$ew->setUserName( $user->getName() );
			$ew->setArticleID( $out->getTitle()->getArticleID() );
			$section = $hook->getSection();
			$msg_params = [];

			// Get article title for cancel button
			if ( $out->getTitle()->getNamespace() == 'NS_MAIN' ) {
				$article_title = $out->getTitle()->getPartialURL();
			} else {
				$article_title = $article_title =
				$out->getTitle()->getNsText() . ":" . $article_title = $out->getTitle()->getPartialURL();
			}

			$url = $wgPHP_SELF . "?title=" . $article_title . "&cancel=true";

			// Check request values
			if ( $section > 0 ) {
				// Section editing
				$ew->setSection( $section );
				$ew->load( $dbr );

				// Is the whole article locked?
				if ( $ew->isArticleLocked() ) {
					// Is it by the user?
					if ( $ew->isArticleLockedByUser() ) {
						// The user has already a lock on the whole article, but
						// edits now a single section. Change article lock to
						// section lock.
						if ( defined( 'EDITWARNING_UNITTEST' ) ) {
							return EDIT_SECTION_NEW;
						}

						$ew->removeLock( $dbw );
						$ew->saveLock( $dbw, $section );
						$hook->showInfoMsg( $wgType_Section, $ew->getTimestamp( $wgTS_Timeout ), $url );
						unset( $ew );

						return true;
					} else {
						// Someone else has a lock on the whole article. Show warning.
						if ( defined( 'EDITWARNING_UNITTEST' ) ) {
							return EDIT_ARTICLE_OTHER;
						}

						$hook->showWarningMsg( $wgType_Article, $ew->getArticleLock(), $url );
						unset( $ew );

						return true;
					}
				} elseif ( $ew->isSectionLocked( $section ) ) {
					$sectionLock = $ew->getSectionLock( $section );

					// Is the section locked by the user?
					if ( $ew->isSectionLockedByUser( $sectionLock ) ) {
						// The section is locked by the user. Update lock.
						if ( defined( 'EDITWARNING_UNITTEST' ) ) {
							return EDIT_SECTION_USER;
						}

						$ew->updateLock( $dbw, $section );
						$hook->showInfoMsg( $wgType_Section, $ew->getTimestamp( $wgTS_Timeout ), $url );
						unset( $ew );

						return true;
					} else {
						// The section is locked by someone else. Show warning.
						if ( defined( 'EDITWARNING_UNITTEST' ) ) {
							return EDIT_SECTION_OTHER;
						}

						$hook->showWarningMsg( $wgType_Section, $sectionLock, $url );
						unset( $ew );

						return true;
					}
				} else {
					// No locks. Create section lock for user.
					if ( defined( 'EDITWARNING_UNITTEST' ) ) {
						return EDIT_SECTION_NEW;
					}

					// Don't save locks for anonymous users.
					if ( $ew->getUserID() < 1 ) {
						return true;
					}

					$ew->saveLock( $dbw, $section );
					$hook->showInfoMsg( $wgType_Section, $ew->getTimestamp( $wgTS_Timeout ), $url );
					unset( $ew );

					return true;
				}
			} else {
				// Article editing
				$ew->load( $dbr );

				// Is the article locked?
				if ( $ew->isArticleLocked() ) {
					if ( $ew->isArticleLockedByUser() ) {
						// The article is locked by the user. Update lock.
						if ( defined( 'EDITWARNING_UNITTEST' ) ) {
							return EDIT_ARTICLE_USER;
						}

						$ew->updateLock( $dbw );
						$hook->showInfoMsg( $wgType_Article, $ew->getTimestamp( $wgTS_Timeout ), $url );
						unset( $ew );

						return true;
					} else {
						// The article is locked by someone else. Show warning.
						if ( defined( 'EDITWARNING_UNITTEST' ) ) {
							return EDIT_ARTICLE_OTHER;
						}

						$article_lock = $ew->getArticleLock();
						$hook->showWarningMsg( $wgType_Article, $article_lock, $url );
						unset( $ew );

						return true;
					}
				} elseif ( $ew->anySectionLocks() ) {
					// There is at least one section lock
					if ( $ew->anySectionLocksByOthers() ) {
						// At least one section lock by another user.
						// So an article lock is not possible. Show warning.
						if ( defined( 'EDITWARNING_UNITTEST' ) ) {
							return EDIT_SECTION_OTHER;
						}

						$sectionLocks = $ew->getSectionLocksByOther();
						// Get the newest lock of a section for the warning message.
						$lock = $sectionLocks[$ew->getSectionLocksByOtherCount() - 1];
						$hook->showWarningMsg( $wgType_Article_Section_Conflict, $lock, $url );
						unset( $ew );

						return true;
					} else {
						// The user has exclusively one or more locks on sections
						// of the article, but now wants to edit the whole article.
						// Change sections locks to article lock.
						if ( defined( 'EDITWARNING_UNITTEST' ) ) {
							return EDIT_ARTICLE_NEW;
						}

						$ew->removeUserLocks( $dbw );
						$ew->saveLock( $dbw );
						$hook->showInfoMsg( $wgType_Article, $ew->getTimestamp( $wgTS_Timeout ), $url );
						unset( $ew );

						return true;
					}
				} else {
					// No locks. Create new article lock.
					if ( defined( 'EDITWARNING_UNITTEST' ) ) {
						return EDIT_ARTICLE_NEW;
					}

					// Don't save locks for anonymous users.
					if ( $ew->getUserID() < 1 ) {
						return true;
					}

					$ew->saveLock( $dbw );
					$hook->showInfoMsg( $wgType_Article, $ew->getTimestamp( $wgTS_Timeout ), $url );
					unset( $ew );
				}
			}
		} else {
			// Action if saved or aborted.
			// !!! This actions is called on each page load except edit actions
			if ( $out->getTitle()->getNamespace() > -1 ) {
				$hook->removeWarning( $ew, $out->getWikiPage(), $user );
			}
		}

		return true;
	}

	/**
	 * Handles schema updates for the extension.
	 *
	 * This static function updates the database schema for the extension by dropping the existing
	 * 'editwarning_locks' table and creating a new one. This approach is taken to handle the renaming
	 * of the 'timestamp' column, which is not allowed in Sqlite.
	 *
	 * @param DatabaseUpdater $updater The DatabaseUpdater instance used to handle the updates.
	 * @return bool Returns true upon successful update.
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		$dir = $GLOBALS['wgExtensionDirectory'] . '/EditWarning/sql/';

		// For Sqlite it is not possible to name a column 'timestamp' (see
		// DatabaseSqlite->replaceVars); a pragmatic approach to rename the column in existing
		// databases is to drop and create the table. The loss of data in this case is OK due to its
		// temporary nature.
		$updater->dropExtensionTable( 'editwarning_locks', $dir . 'editwarning_locks_drop.sql' );
		$updater->addExtensionTable( 'editwarning_locks', $dir . 'editwarning_locks_create.sql' );
		$updater->addExtensionTable( 'editwarning_locks', $dir . 'editwarning_locks_alter.sql' );

		return true;
	}

	/**
	 * Action if article is saved / canceled.
	 *
	 * @param EditWarning $ew
	 * @param WikiPage $wikiPage WikiPage object.
	 * @param User $user object.
	 * @return bool Returns always true.
	 */
	private function removeWarning( $ew, $wikiPage, $user ) {
		// Abort on nonexisting pages or anonymous users.

		if ( $wikiPage->getTitle()->getArticleID() < 1 || $user->getID() < 1 ) {
			return true;
		}

		$dbw = wfGetDB( DB_MASTER );
		$ew->setUserID( $user->getID() );
		$ew->setArticleID( $wikiPage->getTitle()->getArticleID() );
		$ew->removeLock( $dbw );

		return true;
	}

	/**
	 * Action on user logout.
	 *
	 * This method is called by the UserLogout hook.
	 *
	 * @param User $user The User object representing the user who is logging out.
	 * @return bool Always returns true.
	 *
	 */
	public static function logout( $user ) {
		$ew = new EditWarning();

		$dbw = wfGetDB( DB_MASTER );
		$ew->setUserID( $user->getID() );
		$ew->removeUserLocks( $dbw );

		return true;
	}
}
