<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */
/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
class okofen extends eqLogic {
    /*     * *************************Attributs****************************** */
	public $_cookies = '/tmp/jeedom_okofen_curl';/*Better path for cookie?*/
        private $_responseBoiler ='';
	private $_parsedData = array();
	private $_TemperatureExt_OkoLabel = 'CAPPL:LOCAL.L_aussentemperatur_ist';
	private $_TemperatureChaud_OkoLabel = 'CAPPL:FA[0].L_kesseltemperatur';
	private $_TempAmbiante_OkoLabel = 'CAPPL:LOCAL.L_hk[0].raumtemp_ist';
	//in a future version may populate by  http://192.168.1.59:85/js/config.min.js
	//or use stawen map: https://github.com/stawen/okovision/blob/master/_langs/vartx_fr.properties
	private $_OkoValues = array (
	    array( 'name' => 'Etat Chaudière',            'okoId' => 'CAPPL:FA[0].L_kesselstatus'              , 'subType' => 'string'  ),
	    array( 'name' => 'Temp Ext',          'okoId' => 'CAPPL:LOCAL.L_aussentemperatur_ist'      , 'subType' => 'numeric'),	
        array( 'name' => 'Temp Chaudière',    'okoId' => 'CAPPL:FA[0].L_kesseltemperatur'          , 'subType' => 'numeric'),
	    array( 'name' => 'Temp Amb',          'okoId' => 'CAPPL:LOCAL.L_hk[0].raumtemp_ist'        , 'subType' => 'numeric'),        
		array( 'name' => 'Pompe Chf1',        'okoId' => 'CAPPL:LOCAL.L_hk[0].pumpe'               , 'subType' => 'string' ),
        array( 'name' => 'Mode Chf1',         'okoId' => 'CAPPL:LOCAL.hk[0].betriebsart[1]'        , 'subType' => 'string'  ),
		array( 'name' => 'Consigne confort',         'okoId' => 'CAPPL:LOCAL.hk[0].raumtemp_heizen'        , 'subType' => 'numeric'  ),
		array( 'name' => 'Consigne reduit',         'okoId' => 'CAPPL:LOCAL.hk[0].raumtemp_absenken'        , 'subType' => 'numeric'  ),
		array( 'name' => 'Charge ECS',         'okoId' => 'CAPPL:LOCAL.anlage_betriebsart'        , 'subType' => 'string'  ),
		array( 'name' => 'Mode ECS',         'okoId' => 'CAPPL:LOCAL.ww[0].betriebsart[1]'        , 'subType' => 'string'  ),
		array( 'name' => 'Temp Ecs',          'okoId' => 'CAPPL:LOCAL.L_ww[0].einschaltfuehler_ist', 'subType' => 'numeric'),
		array( 'name' => 'Consigne Ecs',          'okoId' => 'CAPPL:LOCAL.ww[0].temp_heizen', 'subType' => 'numeric'),
		array( 'name' => 'Min Ecs',          'okoId' => 'CAPPL:LOCAL.ww[0].temp_absenken', 'subType' => 'numeric'),
		array( 'name' => 'Pompe Ecs',          'okoId' => 'CAPPL:LOCAL.L_ww[0].pumpe', 'subType' => 'string'),
        );
    /*     * ***********************Methode static*************************** */
     /* Fonction exécutée automatiquement toutes les minutes par Jeedom*/
      public static function cron() {
	foreach (eqLogic::byType('okofen', true) as $okofen) {
        	$okofen->getInformations(false);
	}
      }

    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {
      }
     */
    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDayly() {
      }
     */
    /*     * *********************Méthodes d'instance************************* */
    public function preInsert() {

    }
    public function postInsert() {
        
    }
    public function preSave() {
     $this->setDisplay("width","300px");   
    }
    public function postSave() {
  
    }
    public function preUpdate() {
        
    }
    public function postUpdate() {
  
	//--
	//var_dump($this->_OkoValues);exit;
	foreach($this->_OkoValues as $okoValue)
	{
	  $cmdlogic = okofenCmd::byEqLogicIdAndLogicalId($this->getId(),$okoValue['okoId']);
      if (!is_object($cmdlogic)) {
        $cmdlogic = new okofenCmd();
        $cmdlogic->setName($okoValue['name']);
        $cmdlogic->setLogicalId($okoValue['okoId']);
        $cmdlogic->setEqLogic_id($this->getId());
		/*$cmdlogic->setConfiguration('updateField',true);*/

		$cmdlogic->setType('info');
        $cmdlogic->setSubType($okoValue['subType']);
        $cmdlogic->save();
             }
        }
	// --Chaudière
      $cmdlogic = okofenCmd::byEqLogicIdAndLogicalId($this->getId(),$this->_TemperatureChaud_OkoLabel);
      if (!is_object($cmdlogic)) {
        $cmdlogic = new okofenCmd();
        $cmdlogic->setName(__('Temp Chaudière', __FILE__));
        $cmdlogic->setEqLogic_id($this->getId());
        $cmdlogic->setLogicalId($this->_TemperatureChaud_OkoLabel);
        $cmdlogic->setConfiguration('OkofenId',$this->_TemperatureChaud_OkoLabel);
		$cmdlogic->setConfiguration('updateField',true);

		}
		$cmdlogic->setType('info');
		$cmdlogic->setSubType('numeric');
		$cmdlogic->save();
	
	//--Exterieur
      $cmdlogic = okofenCmd::byEqLogicIdAndLogicalId($this->getId(),$this->_TemperatureExt_OkoLabel);
      if (!is_object($cmdlogic)) {
        $cmdlogic = new okofenCmd();
        $cmdlogic->setName(__('Temp Ext', __FILE__));
        $cmdlogic->setEqLogic_id($this->getId());
        $cmdlogic->setLogicalId($this->_TemperatureExt_OkoLabel);
        $cmdlogic->setConfiguration('OkofenId',$this->_TemperatureExt_OkoLabel);
		$cmdlogic->setConfiguration('updateField',true);
      }
		$cmdlogic->setType('info');
		$cmdlogic->setSubType('numeric');
		$cmdlogic->save();
	
	//--Ambiante
	$cmdlogic = okofenCmd::byEqLogicIdAndLogicalId($this->getId(),$this->_TempAmbiante_OkoLabel);
      	if (!is_object($cmdlogic)) {
        $cmdlogic = new okofenCmd();
	    $cmdlogic->setName(__('Temp Ambiante', __FILE__));
        $cmdlogic->setEqLogic_id($this->getId());
	    $cmdlogic->setLogicalId($this->_TempAmbiante_OkoLabel);
	    $cmdlogic->setConfiguration('OkofenId',$this->_TempAmbiante_OkoLabel);
	    $cmdlogic->setConfiguration('updateField',true);
	}
      	$cmdlogic->setType('info');
      	$cmdlogic->setSubType('numeric');
		$cmdlogic->save();

      	$this->getInformations(true);
    }
    public function preRemove() {
        
    }
    public function postRemove() {
        
    }
///----MEs fonctions
	/**
	* Function making live connection whit boiler
	* 
	*/
    public function loginPellematic(){
        $ip = $this->getConfiguration('ip');
        if ($ip == '') {
              throw new Exception(__('ip cannot be empty', __FILE__));
	      return;
	    }
	$login = $this->getConfiguration('login');
	if ($login == '') {
             throw new Exception(__('login cannot be empty', __FILE__));
	     return;
	}
    	$password = $this->getConfiguration('password');
	if ($password == '') {
             throw new Exception(__('password cannot be empty', __FILE__));
	      return;
	}
	log::add('okofen', 'debug', 'ip: '.$ip);
        log::add('okofen', 'debug', 'login: '.$login);
	log::add('okofen', 'debug', 'password: '.$password);
        $loginUrl = 'http://'.$ip.':8080/index.cgi';
        log::add('okofen', 'debug', 'loginUrl: '.$loginUrl);
		
	    $curl = curl_init();
	    
	    curl_setopt_array($curl, array(
	    		   CURLOPT_VERBOSE => false,
	    		   CURLOPT_RETURNTRANSFER => true,
	    		   CURLOPT_URL => $loginUrl,
	    		   CURLOPT_USERAGENT => 'Jeedom',
	    		   CURLOPT_POST => 1,
	    		   CURLOPT_COOKIEJAR => $this->_cookies,
	    		   CURLOPT_POSTFIELDS => 
	        		   http_build_query( array(
	        		        'username' => $login,
	        		        'password' => $password,
	        		        'language' => 'fr',
	        		        'submit'   => 'login'
	        		    ))
	    		));
	    // Send the request & save response to $resp
	    $resp = curl_exec($curl);
            //var_dump($resp);exit;
	    if($resp == "")
            {
                $info = curl_getinfo($curl);
                //var_dump($info);exit;
                if($info['http_code'] == '303'){
	            log::add('okofen', 'debug', 'log succed');
                    $result = true;
	        }else{
	            throw new Exception(__('Failled to Login into device, please check login / password', __FILE__));
                    $result = false;
	            //$this->_connected = false;
	        }
            }
            else
            {
		throw new Exception(__('Cannot connect to"'.$ip.'" please check it! Error: '.curl_error($curl), __FILE__));	        
                 
            }
	    curl_close($curl);
	    return $result;
	    
	}
   public function getDataPellematic(){
        $ip = $this->getConfiguration('ip');
        if ($ip == '') {
              throw new Exception(__('ip cannot be empty', __FILE__));
	      return;
	    }
	//log::add('okofen', 'debug', 'ip: '.$ip);
        //log::add('okofen', 'debug', 'login: '.$login);
	//log::add('okofen', 'debug', 'password: '.$password);
	 $curl = curl_init();
	$cmds = okofenCmd::byEqLogicId($this->getId());
        $postField = '[';
        //var_dump($cmds);exit;
        foreach($cmds as $cmd)
        {
           $postField .= '"'.$cmd->getLogicalId().'"';
           if($cmd !== end($cmds))
	   {
               $postField .= ',';
           }
        }
        $postField .= ']';
        //var_dump($postField);exit;
	 $getDataUrl = 'http://'.$ip.':8080/index.cgi?action=get&attr=1';
	    
	    curl_setopt_array($curl, array(
	           CURLOPT_VERBOSE => false,
			   CURLOPT_RETURNTRANSFER => true,
			   CURLOPT_URL => $getDataUrl,
			   CURLOPT_POST => 1,
			   CURLOPT_HTTPHEADER => array(
			        'Accept: application/json',
	                'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
	                'Accept-Language: fr'),
			   CURLOPT_COOKIEFILE => $this->_cookies,
			   CURLOPT_POSTFIELDS => $postField
			   
			));
	    
	    $resp = curl_exec($curl);
	    $this->_responseBoiler='';
            $code = 0;
	    
	    if(!curl_errno($curl)){
	        
	        $info = curl_getinfo($curl);
	        
	        if($info['http_code'] == '200'){
	            $this->_responseBoiler = $resp;
		    //var_dump($resp);exit;
                    log::add('okofen', 'debug', 'resp'.$resp);
                    $code = true;	            	        	
	        }
                $code = $info['http_code'];
	    }
	    
	    curl_close($curl);
	    return $code;
   }
    public function parseData($_cmdField)
    {
	$this->_parsedData = array();
	//var_dump($this->_responseBoiler);exit;
	$dataBoiler = json_decode($this->_responseBoiler);
	//var_dump($dataBoiler);exit;  
	//var_dump($_cmdField);exit;  
        foreach($dataBoiler as $capt){
                //var_dump($capt);exit; 				
		if($capt->formatTexts != ''){
				
			$shortTxt 	= 'ERROR';
			$value		= 'null';
			$s= array();
			
				
			if($capt->value != '???'){
				$s = explode ("|",$capt->formatTexts);
				$shortTxt 	= $capt->shortText;
				$value		= $s[$capt->value];
			}
				
			$this->_parsedData[$capt->name] =  $value;
		}else{
			$this->_parsedData[$capt->name] = ($capt->divisor != '' && $capt->divisor != '???' )?($capt->value / $capt->divisor):($capt->value);
				//"unitText" => ($capt->unitText=='???')?'':(($capt->unitText=='K')?'°C':$capt->unitText),
				//"divisor" => $capt->divisor,
				//"lowerLimit" => $capt->lowerLimit,
				//"upperLimit" => $capt->upperLimit
                        if($_cmdField)
                        {
		             $cmdlogic = okofenCmd::byEqLogicIdAndLogicalId($this->getId(),$capt->name); 
                             //var_dump($cmdlogic);exit;  
                             if (is_object($cmdlogic))
                             {
                                 $cmdlogic->setUnite( ($capt->unitText=='???')?'':(($capt->unitText=='K')?'°C':$capt->unitText) );
				 $cmdlogic->save();
                             }
			     //var_dump($cmdlogic);exit; 
                        }
		}
		
	}
	//var_dump($this->_parsedData);exit;        
    }
	
