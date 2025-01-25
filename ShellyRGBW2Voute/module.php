<?php

class ShellyRGBW2Voute extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        // properties
        $this->RegisterPropertyString('Topic', '');

        // variables
        $this->RegisterVariableBoolean("Connected", "Connected");

        $this->RegisterVariableFloat("Energy", "Energy", '~Electricity');
        $this->RegisterVariableFloat("Power", "Power", '~Watt.3680');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $topic = $this->ReadPropertyString('Topic');
        $this->SetReceiveDataFilter('.*' . $topic . '.*');
    }

    public function ReceiveData($JSONString)
    {
        $this->SendDebug('JSON', $JSONString, 0);
        if (empty($this->ReadPropertyString('Topic'))) return;

        $data = json_decode($JSONString);

        $Buffer = $data;

        if (fnmatch('*/online', $Buffer->Topic)) {
            $this->SetValue("Connected", $Buffer->Payload === 'true' ? true : false);
        } else if (fnmatch('*/color/0/energy', $Buffer->Topic)) {
            $this->SetValue('Energy', intval($Buffer->Payload)/1000);
        } else if (fnmatch('*/color/0/power', $Buffer->Topic)) {
            $this->SetValue('Power', floatval($Buffer->Payload));
        }
    }

    public function SendRequest(string $Ident, string $Value)
    {
        //MQTT Server
        $Server['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
        $Server['PacketType'] = 3;
        $Server['QualityOfService'] = 0;
        $Server['Retain'] = false;
        $Server['Topic'] = 'shellies/' . $this->ReadPropertyString('Topic') . '/' . $Ident;
        $Server['Payload'] = $Value;
        $ServerJSON = json_encode($Server, JSON_UNESCAPED_SLASHES);
        $resultServer = $this->SendDataToParent($ServerJSON);
    }
}