<?php
namespace App\Service;

use App\Entity\Notification; 

class DolibarrClientService
{
    private $dolibarrKey;
    private $dolibarrUrl;
    private $dolibarrAccountId;
    private $adhesionStartDate;
    private $adhesionEndDate;
    

    public function __construct($dolibarrKey, $dolibarrUrl, $dolibarrAccountId, $adhesionStartDate, $adhesionEndDate){
        $this->dolibarrKey = $dolibarrKey;
        $this->dolibarrUrl = $dolibarrUrl;
        $this->dolibarrAccountId = $dolibarrAccountId;
        $this->adhesionStartDate = $adhesionStartDate;
        $this->adhesionEndDate = $adhesionEndDate;
    }

    public function processNotification(Notification $notification){
        
        switch($notification->getEventType()){
            case Notification::EVENT_TYPE_ORDER:
                $this->processOrderandPayment($notification);
            break;

            case Notification::EVENT_TYPE_FORM:
            break;

            case Notification::EVENT_TYPE_PAYMENT:
                $this->processOrderandPayment($notification);
            break;

            default:
            break;
        } 
    }
    public function processOrderAndPayment(Notification $notification){
        
        $data = $notification->getData();
        
        $city = $data['payer']['city'];
        $email = $data['payer']['email'];
        $address = $data['payer']['address'];
        $company = $data['payer']['company'];
        $zipCode = $data['payer']['zipCode'];
        $dateOfBirth = $data['payer']['dateOfBirth'];
        $payerlastName = $data['payer']['lastName'];
        $payerfirstname = $data['payer']['firstName'];
        $adhesion = false;
        $don = false;       

        foreach($data['items'] as $item){
            if(strcmp($item['type'], 'Membership')==0){
                $montant_adhesion = substr($item['amount'], 0, -2); // suppression des centimes --> à corriger @TODO
                
                if(strcmp($notification->getEventType(),'Order')==0){
                    $lastName = $item['user']['lastName'];
                    $firstname = $item['user']['firstName'];
                } 
                else{
                    $lastName = $payerlastName;
                    $firstname = $payerfirstname;
                } 
                $login = strtolower(substr($firstname,0,1).$lastName);
                $login = str_replace(' ', '', $login);
                if(strcmp($montant_adhesion,'15')==0){
                    $type_adherent = 1;
                    $morphy='phy';
                }
                if(strcmp($montant_adhesion,'50')==0){
                    $type_adherent = 2;
                    $morphy='mor';
                }
                $PostParams = [
                    'email' => $email,
                    'login' => $login,
                    'lastname' => $lastName,
                    'firstname' => $firstname,
                    'pass' => 'tmp',
                    'morphy' => $morphy,
                    'typeid'=> $type_adherent, 
                    'statut' => 1,
                    'country' => 1,
                    'societe' => $company,
                    'town' => $city,
                    'address' => $address,
                    'zip' => $zipCode,
                    'bith' => $dateOfBirth
                ];
    
                $memberId = $this->createObjectIfNotExists('members','login',$login,$PostParams);
                $memberId = $this->getObjectId('members','login',$login);
                
                $startDate = $this->getStartOfAdhesionDate();
                $NomAdhesion = 'Adhésion '.date("Y",$startDate);      
                $PostParams = [
                    'start_date' => $startDate,
                    'end_date' => $this->getEndOfAdhesionDate(),
                    'amount' => $montant_adhesion,
                    'note' => $NomAdhesion,
                    'label'=>$NomAdhesion
                ];
                $memberId = $this->createObjectIfNotExists('members/'.$memberId.'/subscriptions','note',$NomAdhesion,$PostParams);
                $label_ecriture = $NomAdhesion." ".$firstname." ".$lastName;
                $PostParams = [
                    "date"=> time(),
                    "type"=> "6",
                    "label"=> $label_ecriture,
                    "amount"=> $montant_adhesion,
                ];
                $lineId = $this->createObjectIfNotExists("bankaccounts/".$this->dolibarrAccountId."/lines",'label',$label_ecriture,$PostParams);
            }
            if(strcmp($item['type'], 'Donation')==0){
                $montant_don = substr($item['amount'], 0, -2); // suppression des centimes --> à corriger @TODO
                $id_don = $item['id']; 
                $date_don = time();
                $existing = $this->objectExist("donations", 'note_private', $id_don);
                if(!$existing){
                    $PostParams = [
                        'entity' => 1,
                        'status' => '1',
                        "mode_reglement_id" => 6,
                        "fk_payment" => 6,
                        'paid' => 0,
                        'lastname' => $payerlastName,
                        'firstname' => $payerfirstname,
                        'public' => 0,
                        'amount' => $montant_don,
                        'date' => time(),
                        'datedon'=> time(),
                        'address' => $address, 
                        'zip' => $zipCode, 
                        'town' => $city, 
                        'email' => $email, 
                        'public' => 0, 
                        'note_public' => 'Don reçu via Hello Asso',
                        'note_private' => $id_don
                    ];
                    $result = $this->createObject('donations/',$PostParams);

                    $label_ecriture = "Don ".$payerfirstname." ".$payerlastName. " n°".$id_don;
                    $PostParams = [
                        "date"=> time(),
                        "type"=> "6",
                        "label"=> $label_ecriture,
                        "amount"=> $montant_don,
                    ];
                    $lineId = $this->createObjectIfNotExists("bankaccounts/".$this->dolibarrAccountId."/lines",'label',$label_ecriture,$PostParams);
                    $don_doli_id = $this->getObjectId('donations/', 'note_private', $id_don);
                    $PutParams = [
                        'statut'=>2   
                    ];
                    $result = $this->updateObject("donations/",$don_doli_id,$PutParams); // mise à jour du don au statut "Payé"
                }
                else{
                    $result = true;
                } 
                return $result;
            } 
        }
        return true;
    } 

