# Asyntai AI Chatbot For Magento

Create and launch AI assistant/chatbot for your website in minutes. It talks to your visitors, helps, explains, never misses a chat and can increase conversion rates! All while knowing your website, customized just for you. Your website can now talk.

This extension embeds the Asyntai chatbot on your site and provides a simple admin interface to connect your site to Asyntai.

## Why choose Asyntai?

• **Increase conversions**: Instant, human like replies keep shoppers engaged and buying.

• **Never miss a chat**: The AI replies day and night, even when your team is offline.

• **Knows your website**: Customized just for you; it follows your instructions.

• **Works in all languages**: Automatically detects and answers in the visitor's language.

• **Fast responses (1–3s)**: Keeps customers from bouncing.

• **Knows your catalogue**: Quotes the price and the stock your store holds right now.


## Installation

1. Copy the extension folder to `app/code/Asyntai/Chatbot/` or install with Composer `composer require asyntai/magento-ai-chatbot`
2. Enable the module:
   ```bash
   php bin/magento module:enable Asyntai_Chatbot
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento cache:flush
   ```
3. Navigate to Admin Panel → Asyntai AI Chatbot

Note: Alternatively, if you don't want to use an extension, you can also get a JavaScript snippet at  [asyntai.com/dashboard](https://asyntai.com/dashboard) and manually insert it into your web pages.

## Configuration

After installation:

1. Go to Admin Panel → Asyntai AI Chatbot 
2. Click "Get started" to connect your Asyntai account
3. Sign in or create a free account
4. The extension will automatically save your connection
5. The chatbot is now live on your site!
6. Set up your chatbot, review chat logs and more at: [asyntai.com/dashboard](https://asyntai.com/dashboard)

Don't have an account yet? Create a free Asyntai account at [asyntai.com/auth](https://asyntai.com/auth)

## Live prices and stock

When you connect, the extension can also create a read-only integration in
your own store, so the assistant answers with the price and the stock your
catalogue holds at that moment rather than what your pages said when they were
last read.

- The integration is called **Asyntai AI Chatbot** and appears under
  **System → Extensions → Integrations**. You can revoke it there at any
  time, and Reset in the extension removes it as well.
- It reads products, stock, CMS pages and your store's base settings. Order
  lookup is added only if you tick the box when you connect.
- Magento refuses an integration token until **Stores → Configuration →
  Services → OAuth → Consumer Settings → "Allow OAuth Access Tokens to be
  used as standalone Bearer tokens"** is set to Yes. The extension tells you
  when it is off. The chatbot itself works either way.

## Managing Your Chatbot

Once connected, you can manage your chatbot settings, review chat logs, and customize AI responses at:
[asyntai.com/dashboard](https://asyntai.com/dashboard)



## Requirements

- Magento 2.4.0 or higher
- PHP 8.1 or higher
- Backend admin access for configuration
- If your Magento version is not compatible, you can still use Asyntai by getting a JavaScript snippet from [asyntai.com/dashboard](https://asyntai.com/dashboard)


## Changelog

### 1.1.1

- All admin and storefront template output is escaped.

### 1.1.0

- The assistant can read live prices and stock from your catalogue through a
  read-only integration you can revoke at any time.
- Order lookup is available as an option when you connect.
- The widget script now loads from `widget.asyntai.com`. Stores connected
  before this release move over on update, with nothing to reconnect.
- The connect window no longer loads a script from outside your store. The
  extension asks Asyntai from PHP instead.

### 1.0.0

- First release.

## Have a question?
Email us at hello@asyntai.com or try our chatbot directly at [asyntai.com/](https://asyntai.com/)



![Asyntai AI chatbot 1](https://asyntai.com/static/images/ai-chatbot-for-websites-1.png)

![Asyntai AI chatbot 2](https://asyntai.com/static/images/ai-chatbot-for-websites-2.png)

![Asyntai AI chatbot 3](https://asyntai.com/static/images/ai-chatbot-for-websites-3.png)

![Asyntai AI chatbot 4](https://asyntai.com/static/images/ai-chatbot-for-websites-4.png)

![Asyntai AI chatbot 5](https://asyntai.com/static/images/ai-chatbot-for-websites-5.png)


