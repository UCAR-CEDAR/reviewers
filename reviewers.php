<?php
    # Not a valid entry point, skip unless MEDIAWIKI is defined
    if (!defined('MEDIAWIKI')) {
	echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "$IP/extensions/reviewers/reviewers.php" );
EOT;
	exit( 1 );
    }
    
    $dir = dirname( __FILE__ ) . '/' ;
    $wgAutoloadClasses['Reviewers'] = $dir .  'Reviewers_body.php';
    $wgExtensionMessagesFiles['Reviewers'] = $dir .  'Reviewers.i18n.php';
    $wgSpecialPages['Reviewers'] = 'Reviewers';
    $wgAvailableRights[]='reviewers_view';
    $wgGroupPermissions['ReviewerAdmin']['reviewer_admin'] = true;
    $wgGroupPermissions['Reviewer']['review'] = true;
    $wgHooks['LanguageGetSpecialPageAliases'][] = 'ReviewersLocalizedPageName';

    function ReviewersLocalizedPageName( &$specialPageArray, $code )
    {
	# The localized title of the special page is among the messages of
	# the extension:
	#wfLoadExtensionMessages('Reviewers');
	$text = wfMsg('reviewers');

	# Convert from title in text form to DBKey and put it into the
	# alias array:
	$title = Title::newFromText($text);
	$specialPageArray['Reviewers'][] = $title->getDBKey();

	return true;
    }

    $wgExtensionCredits['other'][]=array(
	'name' => 'SSKI 2008 Document Reviewer Wiki',
	'version' => '1.0',
	'author' => 'Patrick West',
	'url' => 'http://cedarweb.hao.ucar.edu',
	'description' => 'Allows a person to review documents for SSKI 2008.'
    );
?>
