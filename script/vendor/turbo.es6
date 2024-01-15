/*
On the page, there is a "turbo container" which has the `data-turbo` attribute. This element contains all forms and
links that will be hooked, and is the element that will update with the new document.

Within the turbo container:
1) All links are hooked so that a click will update the outermost data-turbo-page element's HTML.
2) All forms are hooked so that a change will update the other data-turbo elements on the page.

Form logic:
1) Turbo config is set in the `data-turbo` attribute of the contained forms as a space-separated list of configs.
2) As long as there is not a "nohide" config, the first button in the form will be hidden, and this button will be
clicked when a change event is hooked to mimic the behaviour of pressing the enter key.
3) The form is submitted using a background fetch, and the new document is used to replace the current document. All
turbo containers are replaced.
 */
const DEBUG = true;

const parser = new DOMParser();
let startingTurboContainers = document.querySelectorAll("[data-turbo]");
startingTurboContainers.forEach(handleSpecialName);
startingTurboContainers.forEach(setup);

function setup(turboContainer) {
	let turboName = turboContainer.dataset["turbo"];
	let formList = [...turboContainer.querySelectorAll("form:not([data-turbo-ignore])")];
	let linkList = [...turboContainer.querySelectorAll("a")];

	if(turboContainer instanceof HTMLFormElement) {
		formList.push(turboContainer);
	}

	formList.forEach(setupForm);
}

/** @param {HTMLFormElement} form */
function setupForm(form) {
	DEBUG && console.debug("Setting up form:", form);
	const turboContainer = form.closest("[data-turbo]");
	const turboConfig = form.dataset["turboConfig"] ? form.dataset["turboConfig"].split(' ') : [];
	const hiddenButton = form.querySelector("button");

	if(!turboConfig.includes("nohide")) {
		hiddenButton.hidden = true;
	}

	form.addEventListener("change", e => {
		DEBUG && console.debug(e);

		const formData = new FormData(form);
		formData.set(hiddenButton.name, hiddenButton.value);

		fetch(form.action, {
			"method": form.method,
			"credentials": "same-origin",
			"body": formData,
		}).then(response => {
			if(!response.ok) {
				console.error("Turbo response is NOT OK!", response);
			}

			return response.text();
		}).then(html => updateTurboContainer(html, turboContainer));
	});
}

function updateTurboContainer(html, turboContainer) {
	const newDocument = parser.parseFromString(html, "text/html");
	newDocument.querySelectorAll("[data-turbo]").forEach(handleSpecialName);

	const otherExistingTurboContainers = [...document.querySelectorAll("[data-turbo]")];
	otherExistingTurboContainers.forEach(otherExistingTurboContainer => {
		if(otherExistingTurboContainer === turboContainer) {
			return;
		}

		const otherNewTurboContainer = newDocument.querySelector(`[data-turbo='${otherExistingTurboContainer.dataset["turbo"]}']`);
		if(otherNewTurboContainer) {
			otherNewTurboContainer.open = otherExistingTurboContainer.open;
			otherExistingTurboContainer.replaceWith(otherNewTurboContainer);
			setup(otherNewTurboContainer);
		}
	});

	const newActiveElement = configureActiveElement(document.activeElement, newDocument, turboContainer);
	const newTurboContainer = newDocument.querySelector(`[data-turbo='${turboContainer.dataset["turbo"]}']`);
	if(newTurboContainer) {
		turboContainer.replaceWith(newTurboContainer);

// A quirk of replacing the container with the new container is that the active
// element has to be handled separately. This section of code does two things:
// 1) Replaces the active element under the mouse and leaves the new one in the
// same state (content, focus, etc.)
// 2) Handles a half-click (the mouse went down on the old element, when the
// mouse goes up on the new element).
		if(newActiveElement) {
			DEBUG && console.debug("Active element", newActiveElement);
			newActiveElement.focus();

			let handlePointerUp = () => {
				newActiveElement.click();
			};
			let handleBlur = () => {
				DEBUG && console.debug("Removing mouseup listener", newActiveElement);
				newActiveElement.removeEventListener("pointerup", handlePointerUp);
				newActiveElement.removeEventListener("blur", handleBlur);
			};

			newActiveElement.addEventListener("pointerup", handlePointerUp);
			newActiveElement.addEventListener("blur", handleBlur);
		}

		setup(newTurboContainer);
	}
}

function handleSpecialName(turboContainer) {
	let turboName = turboContainer.dataset["turbo"];
	if(turboName[0] === "@") {
		turboName = turboContainer.dataset[turboName.substring(1)];
		turboContainer.dataset.turbo = turboName;
	}
}

function configureActiveElement(activeElement, newDocument, turboContainer) {
	if(!activeElement || !activeElement.name) {
		return;
	}

	let xpath = getXPathForElement(activeElement, turboContainer);
	let newTurboContainer = newDocument.querySelector(`[data-turbo='${turboContainer.dataset["turbo"]}']`);
	let newElement = newDocument.evaluate(xpath, newTurboContainer).iterateNext();
	newElement.value = activeElement.value;
	newElement.selectionStart = activeElement.selectionStart;
	newElement.selectionEnd = activeElement.selectionEnd;
	return newElement;
}

/** This was adapted from https://developer.mozilla.org/en-US/docs/Web/XPath/Snippets */
function getXPathForElement(element, context) {
	let xpath = "";
	if(context instanceof Document) {
		context = context.documentElement;
	}

	while(element !== context) {
		let pos = 0;
		let sibling = element;
		while (sibling) {
			if (sibling.nodeName === element.nodeName) {
				pos += 1;
			}
			sibling = sibling.previousElementSibling;
		}

		xpath = `./${element.nodeName}[${pos}]/${xpath}`;
		element = element.parentElement;
	}
	xpath = xpath.replace(/\/$/, "");
	return xpath;
}
