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

