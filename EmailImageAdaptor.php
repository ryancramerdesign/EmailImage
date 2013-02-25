<?php
/*******************************************************************************
  *  PHP-CLASSES
  *
  *  @php_version -   5.2.x
  * ---------------------------------------------------------------------------
  *  @version     -   v1.0 RC1
  *  @date        -   $Date: 2013/02/24 01:37:08 $
  *  @author      -   Horst Nogajski <coding AT nogajski DOT de>
  *  @licence     -   GNU GPL v2 - http://www.gnu.org/licenses/gpl-2.0.html
  * ---------------------------------------------------------------------------
  *  $Source: /WEB/pw_pop3/EmailImageAdaptor.php,v $
  *  $Id: EmailImageAdaptor.php,v 1.1.2.2 2013/02/24 01:37:08 horst Exp $
  ******************************************************************************
  *
  *  LAST CHANGES:
  *
  *  2013-02-23    change  	RC1   no more using stream_wrapper for fetching messages, instead we use RetrieveMessage( $msg_id, $headers, $body, -1 ) now
  *                               - so we have only one single-connection to the server, maybe that can solve the issue with Ryans double fetching Mails ?
  *
**/


require_once( dirname(__FILE__) . '/pop3_classes/mime_parser.php' );
require_once( dirname(__FILE__) . '/pop3_classes/rfc822_addresses.php' );
require_once( dirname(__FILE__) . '/pop3_classes/pop3.php' );
//require_once( dirname(__FILE__) . '/pop3_classes/sasl.php' );


//stream_wrapper_register('pop3', 'pop3_stream');  /* Register the pop3 stream handler class */



class EmailImageAdaptor {

	private $pw_pop3 = null;

	public function __construct(array $config) {
		// Set all settings related to the email account
		$this->pw_pop3 = new hnpw_pop3($config);

	}

	public function __destruct() {
		$this->pw_pop3->close();
		unset($this->pw_pop3);
	}

	public function testConnection() {
		// Tests that the email settings work. This would be used by the module at
		// config time to give the user a Yes or No as to whether their email settings
		// are functional. Returns a boolean TRUE or FALSE.
        return $this->pw_pop3->test_connection();
	}

	public function getImages($path) {
		// Connects to pop3 server, grabs queued messages/images, saves them
		// in the directory specified by $path, then deletes them from the POP3.
		// Returns an array of the image filenames that were saved.
		// Ideally the filenames are the same that they were in the email.
		// If fatal error occurred or nothing new, returns blank array.
		// Proposed return array would be:
		// array(
		//   0 => array('filename' => 'file1.jpg', 'subject' => 'subject line text', 'body' => 'maybe some description to the image, but is optional'),
		//   1 => array('filename' => 'file2.jpg', 'subject' => 'another subject line', 'body' => ''),
		//   ...and more items as found...
		//   );

// Ryan:  have I to check for this?  ==>      is_dir($path)     assuming: no
// Ryan:  has it a trailing slash or not ??                     assuming: no

		if( ! $this->pw_pop3->connect() )
		{
			return array();
		}
		if( ! $this->pw_pop3->has_new_msg() )
		{
			return array();
		}
		$aImageEmails = array();
		while( $this->pw_pop3->has_new_msg() )
		{
			@set_time_limit( 120 );

			$aResult = $this->pw_pop3->process_next_msg();

			if( ! is_array($aResult) || ! isset($aResult['imgdata']) || is_null($aResult['imgdata']) )
			{
				continue;
			}
			// $aResult = array('subject', 'imgname', 'imgdata', 'imgextension')
			$img_basename = is_string($aResult['imgname']) ? $aResult['imgname'] : 'imgfile_'. strval(count($aImageEmails)+1) .'.'. $aResult['imgextension'];
			$img_filename = $path .'/'. $img_basename;
			$this->next_free_imgname($img_filename);
			$file_saved = file_put_contents( $img_filename, $aResult['imgdata'], LOCK_EX );
			if( $file_saved===strlen($aResult['imgdata']) )
			{
				$aImageEmails[] = array('filename'=>basename($img_filename), 'subject'=>$aResult['subject'], 'body'=>$aResult['body']);
			}
			#break;
		}
		$this->pw_pop3->close();
		return $aImageEmails;
	}

