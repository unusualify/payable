# Revolut Payment Integration Guide

This guide explains how to implement Revolut payments using the unusualify/payable package.

## Prerequisites

1. Revolut Merchant Account
2. API credentials from Revolut dashboard
3. `unusualify/payable` package installed
4. `@revolut/checkout` npm package for frontend

## Installation

1. Install the npm package:
```bash
npm install @revolut/checkout
```

## Backend Implementation

### 1. Controller Method

Create a controller method to handle the payment initialization:

```php
public function testRevolut()
{
    $payable = new Payable('revolut');

    $params = [
        'amount' => 10.00,        // Amount in major units (e.g., 10.00 EUR)
        'currency' => 'EUR',      // Currency code (e.g., EUR, USD, GBP)
        'order_id' => 'ORDER-'.uniqid(),  // Unique order ID
        'user_email' => 'customer@example.com',
        'user_id' => 1,
        'installment' => 1,       // Number of installments (1 for one-time payment)
        'user_ip' => request()->ip(),
        'description' => 'Order description',
    ];

    $result = $payable->pay($params);
   
    if (($result['type'] ?? null) === 'widget') {
        return view('checkout.revolut', [
            'token' => $result['token'] ?? '',
            'env' => $result['env'] ?? 'sandbox',
        ]);
    }

    abort(400, $result['message'] ?? 'Unable to initialize Revolut order');
}
```

## Frontend Implementation

### 1. Vue Component Implementation

Create a new Vue component at `resources/js/Components/RevolutCheckout.vue`:

```vue
<template>
  <div class="container">
    <h2>Revolut Checkout</h2>

    <input type="hidden" name="token" :value="token">
    <input type="hidden" name="env" :value="env">
    <label for="cardholder-name" class="note">Cardholder name</label>
    <input name="cardholder-name" type="text" value="" placeholder="John Doe">
    <div id="card-field"></div>
    <div style="margin-top:12px">
      <button id="button-submit">Pay</button>
    </div>
  </div>
  
  <!-- Loading Overlay -->
  <div id="revolut-loader" style="position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(255,255,255,0.7);z-index:9999;">
    <div style="display:flex;flex-direction:column;align-items:center;font-family:system-ui, -apple-system, Segoe UI, Roboto;">
      <div style="width:32px;height:32px;border:3px solid #ccc;border-top-color:#111;border-radius:50%;animation:spin .9s linear infinite"></div>
      <div style="margin-top:10px;color:#111">Processing...</div>
    </div>
  </div>
</template>

<script>
import RevolutCheckout from '@revolut/checkout';

export default {
  props: {
    token: String,
    env: String
  },
  mounted() {
    const loader = document.getElementById('revolut-loader');
    const showLoader = () => { loader.style.display = 'flex'; };
    const hideLoader = () => { loader.style.display = 'none'; };

    const setButtonBusy = (btn, busy) => {
      if (!btn) return;
      if (busy) {
        btn.disabled = true;
        if (!btn.dataset.originalText) btn.dataset.originalText = btn.textContent;
        btn.textContent = 'Processing...';
        btn.setAttribute('aria-busy', 'true');
      } else {
        btn.disabled = false;
        btn.textContent = btn.dataset.originalText || 'Pay';
        btn.removeAttribute('aria-busy');
      }
    };

    try {
      const instance = RevolutCheckout(this.token, this.env);
      const cardTarget = document.getElementById('card-field');
      
      if (cardTarget) {
        const submitBtn = document.getElementById('button-submit');

        const cardField = instance.createCardField({
          target: cardTarget,
          onSuccess() {
            hideLoader();
            setButtonBusy(submitBtn, false);
            // Handle successful payment (e.g., redirect to success page)
            this.$emit('payment-success');
          },
          onError(error) {
            hideLoader();
            setButtonBusy(submitBtn, false);
            this.$emit('payment-error', error);
          },
        });

        if (submitBtn) {
          submitBtn.addEventListener('click', () => {
            const nameInput = document.querySelector('input[name="cardholder-name"]');
            const cardholderName = (nameInput && nameInput.value) ? nameInput.value : '';
            const meta = { name: cardholderName, cardholderName };
            
            showLoader();
            setButtonBusy(submitBtn, true);
            cardField.submit(meta);
          });
        }
      }
    } catch (e) {
      console.error('Failed to initialize Revolut', e);
      this.$emit('error', 'Failed to initialize payment: ' + (e?.message || e));
    }
  }
};
</script>

<style scoped>
@keyframes spin { to { transform: rotate(360deg) } }
</style>
```

#### Usage in your Vue application:

1. First, import and register the component:

