API testing without horsing around.
===================================

[API.horse] is a hosted tool you can use to test APIs with. You can send test requests to endpoints to inspect their responses, or host endpoints yourself for testing your webhooks.

**CURRENTLY IN DEVELOPMENT** - this tool is currently in development, but I'll be saddling up very soon.

# Project overview

## Development goals

- [x] Full request tester and response logger
- [x] Secrets, so your shared links don't include keys & tokens
- [x] Instant sharable links to your session

## Future goals

- [ ] Webhooks - hosted endpoints for sending requests to
- [ ] Gateway - an API proxy for logging and debugging third party APIs
- [ ] Make the user interface look less Githubby (hire a designer)

## Development status

It's almost ready to ship! There are two things I need to do before I release the horse into the wild:

1) Tests - all functionality needs a Behat test written for it
2) WebEngine website - I think the project will likely attract a lot of attention from curious coders, and I want the new WebEngine website to be up and running so I don't waste the free marketing.

If people find the tool useful, I will continue on to build the [webhook](//api.horse/webhook) and [gateway](//api.horse/gateway) features. The project is just something I'm building in my spare time, so any [sponsorship](//api.horse/sponsor) it attracts will be a massive motivation.

## Why does it look like Github?

Currently the user interface is built to look and feel just like Github. This is intentional, because I personally have no ability at creating good designs, but I am quite comfortable at implementing a design once I can see it. I'm really familiar with Github's user interface, so for now I've just copied that. Once the product's in use, I'll hire a graphic designer to give it its own look and feel.

## What's with the name? 🐴

Naming stuff is hard, but I don't think a product's name really matters, so I went with a stupid name that is memorable, and is a short URL. That's all there is to it.

# Documentation

## Request editor

The request editor is the main feature of API.horse, and the page you land on when you go to the homepage.

To create a new request, give it a name, and set the endpoint you wish to request. That's all that's needed to make a simple GET request - press the Send request button and you'll see the response appear on the right.

Each request can be built up using the editor tabs. You can add query parameters as key/value pairs, and API.horse will add them to the URL for you when the request is sent. This keeps the endpoint easier to read, especially when you're testing APIs with lots of filters, pagination options, or feature flags.

Headers work in the same way. Add each header name and value on its own row, and the request editor will send them with the request. This is useful for things like `Accept`, `Content-Type`, custom API version headers, bearer tokens, and anything else your endpoint expects.

> [!NOTE]
> You can use keyboard navigation to quickly move around the form - the tab will work as expected, moving to the next field in the form, use Enter to submit the current form, or CTRL+Enter to quickly add a new item in a list, such as a query parameter or header. 

For requests that need a body, use the body editor. You can send JSON, form data, plain text, or whatever content your API accepts. Set the appropriate `Content-Type` header yourself when your endpoint requires one, then put the request payload in the body editor before sending the request.

All of the settings you pick in the request editor will keep the Raw HTTP message section up to date - this is more for debugging, but shows the raw message that will be sent over the wire.

The response viewer shows the response returned by the server after the request has been sent. You can inspect the status, headers, and response body, then adjust the request and send it again until you've got the behaviour you expect.

### Requests and collections

Requests live inside collections. A collection is a group of related requests, so you can keep all the calls for a project, service, bug report, or support conversation together.

You can create multiple named requests in the same collection. For example, one collection might contain requests called `Create customer`, `List customer orders`, `Update address`, and `Delete test customer`. Each request keeps its own method, endpoint, query parameters, headers, body, raw message, and response history, so you can switch between them without rebuilding the request each time.

You can also use different collections for different jobs. A personal sandbox, a production API investigation, a webhook debugging session, and a customer support example can all be kept separate. This makes shared links easier to understand, because the people opening them only see the requests that belong to that collection.

### Sharing and forking

Collections are designed to be shared. When you share a collection, other people can open the link and view the requests and responses in that collection. This is useful when you want to show someone exactly what you sent to an API and exactly what came back, without asking them to recreate the request from screenshots or pasted curl commands.

People who receive a shared collection can view it as-is, or fork it into their own API.horse account. Shared collections are read-only to users that didn't create the collection. Forking creates their own copy of the collection so they can edit requests, change endpoints, add headers, update bodies, send requests again, and save their own results without changing your original collection.

### Secrets

Secrets are for values that should be used in requests but never exposed through shared links. API keys, bearer tokens, session cookies, private IDs, and other sensitive values should be stored as secrets instead of being typed directly into URLs, headers, or request bodies.

When a request uses a secret, API.horse can send the real value with your request, but the shared collection does not reveal that value to other people. Anyone viewing the shared collection can see where a secret is used, but they cannot see the secret's contents.

Secrets are not copied when a collection is forked. The forked collection keeps the placeholder references, but the secret values themselves are left behind. This means someone can fork you collection and add their own API keys or tokens, without gaining access to yours.

# AI coding policy

API.horse is built using [PHP.GT/WebEngine], my own hand-coded framework that aims to combat the nonsense associated with modern web dev. I consider AI to be mainly nonsense. WebEngine is designed to promote and prioritise human thinking. The robots are taking over, but not in my repositories! I believe that we should reach for AI as a useful tool when it can positively augment our human creativity, but currently the tools all seem to prefer trying to do the creative bit for us - and all that achieves is us humans are pushed to one side, becoming nothing more than quality control - and ironically, there's often very little quality output - so it just takes all the joy out of programming for me. I'm not saying don't use AI, I'm just saying to consider what job it's taking off us before we ask it for help.

The horsey pictures you'll find in the error pages are hand-drawn by my wife Sarah. She also takes photographs at [Sarah's Lens](https://www.sarahslens.com).

[API.horse]: https://api.horse/
[PHP.GT/WebEngine]: https://www.php.gt/WebEngine/
