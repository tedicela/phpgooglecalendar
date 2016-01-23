<?php

/*	Google Calendar
 * 	Author: Tedi Cela
 * 	Date: 28.07.2014
 * 	Version: 1.0
 */

session_start();

require "Google/Client.php";
require "Google/Service/Calendar.php";

class OOGoogleCalendar{
    
	private $ApplicationName = '';
	private $DeveloperKey = '';
	private $ClientId = '';
	private $ClientSecret = '';
	private $RedirectUri = 'http://YOURHOSTNAME/start.php';
	private $Scopes =array('https://www.googleapis.com/auth/calendar');
	private $token_path = 'token.conf';
	private $token_source = null;
	
	private $service = null;
	private $client = null;
	
	public function __construct(){
		
	}
	
	public function setTokenSource( $source ){
		$this->token_source = $source;
	}
	
	public function getTokenSource(){
		return $this->token_source;
	}
	
	public function setRefreshTokenSource($source){
		$this->token_path = $source;
	}
	
	public function getRefreshTokenSource(){
		return $this->token_path;
	}
	
	public function setRedirectUri($url){
		$this->RedirectUri = $url;
	}
	
	public function getRedirectUri(){
		return $this->RedirectUri;
	}
	
	public function getConnectData(){
		$data = array(
			'ApplicationName'=> $this->ApplicationName,
			'DeveloperKey'=> $this->DeveloperKey,
			'ClientId'=> $this->ClientId,
			'ClientSecret'=> $this->ClientSecret,
			'RedirectUri'=> $this->RedirectUri,
			'Scopes'=> $this->Scopes,
			'token_path'=> $this->token_path
		);
		
		return $data;
	}
	
	/*
		$clientData = array(
			'ApplicationName' =>,
			'DeveloperKey' =>,
			'ClientId' =>,
			'ClientSecret'=>,
			'RedirectUri' =>
			'Scopes' =>,
		);
		The parameter is optional, if it is not given the properties of the class
		must have been setted or it will return an error
	*/
	public function ConnectCalendar(array $clientData = array() ){
		if($clientData != array() ){
			$this->ApplicationName = $clientData['ApplicationName'];
			$this->DeveloperKey = $clientData['DeveloperKey'];
			$this->ClientId = $clientData['ClientId'];
			$this->ClientSecret = $clientData['ClientSecret'];
			$this->RedirectUri = $clientData['RedirectUri'];
			$this->Scopes = $clientData['Scopes'];
		}
		//echo '<pre>'.print_r($clientData, true).'</pre>';
		try{
			$error = array();
			if( ( trim($this->ApplicationName) == '') or ($this->ApplicationName == null) or (	!isset($this->ApplicationName) ) ){
				array_push($error, 'ApplicationName');
			}
			if( ( trim($this->DeveloperKey) == '') or ($this->DeveloperKey == null) or (	!isset($this->DeveloperKey) ) ){
				array_push($error, 'DeveloperKey');
			}
			if( ( trim($this->ClientId) == '') or ($this->ClientId == null) or (	!isset($this->ClientId) ) ){
				array_push($error, 'ClientId');
			}
			if( ( trim($this->ClientSecret) == '') or ($this->ClientSecret == null) or (	!isset($this->ClientSecret) ) ){
				array_push($error, 'ClientSecret');
			}
			if( ( trim($this->RedirectUri) == '') or ($this->RedirectUri == null) or (	!isset($this->RedirectUri) ) ){
				array_push($error, 'RedirectUri');
			}
			
			if( $error != array() ){
				$errorMessage = '<label>The following elements for authenticating on Google Calendar are missing:</label><ul>';
				foreach($error as $element){
					$errorMessage .= '<li>'.$element.'</li>';
				}
				$errorMessage .= '</ul>';
				throw new Exception($errorMessage);
			}
			
			$this->client = new Google_Client();
			$this->client->setApplicationName($this->ApplicationName);
			$this->client->setDeveloperKey($this->DeveloperKey);
			$this->client->setAccessType('offline'); // default: offline
			$this->client->setClientId($this->ClientId);
			$this->client->setClientSecret($this->ClientSecret);
			$this->client->setRedirectUri($this->RedirectUri);
			$this->client->setScopes($this->Scopes);
			
			$this->service =  new Google_Service_Calendar($this->client);
			//echo "end of the ConnectCalendar";
		}catch(Exception $e){
			echo $e->getMessage();
		}
	}
	