	public function getErrors() {
		// Returns an array of error messages, if they occurred.
		// Returns blank array if no errors occurred.
		// The module would call this after getImages() or testConnection() to
		// see if it should display/log any error messages.
        return (array)$this->pw_pop3->get_errors();
	}


	private function next_free_imgname( &$filename, $sanitize=true ) {
		// sanitize img-basename
		if( $sanitize===true )
		{
			$pi = pathinfo($filename);
			$bn = preg_replace( '/[^a-z 0-9_@\.\-]/', '', strtolower($pi['filename']) );
			$filename = ! is_string($bn) ? $filename : $pi['dirname'] .'/'. str_replace(' ', '_', $bn) .'.'. strtolower($pi['extension']);
		}
		if( ! file_exists($filename) )
		{
			return;
		}
		$pi = pathinfo($filename);
		$n = 1;
		while( file_exists($filename) )
		{
			// we use PHP Version > 5.2.0, so pathinfo['filename'] is present  (pathinfo['filename'] is basename without extension)
			$filename = $pi['dirname'] .'/'. $pi['filename'] .'_'. strval($n++) .'.'. $pi['extension'];
		}
	}

}





class hnpw_pop3
{
	private $debug                          = 0;                     /* Output debug information                    */
	private $html_debug                     = 0;                     /* Debug information is in HTML                */

	private $hostname                       = '';                    /* POP 3 server host name                      */
	private $port                           = 110;                   /* POP 3 server host port,
	                                                                    usually 110 but some servers use other ports
	                                                                    Gmail uses 995                              */
	private $tls                            = 0;                     /* Establish secure connections using TLS      */
	private $realm                          = '';                    /* Authentication realm or domain              */
	private $workstation                    = '';                    /* Workstation for NTLM authentication         */
	private $authentication_mechanism       = 'USER';                /* SASL authentication mechanism               */
	private $join_continuation_header_lines = 1;                     /* Concatenate headers split in multiple lines */

	private $user                           = '';                    /* Authentication user name                    */
	private $password                       = '';                    /* Authentication password                     */
	private $apop                           = 0;                     /* Use APOP authentication                     */
	private $valid_senders                  = array();               /* SenderEmailAddress wich is allowed to post  */
	private $body_password                  = '';                    /* more security: password in Mailbody         */
	private $body_txt_start                 = '';                    /* */
	private $body_txt_end                   = '';                    /* */

	private $max_allowed_email_size         = 10485760;              /* 10 MB (1024 * 1024 * 10) */

	private $aValidVars                     = null;
	private $pop3                           = null;
	private $connected                      = null;
	private $new_msg                        = null;

	private $total_list                     = null;
	private	$total_messages                 = null;
	private	$total_size                     = null;

	private $errors                         = array();


	public function test_connection()
	{
		if( ( $this->errors[] = $this->pop3->Open() ) != '' )
		{
			return false;
		}
        if( ( $this->errors[] = $this->pop3->Login( $this->user, $this->password, $this->apop ) ) != '' )
        {
			return false;
        }

		$this->connected = true;
		$this->total_messages = null;
		$this->total_size = null;
		if( ( $this->errors[] = $this->pop3->Statistics( $this->total_messages, $this->total_size ) ) != '' )
		{
			$this->total_messages = null;
			$this->total_size = null;
			$this->close();
			return false;
		}
		$this->total_messages = null;
		$this->total_size = null;
		$this->close();
		return true;
	}