    public function objectExist($objectType, $queryField, $queryFieldValue){
        $doli_param = ["sortqueryField" => 't.rowid', "sortorder" => "ASC", 'limit' => 100];
        $resultats = $this->callDoliAPI('GET',$objectType,$doli_param);
        $resultats = json_decode($resultats, true);
        $existing = false;
        if (isset($resultats["error"]) && $resultats["error"]["code"] >= "300") {

        } else {
            foreach($resultats as $resultat){
                if(strcmp($resultat[$queryField],($queryFieldValue))==0){
                    $existing = true;
                } 
            } 
        }
        return $existing;
    } 

    public function getObjectId($objectType, $queryField, $queryFieldValue){
        $doli_param = ["sortqueryField" => 't.rowid', "sortorder" => "ASC", 'limit' => 100];
        $resultats = $this->callDoliAPI('GET',$objectType,$doli_param);
        $resultats = json_decode($resultats, true);
        $existing = false;
        if (isset($resultats["error"]) && $resultats["error"]["code"] >= "300") {
            return null;
        } else {
            foreach($resultats as $resultat){
                if(strcmp($resultat[$queryField],($queryFieldValue))==0){
                    return $resultat["id"];
                } 
            } 
        }
        return null;
    } 

    public function createObjectIfNotExists($objectType, $queryField, $queryFieldValue, $PostParams){       
        $existing = $this->objectExist($objectType,$queryField,$queryFieldValue);
        if($existing){
            return false;
        }
        else{ 
            $doli_param = json_encode($PostParams);
            return $this->callDoliAPI('POST',$objectType,$doli_param);
        }

    } 
    public function createObject($objectType, $PostParams){       
        $doli_param = json_encode($PostParams);
        return $this->callDoliAPI('POST',$objectType,$doli_param);
    }

    public function updateObject($objectType, $id,$PutParams){
        $doli_param = json_encode($PutParams);
        return $this->callDoliAPI('PUT',$objectType.$id,$doli_param);
    } 

    public function callDoliAPI($method,$object,$data = false)
    {
        sleep(1); // ajouté à cause d'une erreur 429 'too many request' du serveur (hébergement mutualisé)
        $url = $this->dolibarrUrl.$object;
        $curl = curl_init();
        $httpheader = ['DOLAPIKEY: '.$this->dolibarrKey];

        switch ($method)
        {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);
                $httpheader[] = "Content-Type:application/json";

                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                $httpheader[] = "Content-Type:application/json";

                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

                break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheader);
        $result = curl_exec($curl);

        curl_close($curl);

        return $result;
    }

    public function getEndOfAdhesionDate(){
        $now = time();
        $end = strtotime($this->adhesionEndDate);
        if ($now > $end)
            return(strtotime('+1 year', $end));
        else 
            return($end);
    }
    
    public function getStartOfAdhesionDate(){
        $now = time();
        $start = strtotime($this->adhesionStartDate);
        if ($start>$now)
            return(strtotime('-1 year', $start));
        else 
            return($start);
    } 
}