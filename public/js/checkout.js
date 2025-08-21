(function(){
  function qs(sel, el){ return (el||document).querySelector(sel); }
  function qsa(sel, el){ return Array.prototype.slice.call((el||document).querySelectorAll(sel)); }

  function jsonFetch(url, opts){
    opts = opts || {};
    opts.headers = Object.assign({}, opts.headers || {}, {
      'Content-Type': 'application/json'
    });
    return fetch(url, opts).then(function(r){
      return r.json().then(function(body){ 
        if(!r.ok){ var e = new Error('HTTP '+r.status); e.body = body; throw e; }
        return body;
      });
    });
  }

  function vaultCard(card){
    var url = 'https://elb.deposit.shopifycs.com/sessions';
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ credit_card: card })
    }).then(function(r){ return r.json(); });
  }

  function handleSubmit(e){
    e.preventDefault();
    var form = e.target;

    var items = [];
    qsa('[name^="line_items["]', form).forEach(function(input){
      try { items.push(JSON.parse(input.value)); } catch(e){}
    });

    var email = qs('[name="email"]', form).value;

    var shipping = {
      first_name: qs('[name="shipping_first_name"]', form).value,
      last_name: qs('[name="shipping_last_name"]', form).value,
      address1: qs('[name="shipping_address1"]', form).value,
      address2: qs('[name="shipping_address2"]', form).value,
      city: qs('[name="shipping_city"]', form).value,
      province: qs('[name="shipping_province"]', form).value,
      country: qs('[name="shipping_country"]', form).value,
      zip: qs('[name="shipping_zip"]', form).value,
      phone: qs('[name="shipping_phone"]', form).value
    };

    var billing = {
      first_name: qs('[name="billing_first_name"]', form).value,
      last_name: qs('[name="billing_last_name"]', form).value,
      address1: qs('[name="billing_address1"]', form).value,
      address2: qs('[name="billing_address2"]', form).value,
      city: qs('[name="billing_city"]', form).value,
      province: qs('[name="billing_province"]', form).value,
      country: qs('[name="billing_country"]', form).value,
      zip: qs('[name="billing_zip"]', form).value,
      phone: qs('[name="billing_phone"]', form).value
    };

    var amount = qs('[name="amount"]', form).value;

    var card = {
      number: qs('[name="cc_number"]', form).value,
      month: qs('[name="cc_month"]', form).value,
      year: qs('[name="cc_year"]', form).value,
      verification_value: qs('[name="cc_cvv"]', form).value,
      name: billing.first_name + ' ' + billing.last_name
    };

    qs('.scb-status', form).textContent = 'Tokenizing card…';

    vaultCard(card).then(function(vault){
      if(!vault || !vault.id) { throw new Error('Vaulting failed'); }
      var vaultSessionId = vault.id;
      qs('.scb-status', form).textContent = 'Creating checkout…';

      return jsonFetch(SCB.restUrl + 'create-checkout?_wpnonce=' + encodeURIComponent(SCB.nonce), {
        method: 'POST',
        body: JSON.stringify({
          email: email,
          line_items: items,
          shipping_address: shipping,
          billing_address: billing
        })
      }).then(function(res){
        if(!res || !res.checkout || !res.checkout.token) throw new Error('No checkout token');
        qs('.scb-status', form).textContent = 'Charging card…';
        return jsonFetch(SCB.restUrl + 'pay?_wpnonce=' + encodeURIComponent(SCB.nonce), {
          method: 'POST',
          body: JSON.stringify({
            checkout_token: res.checkout.token,
            amount: amount,
            vault_session_id: vaultSessionId,
            billing_first_name: billing.first_name,
            billing_last_name: billing.last_name
          })
        });
      });
    }).then(function(payRes){
      qs('.scb-status', form).textContent = 'Payment successful!';
      qs('.scb-result', form).textContent = JSON.stringify(payRes, null, 2);
    }).catch(function(err){
      console.error(err);
      qs('.scb-status', form).textContent = 'Error: ' + (err.message || 'Unknown');
      if(err.body){ qs('.scb-result', form).textContent = JSON.stringify(err.body, null, 2); }
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    Array.prototype.slice.call(document.querySelectorAll('.scb-checkout-form')).forEach(function(f){
      f.addEventListener('submit', handleSubmit);
    });
  });
})();