	/*
		This method will e used only the first time that you need to generate a refresh token 
		and occur that the user Confirm on browser and accept that the application uses his
		Google Account
	*/
	public function generateToken(){
	
		$this->client->setApproval_promt('force');

		if (isset($_GET['logout'])) {
		  echo "<br><br><font size=+2>Logging out</font>";
		  unset($_SESSION['token']);
		}

		if (isset($_GET['code'])) {
		  echo "<br>I got a code from Google = ".$_GET['code']; // You won't see this if redirected later
		  $client->authenticate($_GET['code']);
		  $_SESSION['token'] = $client->getAccessToken();
		  if(!file_exists($this->token_path)){
				$f = fopen($this->token_path, 'w+');
				fwrite($f, $_SESSION['token']);
				fclose($f);
				chmod($this->token_path, '0400');
				chown($this->token_path, 'root');
			}
		  header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
		  echo "<br>I got the token = ".$_SESSION['token']; // <-- not needed to get here unless location uncommented
		}


		if (isset($_SESSION['token'])) {
		  echo "<br>Getting access ---";
		  $client->setAccessToken($_SESSION['token']);
			echo $_SESSION['token'];
		}

		if ($client->getAccessToken()){

		  echo "<hr><font size=+1>I have access to your calendar</font>";
		  echo "The refresh token is saven into a file in the same directory";
		  echo "<hr><br><font size=+1>Already connected</font> (No need to login)";

		} else {

		  $authUrl = $client->createAuthUrl();
		  print "<hr><br><font size=+2><a href='$authUrl'>Connect Me!</a></font>";

		}

		$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
		echo "<br><br><font size=+2><a href=$url?logout>Logout</a></font>";

	}
	
	public function logIn(){
		
		
		if($this->token_source != null) {
			$_SESSION['token'] = $this->token_source;
		  $this->client->setAccessToken($_SESSION['token']);
		}
		
		if(!isset($_SESSION['token'])){
			$f = fopen($this->token_path, 'r');
			$_SESSION['token'] = fread($f, filesize($this->token_path));
			fclose($f);
			$this->client->setAccessToken($_SESSION['token']);
		}
		
		if($this->client->getAccessToken()){
			return $this->client->getAccessToken();
		}else{
			return false;
		}
	}
	
	public function logOut(){
		unset($_SESSION['token']);
	}
	/*	Function NAME: createEvent(array arg1, arg2)
		INPUT:
			$eventData = array (
				'summary' =>,
				'location' =>,
				'description' =>,
				'startDate' =>, yyyy-mm-dd
				'startTime'=>, example: 10:25:00.000-05:00;
				'startDateTime'=>
				'endDate'=>,
				'endTime'=>,
				'endDateTime'=>
			)
		OUTPUT:
			String eventID;
	*/
	public function createEvent($calendarID, array $eventData){
		
		$event = new Google_Service_Calendar_Event();
		$event->setSummary($eventData['summary']);
		$event->setLocation($eventData['location']);
		$event->setDescription($eventData['description']);
		$start = new Google_Service_Calendar_EventDateTime();
		//$start->setDateTime('2014-9-30T10:00:00.000-05:00');
		if(!isset($eventData['startDatetime'])){
			$start->setDateTime($eventData['startDate'].'T'.$eventData['startTime']);
		}else{
			$start->setDateTime($eventData['startDateTime']);
		}
		$event->setStart($start);
		$end = new Google_Service_Calendar_EventDateTime();
		//$end->setDateTime('2014-9-30T10:25:00.000-05:00');
		if(!isset($eventData['endDateTime'])){
			$end->setDateTime($eventData['endDate'].'T'.$eventData['endTime']);
		}else{
			$end->setDateTime($eventData['startDateTime']);
		}
		$event->setEnd($end);
		$createdEvent = $this->service->events->insert($calendarID, $event);
		
		return $createdEvent->getID();
	}
	
	/* UPDATE methods are below: */
	
	public function updateEventSummary($calendarID, $eventID, $value){
		$event = $this->service->events->get($calendarID, $eventID);
		$event->setSummary($value);
		$updatedEvent = $this->service->events->update($calendarID, $event->getId(), $event);
		
		return $updatedEvent->getUpdated();
	}
	
	public function updateEventLocation($calendarID, $eventID, $value){
		$event = $this->service->events->get($calendarID, $eventID);
		$event->setLocation($value);
		$updatedEvent = $this->service->events->update($calendarID, $event->getId(), $event);
		
		return $updatedEvent->getUpdated();
	}
	
	public function updateEventDescription($calendarID, $eventID, $value){
		$event = $this->service->events->get($calendarID, $eventID);
		$event->setDescription($value);
		$updatedEvent = $this->service->events->update($calendarID, $event->getId(), $event);
		
		return $updatedEvent->getUpdated();
	}
	
	public function updateEventStartDate($calendarID, $eventID, $value){
		$event = $this->service->events->get($calendarID, $eventID);
		$start = new Google_Service_Calendar_EventDateTime();
		$start->setDate($value);
		$event->setStart($start);
		$updatedEvent = $this->service->events->update($calendarID, $event->getId(), $event);
		
		return $updatedEvent->getUpdated();
	}
	
