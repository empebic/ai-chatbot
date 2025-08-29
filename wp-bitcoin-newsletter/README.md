WP Bitcoin Newsletter (Pay-per-Subscribe)

A WordPress plugin to require Lightning payments before subscribing users to newsletter providers. Implements a `coinsnap_newsletter_form` CPT, shortcode, payment providers (Coinsnap/BTCPay stubs), newsletter providers (MailPoet/Mailchimp/Sendinblue/ConvertKit stubs), and an admin subscribers list.

Usage
- Configure under Subscribers > Settings
- Create a form under Subscribers > Newsletter Forms
- Place shortcode: `[coinsnap_newsletter_form id="123"]`

Development
- Activation creates table `{prefix}wpbn_subscribers`
- AJAX handles form submit; provider creates invoice; webhook/redirect triggers SyncService

Notes
- Payment and newsletter provider classes are stubbed. Replace TODOs with real API calls and security (webhook signing verification, etc.).

Extensibility (Hooks & Filters)

Filter defaults
```php
add_filter('wpbn_default_settings', function(array $defaults){
  $defaults['default_amount'] = 42; // change global default amount
  return $defaults;
});
```

Customize rendered form HTML
```php
add_filter('wpbn_form_html', function(string $html, int $formId){
  return '<div class="my-wrapper">' . $html . '</div>';
}, 10, 2);
```

Alter submission data before insert
```php
add_filter('wpbn_form_submission_data', function(array $data, int $formId){
  $data['custom1'] = strtoupper($data['custom1'] ?? '');
  return $data;
}, 10, 2);
```

Adjust payment parameters (amount/currency)
```php
add_filter('wpbn_payment_parameters', function(array $params, int $formId, array $data){
  if ($formId === 123) { $params['amount'] = 100; $params['currency'] = 'SATS'; }
  return $params;
}, 10, 3);
```

Coinsnap request & webhook hooks
```php
add_filter('wpbn_coinsnap_invoice_payload', function(array $payload, int $formId){
  $payload['metadata']['source'] = 'newsletter';
  return $payload;
}, 10, 2);

add_filter('wpbn_coinsnap_request_args', function(array $args){
  $args['timeout'] = 30; // increase timeout
  return $args;
});

add_action('wpbn_coinsnap_response', function($response, int $formId){
  // inspect API response
}, 10, 2);

add_action('wpbn_coinsnap_webhook_received', function(array $payload){
  // log Coinsnap webhook
});
```

BTCPay request & webhook hooks
```php
add_filter('wpbn_btcpay_request_args', function(array $args){
  $args['timeout'] = 30;
  return $args;
});

add_action('wpbn_btcpay_response', function($response, int $formId){
  // inspect API response
}, 10, 2);

add_action('wpbn_btcpay_webhook_received', function(array $payload){
  // log BTCPay webhook
});
```

Payment and sync lifecycle
```php
add_action('wpbn_before_subscriber_insert', function(array $data, int $formId){
  // validate/augment before DB insert
}, 10, 2);

add_action('wpbn_subscriber_created', function(int $subscriberId, int $formId, array $data){
  // enqueue background jobs, etc.
}, 10, 3);

add_action('wpbn_payment_marked_paid', function(string $invoiceId, array $subscriber){
  // audit logging
}, 10, 2);

add_action('wpbn_before_provider_sync', function(int $subscriberId, array $subscriber, array $options){
  // adjust options before provider upsert
}, 10, 3);

add_action('wpbn_after_provider_sync', function(int $subscriberId, bool $success){
  // follow-up actions
}, 10, 2);
```

Email customization
```php
add_filter('wpbn_welcome_email_template', function(string $html, int $formId, string $email){
  return str_replace('Thank you', 'Welcome aboard', $html);
}, 10, 3);

add_filter('wpbn_welcome_email_subject', function(string $subject, int $formId, string $email){
  return 'Thanks for subscribing!';
}, 10, 3);

add_action('wpbn_welcome_email_sent', function(string $email, int $formId){
  // notify CRM
}, 10, 2);

add_action('wpbn_admin_notification_sent', function(string $email, int $formId){
  // slack notify
}, 10, 2);
```