	public function connect()
	{
		if( ( $this->errors[] = $this->pop3->Open() ) != '' )
		{
			$this->connected = false;
			return false;
		}
        if( ( $this->errors[] = $this->pop3->Login( $this->user, $this->password, $this->apop ) ) != '' )
        {
			$this->connected = false;
			return false;
        }
		$this->connected = true;

		$this->total_messages = null;
		$this->total_size = null;
		if( ( $this->errors[] = $this->pop3->Statistics( $this->total_messages, $this->total_size ) ) != '' )
		{
			$this->connected = false;
			return false;
		}

        if( is_null($this->total_messages) || intval($this->total_messages)<1 )
        {
			$this->new_msg = false;
			return true;
        }

        # aList
		$aList = $this->pop3->ListMessages( '', 0 );
		if( ! is_array($aList) )
		{
			$this->new_msg = false;
			return true;
		}
        $this->total_list = array();
		foreach( $aList as $k=>$size )
		{
			$this->total_list[] = array($size,$k);
		}

		$this->new_msg = count($this->total_list)>0 ? true : false;
        return true;
	}


	public function close()
	{
		if( ! $this->connected )
		{
			return null;
		}
		if( $this->pop3->close() != '' )
		{
			return false;
		}
		$this->connected = false;
		return true;
	}


	public function get_errors()
	{
		$a = array();
		foreach( $this->errors as $e )
		{
			if($e=='')
			{
				continue;
			}
			$a[] = $e;
		}
		$this->errors = $a;
		return $this->errors;
	}


	public function has_new_msg()
	{
		return $this->new_msg===true ? true : false;
	}


	public function process_next_msg()
	{
		if( ! $this->new_msg )
		{
			return false;
		}
		# get id of next unprocessed mail
		$next = array_shift($this->total_list);
        $this->new_msg = count($this->total_list)>0 ? true : false;

        if( $next[0]>$this->max_allowed_email_size )
        {
			$this->errors[] = 'Email processing rejected! max_allowed_email_size exeeded: ' . strval($next[0] .' > '. $this->max_allowed_email_size);
			return false;
        }

        $res = $this->get_message( $next[1] );
		if( ! is_array($res) )
		{
			# error is already set in method get_message()
        	$this->errors[] = $this->pop3->DeleteMessage( $next[1] );
			return false;
		}

		# we have valid data :-)
		# delete Email on PopServer
        if(($this->errors[] = $this->pop3->DeleteMessage( $next[1] )) != "" )
		{
			return false;
		}

		# pass valid data
		return $res;
	}