	public function updateEventStartDateTime($calendarID, $eventID, $value){
		$event = $this->service->events->get($calendarID, $eventID);
		$start = new Google_Service_Calendar_EventDateTime();
		$start->setDateTime($value);
		$event->setStart($start);
		$updatedEvent = $this->service->events->update($calendarID, $event->getId(), $event);
		
		return $updatedEvent->getUpdated();
	}
	public function updateEventStartTimeZone($calendarID, $eventID, $value){
		$event = $this->service->events->get($calendarID, $eventID);
		$start = new Google_Service_Calendar_EventDateTime();
		$start->setTimeZone($value);
		$event->setStart($start);
		$updatedEvent = $this->service->events->update($calendarID, $event->getId(), $event);
		
		return $updatedEvent->getUpdated();
	}
	
	public function updateEventEndDate($calendarID, $eventID, $value){
		$event = $this->service->events->get($calendarID, $eventID);
		$end = new Google_Service_Calendar_EventDateTime();
		$end->setDate($value);
		$event->setEnd($end);
		$updatedEvent = $this->service->events->update($calendarID, $event->getId(), $event);
		
		return $updatedEvent->getUpdated();
	}
	
	public function updateEventEndDateTime($calendarID, $eventID, $value){
		$event = $this->service->events->get($calendarID, $eventID);
		$end = new Google_Service_Calendar_EventDateTime();
		$end->setDateTime($value);
		$event->setEnd($end);
		$updatedEvent = $this->service->events->update($calendarID, $event->getId(), $event);
		
		return $updatedEvent->getUpdated();
	}
	
	public function updateEventEndTimeZone($calendarID, $eventID, $value){
		$event = $this->service->events->get($calendarID, $eventID);
		$end = new Google_Service_Calendar_EventDateTime();
		$end->setTimeZone($value);
		$event->setEnd($end);
		$updatedEvent = $this->service->events->update($calendarID, $event->getId(), $event);
		
		return $updatedEvent->getUpdated();
	}
	
	public function updateEventStatus($calendarID, $eventID, $value){
		$event = $this->service->events->get($calendarID, $eventID);
		$event->setStatus($value);
		$updatedEvent = $this->service->events->update($calendarID, $event->getId(), $event);
		
		return $updatedEvent->getUpdated();
	}
	
	/*	Function NAME: updateEvent(arg1, arg2, array arg3)
		INPUT:
		$calendarID -> ID of the Google Calendar(search on google to see how to find it)
		$eventID -> ID of the event (when you createEvent it return the ID of the event or you can retrieve it by the listEvent) 
		$key_value -> array(
				'Summary' => ,
				'Description' => ,
				'Location' =>,
				'start' => array(
						'Date'=>,
						'DateTime'=>,
						'Timezone'=>
					),
				'end' => array(
						'Date'=>,
						'DateTime'=>,
						'Timezone'=>
					)
				)
				it is an array with KEY the field that have to be updated and VALUE the value that will be setted 
	*/
	
	public function updateEvent($calendarID, $eventID, array $key_value){
		$event = $this->service->events->get($calendarID, $eventID);
		$wasEnd = 0; //it will be used to see if is to update an end datetime or not
					// IMPORTANT: the start date/datetime mus be before the end date/datetime
		
		foreach($key_value as $key => $value){
			if(strtolower($key) == 'start'){
			
				if( array_key_exists('end', $key_value) ){
					$end = new Google_Service_Calendar_EventDateTime();
					foreach($key_value['end'] as $subKey=>$subValue){
						$subAction = 'set'.ucfirst(strtolower($subKey));
						$end->$subAction($subValue);
					}
					$event->setEnd($end);
					
					$wasEnd =1;
				}
				
				$start = new Google_Service_Calendar_EventDateTime();
				foreach($value as $subKey=>$subValue){
					$subAction = 'set'.ucfirst(strtolower($subKey));
					$start->$subAction($subValue);
					
				}
				$value = $start;
				
			}
			if( (strtolower($key) == 'end') AND ($wasEnd !=1) ){
				$end = new Google_Service_Calendar_EventDateTime();
				foreach($value as $subKey=>$subValue){
					$subAction = 'set'.ucfirst(strtolower($subKey));
					$end->$subAction($subValue);
				}
				$value = $end;
			}
			$action = 'set'.ucfirst(strtolower($key));
			$event->$action($value);
		}
		$updatedEvent = $this->service->events->update($calendarID, $event->getId(), $event);
		
		return $updatedEvent->getUpdated();
	}
	
	/*GET methods are below: */
	
	public function getEventEnd($calendarID, $eventID){
		$event = $this->service->events->get($calendarID, $eventID);
		return $event->getEnd();
	}
	