  /**
  *_cmdField set to true on first populate, so we parse extra information from okofen reply to get unit, or any extra information about a cmd
  */
  public function getInformations($_cmdField) {
	$this->loginPellematic();
    	$this->getDataPellematic();
    	$this->parseData($_cmdField);
    	$changed = false;
        //var_dump($this->_parsedData);exit;
	foreach ($this->_parsedData as $okoId => $value) {
		$changed = $this->checkAndUpdateCmd($okoId, $value) || $changed;
	}

    	$temp = $this->_parsedData[$this->_TemperatureExt_OkoLabel];
    	if($temp) { 	 
        	$changed = $this->checkAndUpdateCmd($this->_TemperatureExt_OkoLabel, $temp) || $changed;
    	}   
    	$temp = $this->_parsedData[$this->_TemperatureChaud_OkoLabel];
    	if($temp) { 	 
        	$changed = $this->checkAndUpdateCmd($this->_TemperatureChaud_OkoLabel, $temp) || $changed;
    	} 
	$temp = $this->_parsedData[$this->_TempAmbiante_OkoLabel];
    	if($temp) { 	 
        	$changed = $this->checkAndUpdateCmd($this->_TempAmbiante_OkoLabel, $temp) || $changed;
    	}
  }
    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
      public function toHtml($_version = 'dashboard') {
      }
     */
    /*     * **********************Getteur Setteur*************************** */
}
class okofenCmd extends cmd {
    /*     * *************************Attributs****************************** */
    /*     * ***********************Methode static*************************** */
    /*     * *********************Methode d'instance************************* */
    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */
    public function execute($_options = array()) {
              return $this->getConfiguration('value');
        
    }
    /*     * **********************Getteur Setteur*************************** */
}