	private function get_message( $msg_id )
	{
//		$message_file = 'pop3://'.UrlEncode($this->user).':'.UrlEncode($this->password).'@'.$this->hostname.':'.$this->port.'/'.$msg_id.
//			'?debug='.$this->debug.'&html_debug='.$this->html_debug.'&realm='.UrlEncode($this->realm).'&workstation='.UrlEncode($this->workstation).
//			'&apop='.$this->apop.'&authentication_mechanism='.UrlEncode($this->authentication_mechanism).'&tls='.$this->tls;

		$body = null;
		$headers = null;
		if( ( $error = $this->pop3->RetrieveMessage( $msg_id, $headers, $body, -1 ) ) != "" )
		{
			$this->errors[] = $error;
			return false;
		}

        $message_file = implode("\r\n",$headers) ."\r\n". implode("\r\n",$body);  // ???

		$mime = new mime_parser_class;
		$mime->decode_bodies = 1;
//		$parameters = array( 'File'=>$message_file, 'SkipBody'=>0 );
		$parameters = array( 'Data'=>$message_file, 'SkipBody'=>0 );
		if( ! $mime->Decode( $parameters, $decoded ) )
		{
			$this->errors[] = 'Unknown error when trying to decode MimeMessage';
			return false;
		}

		if( ! isset($decoded['0']['ExtractedAddresses']['from:'][0]['address']) || ! isset($decoded['0']['Headers']['subject:']) || ! isset($decoded['0']['Parts']) || ! is_array($decoded['0']['Parts']) || count($decoded['0']['Parts'])<1 )
		{
			$this->errors[] = 'Missing sections in decoded MimeMessage';
			return false;
		}

		$from = strtolower($decoded['0']['ExtractedAddresses']['from:'][0]['address']);
		if( ! in_array( $from, $this->valid_senders ) )
		{
			$this->errors[] = 'Email processing rejected! Invalid sender: ' . $from;
			return false;
		}
		$subject = $decoded['0']['Headers']['subject:'];

		$html = array();
		$plain = array();
		$image = array();
		$i = -1;
		// loop through all MessageParts and collect all Data (without image data)
		foreach( $decoded['0']['Parts'] as $main_part )
		{
			$i++;
			if( count($main_part['Parts'])>0 )
			{
				$n = -1;
				foreach( $main_part['Parts'] as $sub_part )
				{
					$n++;
					$this->get_message_part( "$i-$n", $sub_part, $html, $plain, $image, false );
				}
			}
			else
			{
				$this->get_message_part( "$i", $main_part, $html, $plain, $image, false );
			}
		}

        // optional checking extra password
		if( is_string($this->body_password) && strlen(trim($this->body_password))>0 )
		{
			$pass = false;
			# check for extra security password
			foreach( array_merge($plain,$html) as $k=>$v )
			{
				if( preg_match( '/.*?('. str_replace('/','\\/',trim($this->body_password)) .').*/', $v['Body'] )===1 )
				{
					$pass = true;
					break;
				}
			}
			if( $pass!==true )
			{
				$this->errors[] = 'Email processing rejected! Required BodyPassword not found in Email: ' . $subject;
				return false;
			}
		}

		// optional checking and extracting BodyText
		$BodyText = '';
		if( is_string($this->body_txt_start) && strlen(trim($this->body_txt_start))>0 && is_string($this->body_txt_end) && strlen(trim($this->body_txt_end))>0 )
		{
			foreach( array_merge($plain,$html) as $k=>$v )
			{
				$tag1 = str_replace( array('/', '[', ']', '{', '}', '(', ')', '.', '?'), array('\\/', '\\[', '\\]', '\\{', '\\}', '\\(', '\\)', '\\.', '\\?'), trim($this->body_txt_start) );
				$tag2 = str_replace( array('/', '[', ']', '{', '}', '(', ')', '.', '?'), array('\\/', '\\[', '\\]', '\\{', '\\}', '\\(', '\\)', '\\.', '\\?'), trim($this->body_txt_end) );
				if( preg_match( '/.*?'.$tag1.'(.*?)'.$tag2.'.*/ms', $v['Body'], $matches )===1 )
				{
					$BodyText = $matches[1];
					break;
				}
			}
		}

		// check for image
		if( count($image)==0 )
		{
			$this->errors[] = 'Email processing rejected! No image found in Email: ' . $subject;
			return false;
		}
		// check for biggest image if there are multiple
		$n = 0;
		$part = array();
		$BodyPartId = null;
		foreach( $image as $img )
		{
			if( $n < $img['size'] )
			{
				$n = $img['size'];
				$BodyPartId = $img['BodyPartId'];
				$part['imgname'] = $img['imgname'];
				$part['imgextension'] = $img['imgextension'];
			}
		}
		$p = explode('-',$BodyPartId);
		if( count($p)==2 )
		{
			$part['Body'] = $decoded['0']['Parts'][$p[0]]['Parts'][$p[1]]['Body'];
		}
		else
		{
			$part['Body'] = $decoded['0']['Parts'][$p[0]]['Body'];
		}

		return array( 'subject'=>$subject, 'body'=>$BodyText, 'imgdata'=>$part['Body'], 'imgname'=>$part['imgname'], 'imgextension'=>$part['imgextension'] );
	}


