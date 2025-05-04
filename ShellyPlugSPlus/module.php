<?php

class ShellyPlugSPlus extends IPSModule
{
    protected function getChannelRelay(string $topic)
    {
        $ShellyTopic = explode('/', $topic);
        $LastKey = count($ShellyTopic) - 1;
        $relay = $ShellyTopic[$LastKey];
        return $relay;
    }

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        // properties
        $this->RegisterPropertyBoolean('RetainActuatorValues', false);
        $this->RegisterPropertyString('Topic', '');

        // variables
        $this->RegisterVariableBoolean("Connected", "Connected");
        $this->RegisterVariableBoolean("State1", "State1");
        $this->RegisterVariableFloat("Energy", "Energy", '~Electricity');
        $this->RegisterVariableFloat("Power", "Power", '~Watt.3680');

        $this->EnableAction("State1");
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

        $Payload = $Buffer['Payload'];
        if (array_key_exists('Topic', $Buffer)) {
            if (fnmatch('*/online', $Buffer['Topic'])) {
                $this->SetValue('Connected', $Payload == 'true');
            } else if (fnmatch('*/events/rpc', $Buffer['Topic'])) {
                $Payload = json_decode($Payload, true);
                if (array_key_exists('params', $Payload)) {
                    if (array_key_exists('switch:0', $Payload['params'])) {
                        $input = $Payload['params']['switch:0'];
                        if (array_key_exists('output', $input)) {
                            $this->SetValue('State1', $input['output']);
                        }
                        if (array_key_exists('apower', $input)) {
                            $this->SetValue('Power', $input['apower']);
                        }
                        if (array_key_exists('aenergy', $input)) {
                            $total = $input['aenergy']['total'] / 1000;

                            $this->SetValue("Energy", $total);
                        }
                    }
                }
            } else if (fnmatch('*/actors/*', $Buffer['Topic'])) {
                $parts = explode('/', $Buffer['Topic']);
                $this->UpdateValue($parts[count($parts)-1], $Payload, false);
            } else if (fnmatch('*/sensors/*', $Buffer['Topic'])) {
                $parts = explode('/', $Buffer['Topic']);
                $this->UpdateValue($parts[count($parts)-1], $Payload, true);
            }
        }
    }

    public function RequestAction($Ident, $Value)
    {
        $Server['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
        $Server['PacketType'] = 3;
        $Server['QualityOfService'] = 0;

        if($Ident == 'State1') {
            $Server['Retain'] = false;
            
            $Payload['id'] = 1;
            $Payload['src'] = 'user_1';
            $Payload['method'] = 'Switch.Set';
            $Payload['params'] = ['id' => 0, 'on' => $Value];

            $Server['Topic'] = $this->ReadPropertyString('Topic') . '/rpc';
            $Server['Payload'] = json_encode($Payload);
        } else {
            $Server['Retain'] = $this->ReadPropertyBoolean('RetainActuatorValues');
            $Server['Topic'] = $this->ReadPropertyString('Topic') . '/actors/' . $Ident . '/cmd';
            $Server['Payload'] = json_encode($Value);
        }

        $ServerJSON = json_encode($Server, JSON_UNESCAPED_SLASHES);
        $resultServer = $this->SendDataToParent($ServerJSON);
    }

    private function UpdateValue($key, $value, $readonly = true) {
        if($value == 'true' || $value == 'false') {
            $value = $value == 'true' ? true : false;
        } else if(floatval($value) == $value) {
            $value = floatval($value);
        }
        $type = gettype($value);
        if($type === 'integer' || $type === 'double') {
            $this->RegisterVariableFloat($key, $key);
        } else if($type === 'boolean') {
            $this->RegisterVariableBoolean($key, $key, $readonly ? '': '~Switch');
        } else {
            $this->RegisterVariableString($key, $key);
        }
        if(!$readonly) {
            $this->EnableAction($key);
        }
        $this->SetValue($key, $value);
    }
}