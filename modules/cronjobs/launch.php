<?php
$Module = $Params['Module'];

$tpl = eZTemplate::factory();
$tpl->setVariable( 'module', $Module );
$http = eZHTTPTool::instance();

if ( $http->hasVariable( 'part' ) )
{
	$part = $http->variable('part');
}
else
{
	$part = '';
}

if( $part == 'global' )
{
	$part = '';
}

// Every word that reaches the command line is quoted for the shell unless it
// only holds characters a cronjob part, siteaccess, option or date can hold.
$shellWord = function( $word )
{
	$word = (string)$word;
	return preg_match( '#^[A-Za-z0-9_./:=-]+$#', $word ) ? $word : escapeshellarg( $word );
};
$part = implode( ' ', array_map( $shellWord, preg_split( '/\s+/', trim( (string)$part ), -1, PREG_SPLIT_NO_EMPTY ) ) );

$options = eZINI::instance( 'occronjobs.ini' )->variable( 'CronOptions', 'PreOption' );
foreach( $options as $name => $type )
{
	if ( $http->variable( $name, false ) )
	{
		if ( $type == 'boolean' )
			$part = "--$name " . $part;
		else
			$part = ' ' . $shellWord( "--$name=" . $http->variable( $name ) ) . ' ' . $part;
	}
}

$lastOptions = eZINI::instance( 'occronjobs.ini' )->variable( 'CronOptions', 'PostOption' );
foreach( $lastOptions as $name => $type )
{
	if ( $http->variable( $name, false ) )
	{		
		$part .= ' ' . $shellWord( "$name=" . $http->variable( $name ) ) . ' ';
	}
}

//$debug =  $http->variable( 'debug', 0 );
//if ( $debug > 0 )
//{
//    $part = '--debug ' . $part;
//}

eZLog::write( $part, 'occrontab.log' );

$manager = OCCronJobProcessManager::instance();
$manager->cleanOutputFile();
$manager->cleanErrorFile();
$manager->addScript( "runcronjobs.php", $part );
$manager->execAll();

eZExecution::cleanExit();

?>
