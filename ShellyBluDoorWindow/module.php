<?php

class ShellyBluDoorWindow extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        // properties
        $this->RegisterPropertyString('Address', '');
        $this->RegisterPropertyFloat('RotationThreshold', 0.0);

        // variables
        $this->RegisterVariableInteger("State", "State");
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
        if(!isset($Payload['pid']) || $lastPID == $Payload['pid']) return;
        $this->SetBuffer('pid', serialize($Payload['pid']));

        $newState = null;
        if(isset($Payload['Rotation']) && 
            abs($Payload['Rotation']) > abs($this->ReadPropertyFloat('RotationThreshold'))) {
            $newState = 2;
        } else if(isset($Payload['Window']) && $Payload['Window'] == 1) {
            $newState = 1;
        } else if(isset($Payload['Window'])) {
            $newState = 0;
        }
        // workaround for firmware bug (v1.0.16): if devices restarts then PID=2 will have Rotation=0, even if the device is actually rotated..
        // if this case is detected, skip the update
        if($Payload['pid'] == 2 && $Payload['Rotation'] == 0 && $this->GetValue('State') == 2) {
            $newState = null;
        }
        if($newState !== null) {
            $this->SetValue('State', $newState);
        }
        if(isset($Payload['Battery'])) {
            $this->SetValue('Battery', $Payload['Battery']);
        }
    }

}