	private function get_message_part( $BodyPartId, &$p, &$html, &$plain, &$image, $withimgdata=false )
	{
		if( ! isset($p['Headers']['content-type:']) )
		{
			return;
		}
		$type = null;
		$aData = array();
		$aData['BodyPartId'] = $BodyPartId;
        if( preg_match( '#^image/(jpeg|png|gif).*$#', $p['Headers']['content-type:'] )===1 )
        {
        	$type = 'image';
        }
        elseif( preg_match( '#^text/plain.*$#', $p['Headers']['content-type:'] )===1 )
        {
			$type = 'plain';
        }
        elseif( preg_match( '#^text/html.*$#', $p['Headers']['content-type:'] )===1 )
        {
			$type = 'html';
        }
        else
        {
			return;
        }
		$aData['size'] = $p['BodyLength'];

		if( $type!='image' )
		{
			$aData['Body'] = $p['Body'];
		}
		else
		{
			$aData['Body'] = $withimgdata===true ? $p['Body'] : null;
			if( isset($p['FileName']) && strlen($p['FileName'])>0 )
			{
				$aData['imgname'] = $p['FileName'];
			}
			elseif( isset($p['Headers']['content-transfer-disposition:']) && strlen($p['Headers']['content-transfer-disposition:'])>0 && preg_match('/.*?name=(.*?\....).*/i', $p['Headers']['content-transfer-disposition:'], $matches)===1 )
			{
				$aData['imgname'] = $matches[1];
			}
			elseif( isset($p['Headers']['content-type:']) && strlen($p['Headers']['content-type:'])>0 && preg_match('/.*?name=(.*?\....).*/i', $p['Headers']['content-type:'], $matches)===1 )
			{
				$aData['imgname'] = $matches[1];
			}
			else
			{
				$aData['imgname'] = null;
			}

			if( is_null($aData['imgname']) )
			{
				$aData['imgextension'] = '';
			}
			else
			{
                $aData['imgextension'] = strtolower( pathinfo($aData['imgname'], PATHINFO_EXTENSION) );
			}
		}

		${$type}[] = $aData;
	}


	private function set_var_val( $k, $v )
	{
		if( ! in_array( $k, $this->aValidVars ) )
		{
			return;
		}

		switch( $k )
		{
			case 'port':
			case 'max_allowed_email_size':
				$this->$k = intval($v);
				break;

			case 'tls':
			case 'apop':
			case 'debug':
			case 'html_debug':
			case 'img_up_scale':
			case 'join_continuation_header_lines':
				if( is_bool($v) )
				{
					$this->$k = $v==true ? 1 : 0;
				}
				elseif( is_int($v) )
				{
					$this->$k = $v==1 ? 1 : 0;
				}
				elseif( is_string($v) && in_array($v, array('1','on','On','ON','true','TRUE')) )
				{
					$this->$k = 1;
				}
				elseif( is_string($v) && in_array($v, array('0','off','Off','OFF','false','FALSE')) )
				{
					$this->$k = 0;
				}
				else
				{
					$this->$k = 0;
				}
				break;

			case 'authentication_mechanism':
				$this->authentication_mechanism = $v;
				break;

			case 'valid_senders':
				$this->valid_senders = is_array($v) || is_string($v) ? (array)$v : array();
				break;

			default:
				if( in_array($k,array('hostname','user','password','workstation','realm','body_password','body_txt_start','body_txt_end')) )
				{
					$this->$k = strval($v);
				}
		}
	}




	public function __construct( $aConfig=null )
	{
		if( ! is_array($aConfig) )
		{
			return;
		}

		$this->aValidVars = get_class_vars(__CLASS__);
		foreach( $aConfig as $k=>$v )
		{
			$this->set_var_val( $k, $v );
		}

		foreach( $this->valid_senders as $k=>$v )
		{
			$this->valid_senders[$k] = str_replace(array('<','>'), '', strtolower(trim($v)));
		}

		$this->pop3                                 = new pop3_class();
		$this->pop3->hostname                       = $this->hostname;
		$this->pop3->port                           = $this->port;
		$this->pop3->tls                            = $this->tls;
		$this->pop3->realm                          = $this->realm;
		$this->pop3->workstation                    = $this->workstation;
		$this->pop3->authentication_mechanism       = $this->authentication_mechanism;
		$this->pop3->join_continuation_header_lines = $this->join_continuation_header_lines;
        $this->pop3->debug                          = $this->debug;
        $this->pop3->html_debug                     = $this->html_debug;
	}


	public function __destruct()
	{
		if( $this->connected )
		{
			$this->close();
		}
		unset($this->pop3);
	}


} // END class hnpw_pop3





