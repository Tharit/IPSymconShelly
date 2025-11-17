<?php

class ShellyBluRCButton4 extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        // properties
        $this->RegisterPropertyString('Address', '');

        // variables
        $this->RegisterVariableInteger("Button0", "Button0");
        $this->RegisterVariableInteger("Button1", "Button1");
        $this->RegisterVariableInteger("Button2", "Button2");
        $this->RegisterVariableInteger("Button3", "Button3");
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
        // packet id must be larger/newer than previous.. but allow for rollover if difference is large enough (e.g., 30 = 5m, assuming 1 packet every ~10s)
        $lastPID = unserialize($this->GetBuffer('pid'));
        $pid = intval($Payload['pid']);
        if($lastPID <= $pid && ($lastPID - $pid < 30)) return;
        $this->SetBuffer('pid', serialize($pid));

        if(isset($Payload['Button'])) {
            $this->SetValue('Button0', $Payload['Button'][0]);
            $this->SetValue('Button1', $Payload['Button'][1]);
            $this->SetValue('Button2', $Payload['Button'][2]);
            $this->SetValue('Button3', $Payload['Button'][3]);
        }
        if(isset($Payload['Battery'])) {
            $this->SetValue('Battery', $Payload['Battery']);
        }
    }

}