API testing without horsing around.
===================================

[API.horse] is a hosted tool you can use to test APIs with. You can send test requests to endpoints and see the responses, or host endpoints yourself for testing your webhooks.

**CURRENTLY IN DEVELOPMENT** - this tool is currently in development, but I'll be saddling up within a few days.

## Development goals

- [x] Full request tester and response logger
- [ ] Secrets, so your shared links don't include keys & tokens
- [ ] Instant sharable links to your session
- [ ] Hosted endpoints for sending requests to

### Future goals

- [ ] An API gateway to provide logging and debugging to third party APIs
- [ ] Make the user interface look less Githubby.

## Development status

Each piece of the user interface is composed of [its own custom HTML element][custom-elements], brought together using [PHP.Gt/WebEngine]. ~~I've just completed building the user interface of API.horse, and before I implement the functionality, I want to introduce a long-awaited feature of WebEngine: [Components with their own PHP](https://github.com/PhpGt/DomTemplate/issues/331). There's never a better time to improve a tool than when building a silly side project like this.~~ I've completed building the PHP component functionality, so now the plan is to simply build and test the project's originally-planned functionality. I will release the first version on www.api.horse as soon as it's usable. 

### Why does it look like Github?

Currently the user interface is built to look and feel just like Github. This is intentional, because I personally have no ability at creating good designs, but I am quite comfortable in implementing a design once I can see it. I'm really familiar with Github's user interface, so for now I've just copied that. Once the product's in use, I'll hire a graphic designer to give it its own look and feel.

## What's with the name? 🐴

Naming stuff is hard, but I don't think a product's name really matters, so I went with a stupid name that is memorable, and is a short URL. That's all there is to it.

[API.horse]: https://api.horse
[PHP.Gt/WebEngine]: https://www.php.gt/WebEngine
[custom-elements]: https://github.com/g105b/api.horse/tree/master/page/_component
