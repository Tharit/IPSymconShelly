<?php

class Shelly1PMMiniGen3 extends IPSModule
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
        $this->RegisterVariableBoolean("State1", "State1");
        $this->RegisterVariableBoolean("Input1", "Input1");
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
                    if (array_key_exists('input:0', $Payload['params'])) {
                        $input = $Payload['params']['input:0'];
                        if (array_key_exists('state', $input)) {
                            $this->SetValue('Input1', $input['state']);
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