<?php
class Reviewers extends SpecialPage
{
    function Reviewers()
    {
	SpecialPage::SpecialPage("Reviewers");
	#wfLoadExtensionMessages( 'Reviewers' ) ;
    }
    
    function execute( $par ) {
	global $wgRequest, $wgOut;
	
	$this->setHeaders();
	
	# Get request data from, e.g.
	$param = $wgRequest->getText('status');
	if( $param == "Add User" )
	{
	    $this->new_user() ;
	}
	else if( $param == "Add Paper" )
	{
	    $this->new_doc() ;
	}
	else if( $param == "Assign User" )
	{
	    $this->assign_user() ;
	}
	else if( $param == "change_status" )
	{
	    $this->change_status() ;
	}
	else if( $param == "unassign_reviewer" )
	{
	    $this->unassign_reviewer() ;
	}
	else if( $param == "really_unassign_reviewer" )
	{
	    $this->really_unassign_reviewer() ;
	}
	else
	{
	    $this->displayForm() ;
	}
    }

    function displayForm() {
	global $wgUser, $wgRequest, $wgOut, $wgServer;

	// does this user already have a wiki login?
	$loggedin = $wgUser->isLoggedIn() ;

	$cedarid = 0 ;
	if( !$loggedin )
	{
	    $wgOut->addHTML( "<BR /><BR />" ) ;
	    $wgOut->addHTML( "<SPAN STYLE=\"font-size:16pt;font-weight:bold;\">You must be logged in to use this page</SPAN>" ) ;
	    return ;
	}

	if( !$wgUser->isAllowed( 'reviewer_admin' ) && !$wgUser->isAllowed( 'review' ) )
	{
	    $wgOut->addHTML( "<BR /><BR />" ) ;
	    $wgOut->addHTML( "<SPAN STYLE=\"font-size:16pt;font-weight:bold;\">You do not have permission to view this page</SPAN>" ) ;
	    return ;
	}

	# grab the master database and the tables needed
	$dbw =& wfGetDB( DB_MASTER );
	$doc_table = $dbw->tableName( 'reviewer_docs' );
	$reviewer_table = $dbw->tableName( 'reviewers' );
	$user_table = $dbw->tableName( 'user' );
	$group_table = $dbw->tableName( 'user_groups' );

	# If I'm a reviewer then display the list of documents that I am reviewing per status. Show new papers first, then reviewing, then done. Next to
	# reviewiing and done documents show their review document link. This way they can update that. Just attach wpDestFile to the name and go with it.

	# If I'm an administrator then display the admin interface. Add a new user, upload a new document, assign a user to a document. Then show the list of
	# documents and which users are reviewing and what their status is.

	# The document list will be doc_id, doc_name, doc_title, doc_real_name (not path, just file name)
	# The reviewer list will be user_id (wiki user id), doc_id, status_id, review_doc_id
	if( $wgUser->isAllowed( 'reviewer_admin' ) )
	{
	    # new user form
	    if ($wgUser->isAllowedToCreateAccount()) {
		$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:18pt;\">Reviewer Admin</SPAN><BR /><BR />\n" ) ;
		$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">Add a New User</SPAN><BR />\n" ) ;
		$wgOut->addHTML( "<FORM name=\"new_user_form\" action=\"$wgServer/wiki/index.php/Special:Reviewers\" method=\"POST\">\n" ) ;
		$wgOut->addHTML( "  <TABLE WIDTH=\"700px\" BORDER=\"0\">\n" ) ;
		$wgOut->addHTML( "    <TR>\n" ) ;
		$wgOut->addHTML( "      <TD WIDTH=\"200\">\n" ) ;
		$wgOut->addHTML( "        username:<BR /> <INPUT TYPE=\"text\" NAME=\"username\" SIZE=\"20\">\n" ) ;
		$wgOut->addHTML( "      </TD>\n" ) ;
		$wgOut->addHTML( "      <TD WIDTH=\"200\">\n" ) ;
		$wgOut->addHTML( "        real name:<BR /> <INPUT TYPE=\"text\" NAME=\"real_name\" SIZE=\"20\">\n" ) ;
		$wgOut->addHTML( "      </TD>\n" ) ;
		$wgOut->addHTML( "      <TD WIDTH=\"200\">\n" ) ;
		$wgOut->addHTML( "        email:<BR /> <INPUT TYPE=\"text\" NAME=\"email\" SIZE=\"20\">\n" ) ;
		$wgOut->addHTML( "      </TD>\n" ) ;
		$wgOut->addHTML( "      <TD WIDTH=\"100\">\n" ) ;
		$wgOut->addHTML( "        <INPUT TYPE=\"SUBMIT\" NAME=\"status\" VALUE=\"Add User\">\n" ) ;
		$wgOut->addHTML( "      </TD>\n" ) ;
		$wgOut->addHTML( "    </TR>\n" ) ;
		$wgOut->addHTML( "  </TABLE>\n" ) ;
		$wgOut->addHTML( "</FORM>\n" ) ;
		$wgOut->addHTML( "<BR />\n" ) ;
		$wgOut->addHTML( "<BR />\n" ) ;
	    }

