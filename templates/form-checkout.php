<?php if (!defined('ABSPATH')) { exit; } ?>
<form class="scb-checkout-form">
  <h3>Checkout</h3>

  <fieldset>
    <legend>Items</legend>
    <p>Replace with your cart. For testing, paste JSON per line item.</p>
    <div>
      <label>Line Item 1 (JSON: {"variant_id":123456789,"quantity":1})</label>
      <input type="text" name="line_items[0]" value='{"variant_id":123456789,"quantity":1}' />
    </div>
  </fieldset>

  <fieldset>
    <legend>Contact</legend>
    <label>Email <input type="email" name="email" required></label>
  </fieldset>

  <fieldset>
    <legend>Shipping Address</legend>
    <label>First name <input name="shipping_first_name"></label>
    <label>Last name <input name="shipping_last_name"></label>
    <label>Address 1 <input name="shipping_address1"></label>
    <label>Address 2 <input name="shipping_address2"></label>
    <label>City <input name="shipping_city"></label>
    <label>Province/State <input name="shipping_province"></label>
    <label>Country <input name="shipping_country" value="US"></label>
    <label>ZIP <input name="shipping_zip"></label>
    <label>Phone <input name="shipping_phone"></label>
  </fieldset>

  <fieldset>
    <legend>Billing Address</legend>
    <label>First name <input name="billing_first_name"></label>
    <label>Last name <input name="billing_last_name"></label>
    <label>Address 1 <input name="billing_address1"></label>
    <label>Address 2 <input name="billing_address2"></label>
    <label>City <input name="billing_city"></label>
    <label>Province/State <input name="billing_province"></label>
    <label>Country <input name="billing_country" value="US"></label>
    <label>ZIP <input name="billing_zip"></label>
    <label>Phone <input name="billing_phone"></label>
  </fieldset>

  <fieldset>
    <legend>Payment</legend>
    <label>Amount (e.g., 10.00) <input name="amount" value="10.00" required></label>
    <label>Card number <input name="cc_number" inputmode="numeric" autocomplete="cc-number" required></label>
    <label>Exp month <input name="cc_month" size="2" required></label>
    <label>Exp year <input name="cc_year" size="4" required></label>
    <label>CVV <input name="cc_cvv" size="4" required></label>
  </fieldset>

  <button type="submit">Pay Now</button>
  <p class="scb-status" style="margin-top:8px;color:#444;"></p>
  <pre class="scb-result" style="white-space:pre-wrap;background:#f6f8fa;padding:8px;border:1px solid #eee;"></pre>
</form>
