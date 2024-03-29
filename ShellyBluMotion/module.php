<?php

class ShellyBluMotion extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        // properties
        $this->RegisterPropertyString('Address', '');

        // variables
        $this->RegisterVariableBoolean("Motion", "Motion", "~Motion");
        $this->RegisterVariableInteger("Illuminance", "Illuminance", "~Illumination");
        $this->RegisterVariableInteger("Battery", "Battery", '~Battery.100');

        $this->SetBuffer('pid', serialize(-1));
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $topic = $this->ReadPropertyString('Address');
        $this->SetReceiveDataFilter('.*' . $topic . '.*');
    }

    public function ReceiveData($JSONString)
    {
        $this->SendDebug('JSON', $JSONString, 0);
        if (empty($this->ReadPropertyString('Address'))) return;

        $Buffer = json_decode($JSONString, true);
        $Payload = json_decode($Buffer['Payload'], true);
        
        // deduplicate packages (e.g., if multiple gateways are receiving..)
        $lastPID = unserialize($this->GetBuffer('pid'));
        if($lastPID == $Payload['pid']) return;
        $this->SetBuffer('pid', serialize($Payload['pid']));

        if(isset($Payload['Motion'])) {
            $this->SetValue('Motion', $Payload['Motion']);
        }
        if(isset($Payload['Illuminance'])) {
            $this->SetValue('Illuminance', $Payload['Illuminance']);
        }
        if(isset($Payload['Battery'])) {
            $this->SetValue('Battery', $Payload['Battery']);
        }
    }

}