	    # new paper form
	    $wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">Add a New Paper to be Reviewed</SPAN><BR />\n" ) ;
	    $wgOut->addHTML( "<FORM name=\"new_paper_form\" action=\"$wgServer/wiki/index.php/Special:Reviewers\" method=\"POST\">\n" ) ;
	    $wgOut->addHTML( "  <TABLE WIDTH=\"900px\" BORDER=\"0\">\n" ) ;
	    $wgOut->addHTML( "    <TR>\n" ) ;
	    $wgOut->addHTML( "      <TD WIDTH=\"400\">\n" ) ;
	    $wgOut->addHTML( "        paper's name (include file extension):<BR /> <INPUT TYPE=\"text\" NAME=\"paper_name\" SIZE=\"20\">\n" ) ;
	    $wgOut->addHTML( "      </TD>\n" ) ;
	    $wgOut->addHTML( "      <TD WIDTH=\"400\">\n" ) ;
	    $wgOut->addHTML( "        paper's title:<BR /> <INPUT TYPE=\"text\" NAME=\"paper_title\" SIZE=\"60\">\n" ) ;
	    $wgOut->addHTML( "      </TD>\n" ) ;
	    $wgOut->addHTML( "      <TD WIDTH=\"100\">\n" ) ;
	    $wgOut->addHTML( "        <INPUT TYPE=\"SUBMIT\" NAME=\"status\" VALUE=\"Add Paper\">\n" ) ;
	    $wgOut->addHTML( "      </TD>\n" ) ;
	    $wgOut->addHTML( "    </TR>\n" ) ;
	    $wgOut->addHTML( "  </TABLE>\n" ) ;
	    $wgOut->addHTML( "</FORM>\n" ) ;
	    $wgOut->addHTML( "<BR />\n" ) ;
	    $wgOut->addHTML( "<BR />\n" ) ;

	    # assign a user to review a paper
	    $wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">Assign a Reviewer to a Paper</SPAN><BR />\n" ) ;
	    $wgOut->addHTML( "<FORM name=\"new_paper_form\" action=\"$wgServer/wiki/index.php/Special:Reviewers\" method=\"POST\">\n" ) ;
	    $wgOut->addHTML( "  <TABLE WIDTH=\"800px\" BORDER=\"0\">\n" ) ;
	    $wgOut->addHTML( "    <TR>\n" ) ;
	    $wgOut->addHTML( "      <TD WIDTH=\"300\">\n" ) ;
	    $wgOut->addHTML( "        reviewer:<BR />\n" ) ;
	    $sql="SELECT u.user_id,u.user_real_name FROM $user_table u,$group_table g where g.ug_group = 'Reviewer' && u.user_id = g.ug_user" ;
	    $u_res = $dbw->query( $sql ) ;
	    if( !$u_res )
	    {
		$wgOut->addHTML( "        Unable to query the Database" ) ;
	    }
	    else
	    {
		$wgOut->addHTML( "        <SELECT NAME=\"user_id\" SIZE=\"1\">" ) ;
		while( ( $u_obj = $dbw->fetchObject( $u_res ) ) )
		{
		    $user_id = $u_obj->user_id ;
		    $user_real_name = $u_obj->user_real_name ;
		    $wgOut->addHTML( "        <OPTION value=\"$user_id\">$user_real_name</OPTION>" ) ;
		}
		$wgOut->addHTML( "        </SELECT>" ) ;
	    }
	    $wgOut->addHTML( "      </TD>\n" ) ;
	    $wgOut->addHTML( "      <TD WIDTH=\"400\">\n" ) ;
	    $wgOut->addHTML( "        paper:<BR />\n" ) ;
	    $sql="SELECT d.doc_id,d.doc_real_name,d.doc_title FROM $doc_table d" ;
	    $g_res = $dbw->query( $sql ) ;
	    if( !$g_res )
	    {
		$wgOut->addHTML( "        Unable to query the Database" ) ;
	    }
	    else
	    {
		$wgOut->addHTML( "        <SELECT NAME=\"doc_id\" SIZE=\"1\">" ) ;
		while( ( $g_obj = $dbw->fetchObject( $g_res ) ) )
		{
		    $doc_id = $g_obj->doc_id ;
		    $doc_real_name = $g_obj->doc_real_name ;
		    $doc_title = $g_obj->doc_title ;
		    $wgOut->addHTML( "        <OPTION value=\"$doc_id\">$doc_real_name</OPTION>" ) ;
		}
		$wgOut->addHTML( "        </SELECT>" ) ;
	    }
	    $wgOut->addHTML( "      </TD>\n" ) ;
	    $wgOut->addHTML( "      <TD WIDTH=\"100\">\n" ) ;
	    $wgOut->addHTML( "        <INPUT TYPE=\"SUBMIT\" NAME=\"status\" VALUE=\"Assign User\">\n" ) ;
	    $wgOut->addHTML( "      </TD>\n" ) ;
	    $wgOut->addHTML( "    </TR>\n" ) ;
	    $wgOut->addHTML( "  </TABLE>\n" ) ;
	    $wgOut->addHTML( "</FORM>\n" ) ;
	    $wgOut->addHTML( "<BR />\n" ) ;
	    $wgOut->addHTML( "<BR />\n" ) ;

	    #document list with list of reviewers and their status
	    $wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">Papers</SPAN><BR />\n" ) ;
	    $wgOut->addHTML( "<TABLE WIDTH=\"810\" BORDER=\"1\">\n" ) ;
	    $wgOut->addHTML( "  <TR>\n" ) ;
	    $wgOut->addHTML( "    <TD WIDTH=\"10\" ALIGN=\"center\" VALIGN=\"center\">\n" ) ;
	    $wgOut->addHTML( "      id\n" ) ;
	    $wgOut->addHTML( "    </TD>\n" ) ;
	    $wgOut->addHTML( "    <TD WIDTH=\"500\" ALIGN=\"center\" VALIGN=\"center\">\n" ) ;
	    $wgOut->addHTML( "      Paper\n" ) ;
	    $wgOut->addHTML( "    </TD>\n" ) ;
	    $wgOut->addHTML( "    <TD WIDTH=\"300\" ALIGN=\"center\" VALIGN=\"center\">\n" ) ;
	    $wgOut->addHTML( "      Reviewers/Status\n" ) ;
	    $wgOut->addHTML( "    </TD>\n" ) ;
	    $wgOut->addHTML( "  </TR>\n" ) ;
	    $sql="SELECT d.doc_id,d.doc_name,d.doc_title,d.doc_real_name FROM $doc_table d" ;
	    $d_res = $dbw->query( $sql ) ;
	    if( !$d_res )
	    {
		$wgOut->addHTML( "  <TR>\n" ) ;
		$wgOut->addHTML( "    <TD COLSPAN=\"3\" WIDTH=\"100%\" ALIGN=\"center\" VALIGN=\"center\">\n" ) ;
		$wgOut->addHTML( "        Unable to query the Database\n" ) ;
		$wgOut->addHTML( "    </TD>\n" ) ;
		$wgOut->addHTML( "  </TR>\n" ) ;
	    }
	    else
	    {
		while( ( $d_obj = $dbw->fetchObject( $d_res ) ) )
		{
		    $d_doc_id = $d_obj->doc_id ;
		    $d_doc_name = $d_obj->doc_name ;
		    $d_doc_title = $d_obj->doc_title ;
		    $d_doc_real_name = $d_obj->doc_real_name ;
		    $wgOut->addHTML( "  <TR>\n" ) ;
		    $wgOut->addHTML( "    <TD WIDTH=\"10\" ALIGN=\"center\" VALIGN=\"center\">\n" ) ;
		    $wgOut->addHTML( "      $d_doc_id\n" ) ;
		    $wgOut->addHTML( "    </TD>\n" ) ;
		    $wgOut->addHTML( "    <TD WIDTH=\"500\" ALIGN=\"left\" VALIGN=\"center\">\n" ) ;
		    $wgOut->addHTML( "      <A HREF=\"$wgServer/wiki/index.php/Media:$d_doc_name\">Paper: $d_doc_name</A>\n" ) ;
		    $wgOut->addHTML( "      <BR />\n" ) ;
		    $wgOut->addHTML( "      <BR />\n" ) ;
		    $wgOut->addHTML( "      File Name:<BR />\n" ) ;
		    $wgOut->addHTML( "      $d_doc_real_name\n" ) ;
		    $wgOut->addHTML( "      <BR />\n" ) ;
		    $wgOut->addHTML( "      <BR />\n" ) ;
		    $wgOut->addHTML( "      Title:<BR />\n" ) ;
		    $wgOut->addHTML( "      $d_doc_title\n" ) ;
		    $wgOut->addHTML( "    </TD>\n" ) ;
		    $wgOut->addHTML( "    <TD WIDTH=\"300\" ALIGN=\"center\" VALIGN=\"center\">\n" ) ;
		    $sql="SELECT u.user_id,u.user_real_name,r.status_id FROM $reviewer_table r, $user_table u WHERE r.doc_id = $d_doc_id AND u.user_id = r.user_id" ;
		    $r_res = $dbw->query( $sql ) ;
		    if( !$r_res )
		    {
			$wgOut->addHTML( "      No Reviewers\n" ) ;
		    }
		    else
		    {
			$is_first = true ;
			while( ( $r_obj = $dbw->fetchObject( $r_res ) ) )
			{
			    $r_user_id = $r_obj->user_id ;
			    $r_user_real_name = $r_obj->user_real_name ;
			    $r_status_id = $r_obj->status_id ;
			    $r_status_name = "" ;
			    switch ($r_status_id) {
			    case 0:
				$r_status_name = "new" ;
				break ;
			    case 1:
				$r_status_name = "reviewing" ;
				break ;
			    case 2:
				$r_status_name = "done" ;
				break ;
			    }
			    $review_doc_name = "R_$d_doc_id" . "_" . "$r_user_id.doc" ;
			    if( $is_first == false )
			    {
				$wgOut->addHTML( "      <BR />\n" ) ;
			    }
			    $wgOut->addHTML( "      $r_user_real_name: $r_status_name" ) ;
			    if( $r_status_id == 2 )
			    {
				$wgOut->addHTML( ": <A HREF=\"$wgServer/wiki/index.php/Media:$review_doc_name\">review doc</A>" ) ;
			    }
			    $wgOut->addHTML( "- <SPAN STYLE=\"font-size:8pt;\"><A HREF=\"$wgServer/wiki/index.php/Special:Reviewers?status=unassign_reviewer&amp;user_id=$r_user_id&amp;doc_id=$d_doc_id\">unassign</A></SPAN>\n" ) ;
			    $is_first = false ;
			}
		    }
		    $wgOut->addHTML( "    </TD>\n" ) ;
		    $wgOut->addHTML( "  </TR>\n" ) ;
		}
	    }
	    $wgOut->addHTML( "</TABLE>\n" ) ;
	    $wgOut->addHTML( "<BR /><BR />\n" ) ;
	}

	if( $wgUser->isAllowed( 'review' ) )
	{
	    $user_id = $wgUser->getId() ;
	    $wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:18pt;\">Reviewer</SPAN><BR /><BR />\n" ) ;
	    $sql="SELECT d.doc_id,d.doc_name,d.doc_title,r.status_id,r.review_doc_name FROM $doc_table d,$reviewer_table r WHERE r.user_id = $user_id AND r.doc_id = d.doc_id" ;
	    $res = $dbw->query( $sql ) ;
	    if( !$res )
	    {
		$wgOut->addHTML( "        Unable to query the Database" ) ;
	    }
	    else
	    {
		$new_papers = array() ;
		$new_index = 0 ;
		$reviewing_papers = array() ;
		$reviewing_index = 0 ;
		$done_papers = array() ;
		$done_index = 0 ;
		while( ( $obj = $dbw->fetchObject( $res ) ) )
		{
		    $doc_id = $obj->doc_id ;
		    $doc_name = $obj->doc_name ;
		    $doc_title = $obj->doc_title ;
		    $review_doc_name = $obj->review_doc_name ;
		    $status_id = $obj->status_id ;
		    switch ($status_id) {
		    case 0:
			{
			$new_papers[$new_index] = array( "doc_id" => $doc_id, "doc_name" => $doc_name, "doc_title" => $doc_title, "review_doc_name" => $review_doc_name, ) ;
			$new_index+=1 ;
			}
			break;
		    case 1:
			{
			$reviewing_papers[$reviewing_index] = array( "doc_id" => $doc_id, "doc_name" => $doc_name, "doc_title" => $doc_title, "review_doc_name" => $review_doc_name, ) ;
			$reviewing_index+=1 ;
			}
			break;
		    case 2:
			{
			$done_papers[$done_index] = array( "doc_id" => $doc_id, "doc_name" => $doc_name, "doc_title" => $doc_title, "review_doc_name" => $review_doc_name, ) ;
			$done_index+=1 ;
			}
			break;
		    }
		}
		$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">New Papers to Review</SPAN>\n" ) ;
		if( $new_index > 0 )
		{
		    $wgOut->addHTML( "<UL>\n" ) ;
		    foreach( $new_papers as $paper )
		    {
			$f_doc_id = $paper["doc_id"] ;
			$f_doc_name = $paper["doc_name"] ;
			$f_doc_title = $paper["doc_title"] ;
			$f_review_doc_name = $paper["review_doc_name"] ;
			$review_tags = Skin::makeSpecialURL( "Reviewers", "status=change_status&user_id=$user_id&doc_id=$f_doc_id" ) ;
			$wgOut->addHTML( "<LI>$f_doc_title<BR />\n" ) ;
			$wgOut->addWikiText( "[[Media:$f_doc_name|download paper to be reviewed]]" ) ;
			$wgOut->addHTML( "<A HREF=\"$wgServer/$review_tags\">review</A><BR />\n" ) ;
			$wgOut->addHTML( "</LI>\n" ) ;
		    }
		    $wgOut->addHTML( "</UL>\n" ) ;
		}

		if( $new_index == 0 )
		{
		    $wgOut->addHTML( "<BR />\n" ) ;
		}
		$wgOut->addHTML( "<BR /><SPAN STYLE=\"font-weight:bold;font-size:14pt;\">Papers You are Reviewing</SPAN>\n" ) ;
		if( $reviewing_index > 0 )
		{
		    $wgOut->addHTML( "<UL>\n" ) ;
		    foreach( $reviewing_papers as $paper )
		    {
			$f_doc_id = $paper["doc_id"] ;
			$f_doc_name = $paper["doc_name"] ;
			$f_doc_title = $paper["doc_title"] ;
			$f_review_doc_name = $paper["review_doc_name"] ;
			$done_tag = Skin::makeSpecialURL( "Reviewers", "status=change_status&user_id=$user_id&doc_id=$f_doc_id" ) ;
			$wgOut->addHTML( "<LI>$f_doc_title\n" ) ;
			$wgOut->addWikiText( "[[Media:$f_doc_name|download paper being reviewed]]" ) ;
			$wgOut->addHTML( "<A HREF=\"$wgServer/wiki/index.php/Media:$f_review_doc_name\">upload your review</A>\n" ) ;
			$wgOut->addHTML( "<BR /><A HREF=\"$wgServer/$done_tag\">done</A><BR />\n" ) ;
			$wgOut->addHTML( "</LI>\n" ) ;
		    }
		    $wgOut->addHTML( "</UL>\n" ) ;
		}

		if( $reviewing_index == 0 )
		{
		    $wgOut->addHTML( "<BR />\n" ) ;
		}
		$wgOut->addHTML( "<BR /><SPAN STYLE=\"font-weight:bold;font-size:14pt;\">Papers You are Done Reviewing</SPAN>\n" ) ;
		if( $done_index > 0 )
		{
		    $wgOut->addHTML( "<UL>\n" ) ;
		    foreach( $done_papers as $paper )
		    {
			$f_doc_id = $paper["doc_id"] ;
			$f_doc_name = $paper["doc_name"] ;
			$f_doc_title = $paper["doc_title"] ;
			$f_review_doc_name = $paper["review_doc_name"] ;
			$done_tag = Skin::makeSpecialURL( "Reviewers", "status=change_status&user_id=$user_id&doc_id=$f_doc_id" ) ;
			$wgOut->addHTML( "<LI>$f_doc_title\n" ) ;
			$wgOut->addWikiText( "[[Media:$f_doc_name|download paper you are reviewing]]" ) ;
			$wgOut->addHTML( "<A HREF=\"$wgServer/wiki/index.php/Media:$f_review_doc_name\">your review (can still upload new versions)</A>\n" ) ;
			$wgOut->addHTML( "</LI>\n" ) ;
		    }
		    $wgOut->addHTML( "</UL>\n" ) ;
		}
	    }
	    $wgOut->addHTML( "<BR /><BR /><SPAN STYLE=\"font-weight:bold;font-size:14pt;\">Reviewers Help</SPAN>\n" ) ;
	    $wgOut->addHTML( "<UL>\n" ) ;
	    $wgOut->addHTML( "<LI>To download the paper, click on the 'download paper' link\n" ) ;
	    $wgOut->addHTML( "<LI>To begin reviewing a new paper, click on the 'review' link. This simply tells the system the status of your review.\n" ) ;
	    $wgOut->addHTML( "<LI>Write your review using MS Word\n" ) ;
	    $wgOut->addHTML( "<LI>To upload your review document, or upload a new version of your review document, click on 'upload your review'\n" ) ;
	    $wgOut->addHTML( "  <UL>\n" ) ;
	    $wgOut->addHTML( "    <LI>If uploading your review for the first time, click on the 'upload it' link on the image page\n" ) ;
	    $wgOut->addHTML( "    <LI>If uploading a new version, this will take you to the document page, then click on 'Upload a new version of this file'\n" ) ;
	    $wgOut->addHTML( "    <LI><SPAN STYLE='font-weight:bold;'>NOTE</SPAN>: Do not change the name in the 'Destination filename' field\n" ) ;
	    $wgOut->addHTML( "  </UL>\n" ) ;
	    $wgOut->addHTML( "<LI>When you are done reviewing a document, click on the 'done' link to let us know you're done. You can still upload new versions of the review.\n" ) ;
	    $wgOut->addHTML( "</UL>\n" ) ;
	}
    }
    
    function new_user() {
	global $wgServer, $wgUser, $wgAuth, $wgOut, $wgRequest;

	if (!$wgUser->isAllowedToCreateAccount()) {
		$this->userNotPrivilegedMessage();
		return false;
	}

	$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:18pt;\">Reviewer Admin</SPAN><BR /><BR />\n" ) ;
	$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">New User</SPAN><BR />\n" ) ;
	$username = trim( $wgRequest->getText('username') ) ;
	$real_name = trim( $wgRequest->getText('real_name') ) ;
	$email = trim( $wgRequest->getText('email') ) ;

	if( !$username || !$real_name || !$email )
	{
	    $wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:12pt;\">You must supply a username, real name, and email address</SPAN><BR />\n" ) ;
	    $wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:12pt;\">Hit the back button and try again</SPAN><BR />\n" ) ;
	    return false ;
	}

	# Check that the username doesn't already exist. If it does, then have then click back arrow
	$u = User::newFromName( $username, 'creatable' );
	if ( is_null( $u ) ) {
	    $wgOut->addHTML( "Failed to create new user with the given username. Hit the back button and try again." ) ;
	    return false ;
	}

	if ( 0 != $u->idForName() ) {
	    $wgOut->addHTML( "User already exists. Hit the back button and try again." ) ;
	    return false ;
	}

	$wgOut->addHTML( "Creating new user ... " ) ;
	if( !$wgAuth->addUser( $u, '' ) ) {
	    $wgOut->addHTML( "FAILED - couldn't authorize new user" ) ;
	    return false ;
	}
	$wgOut->addHTML( "OK" ) ;
	$wgOut->addHTML( "<BR />" ) ;

	$wgOut->addHTML( "Updating Site Stats ... " ) ;
	# Update user count
	$ssUpdate = new SiteStatsUpdate( 0, 0, 0, 0, 1 );
	$ssUpdate->doUpdate();
	$wgOut->addHTML( "OK" ) ;
	$wgOut->addHTML( "<BR />" ) ;

	$wgOut->addHTML( "Updating user info ... " ) ;
	$u->addToDatabase();
	$u->setPassword( null );
	$u->setEmail( $email );
	$u->setRealName( $real_name );
	$u->setToken();

	$wgAuth->initUser( $u );

	// Wipe the initial password and mail a temporary one
	$u->setPassword( null );
	$np = $u->randomPassword();
	$u->setNewpassword( $np, false );
	$u->saveSettings();
	$wgOut->addHTML( "OK" ) ;
	$wgOut->addHTML( "<BR />" ) ;

	$id = $u->getId() ;

	$wgOut->addHTML( "Adding group information ... " ) ;
	$dbw =& wfGetDB( DB_MASTER );
	$group = "Reviewer" ;
	$dbw->insert( 'user_groups',
		array(
			'ug_user' => $id,
			'ug_group' => $group,
		),
		__METHOD__
	) ;
	$wgOut->addHTML( "OK" ) ;
	$wgOut->addHTML( "<BR />" ) ;

	$wgOut->addHTML( "Sending email to new user ... " ) ;
	$m = "An SSKI reviewer account has been created successfully for you with username $username and temporary password $np on server $wgServer/wiki.\n\nThe first time you log in to the wiki with this temporary password you will be asked to create a new password. Once you have created your new, permanent password you will be able to enter the reviewer page at $wgServer/wiki/index.php/Special:Reviewers..\n\nWith this new account you will be able to review documents that are assigned to you. Your reviewer assignments will be activated via another email to follow shortly.";
	$t = "[Reviewer] Reviewer account create for you";
	$result = $u->sendMail( $t, $m );
	if( WikiError::isError( $result ) ) {
	    $wgOut->addHTML( "FAILED<BR />" ) ;
	    $wgOut->addWikiText( wfMsg( 'mailerror', $result->getMessage() ) ) ;
	} else {
	    $wgOut->addHTML( "OK<BR />" ) ;
	    $wgOut->addWikiText( wfMsg( 'accmailtext', $u->getName(), $u->getEmail() ) );
	}
	$wgOut->addHTML( "<BR />" ) ;

	$wgOut->addHTML( "<A HREF=\"$wgServer/wiki/index.php/Special:Reviewers\">Return to Reviewer Page</A>\n" ) ;
    }

    function new_doc() {
	global $wgUser,$wgOut, $wgRequest;

	$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:18pt;\">Reviewer Admin</SPAN><BR /><BR />\n" ) ;
	$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">New Paper</SPAN><BR /><BR />\n" ) ;

	if( !$wgUser->isAllowed( 'reviewer_admin' ) )
	{
	    $wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">You do not have permission to add new papers</SPAN><BR /><BR />\n" ) ;
	    return ;
	}

	$paper_name = trim( $wgRequest->getText('paper_name') ) ;
	$paper_title = trim( $wgRequest->getText('paper_title') ) ;
	$paper_ext = strrchr( $paper_name, "." ) ;
	if( $paper_ext != ".pdf" && $paper_ext != ".doc" && $paper_ext != ".ppt" )
	{
	    $wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:12pt;\">The paper name must include the file extension (pdf, doc, or ppt)</SPAN><BR />\n" ) ;
	    $wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:12pt;\">Hit the back button and try again</SPAN><BR />\n" ) ;
	    return false ;
	}

	if( !$paper_name || !$paper_title )
	{
	    $wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:12pt;\">You must supply a name and title for the new paper</SPAN><BR />\n" ) ;
	    $wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:12pt;\">Hit the back button and try again</SPAN><BR />\n" ) ;
	    return false ;
	}

	$dbw =& wfGetDB( DB_MASTER );
	$doc_table = $dbw->tableName( 'reviewer_docs' );
	$sql = "SELECT MAX(doc_id) max_id FROM ".$doc_table ;
	$res = $dbw->query( $sql ) ;
	if( !$res )
	{
	    $wgOut->addHTML( "Unable to query the Reviewer Paper Database $doc_table<BR />" ) ;
	    return false ;
	}
	$id = 0 ;
	while( ( $obj = $dbw->fetchObject( $res ) ) )
	{
	    $id = $obj->max_id ;
	}
	$id += 1 ;
	$paper_internal_name = "R_$id$paper_ext" ;
	$paper_title = $dbw->strencode( $paper_title ) ;
	$paper_name = $dbw->strencode( $paper_name ) ;
	$dbw->insert( $doc_table,
		array(
			'doc_id' => $id,
			'doc_name' => $paper_internal_name,
			'doc_title' => $paper_title,
			'doc_real_name' => $paper_name,
		),
		__METHOD__
	) ;
	$wgOut->addHTML( "<BR /><A HREF=\"$wgServer/wiki/index.php/Special:Upload?wpDestFile=$paper_internal_name\">Upload the File</A>" ) ;
    }

    function assign_user() {
	global $wgServer, $wgUser, $wgOut, $wgRequest;

	$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:18pt;\">Reviewer Admin</SPAN><BR /><BR />\n" ) ;
	$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">Assign User to Paper</SPAN><BR /><BR />\n" ) ;

	if( !$wgUser->isAllowed( 'reviewer_admin' ) )
	{
	    $wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">You do not have permission to assign reviewers to papers</SPAN><BR /><BR />\n" ) ;
	    return ;
	}

	$paper_name = trim( $wgRequest->getText('paper_name') ) ;
	$user_id = trim( $wgRequest->getText('user_id') ) ;
	$doc_id = trim( $wgRequest->getText('doc_id') ) ;

	$dbw =& wfGetDB( DB_MASTER );

	$paper_name = $dbw->strencode( $paper_name ) ;
	$user_id = $dbw->strencode( $user_id ) ;
	$doc_id = $dbw->strencode( $doc_id ) ;

	$reviewer_table = $dbw->tableName( 'reviewers' );
	$user_table = $dbw->tableName( 'user' );
	$sql = "SELECT status_id FROM $reviewer_table WHERE user_id = $user_id AND doc_id = $doc_id" ;
	$res = $dbw->query( $sql ) ;
	if( !$res )
	{
	    $wgOut->addHTML( "<BR /><BR />Unable to assignthe paper<BR />" ) ;
	    $wgOut->addHTML( "<BR /><A HREF=\"$wgServer/wiki/index.php/Special:Reviewers\">Return to Reviewer Page</A>\n" ) ;
	    return false ;
	}
	$status_id = -1 ;
	while( ( $obj = $dbw->fetchObject( $res ) ) )
	{
	    $status_id = $obj->status_id ;
	}

	if( $status_id != -1 )
	{
	    $wgOut->addHTML( "<BR /><BR />The specified reviewer appears to be already reviewing the specified paper<BR />" ) ;
	    $wgOut->addHTML( "<BR /><A HREF=\"$wgServer/wiki/index.php/Special:Reviewers\">Return to Reviewer Page</A>\n" ) ;
	    return false ;
	}

	$review_name = "R_$doc_id"."_$user_id.doc" ;
	$dbw->insert( $reviewer_table,
		array(
			'user_id' => $user_id,
			'doc_id' => $doc_id,
			'review_doc_name' => $review_name,
		),
		__METHOD__
	) ;

	$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">ASSIGNED</SPAN><BR /><BR />\n" ) ;

	$sql = "SELECT user_name from $user_table WHERE user_id = $user_id" ;
	$res = $dbw->query( $sql ) ;
	if( !$res )
	{
	    $wgOut->addHTML( "<BR /><BR />Unable to email reviewer, please email manually<BR />" ) ;
	    $wgOut->addHTML( "<BR /><A HREF=\"$wgServer/wiki/index.php/Special:Reviewers\">Return to Reviewer Page</A>\n" ) ;
	    return false ;
	}
	$obj = $dbw->fetchObject( $res ) ;
	if( !$obj )
	{
	    $wgOut->addHTML( "<BR /><BR />Unable to email reviewer, please email manually<BR />" ) ;
	    $wgOut->addHTML( "<BR /><A HREF=\"$wgServer/wiki/index.php/Special:Reviewers\">Return to Reviewer Page</A>\n" ) ;
	    return false ;
	}

	$username = $obj->user_name ;
	$u = User::newFromName( $username, 'creatable' );
	if( !$u )
	{
	    $wgOut->addHTML( "<BR /><BR />Unable to email reviewer, please email manually<BR />" ) ;
	    $wgOut->addHTML( "<BR /><A HREF=\"$wgServer/wiki/index.php/Special:Reviewers\">Return to Reviewer Page</A>\n" ) ;
	    return false ;
	}

	$m = "A paper has been assigned to you to be reviewed. Please go to $wgServer/wiki/index.php/Special:Reviewers for a list of all of the papers you have to review.";
	$t = "[Reviewer] Paper assigned to you to be reviewed";
	$result = $u->sendMail( $t, $m );
	if( WikiError::isError( $result ) ) {
	    $wgOut->addHTML( "FAILED<BR />" ) ;
	    $wgOut->addWikiText( wfMsg( 'mailerror', $result->getMessage() ) ) ;
	} else {
	    $wgOut->addHTML( "OK<BR />" ) ;
	    $wgOut->addWikiText( wfMsg( 'accmailtext', $u->getName(), $u->getEmail() ) );
	}

	$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">EMAILED</SPAN><BR /><BR />\n" ) ;

	$wgOut->addHTML( "<A HREF=\"$wgServer/wiki/index.php/Special:Reviewers\">Return to Reviewer Page</A>\n" ) ;
    }

    function change_status() {
	global $wgUser,$wgRequest,$wgOut;

	if( !$wgUser->isAllowed( 'review' ) )
	{
	    $wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">You do not have permission to change the status of a review</SPAN>\n" ) ;
	    $wgOut->addHTML( "<BR /><BR /><A HREF=\"$wgServer/wiki/index.php/Special:Reviewers\">Return to Reviewer Page</A>\n" ) ;
	    return false ;
	}

	$user_id = trim( $wgRequest->getText('user_id') ) ;
	$doc_id = trim( $wgRequest->getText('doc_id') ) ;
	$id = $wgUser->getId() ;
	if( $id != $user_id )
	{
	    $wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">Only the reviewer can change the status of a paper they are reviewing</SPAN>\n" ) ;
	    $wgOut->addHTML( "<BR /><BR /><A HREF=\"$wgServer/wiki/index.php/Special:Reviewers\">Return to Reviewer Page</A>\n" ) ;
	    return false ;
	}

	$dbw =& wfGetDB( DB_MASTER );
	$reviewers_table = $dbw->tableName( 'reviewers' );
	$user_id = $dbw->strencode( $user_id ) ;
	$doc_id = $dbw->strencode( $doc_id ) ;
	$sql = "SELECT status_id FROM $reviewers_table WHERE user_id = $user_id AND doc_id = $doc_id" ;
	$res = $dbw->query( $sql ) ;
	if( !$res )
	{
	    $wgOut->addHTML( "<BR /><BR />Unable to change the status of paper<BR />" ) ;
	    return false ;
	}
	$status_id = -1 ;
	while( ( $obj = $dbw->fetchObject( $res ) ) )
	{
	    $status_id = $obj->status_id ;
	}
	if( $status_id == -1 )
	{
	    $wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">You do not appear to be reviewing the specified paper</SPAN>\n" ) ;
	    $wgOut->addHTML( "<BR /><BR /><A HREF=\"$wgServer/wiki/index.php/Special:Reviewers\">Return to Reviewer Page</A>\n" ) ;
	    return false ;
	}

	if( $status_id == 0 )
	{
	    $status_id = 1 ;
	}
	else if( $status_id == 1 )
	{
	    $status_id = 2 ;
	}
	else
	{
	    $wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">You already appear to be done reviewing the paper</SPAN>\n" ) ;
	    $wgOut->addHTML( "<BR /><BR /><A HREF=\"$wgServer/wiki/index.php/Special:Reviewers\">Return to Reviewer Page</A>\n" ) ;
	    return false ;
	}

	$dbw->update( $reviewers_table,
		array(
			'status_id' => $status_id
		),
		array(
			'user_id' => $user_id,
			'doc_id' => $doc_id
		),
		__METHOD__
	) ;

	$wgOut->addHTML( "<BR /><BR /><SPAN STYLE=\"font-weight:bold;font-size:14pt;\">DONE</SPAN>\n" ) ;
	$wgOut->addHTML( "<BR /><BR /><A HREF=\"$wgServer/wiki/index.php/Special:Reviewers\">Return to Reviewer Page</A>\n" ) ;
    }

    function unassign_reviewer() {
	global $wgRequest,$wgUser,$wgOut;

	$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:18pt;\">Reviewer Admin</SPAN><BR /><BR />\n" ) ;
	$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">Un-Assign User from Paper</SPAN><BR /><BR />\n" ) ;

	if( !$wgUser->isAllowed( 'reviewer_admin' ) )
	{
	    $wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">You do not have permission to unassign reviewers from papers</SPAN><BR /><BR />\n");
	    return false ;
	}

	$user_id = trim( $wgRequest->getText('user_id') ) ;
	$doc_id = trim( $wgRequest->getText('doc_id') ) ;

	# is this reviewer reviewing the said document?
	$dbw =& wfGetDB( DB_MASTER );

	// grab the tables
	$reviewer_table = $dbw->tableName( 'reviewers' );
	$doc_table = $dbw->tableName( 'reviewer_docs' );
	$user_table = $dbw->tableName( 'user' );

	// clean the input
	$user_id = $dbw->strencode( $user_id ) ;
	$doc_id = $dbw->strencode( $doc_id ) ;

	$sql = "SELECT r.user_id,u.user_real_name,d.doc_real_name FROM $reviewer_table r,$doc_table d,$user_table u WHERE r.user_id = $user_id AND r.user_id = u.user_id AND r.doc_id = $doc_id AND r.doc_id = d.doc_id" ;
	$res = $dbw->query( $sql ) ;
	if( !$res )
	{
	    $wgOut->addHTML( "Unable to unassign document<BR />" ) ;
	    return false ;
	}
	$user_id = 0 ;
	$user_real_name = "" ;
	$doc_real_name = "" ;
	while( ( $obj = $dbw->fetchObject( $res ) ) )
	{
	    $user_id = $obj->user_id ;
	    $user_real_name = $obj->user_real_name ;
	    $doc_real_name = $obj->doc_real_name ;
	}
	if( $user_id == 0 )
	{
	    $wgOut->addHTML( "The specified reviewer is not reviewing the specified paper<BR />" ) ;
	    return false ;
	}

	# ask if I'm sure
	$wgOut->addHTML( "Are you sure you want to unassign $user_real_name from the paper $doc_real_name?&nbsp;&nbsp;\n" ) ;
	$wgOut->addHTML( "<A HREF=\"$wgServer/wiki/index.php/Special:Reviewers?status=really_unassign_reviewer&amp;user_id=$user_id&amp;doc_id=$doc_id\">YES</A>\n" ) ;
	$wgOut->addHTML( "&nbsp;&nbsp;/&nbsp;&nbsp;<A HREF=\"$wgServer/wiki/index.php/Special:Reviewers\">NO</A>\n" ) ;
    }

    function really_unassign_reviewer() {
	global $wgRequest,$wgUser,$wgOut;

	$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:18pt;\">Reviewer Admin</SPAN><BR /><BR />\n" ) ;
	$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">Un-Assign User from Paper</SPAN><BR /><BR />\n" ) ;

	if( !$wgUser->isAllowed( 'reviewer_admin' ) )
	{
	    $wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">You do not have permission to unassign reviewers from papers</SPAN><BR /><BR />\n");
	    return false ;
	}

	$user_id = trim( $wgRequest->getText('user_id') ) ;
	$doc_id = trim( $wgRequest->getText('doc_id') ) ;

	# is this reviewer reviewing the said document?
	$dbw =& wfGetDB( DB_MASTER );

	// get the table names
	$reviewer_table = $dbw->tableName( 'reviewers' );
	$doc_table = $dbw->tableName( 'reviewer_docs' );
	$user_table = $dbw->tableName( 'user' );

	// clean the input
	$user_id = $dbw->strencode( $user_id ) ;
	$doc_id = $dbw->strencode( $doc_id ) ;

	$sql = "SELECT r.user_id,u.user_real_name,d.doc_real_name FROM $reviewer_table r,$doc_table d,$user_table u WHERE r.user_id = $user_id AND r.user_id = u.user_id AND r.doc_id = $doc_id AND r.doc_id = d.doc_id" ;
	$res = $dbw->query( $sql ) ;
	if( !$res )
	{
	    $wgOut->addHTML( "Unable to unassign document<BR />" ) ;
	    return false ;
	}
	$user_id = 0 ;
	$user_real_name = "" ;
	$doc_real_name = "" ;
	while( ( $obj = $dbw->fetchObject( $res ) ) )
	{
	    $user_id = $obj->user_id ;
	    $user_real_name = $obj->user_real_name ;
	    $doc_real_name = $obj->doc_real_name ;
	}
	if( $user_id == 0 )
	{
	    $wgOut->addHTML( "The specified reviewer is not reviewing the specified paper<BR />" ) ;
	    return false ;
	}

	$wgOut->addHTML( "<SPAN STYLE=\"font-weight:bold;font-size:14pt;\">Unassigning $user_real_name from the paper $doc_real_name</SPAN>\n" ) ;

	$dbw->delete( $reviewer_table,
		array( /* SET */
			'user_id' => $user_id,
			'doc_id' => $doc_id
		), __METHOD__
	);

	$wgOut->addHTML( "<BR /><BR /><A HREF=\"$wgServer/wiki/index.php/Special:Reviewers\">Return to Reviewer Page</A>" ) ;
    }

    function userNotPrivilegedMessage() {
	    global $wgOut;

	    $wgOut->setPageTitle( wfMsg( 'whitelistacctitle' ) );
	    $wgOut->setRobotpolicy( 'noindex,nofollow' );
	    $wgOut->setArticleRelated( false );

	    $wgOut->addWikiText( wfMsg( 'whitelistacctext' ) );

	    $wgOut->returnToMain( false );
    }

    function loadMessages() {
	static $messagesLoaded = false;
	global $wgMessageCache;
	if ( $messagesLoaded ) return;
	$messagesLoaded = true;
	
	require( dirname( __FILE__ ) . '/Reviewers.i18n.php' );
	foreach ( $allMessages as $lang => $langMessages ) {
	    $wgMessageCache->addMessages( $langMessages, $lang );
	}
    }
}

?>
