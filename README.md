API testing without horsing around.
===================================

[API.horse] is a hosted tool you can use to test APIs with. You can send test requests to endpoints and see the responses, or host endpoints yourself for testing your webhooks.

**CURRENTLY IN DEVELOPMENT** - this tool is currently in development, but I'll be saddling up within a few days.

## Development goals

- [x] Full request tester and response logger
- [x] Secrets, so your shared links don't include keys & tokens
- [x] Instant sharable links to your session

### Future goals

- [ ] Webhooks - hosted endpoints for sending requests to
- [ ] Gateway - an API proxy for logging and debugging third party APIs
- [ ] Make the user interface look less Githubby (hire a designer)

## Development status

It's almost ready to ship! There are two things I need to do before I release the horse into the wild:

1) Tests - all functionality needs a Behat test written for it
2) WebEngine website - I think the project will likely attract a lot of attention from curious coders, and I want the new WebEngine website to be up and running so I don't waste the free marketing.

### Why does it look like Github?

Currently the user interface is built to look and feel just like Github. This is intentional, because I personally have no ability at creating good designs, but I am quite comfortable at implementing a design once I can see it. I'm really familiar with Github's user interface, so for now I've just copied that. Once the product's in use, I'll hire a graphic designer to give it its own look and feel.

## What's with the name? 🐴

Naming stuff is hard, but I don't think a product's name really matters, so I went with a stupid name that is memorable, and is a short URL. That's all there is to it.

## AI coding policy

API.horse is built using [PHP.GT/WebEngine], my own hand-coded framework that aims to combat the nonsense associated with modern web dev. I consider AI to be mainly nonsense, and WebEngine is designed to promote and prioritise human thinking. The robots are taking over, but not in my repositories! I believe that we should reach for AI as a useful tool when it can positively augment our human creativity, but currently the tools all seem to prefer trying to do the creative bit for us - and all that achieves is that us humans are pushed to one side, becoming nothing more than quality control - and there's often very little quality output, so it just takes all the joy out of programming for me. I'm not saying don't use AI, I'm just saying to consider what job it's taking off you before you ask it for help.

The horsey pictures you'll find in the error pages are hand-drawn by my wife Sarah. She also takes photographs at [Sarah's Lens](https://www.sarahslens.com).

[API.horse]: https://api.horse
[PHP.GT/WebEngine]: https://www.php.gt/WebEngine
