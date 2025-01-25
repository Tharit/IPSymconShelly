<?php

class ShellyPMMiniGen3 extends IPSModule
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
        $this->RegisterVariableFloat("PowerTotal", "PowerTotal", '~Electricity');
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

        $Buffer = json_decode($JSONString, true);

        $Payload = $Buffer['Payload'];
        if (array_key_exists('Topic', $Buffer)) {
            if (fnmatch('*/online', $Buffer['Topic'])) {
                $this->SetValue('Connected', $Payload == 'true');
            }
            if (fnmatch('*/events/rpc', $Buffer['Topic'])) {
                $Payload = json_decode($Payload, true);
                if (array_key_exists('params', $Payload)) {
                    if (array_key_exists('pm1:0', $Payload['params'])) {
                        if (array_key_exists('aenergy', $Payload['params']['pm1:0'])) {
                            $total = $Payload['params']['pm1:0']['aenergy']['total'] / 1000;

                            $this->SetValue("PowerTotal", $total);
                        }
                        if (array_key_exists('apower', $Payload['params']['pm1:0'])) {
                            $this->SetValue('Power', $Payload['params']['pm1:0']['apower']);
                        }
                    }
                }
            }
        }
    }

    public function RequestAction($Ident, $Value)
    {
    }
}