	public function getEventStart($calendarID, $eventID){
		$event = $this->service->events->get($calendarID, $eventID);
		return $event->getStart();
	}
	
	public function getEventSummary($calendarID, $eventID){
		$event = $this->service->events->get($calendarID, $eventID);
		return $event->getSummary();
	}
	
	public function getEventLocation($calendarID, $eventID){
		$event = $this->service->events->get($calendarID, $eventID);
		return $event->getLocation();
	}
	
	public function getEventDescription($calendarID, $eventID){
		$event = $this->service->events->get($calendarID, $eventID);
		return $event->getDescription();
	}
	
	public function getEventStatus($calendarID, $eventID){
		$event = $this->service->events->get($calendarID, $eventID);
		return $event->getStatus();
	}
	
	/*	Function NAME: getEventData(arg1, arg2, array arg3 = array())
		
		INPUT:
		 1st parameter: ID of the Google Calendar
		 2nd parameter: ID of the Event of the Google Calendar given
		 3rd parameter: ex: array('summary','description', 'location', 'end', 'start', .....)
		
		OUTPUT:
		 ex: array(
				[summary] => 'The summary of the event',
				[description] => 'The description of the event selected',
				[location] => 'The location of the event',
				[end] => Google_Service_Calendar_EventDateTime Object
						(
							[date] => 
							[dateTime] => 2014-09-29T14:00:00Z
							[timeZone] => 
							[modelData:protected] => Array
								(
								)

							[processed:protected] => Array
								(
								)

						)
				[....] => ..........
			)
	*/
	
	public function getEventData($calendarID, $eventID, array $fields = array() ){
		try{
			if($fields == array() ){
				throw new Exception('Third parameter expected as array(), found nothing');
			}
			$data =array();
			
			$event = $this->service->events->get($calendarID, $eventID);
			foreach($fields as $field){
				$action = 'get'.ucfirst(strtolower($field));
				$data = $data + array($field => $event->$action());
			}
			
			return $data;
			
		}catch(Exception $e){
			echo $e->getMessage();
		}
		
	}
	
	/*	Function NAME: listEvents(arg1);
		INPUT:
			calendarID -> the ID of the Google Calendar
		
		OUTPUT:
			An array with a list of all event IDs of the Google Calendar selected
			ex: array(
				[0] => akjdshkjashdjkahsdknkajsd,
				[1] => yhnkhgiywedcvbnkydcvbjsak,
				[2] => ........................,
				[..] => ....................
				)
			
	*/
	public function listEvents($calendarID){
		$events = $this->service->events->listEvents($calendarID);
		$id_list =array();
		while(true){
			foreach($events->getItems() as $event){
				//echo $event->getSummary().' - '.$event->getId().' - '.$event->getLocation().' - '.$event->getDescription().' \n ';
				array_push($id_list, $event->getId());
			}
			$pageToken = $events->getNextPageToken();
			if($pageToken){
				$optParams = array( 'pageToken' => $pageToken);
				$events = $this->service->events->listEvents($calendarID, $optParams);
			} else{
				break;
			}
		}
		
		return $id_list;
	}
	
	public function listEventsBetweenDates($calendarID, $dateTime1, $dateTime2 ){
		$events = $this->service->events->listEvents(
									$calendarID, 
									array(
										'timeMin'=> $dateTime1,
										'timeMax'=> $dateTime2
									)
								);
		$id_list =array();
		while(true){
			foreach($events->getItems() as $event){
				//echo $event->getSummary().' - '.$event->getId().' - '.$event->getLocation().' - '.$event->getDescription().' \n ';
				array_push($id_list, $event->getId());
			}
			$pageToken = $events->getNextPageToken();
			if($pageToken){
				$optParams = array( 'pageToken' => $pageToken);
				$events = $this->service->events->listEvents($calendarID, $optParams);
			} else{
				break;
			}
		}
		
		return $id_list;
		
	}
	
	public function deleteEvent($calendarID, $eventID){
		return $this->service->events->delete($calendarID,$eventID);
	}
	
	/*	Function NAME: deleteAllEvents(arg1);
		INPUT:
			calendarID -> the ID of the Google Calendar
		
		OUTPUT:
			Nothing will return,
			If any error occur the Google Exception will be printed
	*/
	public function deleteAllEvents($calendarID){
		$list = $this->listEvents($calendarID);
		foreach($list as $id){
			$this->deleteEvent($calendarID, $id);
		}
	}
	
	public function deleteEventsBetweenDates($calendarID, $dateTime1, $dateTime2){
		$list = $this->listEventsBetweenDates($calendarID, $dateTime1, $dateTime2);
		foreach($list as $id){
			$this->deleteEvent($calendarID, $id);
		}
	}
	
}

?>
