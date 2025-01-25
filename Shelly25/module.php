<?php

class Shelly25 extends IPSModule
{
    protected function getChannelRelay(string $topic, $offset = 0)
    {
        $ShellyTopic = explode('/', $topic);
        $LastKey = count($ShellyTopic) - 1 + $offset;
        $relay = $ShellyTopic[$LastKey];
        return $relay;
    }

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        // properties
        $this->RegisterPropertyString('Topic', '');

        // variables
        $this->RegisterVariableBoolean("Connected", "Connected");
        $this->RegisterVariableBoolean("Input1", "Input1");
        $this->RegisterVariableBoolean("Input2", "Input2");
        $this->RegisterVariableBoolean("State1", "State1", '~Switch');
        $this->EnableAction("State1");
        $this->RegisterVariableBoolean("State2", "State2", '~Switch');
        $this->EnableAction("State2");

        $this->RegisterVariableFloat("Energy1", "Energy1", '~Electricity');
        $this->RegisterVariableFloat("Power1", "Power1", '~Watt.3680');

        $this->RegisterVariableFloat("Energy2", "Energy2", '~Electricity');
        $this->RegisterVariableFloat("Power2", "Power2", '~Watt.3680');
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
        }
        if (fnmatch('*/input/[01]', $Buffer->Topic)) {
            $input = $this->getChannelRelay($Buffer->Topic);
            $value = $Buffer->Payload;
            $this->SetValue("Input" . ($input+1), $value == 0 ? false : true);
        }
        if (fnmatch('*/relay/[01]', $Buffer->Topic)) {
            $relay = $this->getChannelRelay($Buffer->Topic);
            $value = $Buffer->Payload;
            $this->SetValue("State" . ($relay+1), $value == 'on' ? true : false);
        }
        if (fnmatch('*/relay/[01]/energy', $Buffer->Topic)) {
            $relay = $this->getChannelRelay($Buffer->Topic, -1);
            $value = $Buffer->Payload;
            $this->SetValue("Energy" . ($relay+1), inval($value) / 1000);
        }
        if (fnmatch('*/relay/[01]/power', $Buffer->Topic)) {
            $relay = $this->getChannelRelay($Buffer->Topic, -1);
            $value = $Buffer->Payload;
            $this->SetValue("Power" . ($relay+1), floatval($value));
        }
    }

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'State1':
                $this->SwitchMode(0, $Value);
                break;
            case 'State2':
                $this->SwitchMode(1, $Value);
                break;
        }
    }

    private function SwitchMode(int $relay, bool $Value)
    {
        $Topic = 'relay/' . $relay . '/command';
        if ($Value) {
            $Payload = 'on';
        } else {
            $Payload = 'off';
        }
        $this->SendRequest($Topic, $Payload);
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