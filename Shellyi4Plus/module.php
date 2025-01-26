<?php

class Shellyi4Plus extends IPSModule
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
        $this->RegisterPropertyString('Topic', '');

        // variables
        $this->RegisterVariableBoolean("Connected", "Connected");
        $this->RegisterVariableBoolean("Input1", "Input1");
        $this->RegisterVariableBoolean("Input2", "Input2");
        $this->RegisterVariableBoolean("Input3", "Input3");
        $this->RegisterVariableBoolean("Input4", "Input4");
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
            } else if (fnmatch('*/events/rpc', $Buffer['Topic'])) {
                if (array_key_exists('params', $Payload)) {
                    
                    for ($i = 0; $i <= 3; $i++) {
                        $inputIndex = 'input:' . $i;
                        if (array_key_exists($inputIndex, $Payload['params'])) {
                            $input = $Payload['params'][$inputIndex];
                            if (array_key_exists('state', $input)) {
                                $this->SetValue('Input' . ($i + 1), $input['state']);
                            }
                        }
                    }
                }
            }
        }
    }
}