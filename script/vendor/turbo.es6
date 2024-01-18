/*
# `data-turbo="autosave"`
On buttons, this will visually hide the button, but automatically "click" it
every time its containing form changes.
 */
const DEBUG = true;

const parser = new DOMParser();
let turboElementList = document.querySelectorAll("[data-turbo]");
let turboUpdateElementList = [];
DEBUG && console.debug("Turbo element list:", turboElementList);
let turboStyle = document.createElement("style");
document.head.append(turboStyle);
turboStyle.id = "turbo-style";
turboStyle.innerHTML = `
.turbo-hidden {
	display: none;
}
`;
turboElementList.forEach(init);

function init(turboElement) {
	let turboType = turboElement.dataset["turbo"];

	if(turboType === "autosave") {
		initAutoSave(turboElement)
	}
	else if(turboType === "update") {
		storeUpdate(turboElement);
	}
	else {
		console.error(`Unknown turbo element type: ${turboType}`, turboElement);
	}
}

function storeUpdate(element) {
	turboUpdateElementList.push(element);
}

function initAutoSave(turboElement) {
	if(!turboElement instanceof HTMLButtonElement) {
		console.error("TurboElement type autosave must be an HTMLButtonElement", turboElement);
		return;
	}

	if(!turboElement.form) {
		console.error("TurboElement type autosave must be within a form", turboElement)
		return;
	}

	turboElement.classList.add("turbo-hidden");

	turboElement.form.turboAutoSaveButton = turboElement;
	turboElement.form.addEventListener("change", formChangeAutoSave);
	turboElement.form.addEventListener("submit", formSubmitAutoSave);
}

function formChangeAutoSave(e) {

}

function formSubmitAutoSave(e) {
	e.preventDefault();
	let form = e.target;
	let formData = new FormData(form);
	if(form.turboAutoSaveButton) {
		formData.set(form.turboAutoSaveButton.name, form.turboAutoSaveButton.value);
	}

	fetch(form.action, {
		method: form.getAttribute("method"),
		credentials: "same-origin",
		body: formData,
	}).then(response => {
		if(!response.ok) {
			console.error("Form submission error", response);
			return;
		}

		return response.text();
	}).then(html => {
		let newDocument = parser.parseFromString(html, "text/html");
		turboUpdateElementList.forEach(existingElement => {
			let xPath = getXPathForElement(existingElement, document);
			console.log(xPath);
			let xPathResult = newDocument.evaluate(xPath, newDocument.documentElement);
// TODO: addEventListener needs overriding, so events on elements within the replaced nodes can be re-attached.
// 1. overridden addEventListener should push a tuple containing listened element and the callback function.
// 2. then when we come to replace an element here, we should loop over all items in the array, and check whether existingElement is WITHIN the listened element.
// 3. attach existing event listeners to the replaced elements!
// 3.a. data-turbo attribute should be space-separated
// 4. we could probably skip data-turbo elements??? maybe... I dunno.
			let newElement = xPathResult.iterateNext();

			let existingElementIndex = turboUpdateElementList.indexOf(existingElement);
			turboUpdateElementList[existingElementIndex] = newElement;

			if(newElement) {
				existingElement.replaceWith(newElement);
			}
		});
	});
}

/** This was adapted from https://developer.mozilla.org/en-US/docs/Web/XPath/Snippets */
function getXPathForElement(element, context) {
	let xpath = "";
	if(context instanceof Document) {
		context = context.documentElement;
	}
	if(!context) {
		context = element.ownerDocument.documentElement;
	}

	while(element !== context) {
		let pos = 0;
		let sibling = element;
		while(sibling) {
			if(sibling.nodeName === element.nodeName) {
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
















// OLD STUFF
function OLD_STUFF() {
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
}