```javascript
// In your app.js or main.js
import RevolutCheckout from './Components/RevolutCheckout.vue';

// If using Vue 3
app.component('RevolutCheckout', RevolutCheckout);

// If using Vue 2
// Vue.component('RevolutCheckout', RevolutCheckout);
```

2. Use the component in your view:

```vue
<template>
  <div>
    <h1>Complete Your Payment</h1>
    <RevolutCheckout 
      :token="revolutToken"
      :env="environment"
      @payment-success="handleSuccess"
      @payment-error="handleError"
      @error="handleGeneralError"
    />
  </div>
</template>

<script>
export default {
  data() {
    return {
      revolutToken: 'your_revolut_token', // Should come from your backend
      environment: 'sandbox' // or 'production'
    };
  },
  methods: {
    handleSuccess() {
      // Handle successful payment
      console.log('Payment successful!');
      // Redirect or show success message
    },
    handleError(error) {
      // Handle payment error
      console.error('Payment error:', error);
      // Show error message to user
    },
    handleGeneralError(message) {
      // Handle general errors
      console.error('Error:', message);
    }
  }
};
</script>
```

### 2. Blade Template (Alternative to Vue) (`resources/views/checkout/revolut.blade.php`)

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revolut Checkout</title>
    <style>
        @keyframes spin { to { transform: rotate(360deg) } }
    </style>
</head>
<body>
    <div class="container">
        <h2>Revolut Checkout</h2>

        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="env" value="{{ $env }}">
        <label for="cardholder-name" class="note">Cardholder name</label>
        <input name="cardholder-name" type="text" value="" placeholder="John Doe">
        <div id="card-field"></div>
        <div style="margin-top:12px">
            <button id="button-submit">Pay</button>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div id="revolut-loader" style="position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(255,255,255,0.7);z-index:9999;">
        <div style="display:flex;flex-direction:column;align-items:center;font-family:system-ui, -apple-system, Segoe UI, Roboto;">
            <div style="width:32px;height:32px;border:3px solid #ccc;border-top-color:#111;border-radius:50%;animation:spin .9s linear infinite"></div>
            <div style="margin-top:10px;color:#111">Processing...</div>
        </div>
    </div>

    @vite(['resources/js/revolut.js'])
</body>
</html>
```

### 2. JavaScript (`resources/js/revolut.js`)

```javascript
import RevolutCheckout from '@revolut/checkout';

document.addEventListener('DOMContentLoaded', async () => {
  const token = document.querySelector('input[name="token"]').value;
  const env = document.querySelector('input[name="env"]').value;
  
  if (!token) {
    console.error('Revolut token missing.');
    return;
  }

  const loader = document.getElementById('revolut-loader');
  const showLoader = () => { loader.style.display = 'flex'; };
  const hideLoader = () => { loader.style.display = 'none'; };

  const setButtonBusy = (btn, busy) => {
    if (!btn) return;
    if (busy) {
      btn.disabled = true;
      if (!btn.dataset.originalText) btn.dataset.originalText = btn.textContent;
      btn.textContent = 'Processing...';
      btn.setAttribute('aria-busy', 'true');
    } else {
      btn.disabled = false;
      btn.textContent = btn.dataset.originalText || 'Pay';
      btn.removeAttribute('aria-busy');
    }
  };

  try {
    const instance = await RevolutCheckout(token, env);
    const cardTarget = document.getElementById('card-field');
    
    if (cardTarget) {
      const submitBtn = document.getElementById('button-submit');

      const cardField = instance.createCardField({
        target: cardTarget,
        onSuccess() {
          hideLoader();
          setButtonBusy(submitBtn, false);
          // Handle successful payment (e.g., redirect to success page)
          window.location.href = '/payment/success';
        },
        onError(error) {
          hideLoader();
          setButtonBusy(submitBtn, false);
          alert(`Payment failed: ${error.message || 'Unknown error'}`);
        },
      });

      if (submitBtn) {
        submitBtn.addEventListener('click', () => {
          const nameInput = document.querySelector('input[name="cardholder-name"]');
          const cardholderName = (nameInput && nameInput.value) ? nameInput.value : '';
          const meta = { name: cardholderName, cardholderName };
          
          showLoader();
          setButtonBusy(submitBtn, true);
          cardField.submit(meta);
        });
      }
    }
  } catch (e) {
    console.error('Failed to initialize Revolut', e);
    alert('Failed to initialize payment: ' + (e?.message || e));
  }
});
```

## Configuration

Make sure to set up your Revolut API credentials in your `.env` file:

```env
REVOLUT_ENV=sandbox  # or 'production' for live
REVOLUT_PUBLIC_KEY=your_public_key
REVOLUT_SECRET_KEY=your_secret_key
```
