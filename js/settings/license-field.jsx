'use strict';

import { Button } from '@wordpress/components';

export default class LicenseField extends React.Component {
  constructor(props) {
    super(props);
    this.mask = 'XXXX-XXXX-XXXX-XXXX';
    this.state = {
        validating: false,
        removing: false,
        value: this.applyMask(this.props.license),
        valid: this.props.status == 'valid',
        status: this.props.status,
        expires: this.props.expires
    };
    this.onChange = this.onChange.bind(this);
    this.onClickValidate = this.onClickValidate.bind(this);
    this.onClickDissociate = this.onClickDissociate.bind(this);
    this.validateOnEnter = this.validateOnEnter.bind(this);
  }

  onChange(event) {
    this.setState({
        value: this.applyMask(event.target.value.replace(/[^A-z0-9]/g, '').toUpperCase())
    })
  }

  validateOnEnter(event){
    if (event.key === 'Enter') {
      this.onClickValidate(event);
    }
  }

  onClickValidate(event) {
    event.preventDefault();
    this.setState({validating:true});
    var that = this;
    jQuery.post({
				url: 'http://local.test/wp-json/eventorg/v1/license',
				contentType: 'application/json',
				//headers: {"X-WP-Nonce": eventorganiserpro.auth_nonce},
				dataType: 'json',
				contentType: 'application/json',
        data: JSON.stringify({
          'key': this.state.value,
          'id': this.props.id,
          'item': this.props.slug 
        }),
        success: function(resp) {
          console.log(resp)
          that.setState({
            validating: false,
            valid: resp.status === 'valid',
            status: resp.status,
            expires: resp.expires
        });
    
        }
    });
  }


  onClickDissociate(event) {
    event.preventDefault();
    this.setState({removing:true});
    var that = this;
    jQuery.post({
				url: 'http://local.test/wp-json/eventorg/v1/remove-license',
				contentType: 'application/json',
				//headers: {"X-WP-Nonce": eventorganiserpro.auth_nonce},
				dataType: 'json',
				contentType: 'application/json',
        data: JSON.stringify({
          'key': this.state.value,
          'id': this.props.id,
          'item': this.props.slug 
        }),
        success: function(resp) {
          that.setState({
            removing: false,
            value: '',
            valid: false,
            status: null,
            expires: null
        });
    
        }
    });
  }

  applyMask(string) {
    var formattedString = "";
    var numberPos = 0;
    for(var j = 0; j < this.mask.length; j++) {
      var currentMaskChar = this.mask[j];
      if(currentMaskChar == "X") {
        var char = string.charAt(numberPos);
        if(!char) {
          break;
        }
        formattedString += char;
        numberPos++;
      } else {
        formattedString += currentMaskChar;
      }
    }
    return formattedString;
  }

  daysUntilExpires() {
    var expires = this.state.expires ? new Date(this.state.expires) : null;
    if(!expires) {
      return false;
    }
    var diffTime = (expires - new Date());
    console.log(diffTime);
    var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
    console.log(diffDays);
    return diffDays;
  }

  expiresSoon() {
    var expires = this.state.expires ? new Date(this.state.expires) : null;
    var daysUntilExpires = this.daysUntilExpires();
    return (expires - new Date()) >= 0 && daysUntilExpires < 21;
  }

  expired() {
    var expires = this.state.expires ? new Date(this.state.expires) : null;
    return (expires - new Date()) < 0;
  }

  status() {
    var status = this.state.status;
    if (status == 'valid' && this.expiresSoon()) {
      status = 'expires-soon';
    } else if (status == 'valid' && this.expired()) {
      status = 'license-expired';
    }
    return status;
  }

  renderMessage() {
    switch(this.status()) {
      case 'no-key-given':
        return 'Please enter a key';
      case 'invalid-license-format':
        return 'Your license key should be a 16 characters long and contain only numbers and letters';
      case 'invalid-response':
        return 'There was an error in authenticating the license key status';
      case 'key-not-found':
      case 'license-not-found':
        return 'Invalid license key';
      case 'license-suspended':
        return 'License key is no longer valid';
      case 'expires-soon':
        var daysUntilExpires = this.daysUntilExpires();
        let text = daysUntilExpires > 1 ? daysUntilExpires + ' days' : daysUntilExpires + ' day';  
        return (<span>Your license key will expire in {text}. To continue to recieve updates and support you will need to <a href="https://wp-event-organiser.com/account/">renew your license</a></span>);
      case 'license-expired':
        return <span>Your license key has expired. To continue to recieve updates and support you will need to <a href="https://wp-event-organiser.com/extensions/">purchase a new license</a></span>;
      case 'incorrect-product':
        return <span>License key is not valid for this product. Check that you are using the correct license key for this extension.</span>;
      case 'site-limit-reached':
        return <span>Your license key has reached its site limit. You can view the sites using the key, and remove it from sites that no longer require it by <a href="https://wp-event-organiser.com/account">logging into your account</a>.</span>;
    }
  }

  render() {
      
    return (
        <div>
            <input type="text" 
              className={this.status() === 'valid' ? 'valid' : (this.status() === 'expires-soon' ? 'expires-soon' : 'invalid')} 
              style={styles} placeholder={this.mask} 
              onChange={this.onChange} 
              value={this.state.value} 
              onKeyDown={this.validateOnEnter}/>
            <Button onClick={this.onClickValidate} 
              isPrimary={true} 
              isBusy={this.state.validating}
              disabled={this.state.validating}>
              {this.state.validating ? "Validating..." : "Apply"}</Button>
            
            <Button onClick={this.onClickDissociate} 
              isTertiary={true} 
              //isBusy={this.state.removing}
              disabled={this.state.removing}>
              {this.state.removing ? "Remove key..." : "Dissociate"}</Button>

            <p className="description">{this.renderMessage()}</p>
            {this.state.expires}
            {this.daysUntilExpires()}
            {this.state.status}
            {this.status()}
        </div>
        
    );
  }
}
var styles = {
    fontFamily: 'monospace',
    lineHeight: '1.5em',
    marginRight: '15px',
    backgroundRepeat: 'no-repeat',
    backgroundPositionX: '100%',
    paddingRight: '25px',
  };
