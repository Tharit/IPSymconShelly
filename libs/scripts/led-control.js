
const values = {
    'led': {
      get: function(cb) {
        Shelly.call("PLUGS_UI.GetConfig", null, function(result, error_code, error_message, userdata) {
          const config = result;
          cb(config.leds.mode != 'off');
        });
      },
      set: function(value, cb) {
        Shelly.call("PLUGS_UI.GetConfig", null, function(result, error_code, error_message, userdata) {
          const config = result;
          config.leds.mode = value == 'true' ? 'switch': 'off';
          Shelly.call("PLUGS_UI.SetConfig", {config:config});
          cb(null, value == 'true');
        });
      }
    }
    };
    
    //------------------
    
    const topic = Shelly.getDeviceInfo().id;
    function onConnect() {
      for(let key in values) {
        values[key].get(function(value) {
          MQTT.publish(topic + '/actors/' + key, ''+value);
        });
      }
    }
    
    if(MQTT.isConnected()) {
      onConnect();
    }
    MQTT.setConnectHandler(onConnect);
    
    for(let key in values) {
      MQTT.subscribe(topic + '/actors/' + key + '/cmd', function(topic, message, userdata) {
        if(!message) return;
        values[key].set(message, function(err, value) {
          if(!err) {
            MQTT.publish(topic + '/actors/' + key, ''+value);
          }
        });
      });
    }