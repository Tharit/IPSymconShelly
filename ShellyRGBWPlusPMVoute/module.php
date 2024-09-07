<?php

class ShellyRGBWPlusPMVoute extends IPSModule
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

        $Buffer = json_decode($JSONString, true);

        $Payload = json_decode($Buffer['Payload'], true);
        if (array_key_exists('Topic', $Buffer)) {
            if (fnmatch('*/online', $Buffer['Topic'])) {
                $this->SetValue('Connected', $Payload);
            }
        }
    }

    public function SendRequest(string $Ident, $Value)
    {
        //MQTT Server
        $Server['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
        $Server['PacketType'] = 3;
        $Server['QualityOfService'] = 0;
        $Server['Retain'] = false;
        $Server['Topic'] =  $this->ReadPropertyString('Topic') . '/' . $Ident;
        $Server['Payload'] = json_encode($Value);
        $ServerJSON = json_encode($Server, JSON_UNESCAPED_SLASHES);
        $resultServer = $this->SendDataToParent($ServerJSON);
    }
}